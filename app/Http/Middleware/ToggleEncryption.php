<?php
namespace NGAFID\Http\Middleware;

use Auth;
use Closure;
use DB;
use NGAFID\CryptoSystem;
use URL;

class ToggleEncryption
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $shouldEncrypt = Auth::user()->fleet->wantsDataEncrypted();
            $secretKey = $request->input('secretKey');
            $toggle = 'T';

            $session = $request->session();
            if ($request->input('toggle') === 'T') {
                $session->forget('encrSK');
                $session->forget('toggleEnc');
                $session->forget('toggle');
                $session->save();

                flash()->success(
                    'Encryption On: You are now viewing your encrypted flight data.'
                );

                return redirect(URL::previous());
            }

            if ($request->input('toggle') === 'F'
                || $session->get('toggleEnc') === 'F') {
                $toggle = 'F';
            }

            if (strpos($request->path(), 'decrypt') !== false
                && $shouldEncrypt) {
                if ( !str_contains(URL::previous(), 'decrypt')) {
                    $session->put('prevURL', URL::previous());
                    $session->save();
                }

                if ($toggle === 'F' && count($secretKey) > 0) {
                    $salt = getenv('STATIC_SALT');
                    $hashedKey = md5($salt . $secretKey);
                    $userKey = CryptoSystem::where(
                        'fleet_id',
                        '=',
                        Auth::user()->org_id
                    )->pluck(
                        DB::raw("AES_DECRYPT(user_key, '{$hashedKey}')")
                    );

                    if (strpos($userKey, '-----BEGIN RSA PRIVATE KEY-----')
                        !== false) {
                        $session->put(
                            'encrSK',
                            gzcompress(base64_encode($userKey), 9)
                        );
                        $session->put('toggleEnc', 'F');
                        $session->save();

                        flash()->success(
                            'Encryption Off: You are now viewing your decrypted flight data.'
                        );

                        return redirect($session->get('prevURL'));
                    } else {
                        flash()->error('Incorrect secret key.');
                    }
                } else {
                    $session->put('toggleEnc', 'T');
                }

                $session->save();
            } elseif ($request->is('flights/*/edit')) {
                // Check if data is decrypted before editing
                if ($shouldEncrypt && $toggle !== 'F'
                    && count($secretKey) <= 0) {
                    $session->setPreviousUrl($request->url());

                    flash()->info(
                        'Please disable the encryption before editing your flight data.'
                    );

                    return redirect('decrypt?toggle=F');
                }
            }
        }

        return $next($request);
    }
}
