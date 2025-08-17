<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        // Lecture publique; création/mise à jour/suppression protégées
        $this->middleware('auth:api')->except(['index','show']);
    }

    /**
     * @OA\Get(
     *   path="/api/subscriptions",
     *   tags={"Subscriptions"},
     *   summary="Lister les abonnements",
     *   description="Filtres: user_id, status, currency, active=1/0, plan_name/plan_code, q (plan/payment ref). Tri & pagination.",
     *   @OA\Parameter(name="user_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","expired","cancelled"})),
     *   @OA\Parameter(name="currency", in="query", @OA\Schema(type="string", maxLength=3)),
     *   @OA\Parameter(name="active", in="query", description="1=encore actif (end_date future + status=active), 0=non", @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="plan_name", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="plan_code", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="q", in="query", description="Recherche plan/payment_reference", @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", description="created_at|start_date|end_date|price|status|plan_name", @OA\Schema(type="string")),
     *   @OA\Parameter(name="dir", in="query", description="asc|desc", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=100)),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $req)
    {
        $q = Subscription::query()->with(['user:id,first_name,last_name,email']);

        // Filtres
        if ($req->filled('user_id'))   $q->where('user_id', (int)$req->input('user_id'));
        if ($req->filled('status'))    $q->where('status', (string)$req->input('status'));
        if ($req->filled('currency'))  $q->where('currency', strtoupper(substr((string)$req->input('currency'),0,3)));
        if ($req->filled('plan_name')) $q->where('plan_name','like','%'.trim((string)$req->input('plan_name')).'%');
        if ($req->filled('plan_code')) $q->where('plan_code','like','%'.trim((string)$req->input('plan_code')).'%');

        if ($req->filled('q')) {
            $term = trim((string)$req->input('q'));
            $q->where(function ($w) use ($term) {
                $w->where('plan_name','like',"%{$term}%")
                    ->orWhere('plan_code','like',"%{$term}%")
                    ->orWhere('payment_reference','like',"%{$term}%");
            });
        }

        // Actif logique (status=active + end_date future)
        if ($req->filled('active')) {
            $flag = (int)$req->input('active') === 1;
            if ($flag) {
                $q->where('status', Subscription::STATUS_ACTIVE)
                    ->whereNotNull('end_date')
                    ->where('end_date','>', now());
            } else {
                $q->where(function ($w) {
                    $w->where('status','!=', Subscription::STATUS_ACTIVE)
                        ->orWhereNull('end_date')
                        ->orWhere('end_date','<=', now());
                });
            }
        }

        // Tri
        $sort = (string)$req->input('sort','created_at');
        $dir  = strtolower((string)$req->input('dir','desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at','start_date','end_date','price','status','plan_name'];
        if (!in_array($sort,$allowed,true)) $sort = 'created_at';
        $q->orderBy($sort,$dir);

        // Pagination
        $perPage = max(1, min((int)$req->input('per_page',15), 100));
        $data = $q->paginate($perPage);

        return response()->success($data, 'Liste des abonnements');
    }

    /**
     * @OA\Get(
     *   path="/api/subscriptions/{subscription}",
     *   tags={"Subscriptions"},
     *   summary="Afficher un abonnement",
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Subscription $subscription)
    {
        $subscription->load(['user:id,first_name,last_name,email']);
        // facultatif: exposer un label lisible
        $subscription->setAttribute('commission_label', $subscription->commission_label);
        return response()->success($subscription, 'Détails de l’abonnement');
    }

    /**
     * @OA\Post(
     *   path="/api/subscriptions",
     *   tags={"Subscriptions"},
     *   summary="Créer un abonnement",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"user_id","plan_name"},
     *       @OA\Property(property="user_id", type="integer", example=42),
     *       @OA\Property(property="plan_name", type="string", example="Gold"),
     *       @OA\Property(property="plan_code", type="string", nullable=true, example="gold_2025"),
     *       @OA\Property(property="price", type="number", format="float", example=5000),
     *       @OA\Property(property="currency", type="string", example="XAF"),
     *       @OA\Property(property="start_date", type="string", format="date-time"),
     *       @OA\Property(property="end_date", type="string", format="date-time"),
     *       @OA\Property(property="status", type="string", enum={"active","expired","cancelled"}, example="active"),
     *       @OA\Property(property="auto_renew", type="boolean", example=true),
     *       @OA\Property(property="payment_method", type="string", example="mobile_money"),
     *       @OA\Property(property="payment_reference", type="string", example="SUB-ABC123"),
     *       @OA\Property(property="commission_type", type="string", enum={"percent","fixed"}, example="percent"),
     *       @OA\Property(property="commission_rate", type="number", format="float", nullable=true, example=5.0),
     *       @OA\Property(property="commission_fixed", type="number", format="float", nullable=true, example=500),
     *       @OA\Property(property="commission_notes", type="string", nullable=true)
     *     )
     *   ),
     *   @OA\Response(response=201, description="Créé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $req)
    {
        $data = $this->validatePayload($req);

        // défauts
        $data['currency'] = strtoupper($data['currency'] ?? 'XAF');
        $data['status']   = $data['status'] ?? Subscription::STATUS_ACTIVE;

        $sub = Subscription::create($data);

        $sub->setAttribute('commission_label', $sub->commission_label);
        return response()->success($sub->fresh(), 'Abonnement créé', 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/subscriptions/{subscription}",
     *   tags={"Subscriptions"},
     *   summary="Mettre à jour un abonnement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     * @OA\Put(
     *   path="/api/subscriptions/{subscription}",
     *   tags={"Subscriptions"},
     *   summary="Mettre à jour un abonnement (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(Request $req, Subscription $subscription)
    {
        $data = $this->validatePayload($req, updating: true);

        // normalisation devise
        if (array_key_exists('currency',$data)) {
            $data['currency'] = strtoupper(substr((string)$data['currency'],0,3));
        }

        $subscription->update($data);

        $subscription->setAttribute('commission_label', $subscription->commission_label);
        return response()->success($subscription->fresh(), 'Abonnement mis à jour');
    }

    /**
     * @OA\Delete(
     *   path="/api/subscriptions/{subscription}",
     *   tags={"Subscriptions"},
     *   summary="Supprimer un abonnement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé")
     * )
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return response()->success(null, 'Abonnement supprimé');
    }

    /**
     * @OA\Post(
     *   path="/api/subscriptions/{subscription}/cancel",
     *   tags={"Subscriptions"},
     *   summary="Annuler un abonnement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Annulé")
     * )
     */
    public function cancel(Subscription $subscription)
    {
        $subscription->update([
            'status'     => Subscription::STATUS_CANCELLED,
            'auto_renew' => false,
        ]);
        return response()->success($subscription->fresh(), 'Abonnement annulé');
    }

    /**
     * @OA\Post(
     *   path="/api/subscriptions/{subscription}/expire",
     *   tags={"Subscriptions"},
     *   summary="Expirer un abonnement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Expiré")
     * )
     */
    public function expire(Subscription $subscription)
    {
        $subscription->update([
            'status'   => Subscription::STATUS_EXPIRED,
            'end_date' => now(),
        ]);
        return response()->success($subscription->fresh(), 'Abonnement expiré');
    }

    /**
     * @OA\Post(
     *   path="/api/subscriptions/{subscription}/activate",
     *   tags={"Subscriptions"},
     *   summary="Activer un abonnement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object",
     *     @OA\Property(property="start_date", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="end_date", type="string", format="date-time", nullable=true)
     *   )),
     *   @OA\Response(response=200, description="Activé")
     * )
     */
    public function activate(Subscription $subscription, Request $req)
    {
        $data = $req->validate([
            'start_date' => ['sometimes','nullable','date'],
            'end_date'   => ['sometimes','nullable','date','after_or_equal:start_date'],
        ]);

        $subscription->update(array_merge($data, [
            'status' => Subscription::STATUS_ACTIVE,
        ]));

        return response()->success($subscription->fresh(), 'Abonnement activé');
    }

    /**
     * @OA\Post(
     *   path="/api/subscriptions/{subscription}/toggle-auto-renew",
     *   tags={"Subscriptions"},
     *   summary="Basculer/forcer le renouvellement automatique",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object", @OA\Property(property="auto_renew", type="boolean", nullable=true))),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function toggleAutoRenew(Subscription $subscription, Request $req)
    {
        if ($req->has('auto_renew')) {
            $val = (bool)$req->input('auto_renew');
            $subscription->update(['auto_renew' => $val]);
        } else {
            $subscription->update(['auto_renew' => !$subscription->auto_renew]);
        }
        return response()->success($subscription->fresh(), 'auto_renew mis à jour');
    }

    /**
     * @OA\Post(
     *   path="/api/subscriptions/{subscription}/compute-commission",
     *   tags={"Subscriptions"},
     *   summary="Calculer la commission pour un montant donné",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subscription", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object",
     *     required={"amount"}, @OA\Property(property="amount", type="number", format="float", example=10000)
     *   )),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function computeCommission(Subscription $subscription, Request $req)
    {
        $data = $req->validate([
            'amount' => ['required','numeric','min:0'],
        ]);

        $value = $subscription->computeCommission((float)$data['amount']);

        return response()->success([
            'amount'             => (float)$data['amount'],
            'commission'         => $value,
            'commission_type'    => $subscription->commission_type,
            'commission_label'   => $subscription->commission_label,
            'currency'           => $subscription->currency ?? 'XAF',
        ], 'Commission calculée');
    }

    /* ======================= Validation ======================= */

    private function validatePayload(Request $req, bool $updating = false): array
    {
        $statusEnum = [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRED, Subscription::STATUS_CANCELLED];
        $commissionEnum = [Subscription::COMMISSION_PERCENT, Subscription::COMMISSION_FIXED];

        $rules = [
            'user_id'           => [$updating ? 'sometimes' : 'required','integer','exists:users,id'],
            'plan_name'         => [$updating ? 'sometimes' : 'required','string','max:255'],
            'plan_code'         => [$updating ? 'sometimes' : 'nullable','string','max:255'],

            'price'             => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],
            'currency'          => [$updating ? 'sometimes' : 'nullable','string','size:3'],

            'start_date'        => [$updating ? 'sometimes' : 'nullable','date'],
            'end_date'          => [$updating ? 'sometimes' : 'nullable','date','after_or_equal:start_date'],

            'status'            => [$updating ? 'sometimes' : 'nullable', Rule::in($statusEnum)],
            'auto_renew'        => [$updating ? 'sometimes' : 'nullable','boolean'],
            'payment_method'    => [$updating ? 'sometimes' : 'nullable','string','max:100'],
            'payment_reference' => [$updating ? 'sometimes' : 'nullable','string','max:255'],

            'commission_type'   => [$updating ? 'sometimes' : 'nullable', Rule::in($commissionEnum)],
            'commission_rate'   => [$updating ? 'sometimes' : 'nullable','numeric','min:0','max:100'],
            'commission_fixed'  => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],
            'commission_notes'  => [$updating ? 'sometimes' : 'nullable','string'],
        ];

        return $req->validate($rules);
    }
}
