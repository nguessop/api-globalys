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
 *
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
 *       schema="Subscription",
 *       type="object",
 *       @OA\Property(property="id", type="integer", example=5),
 *       @OA\Property(property="user_id", type="integer", example=12),
 *       @OA\Property(property="name", type="string", example="Gold"),
 *       @OA\Property(property="price", type="number", format="float", nullable=true, example=29.99),
 *       @OA\Property(property="frequency", type="string", enum={"month","year"}, example="month"),
 *       @OA\Property(property="commission_type", type="string", enum={"percent","flat"}, example="percent"),
 *       @OA\Property(property="commission_rate", type="number", format="float", example=10),
 *       @OA\Property(property="status", type="string", example="active"),
 *       @OA\Property(property="started_at", type="string", format="date-time"),
 *       @OA\Property(property="ends_at", type="string", format="date-time", nullable=true)
 *     ),
 *
 *     @OA\Schema(
 *       schema="Booking",
 *       type="object",
 *       @OA\Property(property="id", type="integer"),
 *       @OA\Property(property="client_id", type="integer"),
 *       @OA\Property(property="provider_id", type="integer"),
 *       @OA\Property(property="service_offering_id", type="integer"),
 *       @OA\Property(property="status", type="string"),
 *       @OA\Property(property="payment_status", type="string"),
 *       @OA\Property(property="start_at", type="string", format="date-time"),
 *       @OA\Property(property="end_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *       schema="ServiceOffering",
 *       type="object",
 *       @OA\Property(property="id", type="integer"),
 *       @OA\Property(property="provider_id", type="integer"),
 *       @OA\Property(property="sub_category_id", type="integer"),
 *       @OA\Property(property="title", type="string"),
 *       @OA\Property(property="city", type="string"),
 *       @OA\Property(property="status", type="string")
 *     ),
 *
 *     @OA\Schema(
 *       schema="Review",
 *       type="object",
 *       @OA\Property(property="id", type="integer"),
 *       @OA\Property(property="author_id", type="integer"),
 *       @OA\Property(property="provider_id", type="integer"),
 *       @OA\Property(property="booking_id", type="integer"),
 *       @OA\Property(property="rating", type="number", format="float"),
 *       @OA\Property(property="comment", type="string"),
 *       @OA\Property(property="created_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *       schema="AvailabilitySlot",
 *       type="object",
 *       @OA\Property(property="id", type="integer"),
 *       @OA\Property(property="provider_id", type="integer"),
 *       @OA\Property(property="start_at", type="string", format="date-time"),
 *       @OA\Property(property="end_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *       schema="UserStoreRequest",
 *       type="object",
 *       required={"first_name","last_name","email","password","account_type","role_id"},
 *       @OA\Property(property="first_name", type="string"),
 *       @OA\Property(property="last_name", type="string"),
 *       @OA\Property(property="email", type="string", format="email"),
 *       @OA\Property(property="password", type="string", format="password", minLength=6),
 *       @OA\Property(property="phone", type="string"),
 *       @OA\Property(property="preferred_language", type="string"),
 *       @OA\Property(property="country", type="string", maxLength=2),
 *       @OA\Property(property="account_type", type="string", enum={"entreprise","particulier"}),
 *       @OA\Property(property="role_id", type="integer"),
 *       @OA\Property(property="gender", type="string", enum={"Homme","Femme","Autre"}, nullable=true),
 *       @OA\Property(property="birthdate", type="string", format="date", nullable=true),
 *       @OA\Property(property="job", type="string", nullable=true),
 *       @OA\Property(property="personal_address", type="string", nullable=true),
 *       @OA\Property(property="user_type", type="string", enum={"client","prestataire"}, nullable=true),
 *       @OA\Property(property="company_name", type="string", nullable=true),
 *       @OA\Property(property="sector", type="string", nullable=true),
 *       @OA\Property(property="tax_number", type="string", nullable=true),
 *       @OA\Property(property="website", type="string", nullable=true),
 *       @OA\Property(property="company_logo", type="string", nullable=true),
 *       @OA\Property(property="company_description", type="string", nullable=true),
 *       @OA\Property(property="company_address", type="string", nullable=true),
 *       @OA\Property(property="company_city", type="string", nullable=true),
 *       @OA\Property(property="company_size", type="string", nullable=true),
 *       @OA\Property(property="preferred_contact_method", type="string", nullable=true),
 *       @OA\Property(property="accepts_terms", type="boolean"),
 *       @OA\Property(property="wants_newsletter", type="boolean")
 *     ),
 *
 *     @OA\Schema(
 *       schema="UserUpdateRequest",
 *       type="object",
 *       @OA\Property(property="first_name", type="string"),
 *       @OA\Property(property="last_name", type="string"),
 *       @OA\Property(property="email", type="string", format="email"),
 *       @OA\Property(property="password", type="string", minLength=6, nullable=true),
 *       @OA\Property(property="phone", type="string"),
 *       @OA\Property(property="preferred_language", type="string"),
 *       @OA\Property(property="country", type="string", maxLength=2),
 *       @OA\Property(property="account_type", type="string", enum={"entreprise","particulier"}),
 *       @OA\Property(property="role_id", type="integer"),
 *       @OA\Property(property="gender", type="string", enum={"Homme","Femme","Autre"}, nullable=true),
 *       @OA\Property(property="birthdate", type="string", format="date", nullable=true),
 *       @OA\Property(property="job", type="string", nullable=true),
 *       @OA\Property(property="personal_address", type="string", nullable=true),
 *       @OA\Property(property="user_type", type="string", enum={"client","prestataire"}, nullable=true),
 *       @OA\Property(property="company_name", type="string", nullable=true),
 *       @OA\Property(property="sector", type="string", nullable=true),
 *       @OA\Property(property="tax_number", type="string", nullable=true),
 *       @OA\Property(property="website", type="string", nullable=true),
 *       @OA\Property(property="company_logo", type="string", nullable=true),
 *       @OA\Property(property="company_description", type="string", nullable=true),
 *       @OA\Property(property="company_address", type="string", nullable=true),
 *       @OA\Property(property="company_city", type="string", nullable=true),
 *       @OA\Property(property="company_size", type="string", nullable=true),
 *       @OA\Property(property="preferred_contact_method", type="string", nullable=true),
 *       @OA\Property(property="accepts_terms", type="boolean"),
 *       @OA\Property(property="wants_newsletter", type="boolean")
 *     ),
 *
 *     @OA\Schema(
 *       schema="ChangePasswordRequest",
 *       type="object",
 *       required={"current_password","new_password","new_password_confirmation"},
 *       @OA\Property(property="current_password", type="string", example="oldPass"),
 *       @OA\Property(property="new_password", type="string", example="NewPass123!"),
 *       @OA\Property(property="new_password_confirmation", type="string", example="NewPass123!")
 *     ),
 *
 *     @OA\Schema(
 *       schema="AssignSubscriptionRequest",
 *       type="object",
 *       required={"name","commission_type","commission_rate"},
 *       @OA\Property(property="name", type="string", example="Gold"),
 *       @OA\Property(property="price", type="number", format="float", example=29.99, nullable=true),
 *       @OA\Property(property="frequency", type="string", enum={"month","year"}, example="month", nullable=true),
 *       @OA\Property(property="commission_type", type="string", enum={"percent","flat"}, example="percent"),
 *       @OA\Property(property="commission_rate", type="number", format="float", example=10),
 *       @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
 *       @OA\Property(property="ends_at", type="string", format="date-time", nullable=true)
 *     )
 *   )
 * )
 */
class OpenApiRoot
{
    // Vide : les annotations ci-dessus suffisent.
}
