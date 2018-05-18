<?php
namespace NGAFID;

use Eloquent;

class Approach extends Eloquent
{
    use Traits\HasCompositePrimaryKey;

    public $primaryKey = ['flight_id', 'approach_id'];

    public $timestamps = false;

    protected $casts = [
        'unstable' => 'boolean',
    ];

    public function scopeStable($query)
    {
        return $query->where('unstable', '=', 0);
    }

    public function scopeUnstable($query)
    {
        return $query->where('unstable', '=', 1);
    }

    public function flight()
    {
        return $this->belongsTo('NGAFID\FlightID', 'flight_id', 'id');
    }

    public function airport()
    {
        return $this->belongsto('NGAFID\TestAirport', 'airport_id', 'id');
    }

    public function runway()
    {
        return $this->belongsTo('NGAFID\TestRunway', 'runway_id', 'id');
    }
}
