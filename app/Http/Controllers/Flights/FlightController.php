<?php namespace NGAFID\Http\Controllers\Flights;

use NGAFID\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use NGAFID\Fleet;
use NGAFID\FlightID;
use NGAFID\Aircraft;
use NGAFID\Main;
use NGAFID\Http\Requests\FlightIdRequest;

class FlightController extends Controller {

    public $perPage = 20;

    public function __construct()
    {
        $this->middleware('auth');
    }

    //PHP 5.4 does have array_column functionality
    function array_column( array $input, $column_key, $index_key = null ) {

        $result = array();
        foreach( $input as $k => $v )
            $result[ $index_key ? $v[ $index_key ] : $k ] = $v[ $column_key ];

        return $result;
    }

    public function validateFlight($flightID = null)
    {
        $fleetTable = new Fleet();
        $selectedFlight = (!$flightID ? \Request::route('flight'): $flightID);

        if($selectedFlight != '')
        {
            $validFlight = $fleetTable->find(\Auth::user()->org_id)->flights()->where('id', '=', $selectedFlight)->first();

            if($validFlight)
            {
                return $validFlight;
            }
        }

        return false;
    }

    public function index()
    {
        $fleetID    = \Auth::user()->org_id;
        $startDate  = \Request::query('startDate');
        $endDate    = \Request::query('endDate');
        $filter     = \Request::query('filter');
        $sort       = \Request::query('sort');
        $event      = \Request::route('exceedance');
        $duration   = \Request::query('duration');
        $flightID   = \Request::query('flightID');
        $action     = 'flights';
        $archived   = ''; //show all flights that are not archived
        $pageName   = 'All Flights';
        $this->perPage = (\Request::query('perPage') == '') ? 20 : \Request::query('perPage');

        if($filter == 'E')
        {
            //show flights with events/exceedances that are not archived
            $archived   = 'N';
        }
        elseif($filter == 'A')
        {
            //show flights with events/exceedances that are archived (only flights with exceedances should be archived).
            $archived = 'Y';
        }

        if($event != '')
        {
            $archived   = 'N';
            $filter     = 'E'; //this may seen redundant but it is not :) its used to ensure the filter options show 'flights with events' for the custom navigation
            $action = 'flights/event/' . $event;
        }

        if($duration == ''){
            $duration = '00:00';
        }

        if(!is_numeric($flightID)){
            $flightID = '';
        }

        $column = '';

        switch($event)
        {
            case 'excessive-roll':
                $column = 'excessive_roll';
                $pageName = 'Excessive Roll';
                break;
            case 'excessive-pitch':
                $column = 'excessive_pitch';
                $pageName = 'Excessive Pitch';
                break;
            case 'excessive-speed':
                $column = 'excessive_speed';
                $pageName = 'Excessive Speed';
                break;
            case 'high-cht':
                $column = 'high_cht';
                $pageName = 'High CHT';
                break;
            case 'high-altitude':
                $column = 'high_altitude';
                $pageName = 'High Altitude';
                break;
            case 'low-fuel':
                $column = 'low_fuel';
                $pageName = 'Low Fuel';
                break;
            case 'low-oil-pressure':
                $column = 'low_oil_pressure';
                $pageName = 'Low Oil Pressure';
                break;
            case 'low-airspeed-approach':
                $column = 'low_airspeed_on_approach';
                $pageName = 'Low Airspeed On Approach';
                break;
            case 'excessive-lateral-acceleration':
                $column = 'excessive_lateral_acceleration';
                $pageName = 'Excessive Lateral Acceleration';
                break;
            case 'excessive-vertical-acceleration':
                $column = 'excessive_vertical_acceleration';
                $pageName = 'Excessive Vertical Acceleration';
                break;
            case 'excessive-longitudinal-acceleration':
                $column = 'excessive_longitudinal_acceleration';
                $pageName = 'Excessive Longitudinal Acceleration';
                break;
            case 'low-airspeed-climbout':
                $column = 'low_airspeed_on_climbout';
                $pageName = 'Low Airspeed On Climbout';
                break;
            case 'excessive-vsi-final':
                $column = 'excessive_vsi_on_final';
                $pageName = 'Excessive VSI On Final';
                break;
            default:
                $column = '';
                $pageName   = 'All Flights';
                break;
        }

        $flightIdTable = new FlightID();

        $flights = $flightIdTable->flightDetails($fleetID, $startDate, $endDate, $archived, $sort, $column, $duration, $flightID)->paginate($this->perPage);

        $selected = array();
        $selected['startDate']   = $startDate;
        $selected['endDate']     = $endDate;
        $selected['sortBy']      = $sort;
        $selected['filter']      = $filter;
        $selected['event']       = $event;
        $selected['duration']    = $duration;
        $selected['perPage']     = $this->perPage;
        $selected['flightID']     = $flightID;

        return view('flights.flights')->with(['data' => $flights, 'selected' => $selected, 'action' => $action, 'pageName' => $pageName]);
    }

