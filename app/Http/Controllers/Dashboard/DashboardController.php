<?php namespace NGAFID\Http\Controllers\Dashboard;

use NGAFID\Http\Controllers\Controller;
use NGAFID\FlightID;
use NGAFID\Aircraft;
use NGAFID\Fleet;


class DashboardController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Dashboard Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders your application's "dashboard" for users that
	| are authenticated.
	|
	*/

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware('auth');
	}

	/**
	 * Show the application dashboard to the user.
	 *
	 * @return Response
	 */
	public function index()
	{
        $fleet = new Fleet();
        $aircraft = new Aircraft();
        $flightIdTable = new FlightID();

        if(\Auth::check()) {
            $fleetID = \Auth::user()->org_id;
            $userType = (\Auth::user()->user_type == 'G') ? 'GAARD' : 'NGAFID';


            $totalFlights = $fleet->find($fleetID)->flights()->count();
            $totalHours = $flightIdTable->totalFlightHours($fleetID)->get('duration')->first();
            $totalHours = $totalHours->duration;
            $totalHours = sprintf('%02d:%02d:%02d', ($totalHours / 3600), ($totalHours / 60 % 60), $totalHours % 60);

            $totalAircraft = $aircraft->uniqueAircraft($fleetID)->count();


            $genericExceedances = array(
                'excessiveRoll'         =>  'Excessive Roll is defined as bank angle in excess of 60 degrees.',
                'excessivePitch'        =>  'Excessive Pitch is defined as pitch in excess of 30 degrees.',
                'excessiveLateral'      =>  'Excessive Lateral Acceleration is defined as lateral G-Loads exceed +4.4 G\'s',
                'excessiveVertical'     =>  'Excessive Vertical Acceleration is defined as vertical G-Loads exceed +4.4 G\'s',
                'excessiveLongitudinal' =>  'Excessive Longitudinal Acceleration is defined as longitudinal G-Loads exceed +4.4 G\'s',
                'excessiveVSI'          =>  'Aircraft\'s vertical speed increases beyond -1000 FPM while on short final',
            );
            $c172Exceedances = array(
                'excessiveSpeed'        =>  'Excessive Speed is defined as indicated airspeed in excess of 163 knots.',
                'highCHT'               =>  'High Cylinder Head Temperature is defined as CHT in any cylinder greater than 500 degrees',
                'highAltitude'          =>  'High Altitude is defined as altitude above 12,800 feet MSL.',
                'lowFuel'               =>  'Low fuel is defined as combined fuel less than 8 gallons while aircraft is in flight.',
                'lowOilPress'           =>  'Low Oil Pressure is defined as engine oil pressure less than 20 PSI while aircraft is in flight.',
                'lowAirspeedApproach'   =>  'Low Airspeed on Approach is defined as the aircraft\'s Indicated Airspeed (IAS) decreases below 57 knots while on short final.',
                'lowAirspeedClimbout'   =>  'Low Airspeed on Climb-out is defined as the aircraft\'s Indicated Airspeed (IAS) decreases below 52 knots on climb-out'
            );

            $c182Exceedances = array(
                'excessiveSpeed'        =>  'Excessive Speed is defined as indicated airspeed in excess of 175 knots.',
                'highCHT'               =>  'High Cylinder Head Temperature is defined as CHT in any cylinder greater than 500 degrees',
                'highAltitude'          =>  'High Altitude is defined as altitude above 15,000 feet MSL.',
                'lowFuel'               =>  'Low fuel is defined as combined fuel less than 8 gallons while aircraft is in flight.',
                'lowOilPress'           =>  'Low Oil Pressure is defined as engine oil pressure less than 20 PSI while aircraft is in flight.',
                'lowAirspeedApproach'   =>  'Low Airspeed on Approach is defined as the aircraft\'s Indicated Airspeed (IAS) decreases below 57 knots while on short final.',
                'lowAirspeedClimbout'   =>  'Low Airspeed on Climb-out is defined as the aircraft\'s Indicated Airspeed (IAS) decreases below 55 knots on climb-out'
            );

            $exceedanceStats = $flightIdTable->exceedanceStats($fleetID);

            $excessiveRoll    = 0;
            $excessivePitch   = 0;
            $excessiveLat     = 0;
            $excessiveVert    = 0;
            $excessiveLon     = 0;
            $excessiveVSI     = 0;
            $totalFlightsByAircraft = $flightIdTable->totalFlightsByAircraft($fleetID);

            $totalC172  = 0;
            $totalC182  = 0;
            $totalGeneric = $totalFlights;

            foreach($totalFlightsByAircraft as $aircraft)
            {
                if($aircraft->aircraft_id == 1)
                {
                    $totalC172 = $aircraft->total;
                }
                elseif($aircraft->aircraft_id == 2)
                {
                    $totalC182 = $aircraft->total;
                }
                //else
                //{
                    //$totalOther += $aircraft->total;
                //}
            }


            $summary = array();

            foreach($exceedanceStats as $stats)
            {
                if($stats->aircraft_type == 1) {
                    $c172['aircraft_type'] = 'c172';

                    $c172['ExcessiveSpeed'] = ($totalC172 > 0) ? (($stats->ExcessiveSpeed / $totalC172) * 100) : 0;
                    if ($c172['ExcessiveSpeed'] <= 50)
                    {
                        $c172['ExcessiveSpeedLabel'] = 'progress-bar-success';
                    }
                    elseif ($c172['ExcessiveSpeed'] > 50 && $c172['ExcessiveSpeed'] < 80)
                    {
                        $c172['ExcessiveSpeedLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c172['ExcessiveSpeedLabel'] = 'progress-bar-danger';
                    }

                    $c172['HighCHT'] = ($totalC172 > 0) ? (($stats->HighCHT / $totalC172) * 100) : 0;
                    if ($c172['HighCHT'] <= 50)
                    {
                        $c172['HighCHTLabel'] = 'progress-bar-success';
                    }
                    elseif ($c172['HighCHT'] > 50 && $c172['HighCHT'] < 80)
                    {
                        $c172['HighCHTLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c172['HighCHTLabel'] = 'progress-bar-danger';
                    }

                    $c172['HighAltitude'] = ($totalC172 > 0) ? (($stats->HighAltitude / $totalC172) * 100) : 0;
                    if ($c172['HighAltitude'] <= 50)
                    {
                        $c172['HighAltitudeLabel'] = 'progress-bar-success';
                    }
                    elseif ($c172['HighAltitude'] > 50 && $c172['HighAltitude'] < 80)
                    {
                        $c172['HighAltitudeLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c172['HighAltitudeLabel'] = 'progress-bar-danger';
                    }

                    $c172['LowFuel'] = ($totalC172 > 0) ? (($stats->LowFuel / $totalC172) * 100) : 0;
                    if ($c172['LowFuel'] <= 50)
                    {
                        $c172['LowFuelLabel'] = 'progress-bar-success';
                    }
                    elseif ($c172['LowFuel'] > 50 && $c172['LowFuel'] < 80)
                    {
                        $c172['LowFuelLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c172['LowFuelLabel'] = 'progress-bar-danger';
                    }

                    $c172['LowOilPress'] = ($totalC172 > 0) ? (($stats->LowOilPress / $totalC172) * 100) : 0;
                    if ($c172['LowOilPress'] <= 50)
                    {
                        $c172['LowOilPressLabel'] = 'progress-bar-success';
                    }
                    elseif ($c172['LowOilPress'] > 50 && $c172['LowOilPress'] < 80)
                    {
                        $c172['LowOilPressLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c172['HighCHTLabel'] = 'progress-bar-danger';
                    }

                    $c172['LowAirspeedApproach'] = ($totalC172 > 0) ? (($stats->LowAirspeedApproach / $totalC172) * 100) : 0;
                    if ($c172['LowAirspeedApproach'] <= 50)
                    {
                        $c172['LowAirspeedApproachLabel'] = 'progress-bar-success';
                    }
                    elseif ($c172['LowAirspeedApproach'] > 50 && $c172['LowAirspeedApproach'] < 80) {
                        $c172['LowAirspeedApproachLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c172['LowAirspeedApproachLabel'] = 'progress-bar-danger';
                    }

                    $c172['LowAirspeedClimbout'] = ($totalC172 > 0) ? (($stats->LowAirspeedClimbout / $totalC172) * 100) : 0;
                    if($c172['LowAirspeedClimbout'] <= 50)
                    {
                        $c172['LowAirspeedClimboutLabel'] = 'progress-bar-success';
                    }
                    elseif($c172['LowAirspeedClimbout'] > 50 && $c172['LowAirspeedClimbout'] < 80)
                    {
                        $c172['LowAirspeedClimboutLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c172['LowAirspeedClimboutLabel'] = 'progress-bar-danger';
                    }

                    $c172['TotalFlights'] = $totalC172;
                    $summary[] = $c172;
                }
                elseif($stats->aircraft_type == 2)
                {
                    $c182['aircraft_type']   = 'c182';

                    $c182['ExcessiveSpeed'] = ($totalC182 > 0) ? (($stats->ExcessiveSpeed / $totalC182) * 100) : 0;
                    if ($c182['ExcessiveSpeed'] <= 50)
                    {
                        $c182['ExcessiveSpeedLabel'] = 'progress-bar-success';
                    }
                    elseif ($c182['ExcessiveSpeed'] > 50 && $c182['ExcessiveSpeed'] < 80)
                    {
                        $c182['ExcessiveSpeedLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c182['ExcessiveSpeedLabel'] = 'progress-bar-danger';
                    }

                    $c182['HighCHT'] = ($totalC182 > 0) ? (($stats->HighCHT / $totalC182) * 100) : 0;
                    if ($c182['HighCHT'] <= 50)
                    {
                        $c182['HighCHTLabel'] = 'progress-bar-success';
                    }
                    elseif ($c182['HighCHT'] > 50 && $c182['HighCHT'] < 80)
                    {
                        $c182['HighCHTLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c182['HighCHTLabel'] = 'progress-bar-danger';
                    }

                    $c182['HighAltitude'] = ($totalC182 > 0) ? (($stats->HighAltitude / $totalC182) * 100) : 0;
                    if ($c182['HighAltitude'] <= 50)
                    {
                        $c182['HighAltitudeLabel'] = 'progress-bar-success';
                    }
                    elseif ($c182['HighAltitude'] > 50 && $c182['HighAltitude'] < 80)
                    {
                        $c182['HighAltitudeLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c182['HighAltitudeLabel'] = 'progress-bar-danger';
                    }

                    $c182['LowFuel'] = ($totalC182 > 0) ? (($stats->LowFuel / $totalC182) * 100) : 0;
                    if ($c182['LowFuel'] <= 50)
                    {
                        $c182['LowFuelLabel'] = 'progress-bar-success';
                    }
                    elseif ($c182['LowFuel'] > 50 && $c182['LowFuel'] < 80)
                    {
                        $c182['LowFuelLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c182['LowFuelLabel'] = 'progress-bar-danger';
                    }

                    $c182['LowOilPress'] = ($totalC182 > 0) ? (($stats->LowOilPress / $totalC182) * 100) : 0;
                    if ($c182['LowOilPress'] <= 50)
                    {
                        $c182['LowOilPressLabel'] = 'progress-bar-success';
                    }
                    elseif ($c182['LowOilPress'] > 50 && $c182['LowOilPress'] < 80)
                    {
                        $c182['LowOilPressLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c182['HighCHTLabel'] = 'progress-bar-danger';
                    }

                    $c182['LowAirspeedApproach'] = ($totalC182 > 0) ? (($stats->LowAirspeedApproach / $totalC182) * 100) : 0;
                    if ($c182['LowAirspeedApproach'] <= 50)
                    {
                        $c182['LowAirspeedApproachLabel'] = 'progress-bar-success';
                    }
                    elseif ($c182['LowAirspeedApproach'] > 50 && $c182['LowAirspeedApproach'] < 80) {
                        $c182['LowAirspeedApproachLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c182['LowAirspeedApproachLabel'] = 'progress-bar-danger';
                    }

                    $c182['LowAirspeedClimbout'] = ($totalC182 > 0) ? (($stats->LowAirspeedClimbout / $totalC182) * 100) : 0;
                    if($c182['LowAirspeedClimbout'] <= 50)
                    {
                        $c182['LowAirspeedClimboutLabel'] = 'progress-bar-success';
                    }
                    elseif($c182['LowAirspeedClimbout'] > 50 && $c182['LowAirspeedClimbout'] < 80)
                    {
                        $c182['LowAirspeedClimboutLabel'] = 'progress-bar-warning';
                    }
                    else
                    {
                        $c182['LowAirspeedClimboutLabel'] = 'progress-bar-danger';
                    }
                    $summary[] = $c182;
                }
                /*else
                {*/
                    $excessiveRoll    += $stats->ExcessiveRoll;
                    $excessivePitch   += $stats->ExcessivePitch;
                    $excessiveLat     += $stats->ExcessiveLat;
                    $excessiveVert    += $stats->ExcessiveVert;
                    $excessiveLon     += $stats->ExcessiveLon;
                    $excessiveVSI     += $stats->ExcessiveVSI;
                //}
            }
            $other['aircraft_type']    = 'other';
            $other['ExcessiveRoll']    = ($totalGeneric > 0) ? (($excessiveRoll / $totalGeneric) * 100) : 0;
            if($other['ExcessiveRoll'] <= 50)
            {
                $other['ExcessiveRollLabel'] = 'progress-bar-success';
            }
            elseif($other['ExcessiveRoll'] > 50 && $other['ExcessiveRoll'] < 80)
            {
                $other['ExcessiveRollLabel'] = 'progress-bar-warning';
            }
            else
            {
                $other['ExcessiveRollLabel'] = 'progress-bar-danger';
            }

            $other['ExcessivePitch']   = ($totalGeneric > 0) ? (($excessivePitch / $totalGeneric) * 100) : 0;
            if($other['ExcessivePitch'] <= 50)
            {
                $other['ExcessivePitchLabel'] = 'progress-bar-success';
            }
            elseif($other['ExcessivePitch'] > 50 && $other['ExcessivePitch'] < 80)
            {
                $other['ExcessivePitchLabel'] = 'progress-bar-warning';
            }
            else
            {
                $other['ExcessivePitchLabel'] = 'progress-bar-danger';
            }

            $other['ExcessiveLat']     = ($totalGeneric > 0) ? (($excessiveLat / $totalGeneric) * 100) : 0;
            if($other['ExcessiveLat'] <= 50)
            {
                $other['ExcessiveLatLabel'] = 'progress-bar-success';
            }
            elseif($other['ExcessiveLat'] > 50 && $other['ExcessiveLat'] < 80)
            {
                $other['ExcessiveLatLabel'] = 'progress-bar-warning';
            }
            else
            {
                $other['ExcessiveLatLabel'] = 'progress-bar-danger';
            }

            $other['ExcessiveVert']    = ($totalGeneric > 0) ? (($excessiveVert / $totalGeneric) * 100) : 0;
            if($other['ExcessiveVert'] <= 50)
            {
                $other['ExcessiveVertLabel'] = 'progress-bar-success';
            }
            elseif($other['ExcessiveVert'] > 50 && $other['ExcessiveVert'] < 80)
            {
                $other['ExcessiveVertLabel'] = 'progress-bar-warning';
            }
            else
            {
                $other['ExcessiveVertLabel'] = 'progress-bar-danger';
            }

            $other['ExcessiveLon']     = ($totalGeneric > 0) ? (($excessiveLon / $totalGeneric) * 100) : 0;
            if($other['ExcessiveLon'] <= 50)
            {
                $other['ExcessiveLonLabel'] = 'progress-bar-success';
            }
            elseif($other['ExcessiveLon'] > 50 && $other['ExcessiveLon'] < 80)
            {
                $other['ExcessiveLonLabel'] = 'progress-bar-warning';
            }
            else
            {
                $other['ExcessiveLonLabel'] = 'progress-bar-danger';
            }

            $other['ExcessiveVSI']     = ($totalGeneric > 0) ? (($excessiveVSI / $totalGeneric) * 100) : 0;
            if($other['ExcessiveVSI'] <= 50)
            {
                $other['ExcessiveVSILabel'] = 'progress-bar-success';
            }
            elseif($other['ExcessiveVSI'] > 50 && $other['ExcessiveVSI'] < 80)
            {
                $other['ExcessiveVSILabel'] = 'progress-bar-warning';
            }
            else
            {
                $other['ExcessiveVSILabel'] = 'progress-bar-danger';
            }

            $other['TotalFlights']     = $totalGeneric;
            $summary[] = $other;

            unset($exceedanceStats);


            $dashData = array(
                'name'          => \Auth::user()->firstname . ' ' . \Auth::user()->lastname,
                'type'          =>  $userType,
                'username'      => \Auth::user()->email,
                'flightHours'   => $totalHours,
                'numFlights'    => $totalFlights,
                'numAircraft'   => $totalAircraft,
                'generic'       =>  $genericExceedances,
                'c172'          =>  $c172Exceedances,
                'c182'          =>  $c182Exceedances,
                'stats'         => $summary,
            );
//dd($dashData);
            return view('dashboard.dashboard')->with('data', $dashData);
        }

	}

    public function faq()
    {
        $userInfo   = \DB::select( \DB::raw("SELECT COUNT(`id`) AS 'total', user_type AS 'type' FROM user GROUP BY user_type") );

        $fleetInfo  = \DB::select( \DB::raw("SELECT COUNT(`id`) AS 'total' FROM organization WHERE org_type = 'F'") );

        $flightInfo = \DB::select( \DB::raw("SELECT o.org_type AS 'type', count(f.id) as 'uploads',
                    COALESCE(concat(floor(SUM( TIME_TO_SEC( f.`duration` ))/3600),\":\",LPAD(floor(SUM( TIME_TO_SEC( f.`duration` ))/60)%60, 2, 0),\":\",LPAD(SUM( TIME_TO_SEC( f.`duration` ))%60, 2, 0)), 'N/A') as 'hours'
                    FROM flight_id f LEFT OUTER JOIN organization o ON o.id = f.fleet_id
                    WHERE o.`org_type` IN ('F', 'O') GROUP BY o.`org_type`") );


        return view('dashboard.faq')->with(['userInfo' => $userInfo, 'fleetInfo' => $fleetInfo, 'flightInfo' => $flightInfo]);
    }

}
