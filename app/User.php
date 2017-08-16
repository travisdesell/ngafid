<?php
namespace NGAFID;

use Eloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Eloquent
    implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'firstname',
        'lastname',
        'email',
        'password',
        'org_id',
        'active',
        'confirmed',
        'access_level',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'password_salt'];

    /*************************************************************************
     * Model Eloquent Relationships
     */

    public function fleet()
    {
        return $this->belongsTo('NGAFID\Fleet', 'org_id', 'id');
    }

    /*************************************************************************
     * Public Methods
     */

    public function isFleetAdministrator()
    {
        return $this->id === $this->fleet->administrator;
    }
}