    public function edit($flightID)
    {
        $aircraftData = array();
        $flightIdData = $this->validateFlight($flightID);
        //$flightIdData   = $fleetTable->find(\Auth::user()->org_id)->flights()->where('id', '=', $flightID)->first();

        $flightIdData['date'] = $flightIdData['date'] . ' ' . $flightIdData['time'];
        //unset($flightIdData['time']);

        $aircraftTable = new Aircraft();
        $aircraftInfo = $aircraftTable->groupBy('aircraft name')->orderBy('aircraft name', 'ASC')->get();
        foreach($aircraftInfo as $aircraft)
        {
            $aircraftData[$aircraft['id']] = $aircraft['aircraft name'] . ' - ' . $aircraft['year'];// . ' ' . $aircraft['make'] . $aircraft['model'];
        }

        $flightIdData['aircraft'] = $aircraftData;

        return view('flights.edit')->with(['data' => $flightIdData, 'flight' => $flightID]);
    }

    public function update($flightID, FlightIdRequest $flightIdRequest)
    {
        $formfields = $flightIdRequest->all();
        //$routeParams = \Route::current()->parameters();
        //$flightID = $routeParams['flights'];

        $flightData = array(
            'n_number'       => $formfields['n_number'],
            'aircraft_type'  => $formfields['aircraft'],
            'origin'         => $formfields['origin'],
            'destination'    => $formfields['destination']
        );

        $flightIdTable = new FlightID();
        if($flightIdTable->find($flightID)->update($flightData))
        {
            //recalculate aircraft exceedance
            \DB::statement('CALL `fdm_test`.`sp_ExceedanceMonitoring`(?, ?)', array(1, $flightID)); //in future check if the aircraft was changed before calling the stored procedure.
            flash()->success('Your flight information has been successfully updated!');
        }


        return redirect('flights/'.$flightID.'/edit');
    }

    public function create()
    {

    }

    public function store()
    {

    }

    /*This method has been refactored into the index method
     * public function exceedance()
    {
        //TO DO: update this method to accept the filter options
        $flightIdTable = new FlightID();

        $event = \Request::query('event');
        $startDate  = \Request::query('startDate');
        $endDate    = \Request::query('endDate');
        $filter   = \Request::query('filter');
        $sort       = \Request::query('sort');
        //$flight = \Request::query('flight');
        //in future allow passing flight id to filter query for the exceedance triggered by a particular flight

        $fleetID = \Auth::user()->org_id;
        $column = '';

        switch($event)
        {
            case 'excessive-roll':
                $column = 'excessive_roll';
                break;
            case 'excessive-pitch':
                $column = 'excessive_pitch';
                break;
            case 'excessive-speed':
                $column = 'excessive_speed';
                break;
            case 'high-cht':
                $column = 'high_cht';
                break;
            case 'high-altitude':
                $column = 'high_altitude';
                break;
            case 'low-fuel':
                $column = 'low_fuel';
                break;
            case 'low-oil-pressure':
                $column = 'low_oil_pressure';
                break;
            case 'low-airspeed-approach':
                $column = 'low_airspeed_on_approach';
                break;
            case 'excessive-lateral-acceleration':
                $column = 'excessive_lateral_acceleration';
                break;
            case 'excessive-vertical-acceleration':
                $column = 'excessive_vertical_acceleration';
                break;
            case 'excessive-longitudinal-acceleration':
                $column = 'excessive_longitudinal_acceleration';
                break;
            case 'low-airspeed-climbout':
                $column = 'low_airspeed_on_climbout';
                break;
            case 'excessive-vsi-final':
                $column = 'excessive_vsi_on_final';
                break;
            default:
                $column = '';
                break;
        }

        if($column != '')
        {
            $selected = array();
            $selected['startDate']   = $startDate;
            $selected['endDate']     = $endDate;
            $selected['sortBy']      = $sort;
            $selected['filter']      = $filter;


            $getFlights = $flightIdTable->aircraftExceedance($fleetID, $column)->paginate($this->perPage);
            return view('flights.flights')->with(['data' => $getFlights, 'selected' => $selected]);
        }

        return redirect('flights');
    }*/

