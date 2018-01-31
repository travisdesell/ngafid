<?php
namespace NGAFID\Http\Controllers;

use Auth;
use NGAFID\Approach;
use NGAFID\Http\Requests;
use NGAFID\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Response;

class ApproachController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $selectedRunway = $request->get('runway');
        $selectedStartDate = $request->get('start_date', date('Y') . '-' . date('m'));
        $selectedEndDate = $request->get('end_date', date('Y') . '-' . date('m'));

        $runways = ['' => 'Select Runway'];

        return view(
            'approach/index',
            [
                'runways' => $runways,
                'selectedRunway' => $selectedRunway,
                'start_date' => $selectedStartDate,
                'end_date' => $selectedEndDate,
            ]
        );
    }

    public function chart(Request $request)
    {
        $startDates = $request->get('startDates');
        $endDates = $request->get('endDates');
        $approachParams = [
            ['crosstrack', 5.0],
            ['heading', 1.0],
            ['ias', 5.0],
            ['vsi', 50.0],
        ];

        $zippedDates = array_map(null, $startDates, $endDates);
        $results = [];

        foreach ($zippedDates as list($start, $end)) {
            $key = "$start => $end";
            $results[$key] = [];
            $approachParamValues = Approach::whereHas('flight', function ($flight) use ($start, $end) {
                return $flight->where('fleet_id', '=', Auth::user()->org_id)
                    ->whereBetween('date', [$start, $end]);
            })->get()->map(function ($approach) {
                return [
                    'id' => $approach->flight_id,
                    'crosstrack' => $approach->f2_crosstrack ?: $approach->all_crosstrack,
                    'heading' => $approach->f1_heading ?: $approach->all_heading,
                    'ias' => $approach->a_ias ?: $approach->all_ias,
                    'vsi' => $approach->s_vsi ?: $approach->all_vsi,
                ];
            });

            foreach ($approachParams as list($param, $stepValue)) {
                $values = $approachParamValues->map(function ($x) use ($param) {
                    return ['id' => $x['id'], 'x' => $x[$param]];
                })->reject(function ($x) use ($param) {
                    return $x['x'] === null;
                });
                $results[$key][$param] = $this->histogram($values, $stepValue);
            }
        }

        return Response::json($results);
    }

    /**
     * Get histogram data out of xy data
     *
     * @param $data array of tuples [x, y]
     * @param $step float for the histogram
     *
     * @return array data
     */
    private function histogram($data, $step)
    {
        $histo = [];
        $arr = [];

        foreach ($data as $datum) {
            $x = floor($datum['x'] / $step) * $step;
            if ( !array_key_exists("$x", $histo)) {
                $histo["$x"] = ['count' => 0, 'flights' => []];
            }
            $histo["$x"]['count']++;
            $histo["$x"]['flights'][] = $datum['id'];
        }

        foreach ($histo as $k => $v) {
            $arr[] = [
                'x' => floatval($k),
                'y' => $v['count'],
                'ids' => $v['flights'],
            ];
        }

        usort(
            $arr,
            function ($a, $b) {
                return $a['x'] - $b['x'];
            }
        );

        return $arr;
    }
}
