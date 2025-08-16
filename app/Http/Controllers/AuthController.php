<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->error('Validation error', $validator->errors(), 400);
        }

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password
        ];

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->error('Identifiants invalides', null, 400);
            }
        } catch (JWTException $e) {
            return response()->error('Impossible de créer le token', null, 500);
        }

        $user = auth()->user();
        return response()->success([
            'token' => $token,
            'user' => $user->load('role'), // pour inclure les infos du rôle
            //'role' => $user->role ? $user->role->name : null,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], 'Connexion réussie');
    }

    public function logout()
    {
        auth()->logout();
        return response()->success(null, 'Déconnexion réussie');
    }

    public function refresh(Request $request)
    {
        try {
            $oldToken = $request->bearerToken() ?? $request->input('refresh_token');

            if (!$oldToken) {
                return response()->error('Token manquant pour le rafraîchissement.', null, 400);
            }

            JWTAuth::setToken($oldToken);

            if (!JWTAuth::check()) {
                return response()->error('Token invalide ou expiré.', null, 401);
            }

            $user = JWTAuth::authenticate();
            $newToken = JWTAuth::refresh();

            return response()->success([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('libelle'),
                ]
            ], 'Token rafraîchi avec succès');

        } catch (JWTException $e) {
            return response()->error('Erreur JWT lors du rafraîchissement.', null, 500);
        } catch (\Exception $e) {
            return response()->error('Une erreur est survenue lors du rafraîchissement du token.', null, 500);
        }
    }

    public function profile()
    {
        $user = auth()->user();
        return response()->success([
            'user' => $user->load('roles'),
            'roles' => $user->role->pluck('name')
        ], 'Profil utilisateur');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->error('Email non trouvé', null, 404);
        }

        return response()->success(null, 'Lien de réinitialisation envoyé');
    }
}
