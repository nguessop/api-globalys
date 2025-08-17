<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        // Protège uniquement ces méthodes : token requis
        $this->middleware('auth:api')->only(['logout', 'refresh']);
        // Optionnel : limiter le brute force sur /login (ex: 10 req/min)
        // $this->middleware('throttle:10,1')->only(['login']);
    }
    /**
     * @OA\Post(
     *   path="/api/auth/login",
     *   tags={"Auth"},
     *   summary="Connexion par email ou téléphone + mot de passe",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"login","password"},
     *       @OA\Property(property="login", type="string", example="admin@globalys.com"),
     *       @OA\Property(property="password", type="string", format="password", example="password")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Connexion réussie",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="expires_in", type="integer", example=3600),
     *         @OA\Property(
     *           property="user",
     *           type="object",
     *           description="Utilisateur connecté avec relation role chargée"
     *         )
     *       ),
     *       @OA\Property(property="message", type="string", example="Connexion réussie")
     *     )
     *   ),
     *   @OA\Response(response=400, description="Validation error / Identifiants invalides"),
     *   @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->error('Validation error', $validator->errors(), 400);
        }

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->error('Identifiants invalides', null, 400);
            }
        } catch (JWTException $e) {
            return response()->error('Impossible de créer le token', null, 500);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        return response()->success([
            'token'      => $token,
            'user'       => $user->load('role'), // relation "role" (singulier) cohérente avec ton modèle
            'expires_in' => auth('api')->factory()->getTTL() * 60, // en secondes
        ], 'Connexion réussie');
    }

    /**
     * @OA\Post(
     *   path="/api/auth/logout",
     *   tags={"Auth"},
     *   summary="Déconnexion (invalidation du token courant)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Déconnexion réussie",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object", nullable=true),
     *       @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout()
    {
        try {
            auth()->logout(); // invalide le token courant
        } catch (\Throwable $e) {
            // On ne bloque pas la sortie même si le token est invalide/expiré
        }

        return response()->success(null, 'Déconnexion réussie');
    }

    /**
     * @OA\Post(
     *   path="/api/auth/refresh",
     *   tags={"Auth"},
     *   summary="Rafraîchir le token JWT (en utilisant le Bearer token actuel ou un token passé en body)",
     *   description="Tymon/jwt-auth n'utilise pas de refresh_token séparé. On rafraîchit le JWT en cours.",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=false,
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="refresh_token",
     *         type="string",
     *         nullable=true,
     *         description="Optionnel si Bearer token est présent dans l’en-tête Authorization"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Token rafraîchi",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="expires_in", type="integer", example=3600),
     *         @OA\Property(
     *           property="user",
     *           type="object",
     *           description="Utilisateur associé au nouveau token (avec role)"
     *         )
     *       ),
     *       @OA\Property(property="message", type="string", example="Token rafraîchi avec succès")
     *     )
     *   ),
     *   @OA\Response(response=400, description="Token manquant"),
     *   @OA\Response(response=401, description="Token invalide ou expiré"),
     *   @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function refresh(Request $request)
    {
        try {
            // 1) Récupère le token depuis Authorization: Bearer xxx OU body.refresh_token
            $oldToken = $request->bearerToken() ?? $request->input('refresh_token');
            if (!$oldToken) {
                return response()->error('Token manquant pour le rafraîchissement.', null, 400);
            }

            // 2) Positionne le token courant
            JWTAuth::setToken($oldToken);

            // 3) Authentifie l’utilisateur à partir de l’ancien token (s’il est encore valide)
            $user = null;
            try {
                $user = JWTAuth::authenticate();
            } catch (\Throwable $e) {
                // authenticate peut échouer si le token est expiré, mais refresh peut encore réussir
            }

            // 4) Rafraîchit le token (nouveau JWT)
            $newToken = JWTAuth::refresh();

            // 5) Si on n’avait pas réussi à récupérer l’utilisateur, on le récupère maintenant avec le nouveau token
            if (!$user) {
                JWTAuth::setToken($newToken);
                $user = JWTAuth::authenticate();
            }

            return response()->success([
                'token'      => $newToken,
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user'       => $user ? $user->load('role') : null,
            ], 'Token rafraîchi avec succès');

        } catch (JWTException $e) {
            return response()->error('Erreur JWT lors du rafraîchissement.', null, 500);
        } catch (\Exception $e) {
            return response()->error('Une erreur est survenue lors du rafraîchissement du token.', null, 500);
        }
    }


    /**
     * @OA\Post(
     *   path="/api/auth/forgot-password",
     *   tags={"Auth"},
     *   summary="Demander un lien de réinitialisation de mot de passe (mock/demo)",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email"},
     *       @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Lien envoyé"),
     *   @OA\Response(response=404, description="Email non trouvé"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->error('Email non trouvé', null, 404);
        }

        // Ici tu branches soit Laravel Password Broker, soit ton propre workflow d'envoi mail/SMS.
        return response()->success(null, 'Lien de réinitialisation envoyé');
    }
}
