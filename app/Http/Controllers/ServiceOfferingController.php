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
        $this->middleware('auth:api')->except(['index', 'show', 'availability', 'incrementViews']);
    }

    /**
     * GET /api/service-offerings
     * Liste + recherche/filtre + tri + pagination
     */
    public function index(Request $req)
    {
        $q = ServiceOffering::query()
            ->with([
                'provider:id,first_name,last_name,company_name,user_type,account_type',
                'subCategory:id,name,category_id',
            ]);

        // Filtres
        $q->when($req->filled('status'),
            fn($w) => $w->where('status', (string) $req->input('status')),
            fn($w) => $w->where('status', '!=', 'archived') // défaut: exclure 'archived'
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

        // Recherche plein texte simple
        $q->when($req->filled('q'), function ($w) use ($req) {
            $term = trim((string) $req->input('q'));
            $w->where(function ($x) use ($term) {
                $x->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        });

        // Plage de prix
        if ($req->filled('price_min') && is_numeric($req->input('price_min'))) {
            $q->where('price_amount', '>=', (float) $req->input('price_min'));
        }
        if ($req->filled('price_max') && is_numeric($req->input('price_max'))) {
            $q->where('price_amount', '<=', (float) $req->input('price_max'));
        }

        // Note minimale
        if ($req->filled('rating_min') && is_numeric($req->input('rating_min'))) {
            $q->where('avg_rating', '>=', (float) $req->input('rating_min'));
        }

        // Tri
        $sort = (string) $req->input('sort', 'published_at');
        $dir  = strtolower((string) $req->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = [
            'published_at','price_amount','avg_rating',
            'bookings_count','views_count','title','created_at'
        ];
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
     * GET /api/service-offerings/{serviceOffering}
     * Détail
     */
    public function show(ServiceOffering $serviceOffering)
    {
        $serviceOffering->load([
            'provider:id,first_name,last_name,company_name,user_type,account_type',
            'subCategory:id,name,category_id',
        ]);

        return response()->success($serviceOffering);
    }

    /**
     * POST /api/service-offerings
     * Création
     */
    public function store(Request $req)
    {
        $validated = $this->validatePayload($req);

        // owner = provider authentifié (ou via payload si admin)
        $user = $req->user();
        if (!$user) {
            return response()->error("Non autorisé", null, 401);
        }

        // Si tu as des rôles/admin, ajuste ici la logique (ex: un admin peut définir provider_id)
        if (!isset($validated['provider_id'])) {
            $validated['provider_id'] = $user->id;
        }

        // Defaults safe
        $validated['status']       = $validated['status'] ?? 'draft';
        $validated['published_at'] = $validated['status'] === 'active' ? now() : null;

        $offering = DB::transaction(fn () => ServiceOffering::create($validated));

        return response()->success($offering->fresh(), 'Service créé', 201);
    }

    /**
     * PUT/PATCH /api/service-offerings/{serviceOffering}
     * Mise à jour
     */
    public function update(Request $req, ServiceOffering $serviceOffering)
    {
        // Optionnel: Gate/policy (ex: $this->authorize('update', $serviceOffering);)
        $validated = $this->validatePayload($req, updating: true);

        // Empêche collisions de contrainte unique (provider_id, sub_category_id, title)
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
            // Publication auto si status -> active
            if (isset($validated['status'])) {
                if ($validated['status'] === 'active' && is_null($serviceOffering->published_at)) {
                    $validated['published_at'] = now();
                    $validated['status_reason'] = null;
                }
                if (in_array($validated['status'], ['paused','archived'], true)) {
                    $validated['featured'] = false; // on enlève la mise en avant si pause/archivage
                }
            }

            $serviceOffering->update($validated);
        });

        return response()->success($serviceOffering->fresh(), 'Service mis à jour');
    }

    /**
     * DELETE /api/service-offerings/{serviceOffering}
     * Suppression (soft delete)
     */
    public function destroy(ServiceOffering $serviceOffering)
    {
        $serviceOffering->delete();
        return response()->success(null, 'Service supprimé');
    }

    /**
     * POST /api/service-offerings/{serviceOffering}/publish
     * Publication (active)
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
     * POST /api/service-offerings/{serviceOffering}/pause
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
     * POST /api/service-offerings/{serviceOffering}/archive
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
     * POST /api/service-offerings/{serviceOffering}/feature
     * Toggle mise en avant
     */
    public function feature(ServiceOffering $serviceOffering, Request $req)
    {
        $featured = (bool) $req->input('featured', true);

        // Optionnel: n’autoriser que si status actif
        if ($featured && $serviceOffering->status !== 'active') {
            return response()->error("Le service doit être 'active' pour être mis en avant.", null, 422);
        }

        $serviceOffering->update(['featured' => $featured]);

        return response()->success($serviceOffering->fresh(), $featured ? 'Service mis en avant' : 'Mise en avant retirée');
    }

    /**
     * POST /api/service-offerings/{serviceOffering}/verify
     * Vérification KYC / interne
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
     * POST /api/service-offerings/{serviceOffering}/attachments
     * Ajout / remplace la liste des pièces jointes (array de strings/URLs)
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
     * GET /api/service-offerings/{serviceOffering}/availability
     * Liste des créneaux de disponibilité du service (futurs)
     * Query params: from, to (YYYY-MM-DD), status, limit
     */
    public function availability(ServiceOffering $serviceOffering, Request $req)
    {
        $from = $req->date('from', now());
        $to   = $req->date('to', now()->copy()->addWeeks(2));
        $status = $req->input('status'); // available|full|blocked|cancelled
        $limit  = min((int) $req->input('limit', 100), 500);

        $slots = AvailabilitySlot::query()
            ->where('service_offering_id', $serviceOffering->id)
            ->whereBetween('start_at', [$from, $to])
            ->when($status, fn($w) => $w->where('status', $status))
            ->orderBy('start_at')
            ->limit($limit)
            ->get();

        return response()->success($slots);
    }

    /**
     * POST /api/service-offerings/{serviceOffering}/increment-views
     * Incrémente le compteur de vues (sans auth)
     */
    public function incrementViews(ServiceOffering $serviceOffering)
    {
        $serviceOffering->increment('views_count');
        return response()->success(['views_count' => $serviceOffering->views_count], 'Vue enregistrée');
    }

    /**
     * POST /api/service-offerings/{serviceOffering}/recompute-stats
     * Recalcule avg_rating & ratings_count depuis reviews
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
     * Validation centralisée
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

        // Si metadata/attachments sont arrays, Laravel les persiste en JSON via $casts (voir modèle)
        return $validated;
    }
}
