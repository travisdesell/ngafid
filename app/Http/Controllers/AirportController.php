<?php
namespace NGAFID\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use NGAFID\TestAirport;

class AirportController extends Controller
{
    public function autoComplete(Request $request)
    {
        if ( !$request->wantsJson()) {
            // TODO: throw exception as this should only be requested by JSON
        }

        $term = Str::lower($request->get('term'));
        $airports = TestAirport::where('code', 'LIKE', "%{$term}%")->orWhere(
            'name',
            'LIKE',
            "%{$term}%"
        )->get()->map(
            function ($airport) {
                return ['id' => $airport->id, 'value' => $airport->name];
            }
        );

        return response()->json($airports);
    }
}