    public function trend()
    {
        $selectedEvent = \Request::query('event');
        $selectedAircraft = \Request::query('aircraft');
        $startDate = \Request::query('startDate');
        $endDate = \Request::query('endDate');
        $fleetID = \Auth::user()->org_id;

        $aircraftTable = new Aircraft();
        $aircraftInfo = $aircraftTable->uniqueAircraft($fleetID)->get();

        $aircraftInfo = $aircraftInfo->toArray();
        /*$aircraftType = array();
        foreach($aircraftInfo as $key => $val){
            $aircraftType[] = $val['id'];
        }*/
        //$aircraftType = array_column($aircraftInfo->toArray(), 'id');

        $aircraftType = $this->array_column($aircraftInfo, 'id');

        $events = array(
            1   =>  'Excessive Roll',
            2   =>  'Excessive Pitch',
            9   =>  'Excessive Lateral Acceleration',
            10  =>  'Excessive Vertical Acceleration',
            11  =>  'Excessive Longitudinal Acceleration',
            13  =>  'Excessive VSI on Final'
        );


        if(in_array('1', $aircraftType) || in_array('2', $aircraftType))
        {
            $cessnaEvents = array(
                3   =>  'Excessive Speed',
                4   =>  'High CHT',
                5   =>  'High Altitude',
                6   =>  'Low Fuel' ,
                7   =>  'Low Oil Pressure',
                8   =>  'Low Airspeed on Approach',
                12  =>  'Low Airspeed on Climb-out'
            );
            $events += $cessnaEvents;
        }

        $aircraftData = array();

        foreach($aircraftInfo as $aircraft)
        {
            $aircraftData[$aircraft['id']] = $aircraft['aircraft name'] . ' - ' . $aircraft['year'] . ' ' . $aircraft['make'] . $aircraft['model'];
        }

        asort($events);

        $data = array(
            'events'    => $events,
            'aircraft'  => $aircraftData,
        );

        $name = '';
        $chartData = array();
        $chartData['categories'] = array();
        $chartData['data'] = array();

        if($selectedEvent != '' && $selectedAircraft != '')
        {
            $result = $aircraftTable->aircraftTrendDetection($fleetID, $startDate, $endDate, $selectedEvent, $selectedAircraft);

            foreach($result as $row)
            {
                $name                     = $row->name;
                $chartData['categories'][]  = 'new Date(' . strtotime('01-' . $row->date) . '*1000)';
                $chartData['data'][]        = $row->percentage;
            }


        }
        $chartData['name']          = $name;
        $data['chart']              = $chartData;
        $data['selectedEvent']      = $selectedEvent;
        $data['selectedAircraft']   = $selectedAircraft;
        $data['startDate']          = $startDate;
        $data['endDate']            = $endDate;


        return view('flights.trend')->with('trendData', $data);
    }

