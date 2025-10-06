<?php

namespace App\Http\Controllers;

use App\Models\ServiceOffering;
use App\Models\AvailabilitySlot;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ServiceOfferingController extends Controller
{
    public function __construct()
    {
        // Protège la création / modif / suppression
        $this->middleware('auth:api')->except(['index', 'show', 'availability', 'incrementViews', 'countByUser', 'countOnline']);
    }

    /**
     * @OA\Get(
     *   path="/api/service-offerings/users/{user}/count",
     *   tags={"ServiceOfferings"},
     *   summary="Nombre de services d’un utilisateur",
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="count", type="integer", example=5)
     *     )
     *   )
     * )
     */
    public function countByUser($userId)
    {
        $count = ServiceOffering::where('provider_id', $userId)->count();

        return response()->success(['count' => $count], 'Nombre de services');
    }

    /**
     * @OA\Get(
     *   path="/api/service-offerings/users/{user}/count/online",
     *   tags={"ServiceOfferings"},
     *   summary="Nombre de services en ligne d’un utilisateur",
     *   description="Retourne le nombre de services actifs (status = active) pour un utilisateur donné.",
     *   @OA\Parameter(
     *     name="user",
     *     in="path",
     *     required=true,
     *     description="ID de l'utilisateur",
     *     @OA\Schema(type="integer", example=42)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Nombre de services en ligne"),
     *       @OA\Property(property="count", type="integer", example=5)
     *     )
     *   ),
     *   @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function countOnline($userId)
    {
        $count = ServiceOffering::where('provider_id', $userId)
            ->where('status', 'active')
            ->count();

        return response()->success(['count' => $count], 'Nombre de services en ligne');
    }





    /**
     * @OA\Get(
     *   path="/api/service-offerings",
     *   tags={"ServiceOfferings"},
     *   summary="Lister les services",
     *   description="Recherche, filtres, tri et pagination.",
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","active","paused","archived"})),
     *   @OA\Parameter(name="sub_category_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="provider_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="city", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="country", in="query", @OA\Schema(type="string", maxLength=2)),
     *   @OA\Parameter(name="q", in="query", description="Recherche par titre/description", @OA\Schema(type="string")),
     *   @OA\Parameter(name="price_min", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="price_max", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="rating_min", in="query", @OA\Schema(type="number", format="float", minimum=0, maximum=5)),
     *   @OA\Parameter(name="sort", in="query", description="published_at|price_amount|avg_rating|bookings_count|views_count|title|created_at", @OA\Schema(type="string")),
     *   @OA\Parameter(name="dir", in="query", description="asc|desc", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=100)),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="OK"),
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=120),
     *         @OA\Property(property="last_page", type="integer", example=8),
     *         @OA\Property(property="data", type="array",
     *           @OA\Items(type="object",
     *             example={"id":10,"title":"Nettoyage de bureaux","price_amount":25000,"currency":"XAF","status":"active","avg_rating":4.6}
     *           )
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $req)
    {
        $q = ServiceOffering::query()
            ->with([
                'provider:id,first_name,last_name,company_name,user_type,account_type',
                'subCategory:id,name,category_id',
                // 👉 On charge aussi les disponibilités actives du service
                'availabilitySlots' => fn($slot) => $slot
                    ->where('status', 'available')
                    ->where('start_at', '>=', now())
                    ->orderBy('start_at', 'asc'),
            ]);

        // Filtres
        $q->when($req->filled('status'),
            fn($w) => $w->where('status', (string) $req->input('status')),
            fn($w) => $w->where('status', '!=', 'archived') // défaut : exclure archived
        );

        $q->when($req->filled('sub_category_id'),
            fn($w) => $w->where('sub_category_id', (int) $req->input('sub_category_id'))
        );

        $q->when($req->filled('provider_id'),
            fn($w) => $w->where('provider_id', (int) $req->input('provider_id'))
        );

        $q->when($req->filled('city'),
            fn($w) => $w->where('city', 'like', '%' . trim((string) $req->input('city')) . '%')
        );

        $q->when($req->filled('country'), function ($w) use ($req) {
            $country = strtoupper(trim((string) $req->input('country')));
            $w->where('country', $country);
        });

        // Recherche
        $q->when($req->filled('q'), function ($w) use ($req) {
            $term = trim((string) $req->input('q'));
            $w->where(function ($x) use ($term) {
                $x->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        });

        // Prix
        if ($req->filled('price_min') && is_numeric($req->input('price_min'))) {
            $q->where('price_amount', '>=', (float) $req->input('price_min'));
        }
        if ($req->filled('price_max') && is_numeric($req->input('price_max'))) {
            $q->where('price_amount', '<=', (float) $req->input('price_max'));
        }

        // Note min
        if ($req->filled('rating_min') && is_numeric($req->input('rating_min'))) {
            $q->where('avg_rating', '>=', (float) $req->input('rating_min'));
        }

        // Tri
        $sort = (string) $req->input('sort', 'published_at');
        $dir  = strtolower((string) $req->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['published_at','price_amount','avg_rating','bookings_count','views_count','title','created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'published_at';
        }
        $q->orderBy($sort, $dir);

        // Pagination
        $perPage = (int) $req->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $data = $q->paginate($perPage);

        return response()->success($data);
    }

    /**
     * @OA\Get(
     *   path="/api/service-offerings/{serviceOffering}",
     *   tags={"ServiceOfferings"},
     *   summary="Détail d'un service",
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="OK"),
     *       @OA\Property(property="data", type="object",
     *         example={"id":10,"title":"Nettoyage de bureaux","status":"active","price_amount":25000,"currency":"XAF"}
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(ServiceOffering $serviceOffering)
    {
        $serviceOffering->load([
            'provider',
            'subCategory',
            'bookings',
            'availabilitySlots' => function ($query) {
                $query->where('status', 'available')
                    ->where('start_at', '>=', now())
                    ->orderBy('start_at', 'asc');
            },
        ]);

        return response()->success($serviceOffering);
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings",
     *   tags={"ServiceOfferings"},
     *   summary="Créer un service",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"sub_category_id","title","price_amount"},
     *       @OA\Property(property="sub_category_id", type="integer", example=3),
     *       @OA\Property(property="provider_id", type="integer", nullable=true, example=42),
     *       @OA\Property(property="title", type="string", example="Nettoyage de bureaux"),
     *       @OA\Property(property="description", type="string", nullable=true),
     *       @OA\Property(property="price_amount", type="number", format="float", example=25000),
     *       @OA\Property(property="price_unit", type="string", nullable=true, enum={"hour","service","km","course","kg","jour"}),
     *       @OA\Property(property="currency", type="string", example="XAF"),
     *       @OA\Property(property="city", type="string", nullable=true, example="Douala"),
     *       @OA\Property(property="country", type="string", nullable=true, example="CM"),
     *       @OA\Property(property="address", type="string", nullable=true),
     *       @OA\Property(property="coverage_km", type="integer", nullable=true, example=10),
     *       @OA\Property(property="on_site", type="boolean", nullable=true),
     *       @OA\Property(property="at_provider", type="boolean", nullable=true),
     *       @OA\Property(property="lat", type="number", format="float", nullable=true),
     *       @OA\Property(property="lng", type="number", format="float", nullable=true),
     *       @OA\Property(property="status", type="string", nullable=true, enum={"draft","active","paused","archived"}),
     *       @OA\Property(property="attachments", type="array", @OA\Items(type="string"))
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Créé",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Service créé"),
     *       @OA\Property(property="data", type="object",
     *         example={"id":11,"title":"Nettoyage de bureaux","status":"draft"}
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $req)
    {
        $validated = $this->validatePayload($req);

        $user = $req->user();
        if (!$user) {
            return response()->error("Non autorisé", null, 401);
        }

        if (!isset($validated['provider_id'])) {
            $validated['provider_id'] = $user->id;
        }

        $validated['status']       = $validated['status'] ?? 'draft';
        $validated['published_at'] = $validated['status'] === 'active' ? now() : null;

        $offering = DB::transaction(fn () => ServiceOffering::create($validated));

        return response()->success($offering->fresh(), 'Service créé', 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/service-offerings/{serviceOffering}",
     *   tags={"ServiceOfferings"},
     *   summary="Mettre à jour un service",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="sub_category_id", type="integer", example=3),
     *       @OA\Property(property="provider_id", type="integer", example=42),
     *       @OA\Property(property="title", type="string", example="Titre modifié"),
     *       @OA\Property(property="description", type="string", nullable=true),
     *       @OA\Property(property="price_amount", type="number", format="float", example=30000),
     *       @OA\Property(property="status", type="string", enum={"draft","active","paused","archived"}),
     *       @OA\Property(property="featured", type="boolean", nullable=true),
     *       @OA\Property(property="attachments", type="array", @OA\Items(type="string"))
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     * @OA\Put(
     *   path="/api/service-offerings/{serviceOffering}",
     *   tags={"ServiceOfferings"},
     *   summary="Mettre à jour un service (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(Request $req, ServiceOffering $serviceOffering)
    {
        $validated = $this->validatePayload($req, updating: true);

        if (isset($validated['title']) || isset($validated['provider_id']) || isset($validated['sub_category_id'])) {
            $exists = ServiceOffering::query()
                ->where('id', '!=', $serviceOffering->id)
                ->where('provider_id', $validated['provider_id'] ?? $serviceOffering->provider_id)
                ->where('sub_category_id', $validated['sub_category_id'] ?? $serviceOffering->sub_category_id)
                ->where('title', $validated['title'] ?? $serviceOffering->title)
                ->exists();
            if ($exists) {
                return response()->error("Un service avec ce titre existe déjà pour ce prestataire et cette sous-catégorie.", null, 422);
            }
        }

        DB::transaction(function () use ($serviceOffering, $validated) {
            if (isset($validated['status'])) {
                if ($validated['status'] === 'active' && is_null($serviceOffering->published_at)) {
                    $validated['published_at'] = now();
                    $validated['status_reason'] = null;
                }
                if (in_array($validated['status'], ['paused','archived'], true)) {
                    $validated['featured'] = false;
                }
            }
            $serviceOffering->update($validated);
        });

        return response()->success($serviceOffering->fresh(), 'Service mis à jour');
    }

    /**
     * @OA\Delete(
     *   path="/api/service-offerings/{serviceOffering}",
     *   tags={"ServiceOfferings"},
     *   summary="Supprimer un service",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy(ServiceOffering $serviceOffering)
    {
        $serviceOffering->delete();
        return response()->success(null, 'Service supprimé');
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings/{serviceOffering}/publish",
     *   tags={"ServiceOfferings"},
     *   summary="Publier un service",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Publié")
     * )
     */
    public function publish(ServiceOffering $serviceOffering, Request $req)
    {
        $serviceOffering->update([
            'status'        => 'active',
            'published_at'  => now(),
            'status_reason' => null,
        ]);

        return response()->success($serviceOffering->fresh(), 'Service publié');
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings/{serviceOffering}/pause",
     *   tags={"ServiceOfferings"},
     *   summary="Mettre en pause un service",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object", @OA\Property(property="reason", type="string", nullable=true))),
     *   @OA\Response(response=200, description="Mis en pause")
     * )
     */
    public function pause(ServiceOffering $serviceOffering, Request $req)
    {
        $serviceOffering->update([
            'status'        => 'paused',
            'status_reason' => $req->input('reason'),
            'featured'      => false,
        ]);

        return response()->success($serviceOffering->fresh(), 'Service mis en pause');
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings/{serviceOffering}/archive",
     *   tags={"ServiceOfferings"},
     *   summary="Archiver un service",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object", @OA\Property(property="reason", type="string", nullable=true))),
     *   @OA\Response(response=200, description="Archivé")
     * )
     */
    public function archive(ServiceOffering $serviceOffering, Request $req)
    {
        $serviceOffering->update([
            'status'        => 'archived',
            'status_reason' => $req->input('reason'),
            'featured'      => false,
        ]);

        return response()->success($serviceOffering->fresh(), 'Service archivé');
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings/{serviceOffering}/feature",
     *   tags={"ServiceOfferings"},
     *   summary="Basculer la mise en avant",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="featured", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function feature(ServiceOffering $serviceOffering, Request $req)
    {
        $featured = (bool) $req->input('featured', true);

        if ($featured && $serviceOffering->status !== 'active') {
            return response()->error("Le service doit être 'active' pour être mis en avant.", null, 422);
        }

        $serviceOffering->update(['featured' => $featured]);

        return response()->success($serviceOffering->fresh(), $featured ? 'Service mis en avant' : 'Mise en avant retirée');
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings/{serviceOffering}/verify",
     *   tags={"ServiceOfferings"},
     *   summary="Vérifier / dévérifier un service",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="is_verified", type="boolean", example=true),
     *       @OA\Property(property="reason", type="string", nullable=true)
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function verify(ServiceOffering $serviceOffering, Request $req)
    {
        $verified = (bool) $req->input('is_verified', true);
        $serviceOffering->update([
            'is_verified'  => $verified,
            'status_reason'=> $req->input('reason'),
        ]);

        return response()->success($serviceOffering->fresh(), $verified ? 'Service vérifié' : 'Vérification retirée');
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings/{serviceOffering}/attachments",
     *   tags={"ServiceOfferings"},
     *   summary="Mettre à jour les pièces jointes",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(type="object",
     *       required={"attachments"},
     *       @OA\Property(property="attachments", type="array", @OA\Items(type="string"))
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function saveAttachments(ServiceOffering $serviceOffering, Request $req)
    {
        $data = $req->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['string', 'max:2048'],
        ]);

        $serviceOffering->update(['attachments' => $data['attachments']]);

        return response()->success($serviceOffering->fresh(), 'Pièces jointes mises à jour');
    }

    /**
     * @OA\Get(
     *   path="/api/service-offerings/{serviceOffering}/availability",
     *   tags={"ServiceOfferings"},
     *   summary="Lister les créneaux disponibles d’un service",
     *   description="Retourne les créneaux disponibles entre 'from' et 'to' (format YYYY-MM-DD).
     *                Si aucun paramètre n’est fourni, la plage par défaut couvre les 14 prochains jours à partir d’aujourd’hui.",
     *   @OA\Parameter(
     *     name="serviceOffering",
     *     in="path",
     *     required=true,
     *     description="ID du service concerné",
     *     @OA\Schema(type="integer", example=10)
     *   ),
     *   @OA\Parameter(name="from", in="query", description="Date de début (par défaut: aujourd’hui)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="to", in="query", description="Date de fin (par défaut: dans 14 jours)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="status", in="query", description="Filtrer selon le statut des créneaux", @OA\Schema(type="string", enum={"available","full","blocked","cancelled"})),
     *   @OA\Parameter(name="limit", in="query", description="Nombre maximum de créneaux renvoyés (max 500)", @OA\Schema(type="integer", minimum=1, maximum=500, example=100)),
     *   @OA\Response(
     *     response=200,
     *     description="Liste des créneaux",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="OK"),
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(
     *           type="object",
     *           example={
     *             "id":99,
     *             "service_offering_id":10,
     *             "provider_id":3,
     *             "start_at":"2025-08-20T09:00:00Z",
     *             "end_at":"2025-08-20T10:00:00Z",
     *             "timezone":"Africa/Douala",
     *             "capacity":1,
     *             "booked_count":0,
     *             "status":"available"
     *           }
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function availability(ServiceOffering $serviceOffering, Request $req)
    {
        // 1) Parse des bornes de date (défauts si absents)
        $fromInput = $req->input('from');
        $toInput   = $req->input('to');

        $from = $fromInput ? Carbon::parse($fromInput) : now();
        $to   = $toInput   ? Carbon::parse($toInput)   : now()->addWeeks(2);

        // Si l’utilisateur envoie juste une date (YYYY-MM-DD),
        // on prend toute la journée pour 'to'
        if (!$toInput || strlen((string)$toInput) <= 10) {
            $to = $to->endOfDay();
        }
        if ($fromInput && strlen((string)$fromInput) <= 10) {
            $from = $from->startOfDay();
        }

        // Swap si bornes inversées
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        // 2) Filtres optionnels
        $status = $req->input('status'); // 'available','blocked','full','cancelled'
        $limit  = $req->filled('limit') ? max(1, min((int)$req->input('limit'), 500)) : null;

        // 3) Requête
        $q = AvailabilitySlot::query()
            ->where('service_offering_id', $serviceOffering->id)
            ->whereBetween('start_at', [$from, $to])
            ->orderBy('start_at');

        // Par défaut on ne renvoie que les dispos
        if ($status) {
            $q->where('status', $status);
        } else {
            $q->where('status', 'available');
        }

        // Masquer les créneaux déjà pleins (optionnel : ?with_full=1 pour les inclure)
        if (!$req->boolean('with_full', false)) {
            $q->whereColumn('booked_count', '<', 'capacity');
        }

        if ($limit) {
            $q->limit($limit);
        }

        $slots = $q->get([
            'id','start_at','end_at','status','capacity','booked_count',
            'price_override','currency','timezone'
        ]);

        return response()->success($slots);
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings/{serviceOffering}/increment-views",
     *   tags={"ServiceOfferings"},
     *   summary="Incrémenter le compteur de vues",
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function incrementViews(ServiceOffering $serviceOffering)
    {
        $serviceOffering->increment('views_count');
        return response()->success(['views_count' => $serviceOffering->views_count], 'Vue enregistrée');
    }

    /**
     * @OA\Post(
     *   path="/api/service-offerings/{serviceOffering}/recompute-stats",
     *   tags={"ServiceOfferings"},
     *   summary="Recalculer les statistiques (notes)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="serviceOffering", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function recomputeStats(ServiceOffering $serviceOffering)
    {
        $agg = Review::query()
            ->where('service_offering_id', $serviceOffering->id)
            ->where('is_approved', true)
            ->selectRaw('COUNT(*) as c, AVG(rating) as avg')
            ->first();

        $serviceOffering->update([
            'ratings_count' => (int) ($agg->c ?? 0),
            'avg_rating'    => round((float) ($agg->avg ?? 0), 2),
        ]);

        return response()->success($serviceOffering->fresh(), 'Statistiques recalculées');
    }

    /**
     * Validation centralisée (non exposée Swagger)
     */
    private function validatePayload(Request $req, bool $updating = false): array
    {
        $rules = [
            'sub_category_id' => [$updating ? 'sometimes' : 'required', 'integer', 'exists:sub_categories,id'],
            'provider_id'     => [$updating ? 'sometimes' : 'nullable', 'integer', 'exists:users,id'],
            'title'           => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'description'     => [$updating ? 'sometimes' : 'nullable', 'string'],

            'price_amount'    => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'price_unit'      => [$updating ? 'sometimes' : 'nullable', Rule::in(['hour','service','km','course','kg','jour'])],
            'currency'        => [$updating ? 'sometimes' : 'nullable', 'string', 'size:3'],
            'tax_rate'        => [$updating ? 'sometimes' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => [$updating ? 'sometimes' : 'nullable', 'numeric', 'min:0'],

            'city'            => [$updating ? 'sometimes' : 'nullable', 'string', 'max:255'],
            'country'         => [$updating ? 'sometimes' : 'nullable', 'string', 'size:2'],
            'address'         => [$updating ? 'sometimes' : 'nullable', 'string', 'max:255'],
            'coverage_km'     => [$updating ? 'sometimes' : 'nullable', 'integer', 'min:0', 'max:5000'],
            'on_site'         => [$updating ? 'sometimes' : 'nullable', 'boolean'],
            'at_provider'     => [$updating ? 'sometimes' : 'nullable', 'boolean'],
            'lat'             => [$updating ? 'sometimes' : 'nullable', 'numeric', 'between:-90,90'],
            'lng'             => [$updating ? 'sometimes' : 'nullable', 'numeric', 'between:-180,180'],

            'min_delay_hours' => [$updating ? 'sometimes' : 'nullable', 'integer', 'min:0', 'max:720'],
            'max_delay_hours' => [$updating ? 'sometimes' : 'nullable', 'integer', 'min:0', 'max:720'],
            'duration_minutes'=> [$updating ? 'sometimes' : 'nullable', 'integer', 'min:5', 'max:1440'],
            'capacity'        => [$updating ? 'sometimes' : 'nullable', 'integer', 'min:1', 'max:1000'],

            'status'          => [$updating ? 'sometimes' : 'nullable', Rule::in(['draft','active','paused','archived'])],
            'published_at'    => [$updating ? 'sometimes' : 'nullable', 'date'],
            'featured'        => [$updating ? 'sometimes' : 'nullable', 'boolean'],
            'is_verified'     => [$updating ? 'sometimes' : 'nullable', 'boolean'],
            'status_reason'   => [$updating ? 'sometimes' : 'nullable', 'string', 'max:255'],

            'avg_rating'      => [$updating ? 'sometimes' : 'nullable', 'numeric', 'min:0', 'max:5'],
            'ratings_count'   => [$updating ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'views_count'     => [$updating ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'bookings_count'  => [$updating ? 'sometimes' : 'nullable', 'integer', 'min:0'],
            'favorites_count' => [$updating ? 'sometimes' : 'nullable', 'integer', 'min:0'],

            'attachments'     => [$updating ? 'sometimes' : 'nullable', 'array'],
            'attachments.*'   => ['string', 'max:2048'],
            'metadata'        => [$updating ? 'sometimes' : 'nullable', 'array'],
        ];

        $validated = $req->validate($rules);
        return $validated;
    }

    /**
     * @OA\Get(
     *   path="/api/service-offerings/sub-categories/{subCategory}/count",
     *   tags={"ServiceOfferings"},
     *   summary="Nombre de services pour une sous-catégorie",
     *   description="Retourne combien de services appartiennent à une sous-catégorie donnée.",
     *   @OA\Parameter(
     *     name="subCategory",
     *     in="path",
     *     required=true,
     *     description="ID de la sous-catégorie",
     *     @OA\Schema(type="integer", example=15)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Nombre de services pour la sous-catégorie"),
     *       @OA\Property(property="count", type="integer", example=12)
     *     )
     *   ),
     *   @OA\Response(response=404, description="Sous-catégorie non trouvée")
     * )
     */
    public function countBySubCategory($subCategoryId)
    {
        $count = ServiceOffering::where('sub_category_id', $subCategoryId)->count();

        return response()->success(
            ['count' => $count],
            "Nombre de services pour la sous-catégorie {$subCategoryId}"
        );
    }


    /**
     * @OA\Get(
     *   path="/api/service-offerings/stats",
     *   tags={"ServiceOfferings"},
     *   summary="Statistiques globales des services",
     *   description="Retourne un résumé global : total, actifs, vérifiés, en pause, archivés, mis en avant.",
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Statistiques globales des services"),
     *       @OA\Property(property="data", type="object",
     *         example={
     *           "total": 120,
     *           "actifs": 85,
     *           "verifies": 60,
     *           "en_pause": 10,
     *           "archives": 5,
     *           "mis_en_avant": 20
     *         }
     *       )
     *     )
     *   )
     * )
     */
    public function stats()
    {
        $stats = [
            'total'        => ServiceOffering::count(),
            'actifs'       => ServiceOffering::where('status', 'active')->count(),
            'verifies'     => ServiceOffering::where('is_verified', true)->count(),
            'en_pause'     => ServiceOffering::where('status', 'paused')->count(),
            'archives'     => ServiceOffering::where('status', 'archived')->count(),
            'mis_en_avant' => ServiceOffering::where('featured', true)->count(),
        ];

        return response()->success($stats, 'Statistiques globales des services');
    }

}
