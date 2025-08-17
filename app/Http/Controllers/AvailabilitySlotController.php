<?php

namespace App\Http\Controllers;

use App\Models\AvailabilitySlot;
use App\Models\ServiceOffering;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

class AvailabilitySlotController extends Controller
{
    public function __construct()
    {
        // Lecture publique; mutations protégées
        $this->middleware('auth:api')->except(['index','show']);
    }

    /**
     * @OA\Get(
     *   path="/api/availability-slots",
     *   tags={"AvailabilitySlots"},
     *   summary="Lister les créneaux",
     *   description="Filtres: service_offering_id, provider_id, status, available=1, upcoming=1, from, to. Tri & pagination.",
     *   @OA\Parameter(name="service_offering_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="provider_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"available","full","blocked","cancelled"})),
     *   @OA\Parameter(name="available", in="query", description="1 = uniquement réservable", @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="upcoming", in="query", description="1 = seulement futurs", @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="from", in="query", @OA\Schema(type="string", format="date-time")),
     *   @OA\Parameter(name="to", in="query", @OA\Schema(type="string", format="date-time")),
     *   @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"start_at","end_at","capacity","booked_count","price_override","created_at"})),
     *   @OA\Parameter(name="dir", in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="string", example="15 ou 'all'")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $req)
    {
        $q = AvailabilitySlot::query()
            ->with([
                'serviceOffering:id,title,provider_id',
                'provider:id,first_name,last_name,company_name',
            ]);

        if ($req->filled('service_offering_id')) $q->forService((int) $req->input('service_offering_id'));
        if ($req->filled('provider_id'))         $q->forProvider((int) $req->input('provider_id'));
        if ($req->filled('status'))              $q->status((string) $req->input('status'));
        if ((int) $req->input('available', 0) === 1) $q->available();

        $from = $req->input('from') ? Carbon::parse($req->input('from')) : null;
        $to   = $req->input('to')   ? Carbon::parse($req->input('to'))   : null;
        if ($from || $to) $q->between($from, $to);

        if ((int) $req->input('upcoming', 0) === 1) {
            $q->upcoming();
        } else {
            $sort = (string) $req->input('sort', 'start_at');
            $dir  = strtolower((string) $req->input('dir','asc')) === 'desc' ? 'desc' : 'asc';
            $allowedSorts = ['start_at','end_at','capacity','booked_count','price_override','created_at'];
            if (!in_array($sort, $allowedSorts, true)) $sort = 'start_at';
            $q->orderBy($sort, $dir);
        }

        if ($req->input('per_page') === 'all') {
            $data = $q->get();
        } else {
            $perPage = max(1, min((int) $req->input('per_page', 15), 200));
            $data = $q->paginate($perPage);
        }

        return response()->success($data, 'Créneaux récupérés');
    }

    /**
     * @OA\Get(
     *   path="/api/availability-slots/{availabilitySlot}",
     *   tags={"AvailabilitySlots"},
     *   summary="Afficher un créneau",
     *   @OA\Parameter(name="availabilitySlot", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(AvailabilitySlot $availabilitySlot)
    {
        $availabilitySlot->load([
            'serviceOffering:id,title,provider_id',
            'provider:id,first_name,last_name,company_name',
            'parent:id,start_at,end_at,status',
        ]);

        return response()->success($availabilitySlot, 'Détails du créneau');
    }

    /**
     * @OA\Post(
     *   path="/api/availability-slots",
     *   tags={"AvailabilitySlots"},
     *   summary="Créer un créneau",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"service_offering_id","start_at","end_at","capacity"},
     *         @OA\Property(property="service_offering_id", type="integer", example=10),
     *         @OA\Property(property="provider_id", type="integer", nullable=true, example=5),
     *         @OA\Property(property="start_at", type="string", format="date-time", example="2025-08-20T10:00:00Z"),
     *         @OA\Property(property="end_at", type="string", format="date-time", example="2025-08-20T11:00:00Z"),
     *         @OA\Property(property="timezone", type="string", nullable=true, example="Africa/Douala"),
     *         @OA\Property(property="capacity", type="integer", example=3),
     *         @OA\Property(property="booked_count", type="integer", nullable=true, example=0),
     *         @OA\Property(property="price_override", type="number", format="float", nullable=true, example=15000),
     *         @OA\Property(property="currency", type="string", nullable=true, example="XAF"),
     *         @OA\Property(property="is_recurring", type="boolean", nullable=true, example=false),
     *         @OA\Property(property="recurrence_rule", type="string", nullable=true, example="RRULE:FREQ=WEEKLY;BYDAY=MO,WE"),
     *         @OA\Property(property="status", type="string", nullable=true, enum={"available","full","blocked","cancelled"}),
     *         @OA\Property(property="notes", type="string", nullable=true),
     *         @OA\Property(property="parent_id", type="integer", nullable=true)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=201, description="Créé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $req)
    {
        $data = $req->validate([
            'service_offering_id' => ['required','integer','exists:service_offerings,id'],
            'provider_id'         => ['nullable','integer','exists:users,id'],
            'start_at'            => ['required','date'],
            'end_at'              => ['required','date','after:start_at'],
            'timezone'            => ['nullable','string','max:64'],
            'capacity'            => ['required','integer','min:1'],
            'booked_count'        => ['nullable','integer','min:0'],
            'price_override'      => ['nullable','numeric','min:0'],
            'currency'            => ['nullable','string','size:3'],
            'is_recurring'        => ['nullable','boolean'],
            'recurrence_rule'     => ['nullable','string','max:255'],
            'status'              => ['nullable', Rule::in(['available','full','blocked','cancelled'])],
            'notes'               => ['nullable','string'],
            'parent_id'           => ['nullable','integer','exists:availability_slots,id'],
        ]);

        if (empty($data['provider_id'])) {
            $service = ServiceOffering::select('id','provider_id')->findOrFail($data['service_offering_id']);
            $data['provider_id'] = $service->provider_id;
        }

        $data['status']       = $data['status'] ?? 'available';
        $data['booked_count'] = $data['booked_count'] ?? 0;
        $data['currency']     = !empty($data['currency']) ? strtoupper($data['currency']) : 'XAF';

        if ((int)$data['booked_count'] >= (int)$data['capacity']) {
            $data['status'] = 'full';
        }

        $slot = AvailabilitySlot::create($data)->fresh();

        return response()->success($slot, 'Créneau créé', 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/availability-slots/{availabilitySlot}",
     *   tags={"AvailabilitySlots"},
     *   summary="Mettre à jour un créneau",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="availabilitySlot", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="object")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     * @OA\Put(
     *   path="/api/availability-slots/{availabilitySlot}",
     *   tags={"AvailabilitySlots"},
     *   summary="Mettre à jour un créneau (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="availabilitySlot", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="object")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $req, AvailabilitySlot $availabilitySlot)
    {
        $data = $req->validate([
            'service_offering_id' => ['sometimes','integer','exists:service_offerings,id'],
            'provider_id'         => ['sometimes','nullable','integer','exists:users,id'],
            'start_at'            => ['sometimes','date'],
            'end_at'              => ['sometimes','date','after:start_at'],
            'timezone'            => ['sometimes','nullable','string','max:64'],
            'capacity'            => ['sometimes','integer','min:1'],
            'booked_count'        => ['sometimes','integer','min:0'],
            'price_override'      => ['sometimes','nullable','numeric','min:0'],
            'currency'            => ['sometimes','nullable','string','size:3'],
            'is_recurring'        => ['sometimes','boolean'],
            'recurrence_rule'     => ['sometimes','nullable','string','max:255'],
            'status'              => ['sometimes', Rule::in(['available','full','blocked','cancelled'])],
            'notes'               => ['sometimes','nullable','string'],
            'parent_id'           => ['sometimes','nullable','integer','exists:availability_slots,id'],
        ]);

        if (array_key_exists('currency', $data) && !empty($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        $capacity    = array_key_exists('capacity', $data) ? (int)$data['capacity'] : (int)$availabilitySlot->capacity;
        $bookedCount = array_key_exists('booked_count', $data) ? (int)$data['booked_count'] : (int)$availabilitySlot->booked_count;

        if (!array_key_exists('status', $data)) {
            if ($bookedCount >= $capacity) {
                $data['status'] = 'full';
            } elseif ($availabilitySlot->status === 'full' && $bookedCount < $capacity) {
                $data['status'] = 'available';
            }
        }

        $availabilitySlot->update($data);

        return response()->success($availabilitySlot->fresh(), 'Créneau mis à jour');
    }

    /**
     * @OA\Delete(
     *   path="/api/availability-slots/{availabilitySlot}",
     *   tags={"AvailabilitySlots"},
     *   summary="Supprimer un créneau",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="availabilitySlot", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé")
     * )
     */
    public function destroy(AvailabilitySlot $availabilitySlot)
    {
        $availabilitySlot->delete();
        return response()->success(null, 'Créneau supprimé');
    }

