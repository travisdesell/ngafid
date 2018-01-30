<?php
namespace NGAFID\Http\Controllers;

use Illuminate\Support\Str;
use NGAFID\Airport;
use NGAFID\Http\Requests;
use NGAFID\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Response;

class AirportController extends Controller
{
    public function autocomplete(Request $request)
    {
        $term = Str::lower($request->get('term'));
        $airports = Airport::where('id', 'LIKE', "%$term%")->orWhere(
            'name',
            'LIKE',
            "%$term%"
        )->get()->map(
            function ($airport) {
                return ['id' => $airport->id, 'value' => $airport->city];
            }
        );

        return Response::json($airports);
    }
}
