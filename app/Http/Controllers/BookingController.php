<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServiceOffering;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Str;

class BookingController extends Controller
{
    public function __construct()
    {
        // Protège la création/modif/suppression, laisse index/show publics si tu veux
        $this->middleware('auth:api')->except(['index', 'show']);
    }

    /**
     * GET /api/bookings
     * Liste + recherche/filtre + tri + pagination
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
     * GET /api/bookings/{booking}
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
     * POST /api/bookings
     * Créer une réservation
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
     * PUT/PATCH /api/bookings/{booking}
     * Mettre à jour (recalcule les totaux si prix/quantité/remises changent)
     */
    public function update(Request $req, Booking $booking)
    {
        $validated = $this->validatePayload($req, updating: true);

        // Si des champs financiers changent, on recalcule
        $fieldsAffectTotals = ['quantity','unit_price','subtotal','tax_rate','tax_amount','discount_amount'];
        if (count(array_intersect(array_keys($validated), $fieldsAffectTotals)) > 0) {
            // On force le recalcul depuis quantity/unit_price/discount/tax_rate
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
     * DELETE /api/bookings/{booking}
     * Suppression soft delete
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->success(null, 'Réservation supprimée');
    }

    /**
     * POST /api/bookings/{booking}/confirm
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
     * POST /api/bookings/{booking}/start
     * (optionnel si tu gères "in_progress")
     */
    public function start(Booking $booking)
    {
        $booking->update(['status' => 'in_progress']);
        return response()->success($booking, 'Prestation démarrée');
    }

    /**
     * POST /api/bookings/{booking}/complete
     */
    public function complete(Booking $booking)
    {
        $booking->update(['status' => 'completed']);
        return response()->success($booking, 'Prestation terminée');
    }

    /**
     * POST /api/bookings/{booking}/cancel
     * Body: { "reason": "Client indisponible" }
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
     * POST /api/bookings/{booking}/payment-status
     * Body: { "payment_status": "paid" }
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
     * POST /api/bookings/{booking}/recompute
     * Recalcule les totaux depuis quantity, unit_price, tax_rate, discount_amount
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
