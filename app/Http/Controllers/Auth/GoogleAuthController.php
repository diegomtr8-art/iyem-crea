<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'No se pudo completar el inicio de sesión con Google. Inténtalo de nuevo.']);
        }

        // Buscar por google_id primero, luego por email
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Vincular cuenta Google si aún no está vinculada
            if (! $user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                ]);
            }
        } else {
            // Nuevo usuario: se crea siempre como ciudadano
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'tipo'              => 'ciudadano',
                'email_verified_at' => now(),
                'password'          => bcrypt(Str::random(32)),
            ]);
        }

        Auth::login($user, remember: true);

        return $user->tipo === 'ciudadano'
            ? redirect()->intended(route('portal.dashboard'))
            : redirect()->intended(route('dashboard'));
    }
}
