<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServiceOffering;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class BookingController extends Controller
{
    public function __construct()
    {
        // Protège la création/modif/suppression, laisse index/show publics si tu veux
        $this->middleware('auth:api')->except(['index', 'show']);
    }

    /**
     * @OA\Get(
     *   path="/api/bookings",
     *   tags={"Bookings"},
     *   summary="Lister les réservations",
     *   description="Recherche, filtres, tri et pagination.",
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending","confirmed","in_progress","completed","cancelled"})),
     *   @OA\Parameter(name="payment_status", in="query", @OA\Schema(type="string", enum={"unpaid","paid","refunded","partial"})),
     *   @OA\Parameter(name="client_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="provider_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="service_offering_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="from", in="query", description="Date début (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="to", in="query", description="Date fin (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="q", in="query", description="Recherche (code, city, address)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="amount_min", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="amount_max", in="query", @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="sort", in="query", description="created_at|start_at|end_at|total_amount|status|payment_status|code", @OA\Schema(type="string")),
     *   @OA\Parameter(name="dir", in="query", description="asc|desc", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=100)),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="OK"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="per_page", type="integer", example=15),
     *         @OA\Property(property="total", type="integer", example=120),
     *         @OA\Property(property="last_page", type="integer", example=8),
     *         @OA\Property(
     *           property="data",
     *           type="array",
     *           @OA\Items(type="object",
     *             example={
     *               "id":101,"code":"BK-ABC123","status":"pending","payment_status":"unpaid",
     *               "total_amount":45000,"currency":"XAF","start_at":"2025-08-20 09:00:00"
     *             }
     *           )
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $req)
    {
        $q = Booking::query()
            ->with([
                'client:id,first_name,last_name,email',
                'provider:id,first_name,last_name,company_name',
                'serviceOffering:id,title,price_amount,price_unit,currency,provider_id',
            ]);

        // Filtres
        $q->when($req->filled('status'),
            fn($w) => $w->where('status', (string)$req->input('status'))
        );

        $q->when($req->filled('payment_status'),
            fn($w) => $w->where('payment_status', (string)$req->input('payment_status'))
        );

        $q->when($req->filled('client_id'),
            fn($w) => $w->where('client_id', (int)$req->input('client_id'))
        );

        $q->when($req->filled('provider_id'),
            fn($w) => $w->where('provider_id', (int)$req->input('provider_id'))
        );

        $q->when($req->filled('service_offering_id'),
            fn($w) => $w->where('service_offering_id', (int)$req->input('service_offering_id'))
        );

        // Intervalle de dates (sur start_at)
        $from = $req->date('from');
        $to   = $req->date('to');
        if ($from && $to) {
            $q->whereBetween('start_at', [$from, $to]);
        } elseif ($from) {
            $q->where('start_at', '>=', $from);
        } elseif ($to) {
            $q->where('start_at', '<=', $to);
        }

        // Recherche simple (code + address + city)
        if ($req->filled('q')) {
            $term = trim((string)$req->input('q'));
            $q->where(function ($w) use ($term) {
                $w->where('code', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });
        }

        // Plage de montant
        if ($req->filled('amount_min') && is_numeric($req->input('amount_min'))) {
            $q->where('total_amount', '>=', (float)$req->input('amount_min'));
        }
        if ($req->filled('amount_max') && is_numeric($req->input('amount_max'))) {
            $q->where('total_amount', '<=', (float)$req->input('amount_max'));
        }

        // Tri
        $sort = (string) $req->input('sort', 'created_at');
        $dir  = strtolower((string) $req->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at','start_at','end_at','total_amount','status','payment_status','code'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';
        $q->orderBy($sort, $dir);

        // Pagination
        $perPage = max(1, min((int)$req->input('per_page', 15), 100));
        $data = $q->paginate($perPage);

        return response()->success($data);
    }

    /**
     * @OA\Get(
     *   path="/api/bookings/{booking}",
     *   tags={"Bookings"},
     *   summary="Détail d'une réservation",
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="OK"),
     *       @OA\Property(property="data", type="object",
     *         example={"id":101,"code":"BK-ABC123","status":"confirmed","payment_status":"paid"}
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Booking $booking)
    {
        $booking->load([
            'client:id,first_name,last_name,email,phone',
            'provider:id,first_name,last_name,company_name,phone',
            'serviceOffering:id,title,price_amount,price_unit,currency,provider_id',
        ]);

        return response()->success($booking);
    }

    /**
     * @OA\Post(
     *   path="/api/bookings",
     *   tags={"Bookings"},
     *   summary="Créer une réservation",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"service_offering_id","client_id","quantity","unit_price"},
     *       @OA\Property(property="service_offering_id", type="integer", example=10),
     *       @OA\Property(property="client_id", type="integer", example=25),
     *       @OA\Property(property="provider_id", type="integer", nullable=true, example=5),
     *       @OA\Property(property="quantity", type="number", format="float", example=2),
     *       @OA\Property(property="unit_price", type="number", format="float", example=15000),
     *       @OA\Property(property="tax_rate", type="number", format="float", example=19.25),
     *       @OA\Property(property="discount_amount", type="number", format="float", example=0),
     *       @OA\Property(property="currency", type="string", example="XAF"),
     *       @OA\Property(property="start_at", type="string", format="date-time", example="2025-08-20 09:00:00"),
     *       @OA\Property(property="end_at", type="string", format="date-time", example="2025-08-20 10:00:00"),
     *       @OA\Property(property="city", type="string", example="Douala"),
     *       @OA\Property(property="address", type="string", example="Bonapriso, Rue X")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Créé",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Réservation créée"),
     *       @OA\Property(property="data", type="object",
     *         example={"id":120,"code":"BK-7UQZ9K","status":"pending","payment_status":"unpaid"}
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

        // Si code non fourni, générer un code unique
        $validated['code'] = $validated['code'] ?? $this->generateCode();

        // Sécurité : recalculer les montants côté serveur
        $this->computeTotals($validated);

        // Cohérence: provider = provider du service si non fourni
        if (empty($validated['provider_id'])) {
            $service = ServiceOffering::findOrFail($validated['service_offering_id']);
            $validated['provider_id'] = $service->provider_id;
        }

        // Statut initial par défaut
        $validated['status'] = $validated['status'] ?? 'pending';
        $validated['payment_status'] = $validated['payment_status'] ?? 'unpaid';
        $validated['currency'] = $validated['currency'] ?? 'XAF';

        $booking = DB::transaction(fn() => Booking::create($validated));

        return response()->success($booking->fresh(), 'Réservation créée', 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/bookings/{booking}",
     *   tags={"Bookings"},
     *   summary="Mettre à jour une réservation",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     * @OA\Put(
     *   path="/api/bookings/{booking}",
     *   tags={"Bookings"},
     *   summary="Mettre à jour une réservation (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(Request $req, Booking $booking)
    {
        $validated = $this->validatePayload($req, updating: true);

        // Si des champs financiers changent, on recalcule
        $fieldsAffectTotals = ['quantity','unit_price','subtotal','tax_rate','tax_amount','discount_amount'];
        if (count(array_intersect(array_keys($validated), $fieldsAffectTotals)) > 0) {
            $payload = array_merge($booking->toArray(), $validated);
            $this->computeTotals($payload);
            $validated['subtotal']        = $payload['subtotal'];
            $validated['tax_amount']      = $payload['tax_amount'];
            $validated['total_amount']    = $payload['total_amount'];
            $validated['total_price']     = $payload['total_price'];
        }

        DB::transaction(fn() => $booking->update($validated));

        return response()->success($booking->fresh(), 'Réservation mise à jour');
    }

    /**
     * @OA\Delete(
     *   path="/api/bookings/{booking}",
     *   tags={"Bookings"},
     *   summary="Supprimer une réservation",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->success(null, 'Réservation supprimée');
    }

    /**
     * @OA\Post(
     *   path="/api/bookings/{booking}/confirm",
     *   tags={"Bookings"},
     *   summary="Confirmer une réservation",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Confirmée")
     * )
     */
    public function confirm(Booking $booking)
    {
        if (!in_array($booking->status, ['pending','in_progress'], true)) {
            $booking->status = 'confirmed';
            $booking->save();
            return response()->success($booking, 'Réservation confirmée');
        }
        $booking->update(['status' => 'confirmed']);
        return response()->success($booking, 'Réservation confirmée');
    }

    /**
     * @OA\Post(
     *   path="/api/bookings/{booking}/start",
     *   tags={"Bookings"},
     *   summary="Démarrer la prestation",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Démarrée")
     * )
     */
    public function start(Booking $booking)
    {
        $booking->update(['status' => 'in_progress']);
        return response()->success($booking, 'Prestation démarrée');
    }

    /**
     * @OA\Post(
     *   path="/api/bookings/{booking}/complete",
     *   tags={"Bookings"},
     *   summary="Terminer la prestation",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Terminée")
     * )
     */
    public function complete(Booking $booking)
    {
        $booking->update(['status' => 'completed']);
        return response()->success($booking, 'Prestation terminée');
    }

    /**
     * @OA\Post(
     *   path="/api/bookings/{booking}/cancel",
     *   tags={"Bookings"},
     *   summary="Annuler une réservation",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(type="object", @OA\Property(property="reason", type="string", nullable=true))),
     *   @OA\Response(response=200, description="Annulée")
     * )
     */
    public function cancel(Booking $booking, Request $req)
    {
        $data = $req->validate([
            'reason' => ['nullable','string','max:255'],
        ]);

        $booking->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $data['reason'] ?? null,
            'cancelled_at'        => now(),
        ]);

        return response()->success($booking, 'Réservation annulée');
    }

    /**
     * @OA\Post(
     *   path="/api/bookings/{booking}/payment-status",
     *   tags={"Bookings"},
     *   summary="Modifier le statut de paiement",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(type="object",
     *       required={"payment_status"},
     *       @OA\Property(property="payment_status", type="string", enum={"unpaid","paid","refunded","partial"}, example="paid")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function setPaymentStatus(Booking $booking, Request $req)
    {
        $data = $req->validate([
            'payment_status' => ['required', Rule::in(['unpaid','paid','refunded','partial'])],
        ]);

        $booking->update(['payment_status' => $data['payment_status']]);
        return response()->success($booking, 'Statut de paiement mis à jour');
    }

    /**
     * @OA\Post(
     *   path="/api/bookings/{booking}/recompute",
     *   tags={"Bookings"},
     *   summary="Recalculer les montants",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="booking", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function recompute(Booking $booking)
    {
        $payload = $booking->toArray();
        $this->computeTotals($payload);

        $booking->update([
            'subtotal'     => $payload['subtotal'],
            'tax_amount'   => $payload['tax_amount'],
            'total_amount' => $payload['total_amount'],
            'total_price'  => $payload['total_price'],
        ]);

        return response()->success($booking->fresh(), 'Montants recalculés');
    }

    /**
     * Validation centralisée
     */
    private function validatePayload(Request $req, bool $updating = false): array
    {
        $rules = [
            'service_offering_id' => [$updating ? 'sometimes' : 'required','integer','exists:service_offerings,id'],
            'client_id'           => [$updating ? 'sometimes' : 'required','integer','exists:users,id'],
            'provider_id'         => [$updating ? 'sometimes' : 'nullable','integer','exists:users,id'],

            'quantity'            => [$updating ? 'sometimes' : 'required','numeric','min:1'],
            'unit_price'          => [$updating ? 'sometimes' : 'required','numeric','min:0'],
            'total_price'         => [$updating ? 'sometimes' : 'nullable','numeric','min:0'], // sera recalculé
            'currency'            => [$updating ? 'sometimes' : 'nullable','string','max:10'],

            'subtotal'            => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],
            'tax_rate'            => [$updating ? 'sometimes' : 'nullable','numeric','min:0','max:100'],
            'tax_amount'          => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],
            'discount_amount'     => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],
            'total_amount'        => [$updating ? 'sometimes' : 'nullable','numeric','min:0'],

            'status'              => [$updating ? 'sometimes' : 'nullable', Rule::in(['pending','confirmed','in_progress','completed','cancelled'])],
            'payment_status'      => [$updating ? 'sometimes' : 'nullable', Rule::in(['unpaid','paid','refunded','partial'])],

            'notes_client'        => [$updating ? 'sometimes' : 'nullable','string'],
            'notes_provider'      => [$updating ? 'sometimes' : 'nullable','string'],
            'cancellation_reason' => [$updating ? 'sometimes' : 'nullable','string','max:255'],

            'code'                => [$updating ? 'sometimes' : 'nullable','string','max:255','unique:bookings,code'],
            'start_at'            => [$updating ? 'sometimes' : 'nullable','date'],
            'end_at'              => [$updating ? 'sometimes' : 'nullable','date','after_or_equal:start_at'],
            'city'                => [$updating ? 'sometimes' : 'nullable','string','max:255'],
            'address'             => [$updating ? 'sometimes' : 'nullable','string','max:255'],
        ];

        // Pour update, autoriser de garder le même code
        if ($updating && $req->route('booking')) {
            $rules['code'] = ['sometimes','nullable','string','max:255','unique:bookings,code,' . $req->route('booking')->id];
        }

        return $req->validate($rules);
    }

    /**
     * Recalcule subtotal, tax_amount, total_amount, total_price
     */
    private function computeTotals(array &$payload): void
    {
        $qty   = (float)($payload['quantity'] ?? 1);
        $unit  = (float)($payload['unit_price'] ?? 0);
        $disc  = (float)($payload['discount_amount'] ?? 0);
        $trate = (float)($payload['tax_rate'] ?? 0);

        $subtotal = max(0, $qty * $unit - $disc);
        $tax      = round($subtotal * ($trate / 100), 2);
        $total    = $subtotal + $tax;

        $payload['subtotal']     = round($subtotal, 2);
        $payload['tax_amount']   = $tax;
        $payload['total_amount'] = round($total, 2);
        // total_price => historisation prix (si tu la gardes distincte)
        $payload['total_price']  = $payload['total_amount'];
    }

    /**
     * Génération d’un code unique lisible
     */
    private function generateCode(): string
    {
        do {
            $code = 'BK-' . strtoupper(Str::random(6));
        } while (Booking::where('code', $code)->exists());

        return $code;
    }
}
