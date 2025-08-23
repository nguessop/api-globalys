<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ServiceOffering;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class CategoryController extends Controller
{
    public function __construct()
    {
        // Protège les méthodes d’écriture par JWT (guard api)
        $this->middleware('auth:api')->only(['store', 'update']);
    }

    /**
     * @OA\Get(
     *   path="/api/categories",
     *   tags={"Categories"},
     *   summary="Lister les catégories (avec sous-catégories)",
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Filtre par nom de catégorie (LIKE)",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Affichage parfait"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="slug", type="string", example="nettoyage"),
     *           @OA\Property(property="name", type="string", example="Nettoyage"),
     *           @OA\Property(property="icon", type="string", nullable=true, example="broom"),
     *           @OA\Property(property="color_class", type="string", nullable=true, example="bg-indigo-500"),
     *           @OA\Property(property="description", type="string", nullable=true, example="Services de nettoyage"),
     *           @OA\Property(property="sub_categories_count", type="integer", example=5),
     *           @OA\Property(
     *             property="sub_categories",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=11),
     *               @OA\Property(property="category_id", type="integer", example=1),
     *               @OA\Property(property="slug", type="string", example="nettoyage-bureaux"),
     *               @OA\Property(property="name", type="string", example="Nettoyage de bureaux"),
     *               @OA\Property(property="icon", type="string", nullable=true, example="building"),
     *               @OA\Property(property="service_offerings_count", type="integer", example=12)
     *             )
     *           )
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $query = Category::query()
            ->withCount('subCategories')
            ->with(['subCategories' => function ($q) {
                $q->select();
            }]);

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('name', 'like', "%{$s}%");
        }

        $categories = $query->orderBy('name')->get();

        return response()->success($categories, 'Affichage parfait');
    }

    /**
     * @OA\Get(
     *   path="/api/categories/{category}",
     *   tags={"Categories"},
     *   summary="Détails d’une catégorie (par slug)",
     *   description="Le modèle Category utilise getRouteKeyName() = 'slug'.",
     *   @OA\Parameter(
     *     name="category",
     *     in="path",
     *     required=true,
     *     description="Slug de la catégorie",
     *     @OA\Schema(type="string", example="nettoyage")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Détails complets de la catégorie récupérés"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="slug", type="string", example="nettoyage"),
     *           @OA\Property(property="name", type="string", example="Nettoyage"),
     *           @OA\Property(property="icon", type="string", nullable=true, example="broom"),
     *           @OA\Property(property="color_class", type="string", nullable=true, example="bg-indigo-500"),
     *           @OA\Property(property="description", type="string", nullable=true),
     *           @OA\Property(
     *             property="sub_categories",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=11),
     *               @OA\Property(property="category_id", type="integer", example=1),
     *               @OA\Property(property="slug", type="string", example="nettoyage-bureaux"),
     *               @OA\Property(property="name", type="string", example="Nettoyage de bureaux"),
     *               @OA\Property(property="icon", type="string", nullable=true, example="building"),
     *               @OA\Property(property="service_offerings_count", type="integer", example=12)
     *             )
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Category $category)
    {
        $res = $category->load([
            'subCategories' => function ($q) {
                $q->select('id', 'category_id', 'slug', 'name', 'icon')
                    ->withCount('serviceOfferings');
            },
        ])->toArray();

        return response()->success([$res], 'Détails complets de la catégorie récupérés');
    }

    /**
     * @OA\Get(
     *   path="/api/categories/{category}/subcategories",
     *   tags={"Categories"},
     *   summary="Lister les sous-catégories d’une catégorie",
     *   @OA\Parameter(
     *     name="category",
     *     in="path",
     *     required=true,
     *     description="Slug de la catégorie",
     *     @OA\Schema(type="string", example="nettoyage")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Vos sous catégories affichées"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=11),
     *           @OA\Property(property="category_id", type="integer", example=1),
     *           @OA\Property(property="slug", type="string", example="nettoyage-bureaux"),
     *           @OA\Property(property="name", type="string", example="Nettoyage de bureaux"),
     *           @OA\Property(property="icon", type="string", nullable=true, example="building"),
     *           @OA\Property(property="service_offerings_count", type="integer", example=12)
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function subcategories(Category $category)
    {
        $subs = $category->subCategories()
            ->select()
            ->withCount('serviceOfferings')
            ->orderBy('name')
            ->get();

        return response()->success($subs, 'Vos sous catégories affichées');
    }

    /**
     * @OA\Post(
     *   path="/api/categories",
     *   tags={"Categories"},
     *   summary="Créer une catégorie",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name"},
     *       @OA\Property(property="name", type="string", example="Nettoyage"),
     *       @OA\Property(property="slug", type="string", nullable=true, example="nettoyage"),
     *       @OA\Property(property="icon", type="string", nullable=true, example="broom"),
     *       @OA\Property(property="color_class", type="string", nullable=true, example="bg-indigo-500"),
     *       @OA\Property(property="description", type="string", nullable=true, example="Services de nettoyage")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Créé",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Catégorie créée avec succès"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="slug", type="string", example="nettoyage"),
     *           @OA\Property(property="name", type="string", example="Nettoyage"),
     *           @OA\Property(property="icon", type="string", nullable=true),
     *           @OA\Property(property="color_class", type="string", nullable=true),
     *           @OA\Property(property="description", type="string", nullable=true),
     *           @OA\Property(property="sub_categories", type="array", @OA\Items(type="object"))
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'icon'        => ['nullable', 'string', 'max:255'],
            'color_class' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $category = Category::create($data);

        $category->load([
            'subCategories' => function ($q) {
                $q->select('id', 'category_id', 'slug', 'name', 'icon')
                    ->withCount('serviceOfferings');
            },
        ]);

        return response()->success([$category->toArray()], 'Catégorie créée avec succès');
    }

    /**
     * @OA\Patch(
     *   path="/api/categories/{category}",
     *   tags={"Categories"},
     *   summary="Mettre à jour une catégorie (PATCH, par slug)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="category",
     *     in="path",
     *     required=true,
     *     description="Slug de la catégorie",
     *     @OA\Schema(type="string", example="nettoyage")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="Nettoyage & Entretien"),
     *       @OA\Property(property="slug", type="string", nullable=true, example="nettoyage-entretien"),
     *       @OA\Property(property="icon", type="string", nullable=true, example="broom"),
     *       @OA\Property(property="color_class", type="string", nullable=true, example="bg-indigo-600"),
     *       @OA\Property(property="description", type="string", nullable=true, example="Mise à jour de la description")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Catégorie mise à jour avec succès"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="slug", type="string", example="nettoyage-entretien"),
     *           @OA\Property(property="name", type="string", example="Nettoyage & Entretien"),
     *           @OA\Property(property="icon", type="string", nullable=true),
     *           @OA\Property(property="color_class", type="string", nullable=true),
     *           @OA\Property(property="description", type="string", nullable=true),
     *           @OA\Property(
     *             property="sub_categories",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=11),
     *               @OA\Property(property="category_id", type="integer", example=1),
     *               @OA\Property(property="slug", type="string", example="nettoyage-bureaux"),
     *               @OA\Property(property="name", type="string", example="Nettoyage de bureaux"),
     *               @OA\Property(property="icon", type="string", nullable=true),
     *               @OA\Property(property="service_offerings_count", type="integer", example=12)
     *             )
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     *
     * @OA\Put(
     *   path="/api/categories/{category}",
     *   tags={"Categories"},
     *   summary="Mettre à jour une catégorie (PUT, par slug)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="category",
     *     in="path",
     *     required=true,
     *     description="Slug de la catégorie",
     *     @OA\Schema(type="string", example="nettoyage")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="Nettoyage & Entretien"),
     *       @OA\Property(property="slug", type="string", nullable=true, example="nettoyage-entretien"),
     *       @OA\Property(property="icon", type="string", nullable=true, example="broom"),
     *       @OA\Property(property="color_class", type="string", nullable=true, example="bg-indigo-600"),
     *       @OA\Property(property="description", type="string", nullable=true, example="Mise à jour de la description")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'slug'        => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'icon'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'color_class' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $category->fill($data)->save();

        $category->load([
            'subCategories' => function ($q) {
                $q->select('id', 'category_id', 'slug', 'name', 'icon')
                    ->withCount('serviceOfferings');
            },
        ]);

        return response()->success([$category->toArray()], 'Catégorie mise à jour avec succès');
    }


    /**
     * Liste paginée des prestataires ayant au moins une offre dans la catégorie.
     *
     * @OA\Get(
     *   path="/api/categories/{category}/providers",
     *   tags={"Categories"},
     *   summary="Prestataires par catégorie",
     *   description="Retourne les utilisateurs (prestataires) qui ont au moins une service_offering dans une sous-catégorie de cette catégorie.",
     *   @OA\Parameter(
     *     name="category",
     *     in="path",
     *     required=true,
     *     description="Slug (ou ID si le binding est configuré ainsi) de la catégorie",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=12)),
     *   @OA\Parameter(name="city", in="query", description="Filtrer par ville", @OA\Schema(type="string")),
     *   @OA\Parameter(name="search", in="query", description="Nom/prénom/entreprise", @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Prestataires listés avec succès"),
     *       @OA\Property(property="data", type="object")
     *     )
     *   )
     * )
     */
    public function providers(Request $request, Category $category)
    {
        $perPage = (int) $request->get('per_page', 12);
        $city    = $request->get('city');
        $search  = $request->get('search');

        // includes optionnels (whitelist simple)
        $allowedIncludes = ['role','currentSubscription','subscriptions','serviceOfferings','reviewsReceived','availabilitySlots'];
        $includes = [];
        if ($inc = $request->get('include')) {
            $parts = array_filter(array_map('trim', explode(',', $inc)));
            $includes = array_values(array_intersect($parts, $allowedIncludes));
        }

        // Tous les providers qui ont AU MOINS une offre dans une sous-cat de cette catégorie
        $providerIds = ServiceOffering::query()
            ->select('provider_id')
            ->whereHas('subCategory', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->groupBy('provider_id');

        $query = User::query()
            ->with($includes)
            ->whereHas('role', function ($q) {
                $q->where('name', 'prestataire');
            })
            ->whereIn('id', $providerIds)
            ->when($city, function ($q) use ($city) {
                $q->where(function ($qq) use ($city) {
                    $qq->where('company_city', $city)
                        ->orWhere('personal_address', 'like', "%{$city}%");
                });
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->withCount(['serviceOfferings as services_count' => function ($q) use ($category) {
                $q->whereHas('subCategory', function ($qq) use ($category) {
                    $qq->where('category_id', $category->id);
                });
            }])
            ->orderBy('created_at', 'desc');

        $users = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate($perPage);

        return response()->success($users, 'Prestataires listés avec succès');
    }
}
