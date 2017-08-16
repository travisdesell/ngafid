<?php
namespace NGAFID\Commands;

ini_set("memory_limit", "10240M");

use DB;
use File;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NGAFID\CryptoSystem;
use NGAFID\FileUpload;
use NGAFID\FlightID;
use NGAFID\Main;

class ProcessImportCommand extends Command
    implements SelfHandling, ShouldBeQueued
{
    use InteractsWithQueue, SerializesModels;

    protected $upload;

    protected $newFilePath = '';

    protected $newFileHeaders = '';

    protected $csvRowCtr = 0;

    protected $flightDate = '';

    protected $flightTime = '';

    /**
     * Create a new command instance
     */
    public function __construct($uploadID)
    {
        $this->upload = FileUpload::find($uploadID);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $validFile = 0;

        $validFile = $this->createTempFile();  // Make a copy of the CSV
        if ($validFile == 1) {
            // Error found with the file, stop processing
            File::delete($this->newFilePath);

            return;
        }

        if ($this->csvRowCtr > 0) {
            // Check date and time from the CSV to see if the flight has
            // already been imported before we begin to process the file
            $flight = $this->validateFlight();

            // Update the uploaded_file table with the flight ID
            $this->upload->flight_id = $flight->id;
            $this->upload->total_num_of_data = $this->csvRowCtr;
            $this->upload->save();

            // @TODO: use $flight->hasDataInMainTable() to check is flight has
            // existing data in Main Table

            // Check the main table to see if the flight exists
            $numRows = $this->validateFlightData($flight->id);

            if ($numRows == 0) {
                // 'flight not found'

                // Derive radio altitude and time in milliseconds, then
                // append them to the csv file
                $this->processFlightData();

                //insert data into the DB
                $this->loadDataIntoDB($flight->id);

                // Encrypt flight data
                $this->encryptFlightData($flight, $flight->fleet_id);

                // Calculate flight duration and update it into the flight_id
                // table after successful import
                DB::statement(
                    'CALL `fdm_test`.`sp_CalculateFlightDuration`(?)',
                    [$flight['id']]
                );

                // Calculate aircraft exceedance
                DB::statement(
                    'CALL `fdm_test`.`sp_ExceedanceMonitoring`(?, ?)',
                    [1, $flight['id']]
                );
            } else {
                //'flight found';

                $this->upload->import_notes = 'You have an existing flight matching the date/time.';
                $this->upload->error = 1;
                $this->upload->save();
            }
        } else {
            $this->upload->error = 1;
            $this->upload->import_notes = 'Unsupported file format. Error Type: Invalid flight data.';
            $this->upload->import_time = DB::RAW('NOW()');
            $this->upload->save();
        }

        // Delete temp file
        File::delete($this->newFilePath);
    }

    public function loadDataIntoDB($flightID)
    {
        //create temporary table and insert the data
        $tmpTable = $flightID . '_main_tmp';
        DB::statement(
            "DROP TEMPORARY TABLE IF EXISTS `fdm_test`.`{$tmpTable}`"
        );

        DB::statement(
            "CREATE TEMPORARY TABLE `fdm_test`.`{$tmpTable}` LIKE `fdm_test`.main"
        );

        $aircraftID = $this->upload->aircraft_type;

        $sql = "LOAD DATA LOCAL INFILE '%s' INTO TABLE `fdm_test`.`{$tmpTable}"
               . "` FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\"' LINES TERMINATED BY '\\n' IGNORE 1 LINES ";

        if ($aircraftID == 1) {  // Cessna 172
            $sql .= " (time, radio_altitude_derived, @dummy, @dummy, latitude, longitude, altimeter, msl_altitude, oat, indicated_airspeed, groundspeed, vertical_airspeed, pitch_attitude, roll_attitude,";
            $sql .= " lateral_acceleration, vertical_acceleration, heading, course, system_1_volts, system_2_volts, system_1_amps, system_2_amps, fuel_quantity_left_main, fuel_quantity_right_main,";
            $sql .= " eng_1_fuel_flow, eng_1_oil_temp, eng_1_oil_press, eng_1_rpm, eng_1_cht_1, eng_1_cht_2, eng_1_cht_3, eng_1_cht_4, eng_1_egt_1, eng_1_egt_2, eng_1_egt_3, eng_1_egt_4, tas, obs_1,";
            $sql .= " nav_1_freq, nav_2_freq)";
        } elseif ($aircraftID == 2) {  // Cessna 182
            $sql .= " (time, radio_altitude_derived, @dummy, @dummy, latitude, longitude, altimeter, msl_altitude, oat, indicated_airspeed, groundspeed, vertical_airspeed, pitch_attitude, roll_attitude,";
            $sql .= " lateral_acceleration, vertical_acceleration, heading, course, system_1_volts, system_2_volts, system_1_amps, system_2_amps, fuel_quantity_left_main, fuel_quantity_right_main,";
            $sql .= " eng_1_fuel_flow, eng_1_oil_temp, eng_1_oil_press, eng_1_mp, eng_1_rpm, eng_1_cht_1, eng_1_cht_2, eng_1_cht_3, eng_1_cht_4, eng_1_cht_5, eng_1_cht_6,";
            $sql .= " eng_1_egt_1, eng_1_egt_2, eng_1_egt_3, eng_1_egt_4, eng_1_egt_5, eng_1_egt_6, tas, obs_1, nav_1_freq, nav_2_freq)";
        } elseif ($aircraftID == 6) {  // Piper Seminole PA44
            $sql .= " (time, radio_altitude_derived, @dummy, @dummy, latitude, longitude, altimeter, msl_altitude, oat, indicated_airspeed, groundspeed, vertical_airspeed, pitch_attitude, roll_attitude,";
            $sql .= " lateral_acceleration, vertical_acceleration, heading, course, system_1_volts, system_1_amps, fuel_quantity_left_main, fuel_quantity_right_main,";
            $sql .= " eng_1_fuel_flow, eng_1_oil_temp, eng_1_oil_press, eng_1_mp, eng_1_rpm, eng_1_cht_1, eng_1_egt_1, eng_1_egt_2, eng_1_egt_3, eng_1_egt_4,";
            $sql .= " eng_2_fuel_flow, eng_2_oil_temp, eng_2_oil_press, eng_2_mp, eng_2_rpm, eng_2_cht_1, eng_2_egt_1, eng_2_egt_2, eng_2_egt_3, eng_2_egt_4,";
            $sql .= " tas, obs_1, nav_1_freq, nav_2_freq)";
        } elseif ($aircraftID == 7) {  // Piper Archer PA28
            $sql .= " (time, radio_altitude_derived, @dummy, @dummy, latitude, longitude, altimeter, msl_altitude, oat, indicated_airspeed, groundspeed, vertical_airspeed, pitch_attitude, roll_attitude,";
            $sql .= " lateral_acceleration, vertical_acceleration, heading, course, system_1_volts, system_1_amps, fuel_quantity_left_main, fuel_quantity_right_main,";
            $sql .= " eng_1_fuel_flow, eng_1_oil_temp, eng_1_oil_press, eng_1_rpm, eng_1_egt_1, eng_1_egt_2, eng_1_egt_3, eng_1_egt_4, tas, obs_1, nav_1_freq, nav_2_freq)";
        } elseif ($aircraftID == 8) {  // Cirrus SR20
            $sql .= " (time, radio_altitude_derived, @dummy, @dummy, latitude, longitude, altimeter, msl_altitude, oat, indicated_airspeed, groundspeed, vertical_airspeed, pitch_attitude, roll_attitude,";
            $sql .= " lateral_acceleration, vertical_acceleration, heading, course, system_1_volts, system_2_volts, system_1_amps, eng_1_fuel_flow, eng_1_oil_temp, eng_1_oil_press, eng_1_mp, eng_1_rpm,";
            $sql .= " eng_1_cht_1, eng_1_cht_2, eng_1_cht_3, eng_1_cht_4, eng_1_cht_5, eng_1_cht_6, eng_1_egt_1, eng_1_egt_2, eng_1_egt_3, eng_1_egt_4, eng_1_egt_5, eng_1_egt_6, tas, obs_1, nav_1_freq, nav_2_freq)";
        }

        $sql .= "SET phase = 0, flight = " . $flightID;

        $sql = sprintf($sql, addslashes($this->newFilePath));
        // @TODO: make call to DB using $sql?

        $numRowsInMain = $this->transferToMainTable($flightID, $tmpTable);
        $this->upload->imported_num_of_data = $numRowsInMain;
        $this->upload->import_notes = 'Your flight data was successfully imported.';
        $this->upload->import_time = DB::RAW('NOW()');
        $this->upload->error = 0;
        $this->upload->save();

        DB::statement(
            "DROP TEMPORARY TABLE IF EXISTS `fdm_test`.`{$tmpTable}`'"
        );
    }

    public function transferToMainTable($flightID, $tmpTableName)
    {
        $sql = 'INSERT INTO `fdm_test`.`main` (`flight`, `time`, `radio_altitude_derived`, `latitude`, `longitude`, `altimeter`, `msl_altitude`, `oat`, `indicated_airspeed`, `groundspeed`, `vertical_airspeed`, `pitch_attitude`, `roll_attitude`, ';
        $sql .= '`lateral_acceleration`, `vertical_acceleration`, `heading`, `course`, `system_1_volts`, `system_2_volts`, `system_1_amps`, `system_2_amps`, `fuel_quantity_left_main`, `fuel_quantity_right_main`, ';
        $sql .= '`eng_1_fuel_flow`, `eng_1_oil_temp`, `eng_1_oil_press`, `eng_1_mp`, `eng_1_rpm`, `eng_1_cht_1`, `eng_1_cht_2`, `eng_1_cht_3`, `eng_1_cht_4`, `eng_1_cht_5`, `eng_1_cht_6`, ';
        $sql .= '`eng_1_egt_1`, `eng_1_egt_2`, `eng_1_egt_3`, `eng_1_egt_4`, `eng_1_egt_5`, `eng_1_egt_6`, `tas`, `obs_1`, `nav_1_freq`, `nav_2_freq`) ';
        $sql .= 'SELECT `flight`, `time`, `radio_altitude_derived`, `latitude`, `longitude`, `altimeter`, `msl_altitude`, `oat`, `indicated_airspeed`, `groundspeed`, `vertical_airspeed`, `pitch_attitude`, `roll_attitude`, ';
        $sql .= '`lateral_acceleration`, `vertical_acceleration`, `heading`, `course`, `system_1_volts`, `system_2_volts`, `system_1_amps`, `system_2_amps`, `fuel_quantity_left_main`, `fuel_quantity_right_main`, ';
        $sql .= '`eng_1_fuel_flow`, `eng_1_oil_temp`, `eng_1_oil_press`, `eng_1_mp`, `eng_1_rpm`, `eng_1_cht_1`, `eng_1_cht_2`, `eng_1_cht_3`, `eng_1_cht_4`, `eng_1_cht_5`, `eng_1_cht_6`, ';
        $sql .= '`eng_1_egt_1`, `eng_1_egt_2`, `eng_1_egt_3`, `eng_1_egt_4`, `eng_1_egt_5`, `eng_1_egt_6`, `tas`, `obs_1`, `nav_1_freq`, `nav_2_freq`';
        $sql .= 'FROM `fdm_test`.`' . $tmpTableName . '` WHERE `flight` = '
                . $flightID . ' ORDER BY `time` ASC';

        $numInserted = DB::connection()
            ->getpdo()
            ->exec($sql);

        return $numInserted;
    }

    public function processFlightData()
    {
        // Loop through csv and derive radio altitude and time in milliseconds
        $ctr = 0;
        $csvCurTime = '';
        $csvPrevTime = '';
        $csvLatitude = '';
        $csvLongitude = '';
        $csvMsl = '';

        $time = [];
        $radioAltitude = [];

        $importFileName = $this->upload->path . 'import_'
                          . $this->upload->file_name;
        File::put(
            $importFileName,
            'Time,RadioAltitude,' . $this->newFileHeaders . "\r\n"
        );

        $headers = explode(',', $this->newFileHeaders);

        foreach (file($this->newFilePath, FILE_SKIP_EMPTY_LINES) as $row) {
            if ($ctr == 0) {
                $ctr++;
                continue;
            }

            $row = explode(',', $row);
            $csvRow = array_combine($headers, $row);

            $csvCurTime = $csvRow['Lcl Time'];
            $csvLatitude = $csvRow['Latitude'];
            $csvLongitude = $csvRow['Longitude'];
            $csvMsl = $csvRow['AltMSL'];

            $radioAltDerived = $this->deriveRadioAltitude(
                $csvMsl,
                $csvLatitude,
                $csvLongitude
            );

            $radioAltitude[$ctr] = $radioAltDerived;

            $time[$ctr] = $ctr !== 1
                ? $this->deriveTimeInMilliseconds(
                    $csvPrevTime,
                    $csvCurTime,
                    $time[$ctr - 1]
                )
                : 0;

            File::append(
                $importFileName,
                $time[$ctr] . ',' . $radioAltitude[$ctr] . ',' . implode(
                    ',',
                    $row
                )
            );

            $csvPrevTime = $csvCurTime;
            $ctr++;
        }

        // Unlink old tmp_file
        File::delete($this->newFilePath);
        $this->newFilePath = $importFileName;
    }

    public function deriveTimeInMilliseconds($prevTime, $curTime, $prevTimeMs)
    {
        $prevTimeSec = strtotime($prevTime) - strtotime('today');
        $curTimeSec = strtotime($curTime) - strtotime('today');

        $timeIntervalMs = ($curTimeSec - $prevTimeSec) * 1000;

        if ($timeIntervalMs == 0) {
            $curTimeMs = $prevTimeMs + 1;
        } else {
            $curTimeMs = floor(($prevTimeMs + $timeIntervalMs) / 1000) * 1000;
        }

        return $curTimeMs;
    }

    public function deriveRadioAltitude($msl, $latitude, $longitude)
    {
        $result = null;

        if (($msl !== '' || $msl > 0) && ($latitude || $latitude !== '')
            && ($longitude || $longitude !== '')) {
            $sql = "SELECT ROUND(GREATEST(" . $msl
                   . " - (SELECT t.msl_altitude FROM fdm_test.terrain_elevation t ";
            $sql .= "WHERE t.latitude > (" . $latitude
                    . " - 0.00015) AND t.latitude < (" . $latitude
                    . " + 0.00015) ";
            $sql .= "AND t.longitude > (" . $longitude
                    . " - 0.00015) AND t.longitude < (" . $longitude
                    . " + 0.00015) ";
            $sql .= "ORDER BY t.latitude ASC , t.longitude ASC LIMIT 1) * 3.2808399, 0)) AS 'ra_derived'";

            $result = DB::select($sql);
            $result = $result[0]->ra_derived;
        }

        return $result;
    }

    public function validateFlightData($flightID)
    {
        return Main::where('flight', '=', $flightID)
            ->count();
    }

    public function validateFlight()
    {
        return FlightID::firstOrCreate(
            [
                'n_number'      => $this->upload->n_number,
                'date'          => $this->flightDate,
                'time'          => $this->flightTime,
                'aircraft_type' => $this->upload->aircraft_type,
                'fleet_id'      => $this->upload->fleet_id,
            ]
        );
    }

    public function encryptFlightData(FlightID $flightInfo, $fleetID)
    {
        $shouldEncrypt = $flightInfo->fleet->wantsDataEncrypted();
        $encNnumber = '';

        if ( !$shouldEncrypt) {
            return;
        }

        $salt = getenv('STATIC_SALT');

        // Get the public key
        $ngafidKey = CryptoSystem::where('fleet_id', '=', $fleetID)
            ->pluck(DB::raw("DECODE(ngafid_key, '{$salt}')"));

        // Get the log record
        $uploadsTable = FileUpload::where('flight_id', '=', $flightInfo->id)
            ->get()// @TODO: cut out get() & jump straight to first()?
            ->first();

        // Encrypt the n_number and day in the flight_id table
        if (trim($flightInfo->n_number)) {
            openssl_public_encrypt(
                trim($flightInfo->n_number),
                $encNnumber,
                $ngafidKey
            );

            $flightInfo->n_number = DB::raw("NULL");
            $flightInfo->enc_n_number = DB::raw(
                "COMPRESS('" . base64_encode($encNnumber) . "')"
            );
        }

        if (trim($flightInfo->date)
            && !ends_with($flightInfo->date, '00')) {
            $day = substr(trim($flightInfo->date), -2);
            $tmpDate = rtrim(trim($flightInfo->date), $day);
            openssl_public_encrypt($day, $encDate, $ngafidKey);

            $flightInfo->date = $tmpDate . '00';
            $flightInfo->enc_day = DB::raw(
                "COMPRESS('" . base64_encode($encDate) . "')"
            );
        }

        $flightInfo->save();

        // Encrypt the filename and n_number in the log table
        if ($uploadsTable && $encNnumber !== '') {
            openssl_public_encrypt(
                $uploadsTable->file_name,
                $encFileName,
                $ngafidKey
            );

            $uploadsTable->file_name = DB::raw(
                "COMPRESS('" . base64_encode($encFileName) . "')"
            );

            $uploadsTable->n_number = DB::raw(
                "COMPRESS('" . base64_encode($encNnumber) . "')"
            );

            $uploadsTable->save();
        }
    }

    public function createTempFile()
    {
        // Create a temporary copy of the file for manipulation
        $origFileName = $this->upload->path . $this->upload->file_name;
        $newFileName = $this->upload->path . 'tmp_' . $this->upload->file_name;
        $this->newFilePath = $newFileName;
        $aircraft = $this->upload->aircraft_type;

        if ($aircraft == 1) {  // Cessna 172
            $origHeader = 'Lcl Date,Lcl Time,UTCOfst,AtvWpt,Latitude,Longitude,AltB,BaroA,AltMSL,OAT,IAS,GndSpd,';
            $origHeader .= 'VSpd,Pitch,Roll,LatAc,NormAc,HDG,TRK,volt1,volt2,amp1,amp2,FQtyL,FQtyR,E1 FFlow,';
            $origHeader .= 'E1 OilT,E1 OilP,E1 RPM,E1 CHT1,E1 CHT2,E1 CHT3,E1 CHT4,E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,';
            $origHeader .= 'AltGPS,TAS,HSIS,CRS,NAV1,NAV2,COM1,COM2,HCDI,VCDI,WndSpd,WndDr,WptDst,WptBrg,MagVar,';
            $origHeader .= 'AfcsOn,RollM,PitchM,RollC,PichC,VSpdG,GPSfix,HAL,VAL,HPLwas,HPLfd,VPLwas';

            $newHeader = 'Lcl Date,Lcl Time,Latitude,Longitude,BaroA,AltMSL,OAT,IAS,GndSpd,VSpd,Pitch,Roll,';
            $newHeader .= 'LatAc,NormAc,HDG,TRK,volt1,volt2,amp1,amp2,FQtyL,FQtyR,E1 FFlow,E1 OilT,E1 OilP,E1 RPM,';
            $newHeader .= 'E1 CHT1,E1 CHT2,E1 CHT3,E1 CHT4,E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,TAS,CRS,NAV1,NAV2';
        } elseif ($aircraft == 2) {  // Cessna 182
            $origHeader = 'Lcl Date,Lcl Time,UTCOfst,AtvWpt,Latitude,Longitude,AltB,BaroA,AltMSL,OAT,IAS,GndSpd,';
            $origHeader .= 'VSpd,Pitch,Roll,LatAc,NormAc,HDG,TRK,volt1,volt2,amp1,amp2,FQtyL,FQtyR,E1 FFlow,';
            $origHeader .= 'E1 OilT,E1 OilP,E1 MAP,E1 RPM,E1 CHT1,E1 CHT2,E1 CHT3,E1 CHT4,E1 CHT5,E1 CHT6,';
            $origHeader .= 'E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,E1 EGT5,E1 EGT6,AltGPS,TAS,HSIS,CRS,NAV1,NAV2,COM1,';
            $origHeader .= 'COM2,HCDI,VCDI,WndSpd,WndDr,WptDst,WptBrg,MagVar,AfcsOn,RollM,PitchM,RollC,PichC,';
            $origHeader .= 'VSpdG,GPSfix,HAL,VAL,HPLwas,HPLfd,VPLwas';

            $newHeader = 'Lcl Date,Lcl Time,Latitude,Longitude,BaroA,AltMSL,OAT,IAS,GndSpd,VSpd,Pitch,Roll,';
            $newHeader .= 'LatAc,NormAc,HDG,TRK,volt1,volt2,amp1,amp2,FQtyL,FQtyR,E1 FFlow,E1 OilT,E1 OilP,E1 MAP,E1 RPM,';
            $newHeader .= 'E1 CHT1,E1 CHT2,E1 CHT3,E1 CHT4,E1 CHT5,E1 CHT6,E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,E1 EGT5,E1 EGT6,';
            $newHeader .= 'TAS,CRS,NAV1,NAV2';
        } elseif ($aircraft == 6) {  // Piper Seminole PA44
            $origHeader = 'Lcl Date,Lcl Time,UTCOfst,AtvWpt,Latitude,Longitude,AltB,BaroA,AltMSL,OAT,IAS,GndSpd,VSpd,Pitch,Roll,';
            $origHeader .= 'LatAc,NormAc,HDG,TRK,volt1,amp1,FQtyL,FQtyR,E1 FFlow,E1 OilT,E1 OilP,E1 MAP,E1 RPM,E1 CHT1,E1 EGT1,E1 EGT2,';
            $origHeader .= 'E1 EGT3,E1 EGT4,E2 FFlow,E2 OilT,E2 OilP,E2 MAP,E2 RPM,E2 CHT1,E2 EGT1,E2 EGT2,E2 EGT3,E2 EGT4,AltGPS,TAS,';
            $origHeader .= 'HSIS,CRS,NAV1,NAV2,COM1,COM2,HCDI,VCDI,WndSpd,WndDr,WptDst,WptBrg,MagVar,AfcsOn,RollM,PitchM,RollC,PichC,VSpdG,';
            $origHeader .= 'GPSfix,HAL,VAL,HPLwas,HPLfd,VPLwas';

            $newHeader = 'Lcl Date,Lcl Time,Latitude,Longitude,BaroA,AltMSL,OAT,IAS,GndSpd,VSpd,Pitch,Roll,LatAc,NormAc,HDG,TRK,volt1,amp1,FQtyL,FQtyR,';
            $newHeader .= 'E1 FFlow,E1 OilT,E1 OilP,E1 MAP,E1 RPM,E1 CHT1,E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,';
            $newHeader .= 'E2 FFlow,E2 OilT,E2 OilP,E2 MAP,E2 RPM,E2 CHT1,E2 EGT1,E2 EGT2,E2 EGT3,E2 EGT4,TAS,CRS,NAV1,NAV2';
        } elseif ($aircraft == 7) {  // Piper Archer PA28
            $origHeader = 'Lcl Date,Lcl Time,UTCOfst,AtvWpt,Latitude,Longitude,AltB,BaroA,AltMSL,OAT,IAS,GndSpd,VSpd,Pitch,Roll,';
            $origHeader .= 'LatAc,NormAc,HDG,TRK,volt1,amp1,FQtyL,FQtyR,E1 FFlow,E1 OilT,E1 OilP,E1 RPM,E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,';
            $origHeader .= 'AltGPS,TAS,HSIS,CRS,NAV1,NAV2,COM1,COM2,HCDI,VCDI,WndSpd,WndDr,WptDst,WptBrg,MagVar,AfcsOn,RollM,PitchM,RollC,';
            $origHeader .= 'PichC,VSpdG,GPSfix,HAL,VAL,HPLwas,HPLfd,VPLwas';

            $newHeader = 'Lcl Date,Lcl Time,Latitude,Longitude,BaroA,AltMSL,OAT,IAS,GndSpd,VSpd,Pitch,Roll,LatAc,NormAc,HDG,TRK,volt1,amp1,';
            $newHeader .= 'FQtyL,FQtyR,E1 FFlow,E1 OilT,E1 OilP,E1 RPM,E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,TAS,CRS,NAV1,NAV2';
        } elseif ($aircraft == 8) {  // Cirrus SR20
            $origHeader = 'Lcl Date,Lcl Time,UTCOfst,AtvWpt,Latitude,Longitude,AltB,BaroA,AltMSL,OAT,IAS,GndSpd,VSpd,Pitch,Roll,';
            $origHeader .= 'LatAc,NormAc,HDG,TRK,volt1,volt2,amp1,E1 FFlow,E1 OilT,E1 OilP,E1 MAP,E1 RPM,E1 CHT1,E1 CHT2,E1 CHT3,E1 CHT4,E1 CHT5,E1 CHT6,';
            $origHeader .= 'E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,E1 EGT5,E1 EGT6,AltGPS,TAS,HSIS,CRS,NAV1,NAV2,COM1,COM2,HCDI,VCDI,WndSpd,WndDr,WptDst,WptBrg,';
            $origHeader .= 'MagVar,AfcsOn,RollM,PitchM,RollC,PichC,VSpdG,GPSfix,HAL,VAL,HPLwas,HPLfd,VPLwas';

            $newHeader = 'Lcl Date,Lcl Time,Latitude,Longitude,BaroA,AltMSL,OAT,IAS,GndSpd,VSpd,Pitch,Roll,LatAc,NormAc,HDG,TRK,volt1,volt2,amp1,';
            $newHeader .= 'E1 FFlow,E1 OilT,E1 OilP,E1 MAP,E1 RPM,E1 CHT1,E1 CHT2,E1 CHT3,E1 CHT4,E1 CHT5,E1 CHT6,E1 EGT1,E1 EGT2,E1 EGT3,E1 EGT4,E1 EGT5,E1 EGT6,';
            $newHeader .= 'TAS,CRS,NAV1,NAV2';
        } else {
            // Unable to import, unknown file/headers for automatic import
            $this->upload->import_notes = 'Unable to import the selected aircraft data. Error Type: Unknown CSV headers';
            $this->upload->error = 1;
            $this->upload->save();

            // Return error code to the above handler to stop processing
            // remaining function call
            return $this->upload->error;
        }

        $this->newFileHeaders = $newHeader;

        $rowCtr = 0;

        File::put($newFileName, $newHeader . "\r\n");

        $origHeader = explode(',', $origHeader);

        foreach (file($origFileName, FILE_SKIP_EMPTY_LINES) as $line) {
            // Loop through each line of the csv. Ignore comments and prepare a
            // new file for import only with the required fields

            if (starts_with($line, '#')) {
                continue;
            }

            if ($rowCtr == 0) {
                // Skip header
                $rowCtr++;
                continue;
            }

            $line = explode(',', $line);
            if (count($line) <> count($origHeader)) {
                // If the number of fields in the CSV row is not equal to the
                // number of field names in the CSV header, this indicates
                // potentially bad/invalid data recording and that row will
                // not be imported
                continue;
            }

            $csv = array_combine($origHeader, $line);

            // Extract a copy of the line containing only the
            // required headers/fields
            $newLine = array_intersect_key(
                $csv,
                array_flip(explode(',', $newHeader))
            );

            if ($rowCtr == 1) {
                // Extract the date and time
                $this->flightDate = date_format(
                    date_create($newLine['Lcl Date']),
                    "Y-m-d"
                );

                $this->flightTime = $newLine['Lcl Time'];

                // If 'Lcl Date' and 'Lcl Time' fields are not found,
                // the import should be terminated and log table updated
                if ( !$newLine['Lcl Date'] || !$newLine['Lcl Time']) {
                    $this->upload->error = 1;
                    $this->upload->import_notes = 'Unsupported file format. Error Type: Invalid flight date/time';
                    $this->upload->save();

                    return $this->upload->error;
                }
            }

            File::append($newFileName, implode(',', $newLine) . "\r\n");

            $rowCtr++;
        }

        chmod($newFileName, 0777);
        $this->csvRowCtr = ($rowCtr - 1);  // Num rows minus the header column

        return $this->upload->error;
    }
}
