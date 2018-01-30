<?php
namespace NGAFID\Http\Controllers;

use NGAFID\Airport;
use NGAFID\Http\Requests;
use NGAFID\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Response;

class RunwayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function autocomplete(Request $request)
    {
        $airport_id = $request->get('airport_id');
        $runways = Airport::find($airport_id)->runways()->lists('id');

        return Response::json($runways);
    }
}
