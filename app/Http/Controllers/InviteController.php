<?php

namespace App\Http\Controllers;

use App\Models\RoleInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class InviteController extends Controller
{
    public function __construct()
    {
        // Protège la création/révocation (ex: réservé aux admins via policies/guards si besoin)
        $this->middleware('auth:api')->only(['create','revoke']);
    }

    /**
     * @OA\Post(
     *   path="/api/invites",
     *   tags={"Invitations"},
     *   summary="Créer un lien d’invitation (prestataire/entreprise)",
     *   description="Crée un token d’invitation utilisable sur /api/auth/register. À sécuriser côté autorisations (ex: admin).",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"role"},
     *       @OA\Property(property="role", type="string", enum={"prestataire","entreprise"}, example="prestataire"),
     *       @OA\Property(property="email", type="string", format="email", nullable=true, example="prospect@exemple.com", description="Optionnel: verrouille l’invitation à cet email"),
     *       @OA\Property(property="expires_in", type="integer", minimum=1, nullable=true, example=48, description="Durée de validité en heures"),
     *       @OA\Property(property="max_uses", type="integer", minimum=1, nullable=true, example=1),
     *       @OA\Property(property="meta", type="object", nullable=true, additionalProperties=true, example={"note":"Salon 2025", "source":"linkedin"})
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Invitation créée",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="token", type="string", format="uuid", example="e7dfc0c3-5b7b-4d7e-9e3e-0d8b8a0d2c8e"),
     *         @OA\Property(property="role", type="string", example="prestataire"),
     *         @OA\Property(property="link", type="string", example="https://ton-domaine.com/register?invite=e7dfc0c3-5b7b-4d7e-9e3e-0d8b8a0d2c8e")
     *       ),
     *       @OA\Property(property="message", type="string", example="Invitation créée")
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function create(Request $request)
    {
        // TODO: appliquer une policy pour restreindre aux admins
        $data = $request->validate([
            'role'       => ['required','in:prestataire,entreprise'],
            'email'      => ['nullable','email'],
            'expires_in' => ['nullable','integer','min:1'], // heures
            'max_uses'   => ['nullable','integer','min:1'],
            'meta'       => ['nullable','array'],
        ]);

        $invite = RoleInvite::create([
            'token'      => (string) Str::uuid(),
            'role'       => $data['role'],
            'email'      => $data['email'] ?? null,
            'expires_at' => isset($data['expires_in']) ? now()->addHours($data['expires_in']) : null,
            'max_uses'   => $data['max_uses'] ?? 1,
            'created_by' => optional($request->user())->id,
            'meta'       => $data['meta'] ?? null,
        ]);

        return response()->success([
            'token' => $invite->token,
            'role'  => $invite->role,
            'link'  => url("/register?invite={$invite->token}"),
        ], 'Invitation créée');
    }

    /**
     * @OA\Get(
     *   path="/api/invites/{token}",
     *   tags={"Invitations"},
     *   summary="Afficher le statut d’une invitation",
     *   description="Permet au front de préremplir le rôle et de vérifier la validité du lien d’inscription.",
     *   @OA\Parameter(
     *     name="token",
     *     in="path",
     *     required=true,
     *     description="Token d’invitation (UUID)",
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="role", type="string", example="entreprise"),
     *         @OA\Property(property="email", type="string", format="email", nullable=true, example="prospect@exemple.com"),
     *         @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, example="2025-09-01T12:00:00Z"),
     *         @OA\Property(property="max_uses", type="integer", example=1),
     *         @OA\Property(property="used_count", type="integer", example=0),
     *         @OA\Property(property="valid", type="boolean", example=true)
     *       ),
     *       @OA\Property(property="message", type="string", example="Statut du token")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Invitation introuvable")
     * )
     */
    public function show(string $token)
    {
        $invite = RoleInvite::where('token', $token)->first();
        if (!$invite) {
            return response()->error('Invitation introuvable', null, 404);
        }

        return response()->success([
            'role'       => $invite->role,
            'email'      => $invite->email,
            'expires_at' => $invite->expires_at,
            'max_uses'   => $invite->max_uses,
            'used_count' => $invite->used_count,
            'valid'      => $invite->isValid(),
        ], 'Statut du token');
    }

    /**
     * @OA\Post(
     *   path="/api/invites/{token}/revoke",
     *   tags={"Invitations"},
     *   summary="Révoquer une invitation",
     *   description="Invalide le token d’invitation (utile en cas d’erreur d’envoi ou d’abus).",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="token",
     *     in="path",
     *     required=true,
     *     description="Token d’invitation (UUID)",
     *     @OA\Schema(type="string", format="uuid")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Invitation révoquée",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", type="object", nullable=true),
     *       @OA\Property(property="message", type="string", example="Invitation révoquée")
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Invitation introuvable")
     * )
     */
    public function revoke(string $token)
    {
        $invite = RoleInvite::where('token', $token)->first();
        if (!$invite) {
            return response()->error('Invitation introuvable', null, 404);
        }

        $invite->revoked_at = now();
        $invite->save();

        return response()->success(null, 'Invitation révoquée');
    }
}
