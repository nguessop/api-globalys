<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceOfferingController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;

/** @var Router $api */
$api = app(Router::class);

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api->version('v1', function (Router $api) {

    $api->group(['prefix' => 'auth'], function (Router $api) {
        $api->post('login', 'App\Http\Controllers\AuthController@login');
        $api->post('forgot-password', 'App\Http\Controllers\AuthController@forgotPassword');
        $api->post('refresh', 'App\Http\Controllers\AuthController@refresh');
    });

    Route::prefix('users')->group(function () {
        Route::get('/',           [UserController::class, 'index']);
        Route::get('/me',         [UserController::class, 'me']);
        Route::get('/roles',      [UserController::class, 'roles']);
        Route::post('/',          [UserController::class, 'store']);
        Route::get('/{user}',     [UserController::class, 'show']);               // {user} = id ou email (via resolveRouteBinding)
        Route::put('/{user}',     [UserController::class, 'update']);
        Route::patch('/{user}',   [UserController::class, 'update']);
        Route::delete('/{user}',  [UserController::class, 'destroy']);

        Route::get('/{user}/bookings',           [UserController::class, 'bookings']);          // ?as=client|provider
        Route::get('/{user}/service-offerings',  [UserController::class, 'serviceOfferings']);
        Route::get('/{user}/reviews',            [UserController::class, 'reviews']);           // ?as=received|given
        Route::get('/{user}/availability',       [UserController::class, 'availability']);

        Route::post('/{user}/avatar',            [UserController::class, 'uploadAvatar']);      // multipart/form-data
        Route::post('/{user}/password',          [UserController::class, 'changePassword']);

        Route::post('/{user}/subscription/assign', [UserController::class, 'assignSubscription']);
        Route::post('/{user}/subscription/revoke', [UserController::class, 'revokeSubscription']);
    });

    Route::prefix('catalogue')->group(function () {
        // --- Categories
        Route::get('/categories', [CategoryController::class, 'index']); // tu peux aussi faire ceci: GET /api/categories?search=Beauté
        Route::get('/categories/{category}', [CategoryController::class, 'show']); // {category} = slug
        Route::get('/categories/{category}/subcategories', [CategoryController::class, 'subcategories']); // liste des subcats d'une catégorie
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);

        // --- SubCategories
        Route::get('/subcategories', [SubCategoryController::class, 'index']);
        Route::get('/subcategories/{subCategory}', [SubCategoryController::class, 'show']); // {subCategory} = slug

    });


    Route::prefix('service-offerings')->group(function () {
        Route::get('/',               [ServiceOfferingController::class, 'index']); // GET /api/service-offerings?q=nettoyage&city=Douala&status=active&per_page=10&sort=avg_rating&dir=desc
        Route::get('/{serviceOffering}', [ServiceOfferingController::class, 'show']);

        // publiques
        Route::get('/{serviceOffering}/availability', [ServiceOfferingController::class, 'availability']);
        Route::post('/{serviceOffering}/increment-views', [ServiceOfferingController::class, 'incrementViews']);

        // protégées (appliqué via __construct)
        Route::post('/',              [ServiceOfferingController::class, 'store']);
        Route::match(['put','patch'],'/{serviceOffering}', [ServiceOfferingController::class, 'update']);
        Route::delete('/{serviceOffering}', [ServiceOfferingController::class, 'destroy']);

        // actions
        Route::post('/{serviceOffering}/publish',   [ServiceOfferingController::class, 'publish']);
        Route::post('/{serviceOffering}/pause',     [ServiceOfferingController::class, 'pause']);
        Route::post('/{serviceOffering}/archive',   [ServiceOfferingController::class, 'archive']);
        Route::post('/{serviceOffering}/feature',   [ServiceOfferingController::class, 'feature']);
        Route::post('/{serviceOffering}/verify',    [ServiceOfferingController::class, 'verify']);
        Route::post('/{serviceOffering}/attachments', [ServiceOfferingController::class, 'saveAttachments']);
        Route::post('/{serviceOffering}/recompute-stats', [ServiceOfferingController::class, 'recomputeStats']);
    });

    Route::prefix('bookings')->group(function () {
        Route::get('/',                 [BookingController::class, 'index']);
        Route::get('/{booking}',        [BookingController::class, 'show']);

        // protégées
        Route::post('/',                [BookingController::class, 'store']);
        Route::match(['put','patch'],'/{booking}', [BookingController::class, 'update']);
        Route::delete('/{booking}',     [BookingController::class, 'destroy']);

        // actions
        Route::post('/{booking}/confirm',        [BookingController::class, 'confirm']);
        Route::post('/{booking}/start',          [BookingController::class, 'start']);      // optionnel
        Route::post('/{booking}/complete',       [BookingController::class, 'complete']);
        Route::post('/{booking}/cancel',         [BookingController::class, 'cancel']);
        Route::post('/{booking}/payment-status', [BookingController::class, 'setPaymentStatus']);
        Route::post('/{booking}/recompute',      [BookingController::class, 'recompute']);
    });

});