    public function chart()
    {
        $selectedFlight = \Request::route('flight'); //\Request::query('flight');
        $param     = \Request::query('param');
        //$chartData      = array();
        $seriesTime     = array();
        $seriesData     = array();
        $seriesName     = '';
        $selectedParam               = array();
        $summaryData                 = '';

        $mainTable  = new Main();
        $validFlight = $this->validateFlight();

        if($validFlight) {
            $startTime = $validFlight->time;

            if(isset($param))
            {
                //foreach ($parameters as $param)
                {
                    $columns = "AddTime('" . $startTime . "', COALESCE(SEC_TO_TIME(time/1000), 0)) AS 'time'";

                    switch ($param) {
                        case 1:
                            $columns .= ', indicated_airspeed';
                            $seriesName = 'Airspeed';
                            break;

                        case 2:
                            $columns .= ', msl_altitude';
                            $seriesName = 'MSL Altitude';
                            break;

                        case 3:
                            $columns .= ', eng_1_rpm';
                            $seriesName = 'Engine RPM';
                            break;

                        case 4:
                            $columns .= ', pitch_attitude';
                            $seriesName = 'Pitch';
                            break;

                        case 5:
                            $columns .= ', roll_attitude';
                            $seriesName = 'Roll';
                            break;

                        case 6:
                            $columns .= ', vertical_airspeed';
                            $seriesName = 'Vertical Speed';
                            break;
                    }
                    $selectedParam[] = $param;

                    $result  = $mainTable->flightParameters($columns, $selectedFlight)->get()->toArray();

                    if($result != '') {
                        $time    = $this->array_column($result, 'time');

                        foreach($time as $key => $val) {
                            $time[$key] = $val; //$validFlight->date  . ' ' .  //'new Date(' . strtotime($validFlight->date  . ' ' . $val ) . '*1000)';
                        }
                        $seriesTime = $time;
                        //$seriesName = $seriesName;

                        if(strpos($columns, 'indicated_airspeed') !== FALSE) {
                            if ($this->array_column($result, 'indicated_airspeed')) {
                                //$chartData['series'][] = array("name" => "Indicated Airspeed", "data" => array_column($result, 'indicated_airspeed') );
                                $seriesData = $this->array_column($result, 'indicated_airspeed');
                            }
                        }

                        if(strpos($columns, 'msl_altitude') !== FALSE) {
                            if ($this->array_column($result, 'msl_altitude')) {
                                //$chartData['series'][] = array("name" => "MSL Altitude", "data" => array_column($result, 'msl_altitude') );
                                $seriesData = $this->array_column($result, 'msl_altitude');
                            }
                        }

                        if(strpos($columns, 'eng_1_rpm') !== FALSE) {
                            if ($this->array_column($result, 'eng_1_rpm')) {
                                //$chartData['series'][] = array("name" => "Engine RPM", "data" => array_column($result, 'eng_1_rpm') );
                                $seriesData = $this->array_column($result, 'eng_1_rpm');
                            }
                        }

                        if(strpos($columns, 'pitch_attitude') !== FALSE) {
                            if ($this->array_column($result, 'pitch_attitude')) {
                                //$chartData['series'][] = array("name" => "Pitch", "data" => array_column($result, 'pitch_attitude') );
                                $seriesData = $this->array_column($result, 'pitch_attitude');
                            }
                        }

                        if(strpos($columns, 'roll_attitude') !== FALSE) {
                            if ($this->array_column($result, 'roll_attitude')) {
                                //$chartData['series'][] = array("name" => "Roll", "data" => array_column($result, 'roll_attitude') );
                                $seriesData = $this->array_column($result, 'roll_attitude');
                            }
                        }

                        if(strpos($columns, 'vertical_airspeed') !== FALSE) {
                            if ($this->array_column($result, 'vertical_airspeed')) {
                                //$chartData['series'][] = array("name" => "Vertical Speed", "data" => array_column($result, 'vertical_airspeed') );
                                $seriesData = $this->array_column($result, 'vertical_airspeed');
                            }
                        }
                    }
                }
                return \Response::json(['success' => true,'data' => ['series' => $seriesData, 'time' => $seriesTime, 'name' => $seriesName]]);
            }

            $summary = $mainTable->flightSummary($selectedFlight)->get()->toArray();
        }

        if(isset($summary)) {
            $summaryData  = '<table class="table table-hover table-striped table-bordered table-condensed">';
            $summaryData .= '<thead><tr><th>Parameter</th><th>Average</th><th>Range</th></tr></thead>';
            foreach ($summary as $stats) {
                $summaryData .= '<tr><td>Airspeed</td><td>' . round($stats['avg_airspeed'], 2) . '</td><td>' . round($stats['min_airspeed'], 2) . ' to ' . round($stats['max_airspeed'], 2) . "</td></tr>";
                $summaryData .= '<tr><td>Altitude</td><td>' . round($stats['avg_msl'], 2) . '</td><td>' . round($stats['min_msl'], 2) . ' to ' . round($stats['max_msl'], 2) . "</td></tr>";
                $summaryData .= '<tr><td>Engine RPM</td><td>' . round($stats['avg_eng_rpm'], 2) . '</td><td>' . round($stats['min_eng_rpm'], 2) . ' to ' . round($stats['max_eng_rpm'], 2) . "</td></tr>";
                $summaryData .= '<tr><td>Pitch</td><td>' . round($stats['avg_pitch'], 2) . '</td><td>' . round($stats['min_pitch'], 2) . ' to ' . round($stats['max_pitch'], 2) . "</td></tr>";
                $summaryData .= '<tr><td>Roll</td><td>' . round($stats['avg_roll'], 2) . '</td><td>' . round($stats['min_roll'], 2) . ' to ' . round($stats['max_roll'], 2) . "</td></tr>";
                $summaryData .= '<tr><td>Vertical Speed</td><td>' . round($stats['avg_vert'], 2) . '</td><td>' . round($stats['min_vert'], 2) . ' to ' . round($stats['max_vert'], 2) . "</td></tr>";
            }
            $summaryData .= '</table>';
        }

        return view('flights.chart')->with(['flight' => $selectedFlight, 'summary' => $summaryData]);
    }

