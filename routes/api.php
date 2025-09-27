<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvailabilitySlotController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MessageThreadController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceOfferingController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\SubCategoryImageController;
use App\Http\Controllers\SubscriptionController;
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

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']); // public avec invite_token
    });

    Route::middleware('auth:api')->group(function () {
        Route::post('/invites', [InviteController::class, 'create']);      // créer un lien (admin/staff)
        Route::post('/invites/{token}/revoke', [InviteController::class, 'revoke']);
    });

// public: vérifier un token (pour pré-remplir l’écran)
    Route::get('/invites/{token}', [InviteController::class, 'show']);



    Route::prefix('users')->group(function () {
        Route::get('/',           [UserController::class, 'index']);
        Route::get('/me',         [UserController::class, 'me']);
        Route::get('/roles',      [UserController::class, 'roles']);

        // ✅ Routes fixes AVANT le paramètre dynamique
        Route::get('/admins',       [UserController::class, 'admins']);
        Route::get('/entreprises',  [UserController::class, 'entreprises']);
        Route::get('/prestataires', [UserController::class, 'prestataires']);

        Route::post('/',          [UserController::class, 'store']);

        // ❗ Empêche {user} de matcher les mots réservés
        $notReserved = '^(?!admins$|entreprises$|prestataires$|me$|roles$).+';

        Route::get('/{user}',     [UserController::class, 'show'])->where('user', $notReserved);
        Route::put('/{user}',     [UserController::class, 'update'])->where('user', $notReserved);
        Route::patch('/{user}',   [UserController::class, 'update'])->where('user', $notReserved);
        Route::delete('/{user}',  [UserController::class, 'destroy'])->where('user', $notReserved);

        Route::get('/{user}/bookings',           [UserController::class, 'bookings'])->where('user', $notReserved);
        Route::get('/{user}/service-offerings',  [UserController::class, 'serviceOfferings'])->where('user', $notReserved);
        Route::get('/{user}/reviews',            [UserController::class, 'reviews'])->where('user', $notReserved);
        Route::get('/{user}/availability',       [UserController::class, 'availability'])->where('user', $notReserved);

        Route::post('/{user}/avatar',            [UserController::class, 'uploadAvatar'])->where('user', $notReserved);
        Route::post('/{user}/password',          [UserController::class, 'changePassword'])->where('user', $notReserved);

        Route::post('/{user}/subscription/assign', [UserController::class, 'assignSubscription'])->where('user', $notReserved);
        Route::post('/{user}/subscription/revoke', [UserController::class, 'revokeSubscription'])->where('user', $notReserved);


        // NEW: meetings & contracts d’un user
        Route::get('/{user}/meetings',  [UserController::class, 'meetings'])->where('user', $notReserved);
        Route::get('/{user}/contracts', [UserController::class, 'contracts'])->where('user', $notReserved);
    });

    Route::prefix('meetings')->group(function () {
        Route::get('/',               [MeetingController::class, 'index']);
        Route::get('/{meeting}',      [MeetingController::class, 'show']);
        Route::post('/',              [MeetingController::class, 'store']);
        Route::put('/{meeting}',      [MeetingController::class, 'update']);
        Route::patch('/{meeting}',    [MeetingController::class, 'update']);
        Route::delete('/{meeting}',   [MeetingController::class, 'destroy']);

        Route::post('/{meeting}/slots',        [MeetingController::class, 'addSlots']);
        Route::post('/{meeting}/select-slot',  [MeetingController::class, 'selectSlot']);
    });


    Route::prefix('categories')->group(function () {
        // --- Categories
        Route::get('/', [CategoryController::class, 'index']); // tu peux aussi faire ceci: GET /api/categories?search=Beauté
        Route::get('/{category}', [CategoryController::class, 'show']); // {category} = slug
        Route::get('/{category}/subcategories', [CategoryController::class, 'subcategories']); // liste des subcats d'une catégorie
        // 👉 nouvelle route
        Route::get('/{category}/providers', [CategoryController::class, 'providers']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);

    });

    Route::prefix('subcategories')->group(function () {
        Route::get('/', [SubCategoryController::class, 'index']); // GET /api/subcategories?search=...
        Route::get('/{subCategory}', [SubCategoryController::class, 'show']); // {subCategory} = slug ou id
        Route::post('/', [SubCategoryController::class, 'store']); // POST /api/subcategories
        Route::put('/{subCategory}', [SubCategoryController::class, 'update']); // PUT /api/subcategories/{slug}
        Route::patch('/{subCategory}', [SubCategoryController::class, 'update']); // PATCH aussi
        Route::get('/{subCategory}/providers', [SubCategoryController::class, 'providers']);
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

    // Bloc /api/payments
    Route::prefix('payments')->group(function () {
        // CRUD principal
        Route::get('/', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('/', [PaymentController::class, 'store'])->name('payments.store');
        Route::match(['put','patch'], '/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        // Actions métier
        Route::post('/{payment}/authorize', [PaymentController::class, 'authorizePayment'])->name('payments.authorize');
        Route::post('/{payment}/capture',   [PaymentController::class, 'capture'])->name('payments.capture');
        Route::post('/{payment}/fail',      [PaymentController::class, 'fail'])->name('payments.fail');
        Route::post('/{payment}/refund',    [PaymentController::class, 'refund'])->name('payments.refund');
        Route::post('/{payment}/recompute-net', [PaymentController::class, 'recomputeNet'])->name('payments.recompute_net');
    });


    // /api/subscriptions...
    Route::prefix('subscriptions')->group(function () {
        Route::get('/',            [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');

        Route::post('/',           [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::match(['put','patch'],'/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');

        // Actions
        Route::post('/{subscription}/cancel',            [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('/{subscription}/expire',            [SubscriptionController::class, 'expire'])->name('subscriptions.expire');
        Route::post('/{subscription}/activate',          [SubscriptionController::class, 'activate'])->name('subscriptions.activate');
        Route::post('/{subscription}/toggle-auto-renew', [SubscriptionController::class, 'toggleAutoRenew'])->name('subscriptions.toggle_auto_renew');
        Route::post('/{subscription}/compute-commission',[SubscriptionController::class, 'computeCommission'])->name('subscriptions.compute_commission');
    });



    Route::prefix('commissions')->group(function () {
        // CRUD
        Route::get('/',               [CommissionController::class, 'index'])->name('commissions.index');
        Route::get('/{commission}',   [CommissionController::class, 'show'])->name('commissions.show');
        Route::post('/',              [CommissionController::class, 'store'])->name('commissions.store');
        Route::match(['put','patch'], '/{commission}', [CommissionController::class, 'update'])->name('commissions.update');
        Route::delete('/{commission}',[CommissionController::class, 'destroy'])->name('commissions.destroy');

        // Actions
        Route::post('/{commission}/capture',          [CommissionController::class, 'capture'])->name('commissions.capture');
        Route::post('/{commission}/settle',           [CommissionController::class, 'settle'])->name('commissions.settle');
        Route::post('/{commission}/refund',           [CommissionController::class, 'refund'])->name('commissions.refund');
        Route::post('/{commission}/cancel',           [CommissionController::class, 'cancel'])->name('commissions.cancel');
        Route::post('/{commission}/recompute-amount', [CommissionController::class, 'recomputeAmount'])->name('commissions.recompute_amount');
    });


    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/{review}', [ReviewController::class, 'show'])->name('reviews.show');

        Route::post('/', [ReviewController::class, 'store'])->name('reviews.store');
        Route::match(['put','patch'], '/{review}', [ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::post('/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/{review}/unapprove', [ReviewController::class, 'unapprove'])->name('reviews.unapprove');
    });


    Route::prefix('availability-slots')->group(function () {
        Route::get('/', [AvailabilitySlotController::class, 'index']);
        Route::get('/{availabilitySlot}', [AvailabilitySlotController::class, 'show']);

        Route::post('/', [AvailabilitySlotController::class, 'store']);
        Route::patch('/{availabilitySlot}', [AvailabilitySlotController::class, 'update']);
        Route::put('/{availabilitySlot}', [AvailabilitySlotController::class, 'update']);
        Route::delete('/{availabilitySlot}', [AvailabilitySlotController::class, 'destroy']);

        Route::post('/{availabilitySlot}/book', [AvailabilitySlotController::class, 'book']);
        Route::post('/{availabilitySlot}/unbook', [AvailabilitySlotController::class, 'unbook']);
        Route::post('/{availabilitySlot}/status', [AvailabilitySlotController::class, 'setStatus']);
    });

    Route::prefix('metrics')->group(function () {
        Route::get('/overview', [MetricsController::class, 'overview']); // GET /api/metrics/overview?period=30d
    });

    Route::prefix('subcategories/{subCategory}/images')->group(function () {
        Route::get('/', [SubCategoryImageController::class, 'index']);
        Route::get('/{image}', [SubCategoryImageController::class, 'show']);
        Route::post('/', [SubCategoryImageController::class, 'store']);
        Route::patch('/{image}', [SubCategoryImageController::class, 'update']);
        Route::post('/reorder', [SubCategoryImageController::class, 'reorder']);
        Route::post('/{image}/primary', [SubCategoryImageController::class, 'setPrimary']);
        Route::delete('/{image}', [SubCategoryImageController::class, 'destroy']);
    });

    Route::prefix('newsletter')->group(function () {
        Route::post('/subscribe',   [NewsletterController::class, 'subscribe']);
        Route::post('/unsubscribe', [NewsletterController::class, 'unsubscribe']);
        Route::get('/confirm/{token}', [NewsletterController::class, 'confirm']);
    });


    Route::middleware('auth:api')->prefix('messages')->group(function () {
        // Lister mes threads
        Route::get('/threads', [MessageThreadController::class, 'index']);

        // Créer / récupérer le thread pour un service
        Route::post('/threads/ensure', [MessageThreadController::class, 'ensure']);

        // Messages d’un thread
        Route::get('/threads/{thread}/messages',  [MessageThreadController::class, 'listMessages']);
        Route::post('/threads/{thread}/messages', [MessageThreadController::class, 'send']);

        // Marquer un thread comme lu
        Route::post('/threads/{thread}/read', [MessageThreadController::class, 'markRead']);
    });

});
