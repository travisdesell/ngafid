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
    public function index($flightId = null)
    {
        return view('turn_to_final/index', [
            'flightId' => $flightId,
            // 'approaches' => $flightId->approaches,
            // 'data' => $allData,
        ]);
    }

    public function chart($flightId, Request $request)
    {
        $flightId = FlightID::with('approaches')
            ->findOrFail($flightId);

        $allData = $flightId->approaches->map(function ($approach) use ($flightId) {
            $data = $flightId->mainTableData()
                ->select(['longitude', 'latitude'])
                ->offset($approach->approach_start)
                ->take($approach->landing_end - $approach->approach_start + 1)
                ->get()
                ->map(function ($point) {
                    return [$point->longitude, $point->latitude];
                });

            $approachData = $data->slice(
                0, $approach->approach_end - $approach->approach_start + 1
            );
            $landingData = $data->slice(
                $approach->approach_end - $approach->approach_start + 1,
                $approach->landing_end - $approach->landing_start + 1
            );
            $runway = $approach->runway;

            return [
                'approach' => $approachData,
                'landing' => $landingData,
                'takeoff' => [],
                'runway' => $runway,
            ];
        });

        return response()->json($allData);
    }
}
