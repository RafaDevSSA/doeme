<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redireciona o usuário para o provedor de autenticação
     */
    public function redirectToProvider($provider)
    {
        $validProviders = ['google', 'facebook'];
        
        if (!in_array($provider, $validProviders)) {
            return response()->json([
                'error' => 'Provedor de autenticação inválido'
            ], 400);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtém as informações do usuário do provedor e faz login/registro
     */
    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Verifica se o usuário já existe pelo email
            $user = User::where('email', $socialUser->getEmail())->first();
            
            if ($user) {
                // Usuário já existe, atualiza informações do provedor social
                $user->update([
                    $provider . '_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);
            } else {
                // Cria novo usuário
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(24)), // Senha aleatória
                    $provider . '_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
            }

            // Faz login do usuário
            Auth::login($user);

            // Cria token de acesso pessoal
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login realizado com sucesso',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro na autenticação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout do usuário
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }

    /**
     * Obtém informações do usuário autenticado
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}

