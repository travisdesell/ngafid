<?php
namespace NGAFID;

use Eloquent;

class TestRunway extends Eloquent
{
    protected $table = 'test_runways';

    public $timestamps = false;

    public function airport()
    {
        return $this->belongsTo('NGAFID\TestAirport', 'airport_id', 'id');
    }

    public function approaches()
    {
        return $this->hasMany('NGAFID\Approach', 'runway_id', 'id');
    }
}
