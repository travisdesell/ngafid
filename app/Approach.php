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

    // protected $appends = [
    //     'isHeadingUnstable',
    //     'isCrosstrackUnstable',
    //     'isIasUnstable',
    //     'isVsiUnstable',
    // ];

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

    public function isHeadingUnstable()
    {
        return $this->unstable && $this->f1_heading !== null;
    }

    public function isCrosstrackUnstable()
    {
        return $this->unstable && $this->f2_crosstrack !== null;
    }

    public function isIasUnstable()
    {
        return $this->unstable && $this->a_ias !== null;
    }

    public function isVsiUnstable()
    {
        return $this->unstable && $this->s_vsi !== null;
    }
}
