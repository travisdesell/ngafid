<?php
namespace NGAFID;

use Eloquent;

class TestRunway extends Eloquent
{
    use Traits\HasCompositePrimaryKey;

    protected $table = 'test_runways';

    protected $primaryKey = ['id', 'airport_id'];

    public $timestamps = false;

    public function airport()
    {
        return $this->belongsTo('NGAFID\TestAirport', 'airport_id', 'id');
    }
}
