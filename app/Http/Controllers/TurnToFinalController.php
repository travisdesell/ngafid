<?php
namespace NGAFID\Http\Controllers;

use Illuminate\Http\Request;
use NGAFID\FlightID;
use NGAFID\TestRunway;
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
            'date' => date('Y-m'),
            'airports' => ['' => 'Select Runway'],
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
                $approach->approach_end - $approach->approach_start,
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

    public function chartAgg(Request $request)
    {
        $fromDate = $request->get('from');
        $toDate = $request->get('to');
        $runway = TestRunway::with(['approaches' => function ($query) use ($fromDate, $toDate) {
            $query->whereHas('flight', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('date', [$fromDate, $toDate]);
            });
        }, 'approaches.flight'])->findOrFail($request->get('runway'));

        $approaches = $runway->approaches;

        $allData = [];
        // $allData = $approaches->map(function ($approach) use ($runway) {
        //     $data = $approach->flight->mainTableData()
        //         ->select(['longitude', 'latitude'])
        //         ->offset($approach->approach_start)
        //         ->take($approach->landing_end - $approach->approach_start + 1)
        //         ->get()
        //         ->map(function ($point) {
        //             return [$point->longitude, $point->latitude];
        //         });
        //
        //     $approachData = $data->slice(
        //         0, $approach->approach_end - $approach->approach_start + 1
        //     );
        //     $landingData = $data->slice(
        //         $approach->approach_end - $approach->approach_start,
        //         $approach->landing_end - $approach->landing_start + 1
        //     );
        //
        //     return [
        //         'approach' => $approachData,
        //         'landing' => $landingData,
        //         'takeoff' => [],
        //         'runway' => $runway,
        //     ];
        // });

        return response()->json($allData);
    }
}
