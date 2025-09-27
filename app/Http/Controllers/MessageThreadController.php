<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\ServiceOffering;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Tag(
 *   name="Messaging",
 *   description="Messagerie (threads & messages) protégée par JWT"
 * )
 *
 * @OA\Schema(
 *   schema="Thread",
 *   type="object",
 *   required={"id","service_offering_id","customer_id","provider_id"},
 *   @OA\Property(property="id", type="string", example="1"),
 *   @OA\Property(property="service_offering_id", type="integer", example=42),
 *   @OA\Property(property="customer_id", type="integer", example=15),
 *   @OA\Property(property="provider_id", type="integer", example=99),
 *   @OA\Property(property="last_message_at", type="string", format="date-time", nullable=true, example="2025-01-01T12:00:00Z")
 * )
 *
 * @OA\Schema(
 *   schema="ThreadResponse",
 *   type="object",
 *   @OA\Property(property="data", ref="#/components/schemas/Thread")
 * )
 *
 * @OA\Schema(
 *   schema="MessageItem",
 *   type="object",
 *   required={"id","body","created_at","sender_id"},
 *   @OA\Property(property="id", type="string", example="10"),
 *   @OA\Property(property="body", type="string", example="Bonjour 👋"),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-01T12:34:56Z"),
 *   @OA\Property(property="sender_id", type="integer", example=15)
 * )
 *
 * @OA\Schema(
 *   schema="MessageResponse",
 *   type="object",
 *   @OA\Property(property="data", ref="#/components/schemas/MessageItem")
 * )
 *
 * @OA\Schema(
 *   schema="MessageListResponse",
 *   type="object",
 *   @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MessageItem"))
 * )
 *
 * @OA\Schema(
 *   schema="ThreadListItem",
 *   type="object",
 *   required={"id","service_offering_id","customer_id","provider_id"},
 *   @OA\Property(property="id", type="string", example="1"),
 *   @OA\Property(property="service_offering_id", type="integer", example=42),
 *   @OA\Property(property="customer_id", type="integer", example=15),
 *   @OA\Property(property="provider_id", type="integer", example=99),
 *   @OA\Property(property="last_message_at", type="string", format="date-time", nullable=true, example="2025-01-01T12:00:00Z"),
 *   @OA\Property(property="unread_count", type="integer", example=2),
 *   @OA\Property(property="last_message", ref="#/components/schemas/MessageItem")
 * )
 *
 * @OA\Schema(
 *   schema="ThreadListResponse",
 *   type="object",
 *   @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ThreadListItem")),
 *   @OA\Property(property="meta", type="object",
 *     @OA\Property(property="page", type="integer", example=1),
 *     @OA\Property(property="limit", type="integer", example=20),
 *     @OA\Property(property="total", type="integer", example=37)
 *   )
 * )
 */
class MessageThreadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Lister les threads de l'utilisateur courant (participant).
     *
     * @OA\Get(
     *   path="/api/messages/threads",
     *   summary="Lister les fils de discussion (threads) où je suis participant",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="service_id", in="query", description="Filtrer par service", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1), example=1),
     *   @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", minimum=1, maximum=100), example=20),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(ref="#/components/schemas/ThreadListResponse")
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);

        $page      = max(1, (int) $request->query('page', 1));
        $limit     = max(1, min(100, (int) $request->query('limit', 20)));
        $serviceId = $request->query('service_id');

        $q = MessageThread::query()
            ->whereHas('participants', fn ($qq) => $qq->where('user_id', $uid));

        if ($serviceId) {
            $q->where('service_offering_id', (int) $serviceId);
        }

        $q->orderByDesc('last_message_at')->orderByDesc('created_at');

        $total   = (clone $q)->count();
        $threads = $q->limit($limit)->offset(($page - 1) * $limit)->get();

        // Récupérer le last_read_at du user pour chaque thread
        $reads = MessageThreadParticipant::query()
            ->whereIn('thread_id', $threads->pluck('id'))
            ->where('user_id', $uid)
            ->get()
            ->keyBy('thread_id');

        $data = $threads->map(function (MessageThread $t) use ($uid, $reads) {
            // Dernier message (simple et fiable)
            $last = $t->messages()
                ->orderByDesc('created_at')
                ->first(['id', 'body', 'created_at', 'sender_id']);

            // Unread count pour le user courant (messages non envoyés par lui, après last_read_at)
            $lastReadAt = optional($reads->get($t->id))->last_read_at;
            $unread = Message::query()
                ->where('thread_id', $t->id)
                ->where('sender_id', '<>', $uid)
                ->when($lastReadAt, fn($qq) => $qq->where('created_at', '>', $lastReadAt))
                ->count();

            return [
                'id'                  => (string) $t->id,
                'service_offering_id' => (int) $t->service_offering_id,
                'customer_id'         => (int) $t->customer_id,
                'provider_id'         => (int) $t->provider_id,
                'last_message_at'     => optional($t->last_message_at)->toIso8601String(),
                'unread_count'        => (int) $unread,
                'last_message'        => $last ? [
                    'id'         => (string) $last->id,
                    'body'       => $last->body,
                    'created_at' => optional($last->created_at)->toIso8601String(),
                    'sender_id'  => (int) $last->sender_id,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Créer ou récupérer un thread pour un service.
     * @OA\Post(
     *   path="/api/messages/threads/ensure",
     *   summary="Créer ou récupérer un thread pour un service",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(required={"service_id"},
     *       @OA\Property(property="service_id", type="integer", example=42)
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ThreadResponse")),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function ensure(Request $request)
    {
        $userId = $this->userId();
        if (!$userId) return response()->json(['message' => 'Unauthenticated'], 401);

        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:service_offerings,id'],
        ]);

        $service    = ServiceOffering::query()->findOrFail($data['service_id']);
        $providerId = $service->provider_id ?? $service->user_id ?? null;

        if (!$providerId) {
            return response()->json(['message' => 'Prestataire introuvable pour ce service.'], 422);
        }

        $thread = DB::transaction(function () use ($data, $userId, $providerId) {
            /** @var MessageThread $thread */
            $thread = MessageThread::firstOrCreate(
                [
                    'service_offering_id' => (int) $data['service_id'],
                    'customer_id'         => (int) $userId,
                ],
                [
                    'provider_id'     => (int) $providerId,
                    'last_message_at' => null,
                ]
            );

            MessageThreadParticipant::firstOrCreate([
                'thread_id' => $thread->id,
                'user_id'   => (int) $userId,
            ]);
            MessageThreadParticipant::firstOrCreate([
                'thread_id' => $thread->id,
                'user_id'   => (int) $providerId,
            ]);

            return $thread;
        });

        return response()->json([
            'data' => [
                'id'                  => (string) $thread->id,
                'service_offering_id' => (int) $thread->service_offering_id,
                'customer_id'         => (int) $thread->customer_id,
                'provider_id'         => (int) $thread->provider_id,
                'last_message_at'     => optional($thread->last_message_at)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Lister les messages d’un thread.
     *
     * @OA\Get(
     *   path="/api/messages/threads/{thread}/messages",
     *   summary="Lister les messages d’un thread",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", minimum=1, maximum=100), example=30),
     *   @OA\Parameter(name="after", in="query", @OA\Schema(type="string", format="date-time")),
     *   @OA\Parameter(name="before", in="query", @OA\Schema(type="string", format="date-time")),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(ref="#/components/schemas/MessageListResponse")
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function listMessages(Request $request, MessageThread $thread)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);

        $this->assertParticipant($uid, $thread);

        $limit  = (int) max(1, min(100, (int) $request->query('limit', 30)));
        $after  = $request->query('after');
        $before = $request->query('before');

        $q = $thread->messages()->getQuery()->orderBy('created_at', 'asc');

        if ($after)  { try { $q->where('created_at', '>', Carbon::parse($after));   } catch (\Throwable $e) {} }
        if ($before) { try { $q->where('created_at', '<', Carbon::parse($before)); } catch (\Throwable $e) {} }

        $items = $q->limit($limit)->get(['id', 'body', 'created_at', 'sender_id']);

        return response()->json([
            'data' => $items->map(fn (Message $m) => [
                'id'         => (string) $m->id,
                'body'       => $m->body,
                'created_at' => optional($m->created_at)->toIso8601String(),
                'sender_id'  => (int) $m->sender_id,
            ]),
        ]);
    }

    /**
     * Envoyer un message.
     * @OA\Post(
     *   path="/api/messages/threads/{thread}/messages",
     *   summary="Envoyer un message dans un thread",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(required={"body"},
     *       @OA\Property(property="body", type="string", example="Bonjour, j’ai une question…")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Créé", @OA\JsonContent(ref="#/components/schemas/MessageResponse")),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function send(Request $request, MessageThread $thread)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);

        $this->assertParticipant($uid, $thread);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $message = DB::transaction(function () use ($thread, $uid, $data) {
            /** @var Message $message */
            $message = $thread->messages()->create([
                'sender_id' => (int) $uid,
                'body'      => $data['body'],
            ]);

            $thread->update(['last_message_at' => now()]);

            MessageThreadParticipant::where('thread_id', $thread->id)
                ->where('user_id', $uid)
                ->update(['last_read_at' => now()]);

            return $message;
        });

        return response()->json([
            'data' => [
                'id'         => (string) $message->id,
                'body'       => $message->body,
                'created_at' => optional($message->created_at)->toIso8601String(),
                'sender_id'  => (int) $message->sender_id,
            ],
        ], 201);
    }

    /**
     * Marquer un thread comme lu.
     * @OA\Post(
     *   path="/api/messages/threads/{thread}/read",
     *   summary="Marquer un thread comme lu",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(
     *     type="object",
     *     @OA\Property(property="status", type="string", example="ok")
     *   )),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function markRead(Request $request, MessageThread $thread)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);

        $this->assertParticipant($uid, $thread);

        MessageThreadParticipant::where('thread_id', $thread->id)
            ->where('user_id', $uid)
            ->update(['last_read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    /* ===================== Helpers ===================== */

    private function userId(): ?int
    {
        if ($u = auth('api')->user()) {
            return (int) $u->id;
        }
        try {
            if ($u = JWTAuth::parseToken()->authenticate()) {
                return (int) $u->id;
            }
        } catch (\Throwable $e) {}
        if ($id = Auth::guard('api')->id()) return (int) $id;
        if ($id = Auth::id())             return (int) $id;
        return null;
    }

    private function assertParticipant(int $uid, MessageThread $thread): void
    {
        $isParticipant = $thread->participants()->where('user_id', $uid)->exists();
        if (!$isParticipant) {
            abort(403, 'Accès refusé à ce fil de discussion.');
        }
    }
}
