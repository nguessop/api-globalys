<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use App\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/newsletter/subscribe",
     *   tags={"Newsletter"},
     *   summary="Inscrire un email à la newsletter",
     *   requestBody=@OA\RequestBody(
     *     request="NewsletterSubscribeRequest",
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(ref="#/components/schemas/NewsletterSubscribePayload")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Inscription réussie",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Abonné avec succès"),
     *       @OA\Property(property="data", ref="#/components/schemas/NewsletterSubscriberResource")
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email','max:255'],
            'name'  => ['nullable','string','max:255'],
            'tags'  => ['nullable','array'],
            'tags.*'=> ['string','max:50'],
            'source'=> ['nullable','string','max:255'],
        ]);

        $sub = NewsletterSubscriber::firstOrCreate(
            ['email' => strtolower($data['email'])],
            [
                'name'   => $data['name'] ?? null,
                'tags'   => $data['tags'] ?? [],
                'source' => $data['source'] ?? null,
            ]
        );

        if (!$sub->wasRecentlyCreated) {
            $sub->fill([
                'name'   => $data['name'] ?? $sub->name,
                'tags'   => $data['tags'] ?? $sub->tags,
                'source' => $data['source'] ?? $sub->source,
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Abonné avec succès',
            'data'    => $sub,
        ], 201);
    }

    /**
     * @OA\Get(
     *   path="/api/newsletter/subscribers",
     *   tags={"Newsletter"},
     *   summary="Lister les abonnés",
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1)),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=100)),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(ref="#/components/schemas/NewsletterSubscriberCollection")
     *   )
     * )
     */
    public function index(Request $request)
    {
        $perPage = (int) max(1, min(100, $request->input('per_page', 15)));
        $page = NewsletterSubscriber::query()->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'last_page'    => $page->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/newsletter/unsubscribe/{email}",
     *   tags={"Newsletter"},
     *   summary="Désinscrire un email",
     *   @OA\Parameter(name="email", in="path", required=true, @OA\Schema(type="string", format="email")),
     *   @OA\Response(
     *     response=200,
     *     description="Désinscription réussie",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Désinscrit")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function unsubscribe(string $email)
    {
        $subscriber = NewsletterSubscriber::where('email', strtolower($email))->first();

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Abonné introuvable',
            ], 404);
        }

        $subscriber->update(['unsubscribed_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Désinscrit',
        ]);
    }
}
