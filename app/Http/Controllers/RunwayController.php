<?php
namespace NGAFID\Http\Controllers;

use Illuminate\Http\Request;
use NGAFID\TestAirport;

class RunwayController extends Controller
{
    public function autoComplete(Request $request)
    {
        if ( !$request->wantsJson()) {
            // TODO: throw exception as this should only be requested by JSON
        }

        $airportId = $request->get('code');
        $runways = TestAirport::findOrFail($airportId)->runways()->lists(
            'code',
            'id'
        );

        return response()->json($runways);
    }
}
