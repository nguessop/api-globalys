<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingSlot;
use App\Models\MeetingNote;
use App\Models\Contract;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class MeetingController extends Controller
{
    public function __construct()
    {
        // Laisse index/show publics si tu veux, protège les autres
        $this->middleware('auth:api')->except(['index', 'show']);
    }

    /**
     * @OA\Get(
     *   path="/api/meetings",
     *   tags={"Meetings"},
     *   summary="Lister les meetings",
     *   description="Filtres, includes, tri et pagination.",
     *   @OA\Parameter(name="provider_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="client_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="sub_category_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="purpose", in="query", @OA\Schema(type="string", enum={"discovery","pre_contract","contract_assistance"})),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"proposed","scheduled","cancelled","completed"})),
     *   @OA\Parameter(name="from", in="query", description="Filtre sur selectedSlot.start_at ≥ (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="to", in="query", description="Filtre sur selectedSlot.start_at ≤ (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="include", in="query", description="CSV: selectedSlot,slots,notes,contracts,latestContract", @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", description="created_at|selected_at", @OA\Schema(type="string")),
     *   @OA\Parameter(name="dir", in="query", description="asc|desc", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=100)),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $req)
    {
        $with = $this->parseIncludes($req->get('include'));

        $q = Meeting::query()->with($with);

        // Filtres simples
        $q->when($req->filled('provider_id'),
            fn($w) => $w->where('provider_id', (int)$req->input('provider_id'))
        );
        $q->when($req->filled('client_id'),
            fn($w) => $w->where('client_id', (int)$req->input('client_id'))
        );
        $q->when($req->filled('sub_category_id'),
            fn($w) => $w->where('sub_category_id', (int)$req->input('sub_category_id'))
        );
        $q->when($req->filled('purpose'),
            fn($w) => $w->where('purpose', (string)$req->input('purpose'))
        );
        $q->when($req->filled('status'),
            fn($w) => $w->where('status', (string)$req->input('status'))
        );

        // Filtre de dates sur le slot sélectionné
        $from = $req->get('from');
        $to   = $req->get('to');
        if ($from || $to) {
            $q->whereHas('selectedSlot', function ($w) use ($from, $to) {
                if ($from) $w->whereDate('start_at', '>=', $from);
                if ($to)   $w->whereDate('start_at', '<=', $to);
            });
            // Tri par date de slot sélectionné
            $q->orderBy(
                MeetingSlot::query()
                    ->select('start_at')
                    ->whereColumn('meeting_slots.id', 'meetings.selected_slot_id')
                    ->limit(1),
                strtolower($req->get('dir','asc')) === 'desc' ? 'desc' : 'asc'
            );
        } else {
            $sort = $req->get('sort', 'created_at');
            $dir  = strtolower($req->get('dir','desc')) === 'asc' ? 'asc' : 'desc';
            if (!in_array($sort, ['created_at','selected_at'], true)) $sort = 'created_at';
            if ($sort === 'selected_at') {
                $q->orderBy(
                    MeetingSlot::query()
                        ->select('start_at')
                        ->whereColumn('meeting_slots.id', 'meetings.selected_slot_id')
                        ->limit(1),
                    $dir
                );
            } else {
                $q->orderBy('created_at', $dir);
            }
        }

        // Pagination
        $perPage = max(1, min((int)$req->input('per_page', 15), 100));
        $items = $q->paginate($perPage);

        return response()->success($items, 'Meetings récupérés');
    }

    /**
     * @OA\Get(
     *   path="/api/meetings/{meeting}",
     *   tags={"Meetings"},
     *   summary="Afficher un meeting",
     *   @OA\Parameter(name="meeting", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="include", in="query", description="CSV: selectedSlot,slots,notes,contracts,latestContract", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Request $req, Meeting $meeting)
    {
        $with = $this->parseIncludes($req->get('include'));
        if (!empty($with)) {
            $meeting->load($with);
        }

        return response()->success($meeting, 'Meeting récupéré');
    }

    /**
     * @OA\Post(
     *   path="/api/meetings",
     *   tags={"Meetings"},
     *   summary="Créer un meeting",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=201, description="Créé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $req)
    {
        $data = $req->validate([
            'sub_category_id'  => ['required','integer','exists:sub_categories,id'],
            'provider_id'      => ['required','integer','exists:users,id'],
            'client_id'        => ['required','integer','exists:users,id'],
            'subject'          => ['nullable','string','max:255'],
            'purpose'          => ['required', Rule::in(['discovery','pre_contract','contract_assistance'])],
            'location_type'    => ['nullable','string','max:50'],
            'location'         => ['nullable','string','max:255'],
            'timezone'         => ['nullable','string','max:100'],
            'duration_minutes' => ['nullable','integer','min:1'],
            'status'           => ['nullable', Rule::in(['proposed','scheduled','cancelled','completed'])],

            // Slots optionnels à créer avec le meeting
            'slots'            => ['sometimes','array'],
            'slots.*.start_at' => ['required_with:slots','date'],
            'slots.*.end_at'   => ['required_with:slots','date','after:slots.*.start_at'],

            // Sélection directe d’un slot existant (après création de slots)
            'selected_slot_id' => ['nullable','integer'],
        ]);

        $meeting = DB::transaction(function () use ($data) {
            $slots = $data['slots'] ?? null;
            unset($data['slots']);

            // statut par défaut
            $data['status'] = $data['status'] ?? Meeting::STATUS_PROPOSED;

            /** @var Meeting $m */
            $m = Meeting::create($data);

            // Création éventuelle des slots
            if (is_array($slots) && count($slots) > 0) {
                foreach ($slots as $s) {
                    $m->slots()->create([
                        'start_at' => $s['start_at'],
                        'end_at'   => $s['end_at'],
                    ]);
                }
            }

            // Sélection éventuelle d’un slot
            if (!empty($data['selected_slot_id'])) {
                $slotId = (int)$data['selected_slot_id'];
                if ($m->slots()->where('id', $slotId)->exists()) {
                    $m->selected_slot_id = $slotId;
                    $m->status = Meeting::STATUS_SCHEDULED;
                    $m->save();
                }
            }

            return $m->fresh();
        });

        return response()->success($meeting, 'Meeting créé', 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/meetings/{meeting}",
     *   tags={"Meetings"},
     *   summary="Mettre à jour un meeting",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="meeting", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     * @OA\Put(
     *   path="/api/meetings/{meeting}",
     *   tags={"Meetings"},
     *   summary="Mettre à jour un meeting (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="meeting", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $req, Meeting $meeting)
    {
        $data = $req->validate([
            'sub_category_id'  => ['sometimes','integer','exists:sub_categories,id'],
            'provider_id'      => ['sometimes','integer','exists:users,id'],
            'client_id'        => ['sometimes','integer','exists:users,id'],
            'subject'          => ['sometimes','nullable','string','max:255'],
            'purpose'          => ['sometimes', Rule::in(['discovery','pre_contract','contract_assistance'])],
            'location_type'    => ['sometimes','nullable','string','max:50'],
            'location'         => ['sometimes','nullable','string','max:255'],
            'timezone'         => ['sometimes','nullable','string','max:100'],
            'duration_minutes' => ['sometimes','nullable','integer','min:1'],
            'status'           => ['sometimes', Rule::in(['proposed','scheduled','cancelled','completed'])],
            'selected_slot_id' => ['sometimes','nullable','integer'],
        ]);

        DB::transaction(function () use ($meeting, $data) {
            // Si on demande à sélectionner un slot, vérifie qu'il appartient au meeting
            if (array_key_exists('selected_slot_id', $data) && !empty($data['selected_slot_id'])) {
                $slotId = (int)$data['selected_slot_id'];
                if ($meeting->slots()->where('id', $slotId)->exists()) {
                    $meeting->selected_slot_id = $slotId;
                    // si un slot est sélectionné, statut = scheduled (sauf si explicitement autre chose)
                    if (empty($data['status'])) {
                        $meeting->status = Meeting::STATUS_SCHEDULED;
                    }
                } else {
                    // ignorer un slot invalide
                    unset($data['selected_slot_id']);
                }
            }

            $meeting->fill($data)->save();
        });

        return response()->success($meeting->fresh(), 'Meeting mis à jour');
    }

    /**
     * @OA\Delete(
     *   path="/api/meetings/{meeting}",
     *   tags={"Meetings"},
     *   summary="Supprimer un meeting",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="meeting", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy(Meeting $meeting)
    {
        $meeting->delete();
        return response()->success(null, 'Meeting supprimé');
    }

    /**
     * @OA\Post(
     *   path="/api/meetings/{meeting}/slots",
     *   tags={"Meetings"},
     *   summary="Ajouter des slots à un meeting",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="meeting", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"slots"},
     *       @OA\Property(property="slots", type="array",
     *         @OA\Items(type="object",
     *           required={"start_at","end_at"},
     *           @OA\Property(property="start_at", type="string", format="date-time"),
     *           @OA\Property(property="end_at", type="string", format="date-time")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function addSlots(Request $req, Meeting $meeting)
    {
        $data = $req->validate([
            'slots'            => ['required','array','min:1'],
            'slots.*.start_at' => ['required','date'],
            'slots.*.end_at'   => ['required','date','after:slots.*.start_at'],
        ]);

        DB::transaction(function () use ($meeting, $data) {
            foreach ($data['slots'] as $s) {
                $meeting->slots()->create([
                    'start_at' => $s['start_at'],
                    'end_at'   => $s['end_at'],
                ]);
            }
        });

        return response()->success($meeting->load('slots'), 'Slots ajoutés');
    }

    /**
     * @OA\Post(
     *   path="/api/meetings/{meeting}/select-slot",
     *   tags={"Meetings"},
     *   summary="Sélectionner le slot retenu",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="meeting", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"slot_id"},
     *       @OA\Property(property="slot_id", type="integer", example=123)
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function selectSlot(Request $req, Meeting $meeting)
    {
        $data = $req->validate([
            'slot_id' => ['required','integer'],
        ]);

        $slotId = (int)$data['slot_id'];

        if (!$meeting->slots()->where('id', $slotId)->exists()) {
            return response()->error("Ce slot n'appartient pas au meeting.", 422);
        }

        $meeting->selected_slot_id = $slotId;
        if ($meeting->status !== Meeting::STATUS_CANCELLED && $meeting->status !== Meeting::STATUS_COMPLETED) {
            $meeting->status = Meeting::STATUS_SCHEDULED;
        }
        $meeting->save();

        return response()->success($meeting->load('selectedSlot'), 'Slot sélectionné');
    }

    /* ============================================================
     |                       Helpers privés
     ============================================================ */

    private function parseIncludes(?string $include): array
    {
        $allowed = ['selectedSlot','slots','notes','contracts','latestContract'];
        if (!$include) return ['selectedSlot'];
        $parts = array_filter(array_map('trim', explode(',', $include)));
        $safe  = array_values(array_intersect($parts, $allowed));
        // Toujours renvoyer selectedSlot si le tri/filtre s’appuie dessus
        return array_unique(array_merge(['selectedSlot'], $safe));
    }
}