    /**
     * @OA\Post(
     *   path="/api/availability-slots/{availabilitySlot}/book",
     *   tags={"AvailabilitySlots"},
     *   summary="Réserver de la capacité sur un créneau",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="availabilitySlot", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=false,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="object", @OA\Property(property="qty", type="integer", example=1))
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function book(AvailabilitySlot $availabilitySlot, Request $req)
    {
        $data = $req->validate(['qty' => ['nullable','integer','min:1']]);
        $qty = (int)($data['qty'] ?? 1);

        if (!$availabilitySlot->isBookable()) {
            return response()->error("Ce créneau n'est pas réservable.", 422);
        }

        $remaining = $availabilitySlot->remainingCapacity();
        if ($qty > $remaining) {
            return response()->error("Capacité insuffisante. Restant: {$remaining}", 422);
        }

        $availabilitySlot->incrementBooked($qty);

        return response()->success($availabilitySlot->fresh(), 'Réservation enregistrée sur le créneau');
    }

    /**
     * @OA\Post(
     *   path="/api/availability-slots/{availabilitySlot}/unbook",
     *   tags={"AvailabilitySlots"},
     *   summary="Retirer de la capacité réservée",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="availabilitySlot", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=false,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="object", @OA\Property(property="qty", type="integer", example=1))
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function unbook(AvailabilitySlot $availabilitySlot, Request $req)
    {
        $data = $req->validate(['qty' => ['nullable','integer','min:1']]);
        $qty = (int)($data['qty'] ?? 1);

        if ((int)$availabilitySlot->booked_count <= 0) {
            return response()->error("Aucune réservation à retirer sur ce créneau.", 422);
        }

        $availabilitySlot->decrementBooked($qty);

        return response()->success($availabilitySlot->fresh(), 'Réservation retirée du créneau');
    }

    /**
     * @OA\Post(
     *   path="/api/availability-slots/{availabilitySlot}/status",
     *   tags={"AvailabilitySlots"},
     *   summary="Changer le statut d'un créneau",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="availabilitySlot", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"status"},
     *         @OA\Property(property="status", type="string", enum={"available","full","blocked","cancelled"}),
     *         @OA\Property(property="notes", type="string", nullable=true)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function setStatus(AvailabilitySlot $availabilitySlot, Request $req)
    {
        $data = $req->validate([
            'status' => ['required', Rule::in(['available','full','blocked','cancelled'])],
            'notes'  => ['nullable','string'],
        ]);

        $availabilitySlot->update($data);

        return response()->success($availabilitySlot->fresh(), 'Statut du créneau mis à jour');
    }
}
