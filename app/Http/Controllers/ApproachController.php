<?php
namespace NGAFID\Http\Controllers;

use NGAFID\Http\Requests;
use NGAFID\Http\Controllers\Controller;

use Illuminate\Http\Request;

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
