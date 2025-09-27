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
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Tag(
 *   name="Messaging",
 *   description="Messagerie (threads & messages) protégée par JWT"
 * )
 *
 * @OA\Schema(
 *   schema="Attachment",
 *   type="object",
 *   @OA\Property(property="id", type="string", example="att_123"),
 *   @OA\Property(property="url", type="string", example="https://cdn.../file.jpg"),
 *   @OA\Property(property="name", type="string", example="image.jpg"),
 *   @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 *   @OA\Property(property="size", type="integer", example=245678),
 *   @OA\Property(property="kind", type="string", enum={"image","file","video","audio"}),
 *   @OA\Property(property="width", type="integer", nullable=true, example=1024),
 *   @OA\Property(property="height", type="integer", nullable=true, example=768),
 *   @OA\Property(property="thumbnail_url", type="string", nullable=true, example="https://cdn.../thumb.jpg")
 * )
 *
 * @OA\Schema(
 *   schema="Reaction",
 *   type="object",
 *   @OA\Property(property="emoji", type="string", example="👍"),
 *   @OA\Property(property="count", type="integer", example=3),
 *   @OA\Property(property="mine", type="boolean", example=true)
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
 *   @OA\Property(property="sender_id", type="integer", example=15),
 *   @OA\Property(property="attachments", type="array", @OA\Items(ref="#/components/schemas/Attachment")),
 *   @OA\Property(property="reactions", type="array", @OA\Items(ref="#/components/schemas/Reaction"))
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

    /* =======================================================================
     * Threads
     * ======================================================================= */

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
     *   @OA\Response(response=200, description="OK")
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

        // last_read_at par thread pour ce user
        $reads = MessageThreadParticipant::query()
            ->whereIn('thread_id', $threads->pluck('id'))
            ->where('user_id', $uid)
            ->get()
            ->keyBy('thread_id');

        $data = $threads->map(function (MessageThread $t) use ($uid, $reads) {
            $last = $t->messages()->orderByDesc('created_at')
                ->first(['id','body','created_at','sender_id']);

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
            'meta' => ['page'=>$page,'limit'=>$limit,'total'=>$total],
        ]);
    }

    /**
     * Créer / récupérer un thread pour un service.
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
     *   @OA\Response(response=200, description="OK")
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

    /* =======================================================================
     * Messages (liste / envoi)
     * ======================================================================= */

    /**
     * Lister les messages d’un thread (avec pièces jointes & réactions).
     * @OA\Get(
     *   path="/api/messages/threads/{thread}/messages",
     *   summary="Lister les messages d’un thread",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", minimum=1, maximum=100), example=30),
     *   @OA\Parameter(name="after", in="query", @OA\Schema(type="string", format="date-time")),
     *   @OA\Parameter(name="before", in="query", @OA\Schema(type="string", format="date-time")),
     *   @OA\Response(response=200, description="OK")
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

        $items = $q->limit($limit)->get(['id', 'body', 'created_at', 'sender_id', 'thread_id']);
        $ids   = $items->pluck('id')->all();

        // Attachments
        $attByMsg = collect();
        if (!empty($ids)) {
            $atts = DB::table('message_attachments')
                ->whereIn('message_id', $ids)
                ->get();
            $attByMsg = $atts->groupBy('message_id')->map(function ($grp) {
                return $grp->map(function ($a) {
                    return [
                        'id'            => (string) $a->id,
                        'url'           => $a->url ?? ($a->path ? Storage::url($a->path) : null),
                        'name'          => $a->name,
                        'mime_type'     => $a->mime_type,
                        'size'          => (int) ($a->size ?? 0),
                        'kind'          => $a->kind,
                        'width'         => $a->width ? (int) $a->width : null,
                        'height'        => $a->height ? (int) $a->height : null,
                        'thumbnail_url' => $a->thumbnail_url,
                    ];
                })->values();
            });
        }

        // Reactions (agrégées + mine)
        $reactByMsg = collect();
        if (!empty($ids)) {
            $recs = DB::table('message_reactions')->whereIn('message_id', $ids)->get();
            $reactByMsg = $recs->groupBy('message_id')->map(function ($grp) use ($uid) {
                $byEmoji = [];
                foreach ($grp as $r) {
                    $emoji = $r->emoji;
                    if (!isset($byEmoji[$emoji])) $byEmoji[$emoji] = ['emoji'=>$emoji, 'count'=>0, 'mine'=>false];
                    $byEmoji[$emoji]['count']++;
                    if ((int)$r->user_id === (int)$uid) $byEmoji[$emoji]['mine'] = true;
                }
                return array_values($byEmoji);
            });
        }

        return response()->json([
            'data' => $items->map(function (Message $m) use ($attByMsg, $reactByMsg) {
                return [
                    'id'         => (string) $m->id,
                    'body'       => $m->body,
                    'created_at' => optional($m->created_at)->toIso8601String(),
                    'sender_id'  => (int) $m->sender_id,
                    'attachments'=> $attByMsg->get($m->id, collect())->values(),
                    'reactions'  => $reactByMsg->get($m->id, []),
                ];
            }),
        ]);
    }

    /**
     * Envoyer un message (avec pièces jointes optionnelles).
     * @OA\Post(
     *   path="/api/messages/threads/{thread}/messages",
     *   summary="Envoyer un message dans un thread",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"body"},
     *       @OA\Property(property="body", type="string", example="Bonjour, j’ai une question…"),
     *       @OA\Property(property="attachment_ids", type="array", @OA\Items(type="string"))
     *     )
     *   ),
     *   @OA\Response(response=201, description="Créé")
     * )
     */
    public function send(Request $request, MessageThread $thread)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);
        $this->assertParticipant($uid, $thread);

        $data = $request->validate([
            'body'           => ['required', 'string', 'min:1', 'max:5000'],
            'attachment_ids' => ['array'],
            'attachment_ids.*' => ['string'],
        ]);

        $message = DB::transaction(function () use ($thread, $uid, $data) {
            /** @var Message $message */
            $message = $thread->messages()->create([
                'sender_id' => (int) $uid,
                'body'      => $data['body'],
            ]);

            // Attacher les fichiers uploadés au message
            if (!empty($data['attachment_ids'])) {
                DB::table('message_attachments')
                    ->whereIn('id', $data['attachment_ids'])
                    ->whereNull('message_id')
                    ->where('thread_id', $thread->id)
                    ->update(['message_id' => $message->id, 'updated_at' => now()]);
            }

            $thread->update(['last_message_at' => now()]);

            MessageThreadParticipant::where('thread_id', $thread->id)
                ->where('user_id', $uid)
                ->update(['last_read_at' => now()]);

            return $message;
        });

        // Charger les attachments tout juste liés
        $atts = DB::table('message_attachments')->where('message_id', $message->id)->get()->map(function ($a) {
            return [
                'id'            => (string) $a->id,
                'url'           => $a->url ?? ($a->path ? Storage::url($a->path) : null),
                'name'          => $a->name,
                'mime_type'     => $a->mime_type,
                'size'          => (int) ($a->size ?? 0),
                'kind'          => $a->kind,
                'width'         => $a->width ? (int) $a->width : null,
                'height'        => $a->height ? (int) $a->height : null,
                'thumbnail_url' => $a->thumbnail_url,
            ];
        })->values();

        return response()->json([
            'data' => [
                'id'          => (string) $message->id,
                'body'        => $message->body,
                'created_at'  => optional($message->created_at)->toIso8601String(),
                'sender_id'   => (int) $message->sender_id,
                'attachments' => $atts,
                'reactions'   => [],
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
     *   @OA\Response(response=200, description="OK")
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

    /* =======================================================================
     * Pièces jointes
     * ======================================================================= */

    /**
     * Uploader une pièce jointe (multipart/form-data: file).
     * @OA\Post(
     *   path="/api/messages/threads/{thread}/attachments",
     *   summary="Uploader une pièce jointe",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true,
     *     @OA\MediaType(mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(property="file", type="string", format="binary")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function uploadAttachment(Request $request, MessageThread $thread)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);
        $this->assertParticipant($uid, $thread);

        $request->validate([
            'file' => ['required','file','max:20480'], // 20MB
        ]);

        $file = $request->file('file');
        $path = $file->store('message_attachments/'.date('Y/m/d'), 'public');
        $mime = $file->getMimeType();
        $size = $file->getSize();
        $name = $file->getClientOriginalName();

        $kind = 'file';
        if (str_starts_with($mime, 'image/'))      $kind = 'image';
        elseif (str_starts_with($mime, 'video/'))  $kind = 'video';
        elseif (str_starts_with($mime, 'audio/'))  $kind = 'audio';

        $width = null; $height = null;
        if ($kind === 'image') {
            try {
                [$width, $height] = getimagesize($file->getRealPath()) ?: [null, null];
            } catch (\Throwable $e) {}
        }

        $id = DB::table('message_attachments')->insertGetId([
            'thread_id'     => $thread->id,
            'message_id'    => null,
            'uploaded_by'   => $uid,
            'path'          => $path,
            'url'           => Storage::disk('public')->url($path),
            'name'          => $name,
            'mime_type'     => $mime,
            'size'          => $size,
            'kind'          => $kind,
            'width'         => $width,
            'height'        => $height,
            'thumbnail_url' => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $row = DB::table('message_attachments')->where('id', $id)->first();

        return response()->json([
            'data' => [
                'id'            => (string) $row->id,
                'url'           => $row->url ?? Storage::disk('public')->url($row->path),
                'name'          => $row->name,
                'mime_type'     => $row->mime_type,
                'size'          => (int) $row->size,
                'kind'          => $row->kind,
                'width'         => $row->width ? (int) $row->width : null,
                'height'        => $row->height ? (int) $row->height : null,
                'thumbnail_url' => $row->thumbnail_url,
            ]
        ]);
    }

    /* =======================================================================
     * Réactions (style WhatsApp)
     * ======================================================================= */

    /**
     * Réagir à un message (remplace la réaction existante de l'utilisateur si différente).
     * @OA\Post(
     *   path="/api/messages/threads/{thread}/messages/{message}/reactions",
     *   summary="Ajouter / remplacer la réaction (WhatsApp-like)",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="message", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"emoji"},
     *     @OA\Property(property="emoji", type="string", example="❤️")
     *   )),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function react(Request $request, MessageThread $thread, Message $message)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);
        $this->assertParticipant($uid, $thread);

        if ($message->thread_id !== $thread->id) {
            return response()->json(['message' => 'Message hors de ce thread'], 422);
        }

        $data = $request->validate([
            'emoji' => ['required','string','max:8'],
        ]);
        $emoji = $data['emoji'];

        DB::transaction(function () use ($uid, $message, $emoji) {
            // WhatsApp-like: une seule réaction par user & message → on remplace
            DB::table('message_reactions')
                ->where('message_id', $message->id)
                ->where('user_id', $uid)
                ->delete();

            DB::table('message_reactions')->insert([
                'message_id' => $message->id,
                'user_id'    => $uid,
                'emoji'      => $emoji,
                'created_at' => now(),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Retirer sa réaction (DELETE avec body { emoji } via axios).
     * @OA\Delete(
     *   path="/api/messages/threads/{thread}/messages/{message}/reactions",
     *   summary="Retirer ma réaction",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="message", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(@OA\JsonContent(@OA\Property(property="emoji", type="string", example="❤️"))),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function unreact(Request $request, MessageThread $thread, Message $message)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);
        $this->assertParticipant($uid, $thread);

        if ($message->thread_id !== $thread->id) {
            return response()->json(['message' => 'Message hors de ce thread'], 422);
        }

        $emoji = $request->input('emoji'); // body dans DELETE → axios { data: { emoji } }
        $q = DB::table('message_reactions')
            ->where('message_id', $message->id)
            ->where('user_id', $uid);
        if ($emoji) $q->where('emoji', $emoji);
        $q->delete();

        return response()->json(['ok' => true]);
    }

    /* =======================================================================
     * Messages programmés
     * ======================================================================= */

    /**
     * Programmer un message.
     * @OA\Post(
     *   path="/api/messages/threads/{thread}/messages/schedule",
     *   summary="Programmer l’envoi d’un message",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"body","scheduled_at"},
     *     @OA\Property(property="body", type="string", example="Rappel pour demain"),
     *     @OA\Property(property="scheduled_at", type="string", format="date-time"),
     *     @OA\Property(property="attachment_ids", type="array", @OA\Items(type="string"))
     *   )),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function scheduleMessage(Request $request, MessageThread $thread)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);
        $this->assertParticipant($uid, $thread);

        $data = $request->validate([
            'body'            => ['required','string','min:1','max:5000'],
            'scheduled_at'    => ['required','date'],
            'attachment_ids'  => ['array'],
            'attachment_ids.*'=> ['string'],
        ]);

        $when = Carbon::parse($data['scheduled_at']);
        if ($when->isPast()) {
            return response()->json(['message' => 'La date/heure doit être future.'], 422);
        }

        // Optionnel : valider que les attachments appartiennent au thread et ne sont liés à aucun message
        $attachments = [];
        if (!empty($data['attachment_ids'])) {
            $atts = DB::table('message_attachments')
                ->whereIn('id', $data['attachment_ids'])
                ->where('thread_id', $thread->id)
                ->get();
            $attachments = $atts->map(function ($a) {
                return [
                    'id'            => (string) $a->id,
                    'url'           => $a->url ?? ($a->path ? Storage::url($a->path) : null),
                    'name'          => $a->name,
                    'mime_type'     => $a->mime_type,
                    'size'          => (int) ($a->size ?? 0),
                    'kind'          => $a->kind,
                    'width'         => $a->width ? (int) $a->width : null,
                    'height'        => $a->height ? (int) $a->height : null,
                    'thumbnail_url' => $a->thumbnail_url,
                ];
            })->values()->all();
        }

        $id = DB::table('scheduled_messages')->insertGetId([
            'thread_id'      => $thread->id,
            'user_id'        => $uid,
            'body'           => $data['body'],
            'scheduled_at'   => $when,
            'attachment_ids' => json_encode(array_column($attachments, 'id')),
            'status'         => 'scheduled',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json([
            'data' => [
                'id'            => (string) $id,
                'thread_id'     => (string) $thread->id,
                'body'          => $data['body'],
                'scheduled_at'  => $when->toIso8601String(),
                'attachments'   => $attachments,
                'status'        => 'scheduled',
            ]
        ]);
    }

    /**
     * Lister mes messages programmés sur un thread.
     * @OA\Get(
     *   path="/api/messages/threads/{thread}/messages/schedule",
     *   summary="Lister les messages programmés",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function listScheduledMessages(Request $request, MessageThread $thread)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);
        $this->assertParticipant($uid, $thread);

        $rows = DB::table('scheduled_messages')
            ->where('thread_id', $thread->id)
            ->where('user_id', $uid)
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at', 'asc')
            ->get();

        // Joindre les métadonnées d’attachments si besoin
        $list = $rows->map(function ($r) {
            $ids = json_decode($r->attachment_ids ?? '[]', true) ?: [];
            $atts = [];
            if (!empty($ids)) {
                $atts = DB::table('message_attachments')->whereIn('id', $ids)->get()->map(function ($a) {
                    return [
                        'id'            => (string) $a->id,
                        'url'           => $a->url ?? ($a->path ? Storage::url($a->path) : null),
                        'name'          => $a->name,
                        'mime_type'     => $a->mime_type,
                        'size'          => (int) ($a->size ?? 0),
                        'kind'          => $a->kind,
                        'width'         => $a->width ? (int) $a->width : null,
                        'height'        => $a->height ? (int) $a->height : null,
                        'thumbnail_url' => $a->thumbnail_url,
                    ];
                })->values()->all();
            }
            return [
                'id'           => (string) $r->id,
                'thread_id'    => (string) $r->thread_id,
                'body'         => $r->body,
                'scheduled_at' => Carbon::parse($r->scheduled_at)->toIso8601String(),
                'attachments'  => $atts,
                'status'       => $r->status,
                'created_at'   => optional($r->created_at)->toIso8601String(),
            ];
        });

        return response()->json(['data' => $list]);
    }

    /**
     * Annuler un message programmé.
     * @OA\Delete(
     *   path="/api/messages/threads/{thread}/messages/schedule/{scheduled}",
     *   summary="Annuler un message programmé",
     *   tags={"Messaging"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="thread", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="scheduled", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function cancelScheduledMessage(Request $request, MessageThread $thread, $scheduled)
    {
        $uid = $this->userId();
        if (!$uid) return response()->json(['message' => 'Unauthenticated'], 401);
        $this->assertParticipant($uid, $thread);

        $row = DB::table('scheduled_messages')
            ->where('id', $scheduled)
            ->where('thread_id', $thread->id)
            ->where('user_id', $uid)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Message programmé introuvable'], 404);
        }
        if ($row->status !== 'scheduled') {
            return response()->json(['message' => 'Déjà traité'], 422);
        }

        DB::table('scheduled_messages')
            ->where('id', $row->id)
            ->update(['status' => 'canceled', 'updated_at' => now()]);

        return response()->json(['status' => 'canceled']);
    }

    /* =======================================================================
     * Helpers
     * ======================================================================= */

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
