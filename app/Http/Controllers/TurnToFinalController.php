<?php
namespace NGAFID\Http\Controllers;

use Illuminate\Http\Request;
use NGAFID\FlightID;
use Response;

class TurnToFinalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param FlightID $flightId
     *
     * @return Response
     */
    public function index(FlightID $flightId, Request $request)
    {
        // $flightId = $request->get('flight_id');
        $approach = $flightId->approaches()->first();
        $data = $flightId->mainTableData()
            ->select(['longitude', 'latitude'])
            ->offset($approach->approach_start)
            ->take($approach->landing_end - $approach->approach_start + 1)
            ->get()
            ->map(
                function ($point) {
                    return [$point->longitude, $point->latitude];
                }
            );
        $runway = $approach->runway;

        return view(
            'turn_to_final/index',
            [
                'flightId' => $flightId->id,
                'approach' => $approach,
                'runway' => $runway,
                'touchdown' => [$runway->touchdown_lon, $runway->touchdown_lat],
                'coordinates' => $data,
            ]
        );
    }

    public function chart()
    {
    }
}
