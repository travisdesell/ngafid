<?php
namespace NGAFID\Http\Controllers\Profile;


use NGAFID\Http\Controllers\Controller;
use Request;
use NGAFID\Http\Requests\ProfileRequest;
use NGAFID\Http\Requests\ChangePasswordRequest;
use NGAFID\User;
use NGAFID\Fleet;



class ProfileController extends Controller {

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */

	/*public function __construct()
	{
		$this->middleware('auth');
	}*/

	/**
	 * Show the application dashboard to the user.
	 *
	 * @return Response
	 */
	public function index()
	{

        $user = new User();
        $fleetInfo = $user->find(\Auth::user()->id)->fleet;

        $userData = array(
            'firstname'     => \Auth::user()->firstname,
            'lastname'      => \Auth::user()->lastname,
            'type'          => (\Auth::user()->user_type == 'G') ? 'GAARD' : 'NGAFID',
            'username'      => \Auth::user()->email,
            'fleetInfo'     => $fleetInfo,
        );
        return view('profile.profile')->with('data', $userData);

	}

    /*public function create()
    {

    }

    public function store()
    {

    }*/

    public function show()
    {
        return redirect('profile');
    }

    public function edit($fleetID)
    {

    }

    public function update($id, ProfileRequest $profileRequest)
    {
        $fleet  = new Fleet();
        $user   = new User();


        $formfields = $profileRequest->all(); //Request::all();

        $userData = array(
            'firstname' => $formfields['firstname'],
            'lastname'  => $formfields['lastname']
        );

        $fleetData = array(
            'address'   =>  $formfields['address'],
            'city'      =>  $formfields['city'],
            'country'   =>  $formfields['country'],
            'state'     =>  $formfields['state'],
            'zip_code'  =>  $formfields['zip_code'],
            'phone'     =>  $formfields['phone'],
            'fax'       =>  $formfields['fax'],
        );

        $fleet->find($id)->update($fleetData);
        if($user->find(\Auth::user()->id)->update($userData))
        {
            flash()->success('Your profile has been successfully updated!');
        }

        return redirect('profile');
    }

    public function password()
    {
        return view('profile.password');
    }

    public function changePassword(ChangePasswordRequest $passwordRequest)
    {
        $user   = new User();
        $password = $passwordRequest->all();
        $password = array('password' => \Hash::make($password['password']));

        if($user->find(\Auth::user()->id)->update($password))
        {
            flash()->success('Your password has been successfully changed!');
        }

        return redirect('profile');
    }

    public function confirm($token)
    {
        $user = User::where('confirmation_token', $token)->first();
        if($user){
            $user->confirmation_token = '';
            $user->confirmed = 'yes';
            $user->active = 'Y';
            $user->save();

            flash()->success('Your account has been successfully activated! Please change your password.');
            \Auth::loginUsingId($user->id);
            return redirect('profile/password');
        }
        else{
            flash()->success('There was a problem activating your account.');
            return redirect('auth/register');
        }
    }

}
