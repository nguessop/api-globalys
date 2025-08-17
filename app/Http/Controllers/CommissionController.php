<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class CommissionController extends Controller
{
    public function __construct()
    {
        // Lecture publique; modifications protégées
        $this->middleware('auth:api')->except(['index','show']);
    }

    /**
     * @OA\Get(
     *   path="/api/commissions",
     *   tags={"Commissions"},
     *   summary="Lister les commissions",
     *   description="Filtres: status, provider_id, booking_id, subscription_id, type, currency, période (from/to), q (external_reference/notes), amount_min/max. Tri & pagination.",
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending","captured","settled","refunded","cancelled"})),
     *   @OA\Parameter(name="provider_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="booking_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="subscription_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="type", in="query", description="percent|fixed", @OA\Schema(type="string", enum={"percent","fixed"})),
     *   @OA\Parameter(name="currency", in="query", @OA\Schema(type="string", maxLength=3)),
     *   @OA\Parameter(name="from", in="query", description="Date début (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="to", in="query", description="Date fin (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="q", in="query", description="Recherche (external_reference, notes)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="amount_min", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="amount_max", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="sort", in="query", description="created_at|amount|base_amount|captured_at|settled_at|status", @OA\Schema(type="string")),
     *   @OA\Parameter(name="dir", in="query", description="asc|desc", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=100)),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $req)
    {
        $q = Commission::query()->with([
            'booking:id,code,client_id,provider_id,service_offering_id',
            'provider:id,first_name,last_name,company_name',
            'subscription:id,user_id,plan_name,plan_code',
        ]);

        // Filtres
        if ($req->filled('status'))          $q->status($req->string('status'));
        if ($req->filled('provider_id'))     $q->forProvider((int)$req->input('provider_id'));
        if ($req->filled('booking_id'))      $q->where('booking_id', (int)$req->input('booking_id'));
        if ($req->filled('subscription_id')) $q->where('subscription_id', (int)$req->input('subscription_id'));
        if ($req->filled('type'))            $q->where('commission_type', (string)$req->input('type'));
        if ($req->filled('currency'))        $q->where('currency', strtoupper(substr((string)$req->input('currency'),0,3)));

        // Période (sur created_at)
        $from = $req->date('from');
        $to   = $req->date('to');
        if ($from && $to)       $q->forPeriod($from, $to);
        elseif ($from)          $q->where('created_at','>=',$from);
        elseif ($to)            $q->where('created_at','<=',$to);

        // Recherche texte
        if ($req->filled('q')) {
            $term = trim((string)$req->input('q'));
            $q->where(function ($w) use ($term) {
                $w->where('external_reference','like',"%{$term}%")
                    ->orWhere('notes','like',"%{$term}%");
            });
        }

        // Montants
        if ($req->filled('amount_min') && is_numeric($req->input('amount_min'))) {
            $q->where('amount','>=',(float)$req->input('amount_min'));
        }
        if ($req->filled('amount_max') && is_numeric($req->input('amount_max'))) {
            $q->where('amount','<=',(float)$req->input('amount_max'));
        }

        // Tri
        $sort = (string)$req->input('sort','created_at');
        $dir  = strtolower((string)$req->input('dir','desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at','amount','base_amount','captured_at','settled_at','status'];
        if (!in_array($sort,$allowed,true)) $sort = 'created_at';
        $q->orderBy($sort,$dir);

        // Pagination
        $perPage = max(1, min((int)$req->input('per_page',15), 100));
        $data = $q->paginate($perPage);

        return response()->success($data, 'Liste des commissions');
    }

    /**
     * @OA\Get(
     *   path="/api/commissions/{commission}",
     *   tags={"Commissions"},
     *   summary="Afficher une commission",
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Commission $commission)
    {
        $commission->load([
            'booking:id,code,client_id,provider_id,service_offering_id',
            'provider:id,first_name,last_name,company_name',
            'subscription:id,user_id,plan_name,plan_code',
        ]);

        return response()->success($commission, 'Détails de la commission');
    }

    /**
     * @OA\Post(
     *   path="/api/commissions",
     *   tags={"Commissions"},
     *   summary="Créer une commission",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"booking_id","provider_id","base_amount","commission_type"},
     *       @OA\Property(property="booking_id", type="integer", example=120),
     *       @OA\Property(property="provider_id", type="integer", example=45),
     *       @OA\Property(property="subscription_id", type="integer", nullable=true, example=12),
     *       @OA\Property(property="base_amount", type="number", format="float", example=10000),
     *       @OA\Property(property="currency", type="string", example="XAF"),
     *       @OA\Property(property="commission_type", type="string", enum={"percent","fixed"}, example="percent"),
     *       @OA\Property(property="commission_rate", type="number", format="float", nullable=true, example=5.0),
     *       @OA\Property(property="commission_fixed", type="number", format="float", nullable=true, example=500),
     *       @OA\Property(property="status", type="string", enum={"pending","captured","settled","refunded","cancelled"}),
     *       @OA\Property(property="external_reference", type="string", nullable=true),
     *       @OA\Property(property="notes", type="string", nullable=true),
     *       @OA\Property(property="metadata", type="object", nullable=true)
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

        // Normalisation devise
        $data['currency'] = strtoupper($data['currency'] ?? 'XAF');

        $commission = Commission::create($data); // compute auto (booted)

        return response()->success($commission->fresh(), 'Commission créée', 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/commissions/{commission}",
     *   tags={"Commissions"},
     *   summary="Mettre à jour une commission",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     * @OA\Put(
     *   path="/api/commissions/{commission}",
     *   tags={"Commissions"},
     *   summary="Mettre à jour une commission (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(Request $req, Commission $commission)
    {
        $data = $this->validatePayload($req, updating: true);

        if (array_key_exists('currency',$data)) {
            $data['currency'] = strtoupper(substr((string)$data['currency'],0,3));
        }

        $commission->fill($data);

        // Recalcul amount si la base ou la règle changent et amount non fourni
        $touched = array_intersect(array_keys($data), ['base_amount','commission_type','commission_rate','commission_fixed']);
        if (!empty($touched) && !array_key_exists('amount', $data)) {
            $commission->amount = $commission->computeAmount();
        }

        $commission->save();

        return response()->success($commission->fresh(), 'Commission mise à jour');
    }

    /**
     * @OA\Delete(
     *   path="/api/commissions/{commission}",
     *   tags={"Commissions"},
     *   summary="Supprimer une commission",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé")
     * )
     */
    public function destroy(Commission $commission)
    {
        $commission->delete();
        return response()->success(null, 'Commission supprimée');
    }

    /**
     * @OA\Post(
     *   path="/api/commissions/{commission}/capture",
     *   tags={"Commissions"},
     *   summary="Marquer une commission comme capturée",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object", @OA\Property(property="external_reference", type="string", nullable=true))),
     *   @OA\Response(response=200, description="Capturée")
     * )
     */
    public function capture(Commission $commission, Request $req)
    {
        $data = $req->validate([
            'external_reference' => ['sometimes','nullable','string','max:255'],
        ]);

        $commission->markCaptured($data['external_reference'] ?? null);

        return response()->success($commission->fresh(), 'Commission capturée');
    }

    /**
     * @OA\Post(
     *   path="/api/commissions/{commission}/settle",
     *   tags={"Commissions"},
     *   summary="Marquer une commission comme reversée (settled)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Reversée")
     * )
     */
    public function settle(Commission $commission)
    {
        $commission->markSettled();
        return response()->success($commission->fresh(), 'Commission reversée (settled)');
    }

    /**
     * @OA\Post(
     *   path="/api/commissions/{commission}/refund",
     *   tags={"Commissions"},
     *   summary="Marquer une commission comme remboursée",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Remboursée")
     * )
     */
    public function refund(Commission $commission)
    {
        $commission->markRefunded();
        return response()->success($commission->fresh(), 'Commission remboursée');
    }

    /**
     * @OA\Post(
     *   path="/api/commissions/{commission}/cancel",
     *   tags={"Commissions"},
     *   summary="Annuler une commission",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object", @OA\Property(property="reason", type="string", nullable=true))),
     *   @OA\Response(response=200, description="Annulée")
     * )
     */
    public function cancel(Commission $commission, Request $req)
    {
        $data = $req->validate([
            'reason' => ['sometimes','nullable','string','max:255'],
        ]);

        $commission->markCancelled($data['reason'] ?? null);

        return response()->success($commission->fresh(), 'Commission annulée');
    }

    /**
     * @OA\Post(
     *   path="/api/commissions/{commission}/recompute-amount",
     *   tags={"Commissions"},
     *   summary="Recalculer le montant depuis la base et la règle",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commission", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object", @OA\Property(property="base_amount", type="number", format="float", nullable=true))),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function recomputeAmount(Commission $commission, Request $req)
    {
        $data = $req->validate([
            'base_amount' => ['sometimes','nullable','numeric','min:0'],
        ]);

        $amount = $commission->computeAmount(
            array_key_exists('base_amount',$data) ? (float)$data['base_amount'] : null
        );

        if (array_key_exists('base_amount',$data)) {
            $commission->base_amount = (float)$data['base_amount'];
        }
        $commission->amount = $amount;
        $commission->save();

        return response()->success($commission->fresh(), 'Montant recalculé');
    }

    /* ======================= Validation ======================= */

    private function validatePayload(Request $req, bool $updating = false): array
    {
        $statusEnum = [
            Commission::STATUS_PENDING,
            Commission::STATUS_CAPTURED,
            Commission::STATUS_SETTLED,
            Commission::STATUS_REFUNDED,
            Commission::STATUS_CANCELLED,
        ];
        $typeEnum = [Commission::TYPE_PERCENT, Commission::TYPE_FIXED];

        return $req->validate([
            'booking_id'       => [$updating ? 'sometimes' : 'required','integer','exists:bookings,id'],
            'provider_id'      => [$updating ? 'sometimes' : 'required','integer','exists:users,id'],
            'subscription_id'  => [$updating ? 'sometimes' : 'nullable','integer','exists:subscriptions,id'],

            'base_amount'      => [$updating ? 'sometimes' : 'required','numeric','min:0'],
            'currency'         => [$updating ? 'sometimes' : 'nullable','string','size:3'],

            'commission_type'  => [$updating ? 'sometimes' : 'required', Rule::in($typeEnum)],
            'commission_rate'  => [$updating ? 'sometimes' : 'nullable','numeric','min:0','max:100'],
            'commission_fixed' => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],

            'amount'           => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],

            'status'           => [$updating ? 'sometimes' : 'nullable', Rule::in($statusEnum)],
            'captured_at'      => [$updating ? 'sometimes' : 'nullable','date'],
            'settled_at'       => [$updating ? 'sometimes' : 'nullable','date'],
            'refunded_at'      => [$updating ? 'sometimes' : 'nullable','date'],

            'external_reference'=> [$updating ? 'sometimes' : 'nullable','string','max:255'],
            'notes'            => [$updating ? 'sometimes' : 'nullable','string'],
            'metadata'         => [$updating ? 'sometimes' : 'nullable','array'],
        ]);
    }
}
