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
        $user = auth()->user()->load('role');

        // Charger seulement les champs essentiels des services
        $services = $user->serviceOfferings()
            ->select('id', 'title', 'price_amount', 'currency', 'status')
            ->active()
            ->get();

        // Intégrer directement dans la réponse user
        $user->setRelation('services', collect([
            'count' => $services->count(),
            'list'  => $services,
        ]));

        return response()->success([
            'token'      => $token,
            'user'       => $user,
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

    /**
     * @OA\Post(
     *   path="/api/auth/register",
     *   tags={"Auth"},
     *   summary="Inscription (avec ou sans invitation)",
     *   description="Crée un utilisateur puis renvoie un JWT. Si un token d’invitation est fourni, il impose le rôle (prestataire/entreprise) et peut restreindre l’email.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"first_name","last_name","email","password"},
     *       @OA\Property(property="first_name", type="string", example="Alice"),
     *       @OA\Property(property="last_name", type="string", example="Dupont"),
     *       @OA\Property(property="email", type="string", format="email", example="alice@example.com"),
     *       @OA\Property(property="password", type="string", format="password", minLength=6, example="secret123"),
     *       @OA\Property(property="phone", type="string", nullable=true, example="+237670000000"),
     *       @OA\Property(property="account_type", type="string", enum={"entreprise","particulier"}, nullable=true, example="particulier"),
     *       @OA\Property(property="user_type", type="string", enum={"client","prestataire"}, nullable=true, example="client"),
     *       @OA\Property(property="invite", type="string", format="uuid", nullable=true, example="e7dfc0c3-5b7b-4d7e-9e3e-0d8b8a0d2c8e", description="Token d’invitation"),
     *       @OA\Property(property="company_name", type="string", nullable=true),
     *       @OA\Property(property="company_city", type="string", nullable=true),
     *       @OA\Property(property="personal_address", type="string", nullable=true)
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Inscription réussie",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="expires_in", type="integer", example=3600),
     *         @OA\Property(property="user", type="object", description="Utilisateur créé avec son rôle")
     *       ),
     *       @OA\Property(property="message", type="string", example="Inscription réussie")
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error / email déjà pris / token invalide"),
     *   @OA\Response(response=409, description="Invitation non valide (révoquée, expirée, email ne correspond pas)")
     * )
     */
    public function register(Request $request)
    {
        // 1) Validation de base
        $data = $request->validate([
            'first_name'    => ['required','string','max:255'],
            'last_name'     => ['required','string','max:255'],
            'email'         => ['required','email','max:255','unique:users,email'],
            'password'      => ['required','string','min:6'],
            'phone'         => ['nullable','string','max:50'],

            // Informations optionnelles côté business
            'account_type'  => ['nullable','in:entreprise,particulier'],
            'user_type'     => ['nullable','in:client,prestataire'],

            // Token d’invitation éventuel
            'invite'        => ['nullable','uuid'],

            // Champs entreprise / perso optionnels
            'company_name'  => ['nullable','string','max:255'],
            'company_city'  => ['nullable','string','max:255'],
            'personal_address' => ['nullable','string','max:255'],
        ]);

        // Valeurs par défaut si non fournis
        $accountType = $data['account_type'] ?? 'particulier';
        $userType    = $data['user_type'] ?? 'client';

        // 2) Si une invitation est fournie, on verrouille le rôle
        if (!empty($data['invite'])) {
            $invite = RoleInvite::where('token', $data['invite'])->first();

            if (!$invite || !$invite->isValid()) {
                return response()->error('Invitation non valide.', null, 409);
            }

            if ($invite->email && strcasecmp($invite->email, $data['email']) !== 0) {
                return response()->error('Cette invitation est réservée à un autre email.', null, 409);
            }

            // Impose le rôle via l’invitation
            if ($invite->role === 'entreprise') {
                $accountType = 'entreprise';
                // côté business : on peut laisser user_type à null ou le garder tel quel
            } elseif ($invite->role === 'prestataire') {
                $accountType = $accountType === 'entreprise' ? 'entreprise' : 'particulier';
                $userType = 'prestataire';
            }
        }

        // 3) Détermination du role_id depuis la table roles
        $roleName = $accountType === 'entreprise'
            ? 'entreprise'
            : ($userType === 'prestataire' ? 'prestataire' : 'client');

        $roleId = Role::where('name', $roleName)->value('id');
        if (!$roleId) {
            // garde-fou : si la table roles n’a pas la valeur attendue
            return response()->error("Le rôle '{$roleName}' est introuvable. Veuillez initialiser la table roles.", null, 422);
        }

        // 4) Création de l’utilisateur
        /** @var \App\Models\User $user */
        $user = User::create([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'email'        => $data['email'],
            'password'     => $data['password'], // hash automatique via mutator setPasswordAttribute
            'phone'        => $data['phone'] ?? null,

            'account_type' => $accountType,       // 'entreprise' | 'particulier'
            'user_type'    => $userType,          // 'client' | 'prestataire' | null
            'role_id'      => $roleId,

            'company_name'     => $data['company_name'] ?? null,
            'company_city'     => $data['company_city'] ?? null,
            'personal_address' => $data['personal_address'] ?? null,
        ]);

        // 5) Marquer l’utilisation de l’invitation (si présente)
        if (!empty($data['invite']) && isset($invite)) {
            $invite->used_count = (int)$invite->used_count + 1;
            $invite->save();
        }

        // 6) Générer un token JWT et renvoyer la réponse
        $token = JWTAuth::fromUser($user);

        return response()->success([
            'token'      => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user'       => $user->load('role'),
        ], 'Inscription réussie');
    }

}
