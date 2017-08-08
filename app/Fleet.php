<?php namespace NGAFID;

use Illuminate\Database\Eloquent\Model;


class Fleet extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'phone',
        'fax',
    ];

    /*************************************************************************-
     * Model Scopes
     */

    public function scopeDuration($query, $fleetID) {
        return $query->where('fleet_id', '=', $fleetID);
    }

    /*************************************************************************
     * Model Eloquent Relationships
     */

    public function users() {
        return $this->hasMany('NGAFID\User', 'org_id', 'id');
    }

    public function flights() {
        return $this->hasMany('NGAFID\FlightID');
    }

    /*************************************************************************
     * Public functions
     */

    public function wantsDataEncrypted() {
        return $this->encrypt_data === 'Y';
    }

    public function isUND() {
        return $this->id === 1 || $this->id === 3;
    }
}