    public function download()
    {
        $flight     = \Request::route('flight');
        $fileType   = \Request::route('format');
        $filename   = 'tmp/' . $flight;
        $header     = '';
        $contents   = '';
        $dataTable  = '';
        $footer     = '';
        $found      = false;

        $validFlight = $this->validateFlight();
        if($validFlight) {
            $filename   = 'tmp/Flight_' . $flight . '_' . $validFlight['date'];

            $event      = \Request::route('exceedance');
            $duration   = (\Request::route('duration')) ? \Request::route('duration') : 0;
            $eventID    = 0;
            $offset     = 0;
            //echo 'format' . $fileType; echo ' evt' . $event; echo ' duration' . $duration;
            switch($event){
                case 'excessive-roll':
                    $eventID = 1;
                    break;
                case 'excessive-pitch':
                    $eventID = 2;
                    break;
                case 'excessive-speed':
                    $eventID = 3;
                    break;
                case 'high-cht':
                    $eventID = 4;
                    break;
                case 'high-altitude':
                    $eventID = 5;
                    break;
                case 'low-fuel':
                    $eventID = 6;
                    break;
                case 'low-oil-pressure':
                    $eventID = 7;
                    break;
                case 'low-airspeed-approach':
                    $eventID = 8;
                    break;
                case 'excessive-lateral-acceleration':
                    $eventID = 9;
                    break;
                case 'excessive-vertical-acceleration':
                    $eventID = 10;
                    break;
                case 'excessive-longitudinal-acceleration':
                    $eventID = 11;
                    break;
                case 'low-airspeed-climbout':
                    $eventID = 12;
                    break;
                case 'excessive-vsi-final':
                    $eventID = 13;
                    break;
                default:
                    $eventID = 0;
                    break;
            }


            switch ($fileType) {
                case 'csv':
                    $filename .= '.csv';
                    $header  = "#File created by the National General Aviation Flight Information Database Reanimation Tool\n";
                    $header .= "time (seconds), oat, longitude, latitude, msl, radio altitude, pitch, roll, heading, airspeed, vertical speed, "; //in future make column names dynamic
                    $header .= "nav 1 freq, nav 2 freq, obs 1, altimeter, eng 1 egt 1, eng 1 egt 2, eng 1 egt 3, eng 1 egt 4, eng 1 fuel flow, eng 1 rpm\n";
                    break;

                case 'kml':
                    $filename .= '.kml';
                    $header  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                    $header .= '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";
                    $header .= '<Document>' . "\n";
                    $header .= '<name>Flight Path</name>' . "\n";
                    $header .= '<description>File created by the National General Aviation Flight Information Database Reanimation Tool</description>' . "\n";
                    $header .= '<Style id="flightPathStyle">' . "\n";
                    $header .= '<LineStyle>' . "\n";
                    $header .= '<color>FF0000FF</color>' . "\n";
                    $header .= '<width>4</width>' . "\n";
                    $header .= '</LineStyle>' . "\n";
                    $header .= '<PolyStyle>' . "\n";
                    $header .= '<fill>true</fill>' . "\n";
                    $header .= '<outline>false</outline>	' . "\n";
                    $header .= '<color>7F0000FF</color>' . "\n";
                    $header .= '<width>2</width>' . "\n";
                    $header .= '</PolyStyle>' . "\n";
                    $header .= '</Style>' . "\n";
                    $header .= '@LookAt@';
                    $header .= '<Placemark>' . "\n";
                    $header .= '<styleUrl>#flightPathStyle</styleUrl>' . "\n";
                    $header .= '<LineString>' . "\n";
                    $header .= '<extrude>1</extrude>' . "\n";
                    $header .= '<altitudeMode>relativeToSeaFloor</altitudeMode>' . "\n";
                    $header .= '<coordinates>' . "\n";

                    $footer  = '</coordinates>' . "\n";
                    $footer .= '</LineString>' . "\n";
                    $footer .= '</Placemark>' . "\n";
                    $footer .= '</Document>' . "\n";
                    $footer .= '</kml>';
                    break;

                case 'fdr':
                    $filename .= '.fdr';
                    $header  = "A\n1\n\nCOMM, File created by the National General Aviation Flight Information Database Reanimation Tool\n\n";
                    $header .= "ACFT,Aircraft/General Aviation/Cessna 172SP/Cessna_172SP.acf,\n\n"; //need to fix this for all aircraft
                    $header .= "TAIL," . $validFlight['n_number'] . ",\n";
                    $header .= "TIME," . $validFlight['time'] . ",\n";
                    $header .= "DATE," . $validFlight['date'] . ",\n";
                    break;

                case 'data':
                    $header  = '<table class="table table-hover table-striped table-condensed" style="margin:0px;">';
                    $header .= '<thead><tr class="col-md-12">';
                    $header .= '<th class="col-md-1 text-center">Time</th>
                                <th class="col-md-2 text-center">MSL</th>
                                <th class="col-md-2 text-center">Airspeed</th>
                                <th class="col-md-1 text-center">Speed</th>
                                <th class="col-md-2 text-center">Heading</th>
                                <th class="col-md-1 text-left">Pitch</th>
                                <th class="col-md-1 text-right">Roll</th>
                                <th class="col-md-2 text-right">Eng RPM</th>';
                    $header .='</tr></thead></table><div class="div-table-content"><table class="table table-condensed"><tbody>';
                    //NB: CHTs, Eng Oil Press, Fuel Qtys not shown, check if needed....
                    $footer  = '</tbody></table></div>';
                    break;

                default:
                    $filename .= '.fdr';
                    $header  = "A\n1\n\nCOMM, File created by the National General Aviation Flight Information Database Reanimation Tool\n\n";
                    $header .= "ACFT,Aircraft/General Aviation/Cessna 172SP/Cessna_172SP.acf,\n\n";
                    $header .= "TAIL," . $validFlight['n_number'] . ",\n";
                    $header .= "TIME," . $validFlight['time'] . ",\n";
                    $header .= "DATE," . $validFlight['date'] . ",\n";
            }
            if($fileType != 'data')
            {
                \File::put($filename, $header);
            }


            $rowCtr = 0;
            $tmp = '';
            do{
                $result  = \DB::select('CALL sp_GetFlightDetails(?, ?, ?, ?)', array($flight,  $eventID, $duration, $offset));
                foreach($result as $row)
                {
                    if(isset($row->NotFound))
                    {
                        $found = false;

                        if($fileType != 'data')
                        {
                            \File::delete($filename);
                        }

                        $data = ['found' => $found];
                        return \Response::json(['success' => true,'data' => $data]);
                    }
                    else{
                        $found = true;
                    }
                    $row->time = floor($row->time);
                    //print_r($row);
                    switch ($fileType) {
                        case 'csv':
                            $contents .= "$row->time, $row->oat, $row->longitude, $row->latitude, $row->msl_altitude, ";
                            $contents .= "$row->radio_altitude_derived, $row->pitch_attitude, $row->roll_attitude, ";
                            $contents .= "$row->heading, $row->indicated_airspeed, $row->vertical_airspeed, ";
                            $contents .= "$row->nav_1_freq, $row->nav_2_freq, $row->obs_1, $row->altimeter, ";
                            $contents .= "$row->eng_1_egt_1, $row->eng_1_egt_2, $row->eng_1_egt_3, $row->eng_1_egt_4, ";
                            $contents .= "$row->eng_1_fuel_flow, $row->eng_1_rpm \n";
                            break;

                        case 'kml':
                            if($rowCtr == 0)
                            {
                                $tmp  = '<LookAt>' . "\n";
                                $tmp .= '<longitude>' . $row->longitude . '</longitude>' . "\n";
                                $tmp .= '<latitude>'.$row->latitude.'</latitude>' . "\n";
                                $tmp .= '<altitude>0.0</altitude>' . "\n";
                                $tmp .= '<altitudeMode>absolute</altitudeMode>' . "\n";
                                $tmp .= '<range>3000</range>' . "\n";
                                $tmp .= '<tilt>66.7</tilt>' . "\n";
                                $tmp .= '</LookAt>' . "\n";
                            }
                            $contents .= $row->longitude.','.$row->latitude.',' . $row->radio_altitude_derived . "\n";
                            $rowCtr += 1;
                            break;

                        case 'fdr':
                            $contents .= "DATA,".$row->time .",$row->oat,$row->longitude,$row->latitude,$row->msl_altitude,";
                            $contents .= "$row->radio_altitude_derived,0,0,0,$row->pitch_attitude,$row->roll_attitude,";
                            $contents .= "$row->heading,$row->indicated_airspeed,$row->vertical_airspeed,";
                            $contents .= "0,0,0,0,0,0,0,0,0,1,1,1,1,0,";
                            $contents .= "$row->nav_1_freq,$row->nav_2_freq,3,3,$row->obs_1,0,";
                            $contents .= "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,";
                            $contents .= "$row->altimeter,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,";
                            $contents .= "$row->eng_1_egt_1,$row->eng_1_egt_2,$row->eng_1_egt_3,$row->eng_1_egt_4,";
                            $contents .= "$row->eng_1_fuel_flow,0,0,0,0,0,0,0,$row->eng_1_rpm,0,0,0,0,0,0,0";
                            $contents .= ",\n";
                            break;

                        case 'data':
                            $contents .= '<tr class="col-md-12 ' . ($row->event == 1 ? ' danger ' : '') . '">';
                            $contents .= '<td class="col-md-1 text-center">' . floor($row->time)         . '</td>';
                            $contents .= '<td class="col-md-2 text-center">' . $row->msl_altitude        . '</td>';
                            $contents .= '<td class="col-md-2 text-center">' . $row->indicated_airspeed  . '</td>';
                            $contents .= '<td class="col-md-1 text-center">' . $row->vertical_airspeed   . '</td>';
                            $contents .= '<td class="col-md-2 text-center">' . $row->heading             . '</td>';
                            $contents .= '<td class="col-md-1 text-left">'   . $row->pitch_attitude      . '</td>';
                            $contents .= '<td class="col-md-1 text-right">'  . $row->roll_attitude       . '</td>';
                            $contents .= '<td class="col-md-2 text-center">' . $row->eng_1_rpm           . '</td></tr>';
                            break;

                        default:
                            $contents .= "DATA,".$row->time .",$row->oat,$row->longitude,$row->latitude,$row->msl_altitude,";
                            $contents .= "$row->radio_altitude_derived,0,0,0,$row->pitch_attitude,$row->roll_attitude,";
                            $contents .= "$row->heading,$row->indicated_airspeed,$row->vertical_airspeed,";
                            $contents .= "0,0,0,0,0,0,0,0,0,1,1,1,1,0,";
                            $contents .= "$row->nav_1_freq,$row->nav_2_freq,3,3,$row->obs_1,0,";
                            $contents .= "0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,";
                            $contents .= "$row->altimeter,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,";
                            $contents .= "$row->eng_1_egt_1,$row->eng_1_egt_2,$row->eng_1_egt_3,$row->eng_1_egt_4,";
                            $contents .= "$row->eng_1_fuel_flow,0,0,0,0,0,0,0,$row->eng_1_rpm,0,0,0,0,0,0,0";
                            $contents .= ",\n";
                    }

                }

                if(($rowCtr == 0) && ($fileType == 'kml'))
                {
                    $contents  = str_replace('@LookAt@', $tmp, $contents);
                }

                if($fileType != 'data')
                {
                    \File::append($filename, $contents);
                }
                else{
                    $dataTable .= $contents;
                }

                $contents = '';
                $offset += 1000;

            }while($result);

            //print_r($result);

            if($found)
            {
                if($fileType != 'data')
                {
                    \File::append($filename, $footer);

                    //compress and download the file
                    $zipFileName = 'Flight_' . $flight . '_' . $validFlight['date'] . '.zip';

                    $zip = new \ZipArchive;
                    if ($zip->open(public_path() . '/tmp/' . $zipFileName, \ZipArchive::CREATE) === TRUE) {
                        $zip->addFile($filename, basename($filename));
                    }
                    $zip->close();

                    \File::delete($filename);
                    $generated = asset('tmp/' . $zipFileName);
                }
                else
                {
                    $generated = $header . $dataTable . $footer;
                }

                return \Response::json(['success' => true,'data' => ['found' => true, 'file' => $generated]]);
                //return \Response::download(public_path(). '/tmp/' . $zipFileName, $zipFileName, $headers)->deleteFileAfterSend(true);
            }

        }

        \File::delete($filename);
        $data = ['found' => false];
        return \Response::json(['success' => true,'data' => $data]);

        //write file to disk and compress before download

        //return \Redirect::back();
    }

