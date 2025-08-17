<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     version="1.0.0",
 *     title="GLOBALYS API",
 *     description="Documentation de l'API GLOBALYS"
 *   ),
 *   @OA\Server(
 *     url="/",
 *     description="Serveur principal"
 *   ),
 *   @OA\Components(
 *     @OA\SecurityScheme(
 *       securityScheme="bearerAuth",
 *       type="http",
 *       scheme="bearer",
 *       bearerFormat="JWT"
 *     ),
 *     @OA\Response(
 *       response="Unauthorized",
 *       description="Unauthenticated"
 *     ),
 *
 *     @OA\Schema(
 *       schema="User",
 *       type="object",
 *       @OA\Property(property="id", type="integer", example=12),
 *       @OA\Property(property="first_name", type="string", example="Paul"),
 *       @OA\Property(property="last_name", type="string", example="N."),
 *       @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *       @OA\Property(property="phone", type="string", example="+237600000000"),
 *       @OA\Property(property="preferred_language", type="string", example="fr"),
 *       @OA\Property(property="country", type="string", example="CM"),
 *       @OA\Property(property="account_type", type="string", enum={"entreprise","particulier"}),
 *       @OA\Property(property="user_type", type="string", enum={"client","prestataire"}, nullable=true),
 *       @OA\Property(property="company_name", type="string", nullable=true),
 *       @OA\Property(property="company_city", type="string", nullable=true),
 *       @OA\Property(property="profile_photo", type="string", nullable=true),
 *       @OA\Property(property="role", type="object", nullable=true),
 *       @OA\Property(property="currentSubscription", type="object", nullable=true),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *       schema="Category",
 *       type="object",
 *       @OA\Property(property="id", type="integer", example=3),
 *       @OA\Property(property="slug", type="string", example="nettoyage"),
 *       @OA\Property(property="name", type="string", example="Nettoyage"),
 *       @OA\Property(property="icon", type="string", example="broom"),
 *       @OA\Property(property="color_class", type="string", example="bg-indigo-600"),
 *       @OA\Property(property="description", type="string", example="Services de nettoyage divers"),
 *       @OA\Property(property="subCategories", type="array", @OA\Items(ref="#/components/schemas/SubCategory"))
 *     ),
 *
 *     @OA\Schema(
 *       schema="SubCategory",
 *       type="object",
 *       @OA\Property(property="id", type="integer"),
 *       @OA\Property(property="category_id", type="integer"),
 *       @OA\Property(property="slug", type="string"),
 *       @OA\Property(property="name", type="string"),
 *       @OA\Property(property="icon", type="string", nullable=true)
 *     ),
 *
 *     @OA\Schema(
 *       schema="CategoryStoreRequest",
 *       type="object",
 *       required={"name"},
 *       @OA\Property(property="name", type="string", example="Nettoyage"),
 *       @OA\Property(property="slug", type="string", nullable=true),
 *       @OA\Property(property="icon", type="string", nullable=true),
 *       @OA\Property(property="color_class", type="string", nullable=true),
 *       @OA\Property(property="description", type="string", nullable=true)
 *     ),
 *
 *     @OA\Schema(
 *       schema="CategoryUpdateRequest",
 *       type="object",
 *       @OA\Property(property="name", type="string", example="Nettoyage & Entretien"),
 *       @OA\Property(property="slug", type="string", nullable=true),
 *       @OA\Property(property="icon", type="string", nullable=true),
 *       @OA\Property(property="color_class", type="string", nullable=true),
 *       @OA\Property(property="description", type="string", nullable=true)
 *     ),
 *
 *     @OA\RequestBody(
 *       request="ReviewStoreRequest",
 *       required=true,
 *       @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(
 *           type="object",
 *           required={"user_id","provider_id","service_offering_id","booking_id","rating"},
 *           @OA\Property(property="user_id", type="integer", example=25),
 *           @OA\Property(property="provider_id", type="integer", example=7),
 *           @OA\Property(property="service_offering_id", type="integer", example=12),
 *           @OA\Property(property="booking_id", type="integer", example=130),
 *           @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
 *           @OA\Property(property="comment", type="string", nullable=true, example="Excellent service !"),
 *           @OA\Property(property="is_approved", type="boolean", nullable=true, example=false)
 *         )
 *       )
 *     ),
 *
 *     @OA\RequestBody(
 *       request="ReviewUpdateRequest",
 *       required=true,
 *       @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(
 *           type="object",
 *           @OA\Property(property="user_id", type="integer", nullable=true),
 *           @OA\Property(property="provider_id", type="integer", nullable=true),
 *           @OA\Property(property="service_offering_id", type="integer", nullable=true),
 *           @OA\Property(property="booking_id", type="integer", nullable=true),
 *           @OA\Property(property="rating", type="integer", minimum=1, maximum=5, nullable=true),
 *           @OA\Property(property="comment", type="string", nullable=true),
 *           @OA\Property(property="is_approved", type="boolean", nullable=true)
 *         )
 *       )
 *     ),
 *
 *     @OA\RequestBody(
 *       request="ReviewApproveRequest",
 *       required=false,
 *       @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(
 *           type="object",
 *           @OA\Property(property="approved", type="boolean", nullable=true, example=true)
 *         )
 *       )
 *     )
 *   )
 * )
 */
class OpenApiRoot
{
    // Vide : uniquement pour contenir les annotations
}
