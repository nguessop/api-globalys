<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // GET /api/categories?search=...
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

        // return response()->json($categories);
        return response()->success(
            $categories,
            'Affichage parfait'
        );
    }

    // GET /api/categories/{category}
    // Remarque: ton modèle Category retourne getRouteKeyName() = 'slug', donc {category} = slug
    public function show(Category $category)
    {
        $res = $category->load([
            'subCategories' => function ($q) {
                $q->select('id', 'category_id', 'slug', 'name', 'icon')
                    ->withCount('serviceOfferings');
            },
        ])->toArray(); // 🔹 conversion en array

        return response()->success(
            [$res],
            'Détails complets de la catégorie récupérés'
        );
    }

    // GET /api/categories/{category}/subcategories
    public function subcategories(Category $category)
    {
        $subs = $category->subCategories()
            ->select()
            ->withCount('serviceOfferings')
            ->orderBy('name')
            ->get();

        // return response()->json($subs);
        return response()->success(
            $subs,
            'Vos sous catégories affichées'
        );
    }

    // POST /api/categories
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'icon'        => ['nullable', 'string', 'max:255'],
            'color_class' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        // Le modèle génèrera un slug unique si slug est null (via booted())
        $category = Category::create($data);

        // Charger les sous-catégories pour la réponse (même format que show)
        $category->load([
            'subCategories' => function ($q) {
                $q->select('id', 'category_id', 'slug', 'name', 'icon')
                    ->withCount('serviceOfferings');
            },
        ]);

        return response()->success(
            [$category->toArray()],
            'Catégorie créée avec succès'
        );
    }

    // PUT/PATCH /api/categories/{category}  (ID ou slug, si tu as ajouté resolveRouteBinding)
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

        return response()->success(
            [$category->toArray()],
            'Catégorie mise à jour avec succès'
        );
    }
}
