<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index','show']);
    }

    /**
     * @OA\Get(
     *   path="/api/reviews",
     *   tags={"Reviews"},
     *   summary="Lister les avis",
     *   description="Filtres: provider_id, user_id, service_offering_id, booking_id, approved=1/0, rating_min/max, q. Tri & pagination.",
     *   @OA\Parameter(name="provider_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="user_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="service_offering_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="booking_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="approved", in="query", @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="rating_min", in="query", @OA\Schema(type="integer", minimum=1, maximum=5)),
     *   @OA\Parameter(name="rating_max", in="query", @OA\Schema(type="integer", minimum=1, maximum=5)),
     *   @OA\Parameter(name="q", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="dir", in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=100)),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $req)
    {
        $q = Review::query()->with([
            'user:id,first_name,last_name,email',
            'provider:id,first_name,last_name,company_name',
            'serviceOffering:id,title,provider_id',
            'booking:id,code,client_id,provider_id',
        ]);

        if ($req->filled('provider_id'))        $q->where('provider_id', (int)$req->input('provider_id'));
        if ($req->filled('user_id'))            $q->where('user_id', (int)$req->input('user_id'));
        if ($req->filled('service_offering_id'))$q->where('service_offering_id', (int)$req->input('service_offering_id'));
        if ($req->filled('booking_id'))         $q->where('booking_id', (int)$req->input('booking_id'));

        if ($req->filled('approved')) {
            (int)$req->input('approved') === 1
                ? $q->where('is_approved', true)
                : $q->where('is_approved', false);
        }

        if ($req->filled('rating_min')) $q->where('rating', '>=', (int)$req->input('rating_min'));
        if ($req->filled('rating_max')) $q->where('rating', '<=', (int)$req->input('rating_max'));

        if ($req->filled('q')) {
            $term = trim((string)$req->input('q'));
            $q->where('comment', 'like', "%{$term}%");
        }

        $sort = (string)$req->input('sort','created_at');
        $dir  = strtolower((string)$req->input('dir','desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at','rating'];
        if (!in_array($sort, $allowed, true)) $sort = 'created_at';
        $q->orderBy($sort, $dir);

        $perPage = max(1, min((int)$req->input('per_page', 15), 100));
        $data = $q->paginate($perPage);

        return response()->success($data, 'Liste des avis');
    }

    /**
     * @OA\Get(
     *   path="/api/reviews/{review}",
     *   tags={"Reviews"},
     *   summary="Afficher un avis",
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Review $review)
    {
        $review->load([
            'user:id,first_name,last_name,email',
            'provider:id,first_name,last_name,company_name',
            'serviceOffering:id,title,provider_id',
            'booking:id,code,client_id,provider_id',
        ]);

        return response()->success($review, 'Détails de l’avis');
    }

    /**
     * @OA\Post(
     *   path="/api/reviews",
     *   tags={"Reviews"},
     *   summary="Créer un avis",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(ref="#/components/requestBodies/ReviewStoreRequest"),
     *   @OA\Response(response=201, description="Créé"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $req)
    {
        $data = $req->validate([
            'user_id'             => ['required','integer','exists:users,id'],
            'provider_id'         => ['required','integer','exists:users,id'],
            'service_offering_id' => ['required','integer','exists:service_offerings,id'],
            'booking_id'          => ['required','integer','exists:bookings,id'],
            'rating'              => ['required','integer','min:1','max:5'],
            'comment'             => ['nullable','string','max:1000'],
            'is_approved'         => ['nullable','boolean'],
        ]);

        $review = Review::create($data);

        return response()->success($review->fresh(), 'Avis créé', 201);
    }

    /**
     * @OA\Patch(
     *   path="/api/reviews/{review}",
     *   tags={"Reviews"},
     *   summary="Mettre à jour un avis",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(ref="#/components/requestBodies/ReviewUpdateRequest"),
     *   @OA\Response(response=200, description="OK")
     * )
     * @OA\Put(
     *   path="/api/reviews/{review}",
     *   tags={"Reviews"},
     *   summary="Mettre à jour un avis (PUT)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(ref="#/components/requestBodies/ReviewUpdateRequest"),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(Request $req, Review $review)
    {
        $data = $req->validate([
            'user_id'             => ['sometimes','required','integer','exists:users,id'],
            'provider_id'         => ['sometimes','required','integer','exists:users,id'],
            'service_offering_id' => ['sometimes','required','integer','exists:service_offerings,id'],
            'booking_id'          => ['sometimes','required','integer','exists:bookings,id'],
            'rating'              => ['sometimes','required','integer','min:1','max:5'],
            'comment'             => ['sometimes','nullable','string','max:1000'],
            'is_approved'         => ['sometimes','nullable','boolean'],
        ]);

        $review->update($data);

        return response()->success($review->fresh(), 'Avis mis à jour');
    }

    /**
     * @OA\Delete(
     *   path="/api/reviews/{review}",
     *   tags={"Reviews"},
     *   summary="Supprimer un avis",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé")
     * )
     */
    public function destroy(Review $review)
    {
        $review->delete();
        return response()->success(null, 'Avis supprimé');
    }

    /**
     * @OA\Post(
     *   path="/api/reviews/{review}/approve",
     *   tags={"Reviews"},
     *   summary="Approuver / désapprouver un avis",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(ref="#/components/requestBodies/ReviewApproveRequest"),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function approve(Review $review, Request $req)
    {
        $approved = $req->has('approved') ? (bool)$req->input('approved') : true;
        $review->update(['is_approved' => $approved]);

        return response()->success($review->fresh(), $approved ? 'Avis approuvé' : 'Avis désapprouvé');
    }

    /**
     * @OA\Post(
     *   path="/api/reviews/{review}/unapprove",
     *   tags={"Reviews"},
     *   summary="Désapprouver un avis",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function unapprove(Review $review)
    {
        $review->update(['is_approved' => false]);
        return response()->success($review->fresh(), 'Avis désapprouvé');
    }
}
