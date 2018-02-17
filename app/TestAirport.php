<?php
namespace NGAFID;

use Eloquent;

class TestAirport extends Eloquent
{
    protected $table = 'test_airports';

    public $timestamps = false;

    public function runways()
    {
        return $this->hasMany('NGAFID\TestRunway', 'airport_id', 'id');
    }
}
