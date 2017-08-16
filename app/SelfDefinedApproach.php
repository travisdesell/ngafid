<?php
namespace NGAFID;

use Eloquent;

class SelfDefinedApproach extends Eloquent
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'stabilized_approach_self_defined';

    public function flightID()
    {
        $this->belongsTo('NGAFID\FlightID', 'flight', 'id');
    }

    public function scopeApproachData($query, $fleet, $runway, $date)
    {
        return $query->with('flightID')
            ->where('fltDate', $date)
            ->where('fleet_id', $fleet)
            ->where('airport_id', $runway);
    }
}
