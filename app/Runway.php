<?php
namespace NGAFID;

use Eloquent;

class Runway extends Eloquent
{
    use Traits\HasCompositePrimaryKey;

    protected $primaryKey = ['id', 'airport_id'];

    public $timestamps = false;

    public function airport()
    {
        return $this->belongsTo('NGAFID\Airport', 'airport_id', 'id');
    }
}
