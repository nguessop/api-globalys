<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Lecture publique, modifications protégées
        $this->middleware('auth:api')->except(['index','show']);
    }

    /**
     * @OA\Get(
     *   path="/api/payments",
     *   tags={"Payments"},
     *   summary="Lister les paiements",
     *   description="Filtres, tri et pagination.",
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending","authorized","succeeded","failed","refunded","cancelled"})),
     *   @OA\Parameter(name="booking_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="client_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="provider_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="method", in="query", @OA\Schema(type="string", enum={"card","mobile_money","bank_transfer","cash","wallet"})),
     *   @OA\Parameter(name="gateway", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="currency", in="query", @OA\Schema(type="string", maxLength=3)),
     *   @OA\Parameter(name="q", in="query", description="Recherche (reference, external_id, idempotency_key)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="amount_min", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="amount_max", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="sort", in="query", description="created_at|amount|captured_at|status", @OA\Schema(type="string")),
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
     *         @OA\Property(property="total", type="integer", example=42),
     *         @OA\Property(property="last_page", type="integer", example=3),
     *         @OA\Property(property="data", type="array",
     *           @OA\Items(type="object",
     *             example={"id":1,"booking_id":10,"amount":25000,"currency":"XAF","status":"succeeded","reference":"PMT-ABC123"}
     *           )
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $req)
    {
        $q = Payment::query()->with([
            'booking:id,service_offering_id,client_id,provider_id,code',
            'client:id,first_name,last_name,email',
            'provider:id,first_name,last_name,company_name',
        ]);

        // Filtres rapides via scopes
        if ($req->filled('status'))       $q->status($req->string('status'));
        if ($req->filled('booking_id'))   $q->forBooking($req->integer('booking_id'));
        if ($req->filled('client_id'))    $q->forClient($req->integer('client_id'));
        if ($req->filled('provider_id'))  $q->forProvider($req->integer('provider_id'));
        if ($req->filled('method'))       $q->where('method', $req->string('method'));
        if ($req->filled('gateway'))      $q->where('gateway', $req->string('gateway'));
        if ($req->filled('currency'))     $q->where('currency', strtoupper(substr((string)$req->input('currency'), 0, 3)));

        if ($req->filled('q')) {
            $term = trim((string)$req->input('q'));
            $q->where(function ($w) use ($term) {
                $w->where('reference', 'like', "%{$term}%")
                    ->orWhere('external_id', 'like', "%{$term}%")
                    ->orWhere('idempotency_key', 'like', "%{$term}%");
            });
        }

        if ($req->filled('amount_min') && is_numeric($req->input('amount_min'))) {
            $q->where('amount', '>=', (float)$req->input('amount_min'));
        }
        if ($req->filled('amount_max') && is_numeric($req->input('amount_max'))) {
            $q->where('amount', '<=', (float)$req->input('amount_max'));
        }

        // Tri
        $sort = (string) $req->input('sort', 'created_at');
        $dir  = strtolower((string) $req->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at','amount','captured_at','status'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';
        $q->orderBy($sort, $dir);

        // Pagination
        $perPage = max(1, min((int)$req->input('per_page', 15), 100));
        $data = $q->paginate($perPage);

        return response()->success($data);
    }

    /**
     * @OA\Get(
     *   path="/api/payments/{payment}",
     *   tags={"Payments"},
     *   summary="Afficher un paiement",
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Payment $payment)
    {
        $payment->load([
            'booking:id,service_offering_id,client_id,provider_id,code',
            'client:id,first_name,last_name,email,phone',
            'provider:id,first_name,last_name,company_name,phone',
        ]);

        return response()->success($payment);
    }

    /**
     * @OA\Post(
     *   path="/api/payments",
     *   tags={"Payments"},
     *   summary="Créer un paiement",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"booking_id","client_id","amount"},
     *       @OA\Property(property="booking_id", type="integer", example=10),
     *       @OA\Property(property="client_id", type="integer", example=25),
     *       @OA\Property(property="provider_id", type="integer", nullable=true, example=5),
     *       @OA\Property(property="amount", type="number", format="float", example=25000),
     *       @OA\Property(property="currency", type="string", example="XAF"),
     *       @OA\Property(property="processor_fee", type="number", format="float", example=500),
     *       @OA\Property(property="method", type="string", enum={"card","mobile_money","bank_transfer","cash","wallet"}, example="mobile_money"),
     *       @OA\Property(property="gateway", type="string", example="cinetpay"),
     *       @OA\Property(property="reference", type="string", example="PMT-ABC123"),
     *       @OA\Property(property="idempotency_key", type="string", example="idem-xyz-001"),
     *       @OA\Property(property="external_id", type="string", example="trx_123"),
     *       @OA\Property(property="status", type="string", enum={"pending","authorized","succeeded","failed","refunded","cancelled"}),
     *       @OA\Property(property="payload", type="object"),
     *       @OA\Property(property="metadata", type="object")
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

        // Déduire provider_id depuis la réservation si manquant
        if (empty($data['provider_id'])) {
            $booking = Booking::findOrFail($data['booking_id']);
            $data['provider_id'] = $booking->provider_id;
        }

        // Reference auto si absente
        if (empty($data['reference'])) {
            $data['reference'] = $this->generateReference();
        }

        // Calcul net_amount si non fourni
        if (!array_key_exists('net_amount', $data)) {
            $amount = (float) ($data['amount'] ?? 0);
            $fee    = (float) ($data['processor_fee'] ?? 0);
            $data['net_amount'] = max(0, $amount - $fee);
        }

        $payment = DB::transaction(fn() => Payment::create($data));

        return response()->success($payment->fresh(), 'Paiement créé', 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/payments/{payment}",
     *   tags={"Payments"},
     *   summary="Mettre à jour un paiement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     * @OA\Put(
     *   path="/api/payments/{payment}",
     *   tags={"Payments"},
     *   summary="Mettre à jour un paiement (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(Request $req, Payment $payment)
    {
        $data = $this->validatePayload($req, updating: true, payment: $payment);

        // Recalcul net_amount si amount/processor_fee changent et net_amount non fourni
        $affect = array_intersect(array_keys($data), ['amount','processor_fee']);
        if (!empty($affect) && !array_key_exists('net_amount', $data)) {
            $amount = (float)($data['amount'] ?? $payment->amount ?? 0);
            $fee    = (float)($data['processor_fee'] ?? $payment->processor_fee ?? 0);
            $data['net_amount'] = max(0, $amount - $fee);
        }

        DB::transaction(fn() => $payment->update($data));

        return response()->success($payment->fresh(), 'Paiement mis à jour');
    }

    /**
     * @OA\Delete(
     *   path="/api/payments/{payment}",
     *   tags={"Payments"},
     *   summary="Supprimer un paiement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->success(null, 'Paiement supprimé');
    }

    /**
     * @OA\Post(
     *   path="/api/payments/{payment}/authorize",
     *   tags={"Payments"},
     *   summary="Marquer comme autorisé",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Autorisé")
     * )
     */
    public function authorizePayment(Payment $payment)
    {
        $payment->markAuthorized();
        return response()->success($payment->fresh(), 'Paiement autorisé');
    }

    /**
     * @OA\Post(
     *   path="/api/payments/{payment}/capture",
     *   tags={"Payments"},
     *   summary="Capturer (succès) un paiement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object",
     *     @OA\Property(property="processor_fee", type="number", format="float", nullable=true),
     *     @OA\Property(property="external_id", type="string", nullable=true)
     *   )),
     *   @OA\Response(response=200, description="Capturé")
     * )
     */
    public function capture(Payment $payment, Request $req)
    {
        $data = $req->validate([
            'processor_fee' => ['sometimes','nullable','numeric','min:0'],
            'external_id'   => ['sometimes','nullable','string','max:255'],
        ]);

        if (array_key_exists('processor_fee', $data)) {
            $payment->processor_fee = $data['processor_fee'];
        }
        if (array_key_exists('external_id', $data)) {
            $payment->external_id = $data['external_id'];
        }

        // Maj net_amount avant succès
        $amount = (float)($payment->amount ?? 0);
        $fee    = (float)($payment->processor_fee ?? 0);
        $payment->net_amount = max(0, $amount - $fee);
        $payment->save();

        $payment->markSucceeded();

        return response()->success($payment->fresh(), 'Paiement capturé (succeeded)');
    }

    /**
     * @OA\Post(
     *   path="/api/payments/{payment}/fail",
     *   tags={"Payments"},
     *   summary="Marquer comme échoué",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object",
     *     @OA\Property(property="failure_code", type="string", nullable=true),
     *     @OA\Property(property="failure_message", type="string", nullable=true)
     *   )),
     *   @OA\Response(response=200, description="Échoué")
     * )
     */
    public function fail(Payment $payment, Request $req)
    {
        $data = $req->validate([
            'failure_code'    => ['sometimes','nullable','string','max:100'],
            'failure_message' => ['sometimes','nullable','string','max:255'],
        ]);

        $payment->markFailed($data['failure_code'] ?? null, $data['failure_message'] ?? null);

        return response()->success($payment->fresh(), 'Paiement marqué échoué');
    }

    /**
     * @OA\Post(
     *   path="/api/payments/{payment}/refund",
     *   tags={"Payments"},
     *   summary="Marquer comme remboursé",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Remboursé")
     * )
     */
    public function refund(Payment $payment)
    {
        $payment->markRefunded();
        return response()->success($payment->fresh(), 'Paiement remboursé');
    }

    /**
     * @OA\Post(
     *   path="/api/payments/{payment}/recompute-net",
     *   tags={"Payments"},
     *   summary="Recalculer le net_amount (amount - processor_fee)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function recomputeNet(Payment $payment)
    {
        $amount = (float)($payment->amount ?? 0);
        $fee    = (float)($payment->processor_fee ?? 0);
        $payment->update(['net_amount' => max(0, $amount - $fee)]);

        return response()->success($payment->fresh(), 'Net amount recalculé');
    }

    /* ===================== Helpers & Validation ===================== */

    private function validatePayload(Request $req, bool $updating = false, ?Payment $payment = null): array
    {
        $statusEnum = ['pending','authorized','succeeded','failed','refunded','cancelled'];
        $methodEnum = ['card','mobile_money','bank_transfer','cash','wallet'];

        $rules = [
            'booking_id'      => [$updating ? 'sometimes' : 'required','integer','exists:bookings,id'],
            'client_id'       => [$updating ? 'sometimes' : 'required','integer','exists:users,id'],
            'provider_id'     => [$updating ? 'sometimes' : 'nullable','integer','exists:users,id'],

            'amount'          => [$updating ? 'sometimes' : 'required','numeric','min:0'],
            'currency'        => [$updating ? 'sometimes' : 'nullable','string','size:3'],
            'processor_fee'   => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],
            'net_amount'      => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],

            'method'          => [$updating ? 'sometimes' : 'nullable', Rule::in($methodEnum)],
            'gateway'         => [$updating ? 'sometimes' : 'nullable','string','max:100'],

            'reference'       => [$updating ? 'sometimes' : 'nullable','string','max:255','unique:payments,reference' . ($payment ? ',' . $payment->id : '')],
            'idempotency_key' => [$updating ? 'sometimes' : 'nullable','string','max:255','unique:payments,idempotency_key' . ($payment ? ',' . $payment->id : '')],
            'external_id'     => [$updating ? 'sometimes' : 'nullable','string','max:255'],

            'status'          => [$updating ? 'sometimes' : 'nullable', Rule::in($statusEnum)],
            'authorized_at'   => [$updating ? 'sometimes' : 'nullable','date'],
            'captured_at'     => [$updating ? 'sometimes' : 'nullable','date'],
            'refunded_at'     => [$updating ? 'sometimes' : 'nullable','date'],

            'failure_code'    => [$updating ? 'sometimes' : 'nullable','string','max:100'],
            'failure_message' => [$updating ? 'sometimes' : 'nullable','string','max:255'],

            'payload'         => [$updating ? 'sometimes' : 'nullable','array'],
            'metadata'        => [$updating ? 'sometimes' : 'nullable','array'],
        ];

        return $req->validate($rules);
    }

    private function generateReference(): string
    {
        do {
            $ref = 'PMT-' . strtoupper(Str::random(6));
        } while (Payment::where('reference', $ref)->exists());
        return $ref;
    }
}
