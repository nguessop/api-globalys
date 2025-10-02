<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ServiceOffering;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
     *   summary="Lister les catégories avec sous-catégories",
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Filtre par nom de catégorie (LIKE)",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Liste des catégories",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Catégories et sous-catégories affichées"),
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="slug", type="string", example="sante"),
     *           @OA\Property(property="name", type="string", example="Santé & Bien-être"),
     *           @OA\Property(property="icon", type="string", example="heart"),
     *           @OA\Property(property="description", type="string", example="Santé, beauté, sport"),
     *           @OA\Property(property="sub_categories_count", type="integer", example=4),
     *           @OA\Property(property="sub_categories", type="array",
     *             @OA\Items(
     *               @OA\Property(property="id", type="integer", example=10),
     *               @OA\Property(property="category_id", type="integer", example=1),
     *               @OA\Property(property="slug", type="string", example="teleconsultation"),
     *               @OA\Property(property="name", type="string", example="Téléconsultation"),
     *               @OA\Property(property="icon", type="string", example="stethoscope"),
     *               @OA\Property(property="description", type="string", example="Consultation médicale à distance"),
     *               @OA\Property(property="service_offerings_count", type="integer", example=15)
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
            ->whereNotNull('parent_id') // ✅ uniquement les catégories racines
            ->withCount('subCategories')
            ->with([
                'subCategories' => function ($q) {
                    $q->select('id', 'category_id', 'slug', 'name', 'icon', 'description')
                        ->withCount('serviceOfferings')
                        ->orderBy('name');
                },
            ]);

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('name', 'like', "%{$s}%");
        }

        $categories = $query->orderBy('name')->get();

        // ⚡ Normalisation pour que le front reçoive toujours `sub_categories`
        $categories->transform(function ($cat) {
            $cat->sub_categories = $cat->subCategories;
            unset($cat->subCategories);
            return $cat;
        });

        return response()->success($categories, 'Catégories et sous-catégories affichées');
    }



    /**
     * @OA\Get(
     *   path="/api/categories/{category}",
     *   tags={"Categories"},
     *   summary="Détails d’une catégorie (par slug)",
     *   @OA\Parameter(
     *     name="category",
     *     in="path",
     *     required=true,
     *     description="Slug de la catégorie",
     *     @OA\Schema(type="string", example="sante")
     *   ),
     *   @OA\Response(response=200, description="Détails de la catégorie"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Category $category)
    {
        $res = $category->load([
            'subCategories' => function ($q) {
                $q->select('id', 'category_id', 'slug', 'name', 'icon', 'description')
                    ->withCount('serviceOfferings')
                    ->orderBy('name');
            },
        ]);

        return response()->success($res, 'Détails complets de la catégorie récupérés');
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
     *     @OA\Schema(type="string", example="sante")
     *   ),
     *   @OA\Response(response=200, description="Sous-catégories listées")
     * )
     */
    public function subcategories(Category $category)
    {
        $subs = $category->subCategories()
            ->select('id', 'category_id', 'slug', 'name', 'icon', 'description')
            ->withCount('serviceOfferings')
            ->orderBy('name')
            ->get();

        return response()->success($subs, 'Sous-catégories affichées');
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
     *       @OA\Property(property="name", type="string", example="Santé & Bien-être"),
     *       @OA\Property(property="slug", type="string", example="sante"),
     *       @OA\Property(property="icon", type="string", example="heart"),
     *       @OA\Property(property="color_class", type="string", example="bg-pink-500"),
     *       @OA\Property(property="description", type="string", example="Santé, beauté, sport")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Catégorie créée"),
     *   @OA\Response(response=422, description="Validation error")
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
                $q->select('id', 'category_id', 'slug', 'name', 'icon', 'description')
                    ->withCount('serviceOfferings');
            },
        ]);

        return response()->success($category, 'Catégorie créée avec succès', 201);
    }

    /**
     * @OA\Put(
     *   path="/api/categories/{category}",
     *   tags={"Categories"},
     *   summary="Mettre à jour une catégorie",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="category", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     @OA\Property(property="name", type="string", example="Santé & Bien-être modifié"),
     *     @OA\Property(property="slug", type="string", example="sante-bien-etre"),
     *     @OA\Property(property="description", type="string", example="Mise à jour de la description")
     *   )),
     *   @OA\Response(response=200, description="Mise à jour réussie"),
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
                $q->select('id', 'category_id', 'slug', 'name', 'icon', 'description')
                    ->withCount('serviceOfferings');
            },
        ]);

        return response()->success($category, 'Catégorie mise à jour avec succès');
    }

    /**
     * @OA\Get(
     *   path="/api/categories/{category}/providers",
     *   tags={"Categories"},
     *   summary="Lister les prestataires par catégorie",
     *   @OA\Parameter(name="category", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=12)),
     *   @OA\Parameter(name="city", in="query", description="Filtrer par ville", @OA\Schema(type="string")),
     *   @OA\Parameter(name="search", in="query", description="Filtrer par nom ou entreprise", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Prestataires listés")
     * )
     */
    public function providers(Request $request, Category $category)
    {
        $perPage = (int) $request->get('per_page', 12);
        $city    = $request->get('city');
        $search  = $request->get('search');

        $providerIds = ServiceOffering::query()
            ->select('provider_id')
            ->whereHas('subCategory', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->groupBy('provider_id');

        $query = User::query()
            ->whereIn('id', $providerIds)
            ->withCount(['serviceOfferings as services_count' => function ($q) use ($category) {
                $q->whereHas('subCategory', function ($qq) use ($category) {
                    $qq->where('category_id', $category->id);
                });
            }])
            ->when($city, fn($q) => $q->where('company_city', $city)->orWhere('personal_address', 'like', "%{$city}%"))
            ->when($search, fn($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc');

        $users = $request->get('per_page') === 'all'
            ? $query->get()
            : $query->paginate($perPage);

        return response()->success($users, 'Prestataires listés avec succès');
    }
}
