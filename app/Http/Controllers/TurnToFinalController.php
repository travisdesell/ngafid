<?php
namespace NGAFID\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use NGAFID\Approach;
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
     * @param int $flightId
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

    public function flights(Request $request) {
        $flightIds = $request->get('flight_id');

        $flights = FlightID::with('aircraft')
            ->whereIn('id', $flightIds)
            ->where('fleet_id', auth()->user()->org_id)
            ->get();

        return view('turn_to_final/flights', [
            'data' => $flights,
        ]);
    }

    public function chart($flightId, $approachId = null)
    {
        $flight = FlightID::with([
            'approaches' => function ($query) use ($approachId) {
                if ($approachId) {
                    $query->where('approach_id', $approachId);
                }
            },
            'approaches.runway',
        ])->where('id', $flightId)
            ->where('fleet_id', auth()->user()->org_id)
            ->firstOrFail();

        $allData = $flight->approaches->map(function ($approach) use ($flight) {
            $data = $flight->mainTableData()
                ->select(['longitude', 'latitude'])
                ->offset($approach->turn_start)
                ->take($approach->landing_end - $approach->turn_start + 1)
                ->get()
                ->map(function ($point) {
                    return [$point->longitude, $point->latitude];
                });

            $turnData = $data->slice(
                0, $approach->turn_end - $approach->turn_start + 1
            );
            $approachData = $data->slice(
                $approach->approach_start - $approach->turn_start,
                $approach->approach_end - $approach->approach_start + 1
            );
            $landingData = $data->slice(
                $approach->landing_start - $approach->turn_start,
                $approach->landing_end - $approach->landing_start + 1
            );
            $runway = $approach->runway;

            return [
                'flight_id' => $approach->flight_id,
                'approach_id' => $approach->approach_id,
                'phases' => [
                    'turn' => [
                        'severity' => $approach->turn_risk_level,
                        'type' => $approach->turn_error_type,
                        'coordinates' => $turnData,
                    ],
                    'approach' => [
                        'type' => $approach->landing_type,
                        'coordinates' => $approachData,
                    ],
                    'landing' => [
                        'coordinates' => $landingData,
                    ],
                ],
                'runway' => $runway,
            ];
        });

        return response()->json($allData);
    }

    public function chartAgg($runwayId, $date)
    {
        $date = new Carbon($date);
        $fromDate = $date->toDateString();
        $toDate = $date->copy()->endOfMonth()->toDateString();

        $approaches = Approach::whereHas('flight', function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('date', [$fromDate, $toDate])
                ->where('fleet_id', auth()->user()->org_id);
        })->where('runway_id', $runwayId)
            ->where('turn_risk_level', '>', 0)  // filter out the non-risky turns in the aggregate display
            ->get(['flight_id', 'approach_id']);

        return response()->json($approaches);
    }

    public function chartAgg2($runwayId, $date)
    {
        $date = new Carbon($date);
        $fromDate = $date->toDateString();
        $toDate = $date->copy()->endOfMonth()->toDateString();
        $runway = TestRunway::with(['approaches' => function ($query) use ($fromDate, $toDate) {
            $query->whereHas('flight', function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('date', [$fromDate, $toDate]);
            });
        }, 'approaches.flight'])->findOrFail($runwayId);

        $approaches = $runway->approaches;

        $allData = [];
        $allData = $approaches->map(function ($approach) use ($runway) {
            $data = $approach->flight->mainTableData()
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

            return [
                'approach' => $approachData,
                'landing' => $landingData,
                'takeoff' => [],
                'class' => $approach->landing_type,
                'runway' => $runway,
            ];
        });

        return response()->json($allData);
    }
}
