<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\ServiceOffering;
use App\Models\AvailabilitySlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->only([
            'store',
            'update',
            'destroy',
            'uploadAvatar',
            'changePassword',
            'assignSubscription',
            'revokeSubscription',
            'me',
            'index'
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/users/{user}/profile-views",
     *   tags={"Users"},
     *   summary="Nombre de vues du profil d’un utilisateur",
     *   description="Retourne le nombre de fois qu’un profil a été vu.",
     *   @OA\Parameter(
     *     name="user",
     *     in="path",
     *     required=true,
     *     description="ID du prestataire ou particulier",
     *     @OA\Schema(type="integer", example=42)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Nombre de vues du profil"),
     *       @OA\Property(property="views", type="integer", example=120)
     *     )
     *   ),
     *   @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function getProfileViews($userId)
    {
        $user = User::findOrFail($userId);

        return response()->success([
            'views' => $user->profile_views
        ], 'Nombre de vues du profil');
    }

    /**
     * @OA\Post(
     *   path="/api/users/{user}/increment-profile-views",
     *   tags={"Users"},
     *   summary="Incrémenter les vues du profil",
     *   description="Incrémente le compteur de vues lorsqu’un profil est visité.",
     *   @OA\Parameter(
     *     name="user",
     *     in="path",
     *     required=true,
     *     description="ID du prestataire ou particulier",
     *     @OA\Schema(type="integer", example=42)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Vue enregistrée"),
     *       @OA\Property(property="views", type="integer", example=121)
     *     )
     *   )
     * )
     */
    public function incrementProfileViews($userId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $user->increment('profile_views');

        return response()->success([
            'views' => $user->profile_views
        ], 'Vue enregistrée');
    }


    /**
     * @OA\Get(
     *   path="/api/users",
     *   tags={"Users"},
     *   summary="Lister les utilisateurs",
     *   description="Filtres, tri, includes, pagination ou liste complète.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="role", in="query", description="admin|client|prestataire|entreprise", @OA\Schema(type="string")),
     *   @OA\Parameter(name="account_type", in="query", description="entreprise|particulier", @OA\Schema(type="string", enum={"entreprise","particulier"})),
     *   @OA\Parameter(name="user_type", in="query", description="client|prestataire", @OA\Schema(type="string", enum={"client","prestataire"})),
     *   @OA\Parameter(name="country", in="query", description="Code pays (CM, FR...)", @OA\Schema(type="string", maxLength=2)),
     *   @OA\Parameter(name="city", in="query", description="Ville (company_city ou personal_address)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="has_active_subscription", in="query", description="1=avec abonnement actif, 0=sans", @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="q", in="query", description="Recherche (first_name,last_name,email)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="include", in="query", description="Relations CSV (ex: role,currentSubscription,subscriptions,bookings,receivedBookings,serviceOfferings,reviews)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", description="CSV ex: first_name,-created_at", @OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page", in="query", description="Taille de page ex: 15 ou 'all'", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $includes = $this->parseIncludes($request);
        $query = User::query()->with($includes);

        if ($request->filled('role')) {
            $role = $request->get('role');
            $query->whereHas('role', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->get('account_type'));
        }
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->get('user_type'));
        }
        if ($request->filled('country')) {
            $query->where('country', $request->get('country'));
        }
        if ($request->filled('city')) {
            $city = $request->get('city');
            $query->where(function ($q) use ($city) {
                $q->where('company_city', $city)
                    ->orWhere('personal_address', 'like', "%{$city}%");
            });
        }

        if ($request->filled('q')) {
            $kw = trim($request->get('q'));
            $query->where(function ($sub) use ($kw) {
                $sub->where('first_name', 'like', "%{$kw}%")
                    ->orWhere('last_name', 'like', "%{$kw}%")
                    ->orWhere('email', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('has_active_subscription')) {
            $flag = (int) $request->get('has_active_subscription') === 1;
            if ($flag) {
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('status', Subscription::STATUS_ACTIVE);
                });
            } else {
                $query->whereDoesntHave('subscriptions', function ($q) {
                    $q->where('status', Subscription::STATUS_ACTIVE);
                });
            }
        }

        if ($request->filled('sort')) {
            foreach (explode(',', $request->get('sort')) as $s) {
                $direction = 'asc';
                $column = $s;
                if (substr($s, 0, 1) === '-') {
                    $direction = 'desc';
                    $column = substr($s, 1);
                }
                if (in_array($column, ['first_name','last_name','email','created_at','country'], true)) {
                    $query->orderBy($column, $direction);
                }
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->get('per_page') === 'all') {
            $users = $query->get();
        } else {
            $perPage = max(1, (int)($request->get('per_page', 15)));
            $users = $query->paginate($perPage);
        }

        return response()->success($users, 'Liste des utilisateurs récupérée');
    }

    /**
     * @OA\Get(
     *   path="/api/users/{user}",
     *   tags={"Users"},
     *   summary="Afficher un utilisateur",
     *   description="{user} peut être un ID ou un email (binding personnalisé).",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user", in="path", required=true, description="ID ou email", @OA\Schema(type="string")),
     *   @OA\Parameter(name="include", in="query", description="Relations CSV", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Request $request, User $user)
    {
        $includes = $this->parseIncludes($request);
        if (!empty($includes)) {
            $user->load($includes);
        }

        return response()->success($user, 'Détails complets de l’utilisateur récupérés');
    }

    /**
     * @OA\Post(
     *   path="/api/users",
     *   tags={"Users"},
     *   summary="Créer un utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=200, description="Créé"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'        => ['required','string','max:255'],
            'last_name'         => ['required','string','max:255'],
            'email'             => ['required','email','max:255','unique:users,email'],
            'password'          => ['required','string','min:6'],
            'phone'             => ['nullable','string','max:50'],
            'preferred_language'=> ['nullable','string','max:50'],
            'country'           => ['nullable','string','max:2'],
            'account_type'      => ['required', Rule::in(['entreprise','particulier'])],
            'role_id'           => ['required','exists:roles,id'],
            'gender'            => ['nullable', Rule::in(['Homme','Femme','Autre'])],
            'birthdate'         => ['nullable','date'],
            'job'               => ['nullable','string','max:255'],
            'personal_address'  => ['nullable','string','max:255'],
            'user_type'         => ['nullable', Rule::in(['client','prestataire'])],
            'company_name'      => ['nullable','string','max:255'],
            'sector'            => ['nullable','string','max:255'],
            'tax_number'        => ['nullable','string','max:255'],
            'website'           => ['nullable','string','max:255'],
            'company_logo'      => ['nullable','string','max:255'],
            'company_description'=> ['nullable','string'],
            'company_address'   => ['nullable','string','max:255'],
            'company_city'      => ['nullable','string','max:255'],
            'company_size'      => ['nullable','string','max:255'],
            'preferred_contact_method' => ['nullable','string','max:255'],
            'accepts_terms'     => ['boolean'],
            'wants_newsletter'  => ['boolean'],
        ]);

        $user = User::create($data);

        return response()->success($user, 'Utilisateur créé avec succès');
    }

    /**
     * @OA\Patch(
     *   path="/api/users/{user}",
     *   tags={"Users"},
     *   summary="Mettre à jour un utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     * @OA\Put(
     *   path="/api/users/{user}",
     *   tags={"Users"},
     *   summary="Mettre à jour un utilisateur (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name'        => ['sometimes','required','string','max:255'],
            'last_name'         => ['sometimes','required','string','max:255'],
            'email'             => ['sometimes','required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password'          => ['sometimes','nullable','string','min:6'],
            'phone'             => ['sometimes','nullable','string','max:50'],
            'preferred_language'=> ['sometimes','nullable','string','max:50'],
            'country'           => ['sometimes','nullable','string','max:2'],
            'account_type'      => ['sometimes', Rule::in(['entreprise','particulier'])],
            'role_id'           => ['sometimes','exists:roles,id'],
            'gender'            => ['sometimes','nullable', Rule::in(['Homme','Femme','Autre'])],
            'birthdate'         => ['sometimes','nullable','date'],
            'job'               => ['sometimes','nullable','string','max:255'],
            'personal_address'  => ['sometimes','nullable','string','max:255'],
            'user_type'         => ['sometimes','nullable', Rule::in(['client','prestataire'])],
            'company_name'      => ['sometimes','nullable','string','max:255'],
            'sector'            => ['sometimes','nullable','string','max:255'],
            'tax_number'        => ['sometimes','nullable','string','max:255'],
            'website'           => ['sometimes','nullable','string','max:255'],
            'company_logo'      => ['sometimes','nullable','string','max:255'],
            'company_description'=> ['sometimes','nullable','string'],
            'company_address'   => ['sometimes','nullable','string','max:255'],
            'company_city'      => ['sometimes','nullable','string','max:255'],
            'company_size'      => ['sometimes','nullable','string','max:255'],
            'preferred_contact_method' => ['sometimes','nullable','string','max:255'],
            'accepts_terms'     => ['sometimes','boolean'],
            'wants_newsletter'  => ['sometimes','boolean'],
        ]);

        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data)->save();

        return response()->success($user->fresh(), 'Utilisateur mis à jour avec succès');
    }

    /**
     * @OA\Delete(
     *   path="/api/users/{user}",
     *   tags={"Users"},
     *   summary="Supprimer un utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Supprimé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->success(null, 'Utilisateur supprimé');
    }

    /**
     * @OA\Get(
     *   path="/api/users/{user}/bookings",
     *   tags={"Users"},
     *   summary="Réservations d'un utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function bookings(Request $request, User $user)
    {
        $as = $request->get('as', 'client');
        $query = Booking::query()->with(['serviceOffering','client','provider']);

        if ($as === 'provider') {
            $query->where('provider_id', $user->id);
        } else {
            $query->where('client_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        $query->orderBy('start_at', 'desc');

        $items = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate(max(1, (int)$request->get('per_page', 15)));

        return response()->success($items, 'Réservations récupérées');
    }

    /**
     * @OA\Get(
     *   path="/api/users/{user}/service-offerings",
     *   tags={"Users"},
     *   summary="Services offerts par un prestataire/entreprise",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function serviceOfferings(Request $request, User $user)
    {
        $query = ServiceOffering::query()
            ->where('provider_id', $user->id)
            ->with(['subCategory']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('city')) {
            $query->where('city', $request->get('city'));
        }

        $items = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate(max(1, (int)$request->get('per_page', 15)));

        return response()->success($items, 'Services offerts récupérés');
    }

    /**
     * @OA\Get(
     *   path="/api/users/{user}/reviews",
     *   tags={"Users"},
     *   summary="Avis liés à un utilisateur",
     *   description="as=received (avis reçus) | as=given (avis donnés)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function reviews(Request $request, User $user)
    {
        $as = $request->get('as', 'received');
        $query = Review::query()->with(['booking','serviceOffering','author','provider']);

        if ($as === 'given') {
            $query->where('author_id', $user->id);
        } else {
            $query->where('provider_id', $user->id);
        }

        if ($request->filled('rating_min')) {
            $query->where('rating', '>=', (float)$request->get('rating_min'));
        }

        $query->orderBy('created_at','desc');

        $items = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate(max(1, (int)$request->get('per_page', 15)));

        return response()->success($items, 'Avis récupérés');
    }

    /**
     * @OA\Get(
     *   path="/api/users/{user}/availability",
     *   tags={"Users"},
     *   summary="Créneaux de disponibilité d'un prestataire",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function availability(Request $request, User $user)
    {
        $query = AvailabilitySlot::query()->where('provider_id', $user->id);

        if ($request->filled('from')) {
            $query->whereDate('start_at', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('end_at', '<=', $request->get('to'));
        }

        $query->orderBy('start_at');

        $items = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate(max(1, (int)$request->get('per_page', 50)));

        return response()->success($items, 'Créneaux de disponibilité récupérés');
    }

    /**
     * @OA\Post(
     *   path="/api/users/{user}/avatar",
     *   tags={"Users"},
     *   summary="Uploader/Mettre à jour l'avatar",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function uploadAvatar(Request $request, User $user)
    {
        $request->validate([
            'avatar' => ['required','image','max:2048'],
        ]);

        $path = $request->file('avatar')->store('avatars', 'public');

        if (!empty($user->profile_photo)) {
            if (Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
        }

        $user->profile_photo = $path;
        $user->save();

        return response()->success($user->fresh(), 'Avatar mis à jour');
    }

    /**
     * @OA\Post(
     *   path="/api/users/{user}/password",
     *   tags={"Users"},
     *   summary="Changer le mot de passe d'un utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function changePassword(Request $request, User $user)
    {
        $data = $request->validate([
            'current_password'        => ['required','string'],
            'new_password'            => ['required','string','min:6','confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->error('Mot de passe actuel incorrect', 422);
        }

        $user->password = $data['new_password']; // mutator hash
        $user->save();

        return response()->success(null, 'Mot de passe changé avec succès');
    }

    /**
     * @OA\Post(
     *   path="/api/users/{user}/subscription/assign",
     *   tags={"Users"},
     *   summary="Assigner/Créer un abonnement actif pour un utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function assignSubscription(Request $request, User $user)
    {
        $data = $request->validate([
            'name'             => ['required','string','max:255'],
            'price'            => ['nullable','numeric','min:0'],
            'frequency'        => ['nullable', Rule::in(['month','year'])],
            'commission_type'  => ['required', Rule::in(['percent','flat'])],
            'commission_rate'  => ['required','numeric','min:0'],
            'started_at'       => ['nullable','date'],
            'ends_at'          => ['nullable','date','after_or_equal:started_at'],
        ]);

        $sub = new Subscription();
        $sub->user_id          = $user->id;
        $sub->name             = $data['name'];
        $sub->price            = $data['price'] ?? null;
        $sub->frequency        = $data['frequency'] ?? 'month';
        $sub->commission_type  = $data['commission_type'];
        $sub->commission_rate  = $data['commission_rate'];
        $sub->status           = Subscription::STATUS_ACTIVE;
        $sub->started_at       = $data['started_at'] ?? now();
        $sub->ends_at          = $data['ends_at'] ?? null;
        $sub->save();

        $user->subscription_id = $sub->id;
        $user->save();

        return response()->success($sub, 'Abonnement assigné');
    }

    /**
     * @OA\Post(
     *   path="/api/users/{user}/subscription/revoke",
     *   tags={"Users"},
     *   summary="Révoquer l'abonnement courant d'un utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function revokeSubscription(User $user)
    {
        $current = $user->currentSubscription;
        if ($current) {
            $current->status = Subscription::STATUS_CANCELLED;
            $current->save();
        }

        $user->subscription_id = null;
        $user->save();

        return response()->success(null, 'Abonnement révoqué');
    }

    /**
     * @OA\Get(
     *   path="/api/users/me",
     *   tags={"Users"},
     *   summary="Profil de l'utilisateur connecté",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request)
    {
        $auth = auth()->user();
        if (!$auth) {
            return response()->error('Unauthenticated', 401);
        }

        $includes = $this->parseIncludes($request);
        if (!empty($includes)) {
            $auth->load($includes);
        }

        return response()->success($auth, 'Profil courant');
    }

    /* ============================================================
     |                       Helpers privés
     ============================================================ */

    private function parseIncludes(Request $request)
    {
        $allowed = [
            'role',
            'subscriptions',
            'currentSubscription',
            'bookings',
            'receivedBookings',
            'serviceOfferings',
            'reviewsGiven',
            'reviewsReceived',
            'availabilitySlots',
        ];

        $include = $request->get('include');
        if (!$include) {
            return ['role','currentSubscription'];
        }

        $parts = array_filter(array_map('trim', explode(',', $include)));
        return array_values(array_intersect($parts, $allowed));
    }

    /**
     * @OA\Get(
     *   path="/api/users/roles",
     *   tags={"Users"},
     *   summary="Lister les rôles disponibles",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function roles()
    {
        $roles = Role::select('id','name')->get();
        return response()->success($roles, 'Rôles récupérés');
    }

    /**
     * @OA\Get(
     *   path="/api/users/admins",
     *   tags={"Users"},
     *   summary="Lister les administrateurs",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function admins(Request $request)
    {
        $includes = $this->parseIncludes($request);

        $query = User::query()
            ->with($includes)
            ->whereHas('role', function ($q) {
                $q->where('name', 'admin');
            });

        if ($request->filled('country')) {
            $query->where('country', $request->get('country'));
        }
        if ($request->filled('city')) {
            $city = $request->get('city');
            $query->where(function ($q) use ($city) {
                $q->where('company_city', $city)
                    ->orWhere('personal_address', 'like', "%{$city}%");
            });
        }
        if ($request->filled('q')) {
            $kw = trim($request->get('q'));
            $query->where(function ($sub) use ($kw) {
                $sub->where('first_name', 'like', "%{$kw}%")
                    ->orWhere('last_name', 'like', "%{$kw}%")
                    ->orWhere('email', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('sort')) {
            foreach (explode(',', $request->get('sort')) as $s) {
                $direction = 'asc';
                $column = $s;
                if (substr($s, 0, 1) === '-') {
                    $direction = 'desc';
                    $column = substr($s, 1);
                }
                if (in_array($column, ['first_name','last_name','email','created_at','country'], true)) {
                    $query->orderBy($column, $direction);
                }
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $items = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate(max(1, (int) $request->get('per_page', 15)));

        return response()->success($items, 'Administrateurs récupérés');
    }

    /**
     * @OA\Get(
     *   path="/api/users/entreprises",
     *   tags={"Users"},
     *   summary="Lister les comptes de type entreprise",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function entreprises(Request $request)
    {
        $includes = $this->parseIncludes($request);

        $query = User::query()
            ->with($includes)
            // ✅ filtre par type de compte (pas par rôle)
            ->where('account_type', 'entreprise');

        if ($request->filled('country')) {
            $query->where('country', $request->get('country'));
        }
        if ($request->filled('city')) {
            $city = $request->get('city');
            $query->where(function ($q) use ($city) {
                $q->where('company_city', $city)
                    ->orWhere('personal_address', 'like', "%{$city}%");
            });
        }
        if ($request->filled('q')) {
            $kw = trim($request->get('q'));
            $query->where(function ($sub) use ($kw) {
                $sub->where('first_name', 'like', "%{$kw}%")
                    ->orWhere('last_name', 'like', "%{$kw}%")
                    ->orWhere('email', 'like', "%{$kw}%")
                    ->orWhere('company_name', 'like', "%{$kw}%");
            });
        }

        if ($request->filled('sort')) {
            foreach (explode(',', $request->get('sort')) as $s) {
                $direction = 'asc';
                $column = $s;
                if (substr($s, 0, 1) === '-') {
                    $direction = 'desc';
                    $column = substr($s, 1);
                }
                if (in_array($column, ['company_name','first_name','last_name','email','created_at','country'], true)) {
                    $query->orderBy($column, $direction);
                }
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $items = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate(max(1, (int) $request->get('per_page', 15)));

        return response()->success($items, 'Entreprises récupérées');
    }

    /**
     * @OA\Get(
     *   path="/api/users/prestataires",
     *   tags={"Users"},
     *   summary="Lister les prestataires",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function prestataires(Request $request)
    {
        $includes = $this->parseIncludes($request);

        $query = User::query()
            ->with($includes)
            ->whereHas('role', function ($q) {
                $q->where('name', 'prestataire');
            });

        // Filtres simples
        if ($request->filled('country')) {
            $query->where('country', $request->get('country'));
        }
        if ($request->filled('city')) {
            $city = $request->get('city');
            $query->where(function ($q) use ($city) {
                $q->where('company_city', $city)
                    ->orWhere('personal_address', 'like', "%{$city}%");
            });
        }
        if ($request->filled('q')) {
            $kw = trim($request->get('q'));
            $query->where(function ($sub) use ($kw) {
                $sub->where('first_name', 'like', "%{$kw}%")
                    ->orWhere('last_name', 'like', "%{$kw}%")
                    ->orWhere('email', 'like', "%{$kw}%")
                    ->orWhere('company_name', 'like', "%{$kw}%");
            });
        }

        // Filtres par offre de service
        if ($request->filled('sub_category_id')) {
            $scId = (int) $request->get('sub_category_id');
            $query->whereHas('serviceOfferings', function ($q) use ($scId) {
                $q->where('sub_category_id', $scId);
            })->withCount(['serviceOfferings as services_count' => function ($q) use ($scId) {
                $q->where('sub_category_id', $scId);
            }]);
        } elseif ($request->filled('category_id')) {
            $catId = (int) $request->get('category_id');
            $query->whereHas('serviceOfferings.subCategory', function ($q) use ($catId) {
                $q->where('category_id', $catId);
            })->withCount(['serviceOfferings as services_count' => function ($q) use ($catId) {
                $q->whereHas('subCategory', function ($qq) use ($catId) {
                    $qq->where('category_id', $catId);
                });
            }]);
        }

        // Tri
        if ($request->filled('sort')) {
            foreach (explode(',', $request->get('sort')) as $s) {
                $direction = 'asc';
                $column = $s;
                if (substr($s, 0, 1) === '-') {
                    $direction = 'desc';
                    $column = substr($s, 1);
                }
                if (in_array($column, ['first_name','last_name','email','created_at','country'], true)) {
                    $query->orderBy($column, $direction);
                }
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $items = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate(max(1, (int) $request->get('per_page', 15)));

        return response()->success($items, 'Prestataires récupérés');
    }
    /**
     * @OA\Get(
     *   path="/api/users/{user}/meetings",
     *   tags={"Users","Meetings"},
     *   summary="Meetings liés à un utilisateur (provider ou client)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="as", in="query", description="provider|client|any", @OA\Schema(type="string", enum={"provider","client","any"})),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="from", in="query", description="Filtre sur le selectedSlot.start_at ≥ from (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="to", in="query", description="Filtre sur le selectedSlot.start_at ≤ to (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="include", in="query", description="CSV: selectedSlot,slots,notes,contract", @OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function meetings(Request $request, User $user)
    {
        // includes sécurisés
        $allowed = ['selectedSlot','slots','notes','contract'];
        $include = $request->get('include');
        $with = ['selectedSlot']; // par défaut on renvoie le créneau choisi
        if ($include) {
            $parts = array_filter(array_map('trim', explode(',', $include)));
            $with = array_values(array_unique(array_merge($with, array_intersect($parts, $allowed))));
        }

        $q = Meeting::query()->with($with);

        // scope par rôle
        $as = $request->get('as', 'any');
        if ($as === 'provider') {
            $q->where('provider_id', $user->id);
        } elseif ($as === 'client') {
            $q->where('client_id', $user->id);
        } else {
            $q->where(function ($w) use ($user) {
                $w->where('provider_id', $user->id)->orWhere('client_id', $user->id);
            });
        }

        // filtres
        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }

        // fenêtre de dates sur le selectedSlot.start_at si présent
        $from = $request->get('from');
        $to   = $request->get('to');
        if ($from || $to) {
            $q->whereHas('selectedSlot', function ($w) use ($from, $to) {
                if ($from) $w->whereDate('start_at', '>=', $from);
                if ($to)   $w->whereDate('start_at', '<=', $to);
            });
            // tri naturel par horaire si on filtre par dates
            $q->orderBy(
                MeetingSlot::query()
                    ->select('start_at')
                    ->whereColumn('meeting_slots.id', 'meetings.selected_slot_id')
                    ->limit(1)
            );
        } else {
            $q->orderBy('created_at', 'desc');
        }

        $items = $request->get('per_page') === 'all'
            ? $q->get()
            : $q->paginate(max(1, (int)$request->get('per_page', 15)));

        return response()->success($items, 'Meetings récupérés');
    }

    /**
     * @OA\Get(
     *   path="/api/users/{user}/contracts",
     *   tags={"Users","Contracts"},
     *   summary="Contrats liés à un utilisateur (provider ou client)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="as", in="query", description="provider|client|any", @OA\Schema(type="string", enum={"provider","client","any"})),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string",
     *       enum={"draft","sent","partially_signed","signed","cancelled","expired"})),
     *   @OA\Parameter(name="include", in="query", description="CSV: template,parties,signatures,events,meeting", @OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function contracts(Request $request, User $user)
    {
        // includes sécurisés
        $allowed = ['template','parties','signatures','events','meeting'];
        $include = $request->get('include');
        $with = ['template']; // défaut utile
        if ($include) {
            $parts = array_filter(array_map('trim', explode(',', $include)));
            $with = array_values(array_unique(array_merge($with, array_intersect($parts, $allowed))));
        }

        $q = Contract::query()->with($with);

        // scope par rôle
        $as = $request->get('as', 'any');
        if ($as === 'provider') {
            $q->where('provider_id', $user->id);
        } elseif ($as === 'client') {
            $q->where('client_id', $user->id);
        } else {
            $q->where(function ($w) use ($user) {
                $w->where('provider_id', $user->id)->orWhere('client_id', $user->id);
            });
        }

        // filtres
        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }

        // tri
        $q->orderBy('created_at', 'desc');

        $items = $request->get('per_page') === 'all'
            ? $q->get()
            : $q->paginate(max(1, (int)$request->get('per_page', 15)));

        return response()->success($items, 'Contrats récupérés');
    }

}
