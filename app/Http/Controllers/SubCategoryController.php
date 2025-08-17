<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubCategoryController extends Controller
{
    public function __construct()
    {
        // Protéger toutes les routes de ce contrôleur
        // $this->middleware('auth:api');

        // OU : protéger seulement certaines actions
        $this->middleware('auth:api')->only(['store', 'update', 'destroy']);

        // OU : protéger toutes sauf certaines
        // $this->middleware('auth:api')->except(['index', 'show']);
    }
    /**
     * @OA\Get(
     *   path="/api/subcategories",
     *   tags={"SubCategories"},
     *   summary="Lister les sous-catégories",
     *   description="Filtrer par category_id, category_slug et recherche textuelle.",
     *   @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="category_slug", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="search", in="query", description="Recherche par nom", @OA\Schema(type="string")),
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
     *         @OA\Items(ref="#/components/schemas/SubCategory")
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $query = SubCategory::with('category')
            ->withCount('serviceOfferings');

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        if ($request->filled('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('name', 'like', "%{$s}%");
        }

        $subs = $query->orderBy('name')->get();

        return response()->success($subs, 'Affichage parfait');
    }

    /**
     * @OA\Get(
     *   path="/api/subcategories/{subCategory}",
     *   tags={"SubCategories"},
     *   summary="Afficher une sous-catégorie",
     *   description="{subCategory} est le slug si getRouteKeyName() = 'slug'.",
     *   @OA\Parameter(
     *     name="subCategory",
     *     in="path",
     *     required=true,
     *     description="Slug (ou ID selon binding)",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Détails complets de la sous-catégorie récupérés"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/SubCategory")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(SubCategory $subCategory)
    {
        $subCategory->load([
            'category',
        ])->loadCount('serviceOfferings')->toArray();

        return response()->success(
            [$subCategory],
            'Détails complets de la sous-catégorie récupérés'
        );
    }

    /**
     * @OA\Post(
     *   path="/api/subcategories",
     *   tags={"SubCategories"},
     *   summary="Créer une sous-catégorie",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"name"},
     *       @OA\Property(property="category_id", type="integer", nullable=true, example=3),
     *       @OA\Property(property="category_slug", type="string", nullable=true, example="nettoyage"),
     *       @OA\Property(property="name", type="string", example="Nettoyage de bureaux"),
     *       @OA\Property(property="slug", type="string", nullable=true, example="nettoyage-de-bureaux"),
     *       @OA\Property(property="icon", type="string", nullable=true, example="broom"),
     *       @OA\Property(property="providers_count", type="integer", nullable=true, example=12),
     *       @OA\Property(property="average_price", type="string", nullable=true, example="25 000 XAF"),
     *       @OA\Property(property="description", type="string", nullable=true, example="Sous-catégorie de nettoyage...")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Créé",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Sous-catégorie créée avec succès"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/SubCategory")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'name'          => ['required', 'string', 'max:255', 'unique:sub_categories,name'],
            'slug'          => ['nullable', 'string', 'max:255', 'unique:sub_categories,slug'],
            'icon'          => ['nullable', 'string', 'max:255'],
            'providers_count' => ['nullable', 'integer', 'min:0'],
            'average_price' => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
        ]);

        if (empty($data['category_id']) && !empty($data['category_slug'])) {
            $data['category_id'] = Category::where('slug', $data['category_slug'])->value('id');
        }
        if (empty($data['category_id'])) {
            return response()->json(['message' => 'category_id ou category_slug est requis'], 422);
        }
        unset($data['category_slug']);

        $sub = SubCategory::create($data);
        $sub->load(['category:id,slug,name'])->loadCount('serviceOfferings');

        return response()->success(
            [$sub->toArray()],
            'Sous-catégorie créée avec succès'
        );
    }

    /**
     * @OA\Patch(
     *   path="/api/subcategories/{subCategory}",
     *   tags={"SubCategories"},
     *   summary="Mettre à jour une sous-catégorie",
     *   @OA\Parameter(
     *     name="subCategory",
     *     in="path",
     *     required=true,
     *     description="Slug (ou ID selon binding)",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="category_id", type="integer", nullable=true, example=3),
     *       @OA\Property(property="category_slug", type="string", nullable=true, example="nettoyage"),
     *       @OA\Property(property="name", type="string", nullable=true, example="Nettoyage vitres"),
     *       @OA\Property(property="slug", type="string", nullable=true, example="nettoyage-vitres"),
     *       @OA\Property(property="icon", type="string", nullable=true, example="sparkles"),
     *       @OA\Property(property="providers_count", type="integer", nullable=true, example=20),
     *       @OA\Property(property="average_price", type="string", nullable=true, example="35 000 XAF"),
     *       @OA\Property(property="description", type="string", nullable=true, example="Mise à jour de la description...")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Sous-catégorie mise à jour avec succès"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/SubCategory")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     * @OA\Put(
     *   path="/api/subcategories/{subCategory}",
     *   tags={"SubCategories"},
     *   summary="Mettre à jour une sous-catégorie (PUT)",
     *   @OA\Parameter(
     *     name="subCategory",
     *     in="path",
     *     required=true,
     *     description="Slug (ou ID selon binding)",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="category_id", type="integer", nullable=true, example=3),
     *       @OA\Property(property="category_slug", type="string", nullable=true, example="nettoyage"),
     *       @OA\Property(property="name", type="string", nullable=true, example="Nettoyage vitres"),
     *       @OA\Property(property="slug", type="string", nullable=true, example="nettoyage-vitres"),
     *       @OA\Property(property="icon", type="string", nullable=true, example="sparkles"),
     *       @OA\Property(property="providers_count", type="integer", nullable=true, example=20),
     *       @OA\Property(property="average_price", type="string", nullable=true, example="35 000 XAF"),
     *       @OA\Property(property="description", type="string", nullable=true, example="Mise à jour de la description...")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Sous-catégorie mise à jour avec succès"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/SubCategory")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, SubCategory $subCategory)
    {
        $data = $request->validate([
            'category_id'   => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['sometimes', 'nullable', 'string', 'exists:categories,slug'],
            'name'          => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sub_categories', 'name')->ignore($subCategory->id)],
            'slug'          => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('sub_categories', 'slug')->ignore($subCategory->id)],
            'icon'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'providers_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'average_price' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'   => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_key_exists('category_slug', $data)) {
            if (!empty($data['category_slug'])) {
                $data['category_id'] = Category::where('slug', $data['category_slug'])->value('id');
            }
            unset($data['category_slug']);
        }

        $subCategory->fill($data)->save();
        $subCategory->load(['category:id,slug,name'])->loadCount('serviceOfferings');

        return response()->success(
            [$subCategory->toArray()],
            'Sous-catégorie mise à jour avec succès'
        );
    }
}