    public function archive()
    {
        //display flash message after flight is archived
        $validFlight = $this->validateFlight();
        if($validFlight)
        {
            \DB::table('main_exceedances')
                ->where('flight', $validFlight->id)
                ->update(array('archived' => 'Y',
                    'archive_date' => \DB::RAW('NOW()'),
                    'username' => \Auth::user()->email));
        }
        flash()->success('Your flight and its respective exceedance(s) has been archived!');

        return \Redirect::back(); //redirect('flights');
    }

    public function loadReplay()
    {
        $flight         = \Request::route('flight');
        $validFlight    = $this->validateFlight();
        $path           = 'tmp/';
        $filename       = $path . $flight . '.czml';

        if($validFlight) {
            $flightStart = $validFlight['date'] . 'T' . $validFlight['time'] . 'Z'; //ERR: NGAFID TIME NOT ALWAYS IN UTC
            $flightName  = $flight;
            //$flightName .= ' ' . $flight;

            $duration    = ($validFlight['duration'] ? $validFlight['duration'] : '00:00:00');
            $duration    = explode(':', $duration);

            $flightEnd  = date("H:i:s", strtotime("+ $duration[0] hours $duration[1] minutes $duration[2] seconds ",strtotime($validFlight['date'] . ' ' . $validFlight['time'])));
            $flightEnd  = $validFlight['date'] . 'T' . $flightEnd . 'Z';

            $nNumber = ($validFlight['n_number'] ? $validFlight['n_number'] : 'N/A');
            $origin  = ($validFlight['origin'] ? $validFlight['origin'] : 'N/A');
            $destination = ($validFlight['destination'] ? $validFlight['destination'] : 'N/A');

            $description  = "Call Sign " . $nNumber . "<br>";
            $description .= "Duration " . $validFlight['duration'] . "<br>";
            $description .= "Route " . $origin . " => " . $destination;


            $header = <<<HDR
    [{
        "id":"document",
        "name":"Replay",
        "version":"1.0",
        "clock":{
            "interval":"{$flightStart}/{$flightEnd}",
            "currentTime":"{$flightStart}",
            "multiplier":5,
            "range":"LOOP_STOP",
            "step":"SYSTEM_CLOCK_MULTIPLIER"
            }
        },
        {
        "id":"FlightTrack/Replay",
        "name": "{$flightName}",
        "availability":"{$flightStart}/{$flightEnd}",
        "description":"{$description}",
        "path":{
            "show":false,
            "width":2,
            "material":{
                "polylineOutline" : {
                "outlineWidth" : 1,
                "color" : {"rgba":[255,0,255,255]},
                "outlineColor" : {"rgba":[0,0,0,255]}
                }
            }
        },
HDR;

            \File::put($filename, $header);

            $contents = '"position":{"epoch":"' . $flightStart . '","cartographicDegrees":[';

            $offset     = 0;
            $rowCtr = 0;
            do{
                $result  = \DB::select('CALL sp_GetFlightDetails(?, ?, ?, ?)', array($flight,  0, 0, $offset));
                foreach($result as $row)
                {
                    if(isset($row->NotFound))
                    {
                        $found = false;
                        \File::delete($filename);

                        //return view('errors/404');
                        $data = ['found' => $found];
                        return \Response::json(['success' => true,'data' => $data]);
                    }
                    else{
                        $found = true;
                    }

                    if($rowCtr == 0) {
                        $contents .=  floor($row->time) . ',' . $row->longitude  . ',' . $row->latitude  . ',' . ($row->radio_altitude_derived < 5 ? 5 : $row->radio_altitude_derived);
                    }
                    else{
                        $contents .=  ',' . "\n" . floor($row->time) . ',' . $row->longitude  . ',' . $row->latitude  . ',' . ($row->radio_altitude_derived < 5 ? 5 : $row->radio_altitude_derived);
                    }
                    $rowCtr += 1;
                }

                \File::append($filename, $contents);
                $contents = '';
                $offset += 1000;

            }while($result);

            if($found) {
                \File::append($filename, ']}}]');
            }

            return \Response::json(['success' => true,'data' => ['found' => true]]);

        }
        else{
            return view('errors/404');
        }
    }

    public function replay()
    {
        $flight  = \Request::route('flight');
        return view('flights/replay')->with(['flight' => $flight]);
    }

}