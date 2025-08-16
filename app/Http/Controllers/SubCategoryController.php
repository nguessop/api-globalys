<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

class SubCategoryController extends Controller
{
    // GET /api/subcategories?search=...&category_id=...&category_slug=...
    public function index(Request $request)
    {
        $query = SubCategory::with('category')
            ->withCount('serviceOfferings');

        // Filtre par catégorie via ID
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        // Filtre par catégorie via slug
        if ($request->filled('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        // Recherche textuelle
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('name', 'like', "%{$s}%");
        }

        $subs = $query->orderBy('name')->get();

        // return response()->json($subs);
        return response()->success(
            $subs,
            'Affichage parfait'
        );
    }

    // GET /api/subcategories/{subCategory}
    // Ton modèle SubCategory a getRouteKeyName() = 'slug', donc {subCategory} = slug
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

    // POST /api/subcategories
    public function store(Request $request)
    {
        $data = $request->validate([
            // on accepte category_id OU category_slug
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['nullable', 'string', 'exists:categories,slug'],

            'name'          => ['required', 'string', 'max:255', 'unique:sub_categories,name'],
            'slug'          => ['nullable', 'string', 'max:255', 'unique:sub_categories,slug'],
            'icon'          => ['nullable', 'string', 'max:255'],
            'providers_count' => ['nullable', 'integer', 'min:0'],
            'average_price' => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
        ]);

        // Résoudre la catégorie depuis slug si fournie
        if (empty($data['category_id']) && !empty($data['category_slug'])) {
            $data['category_id'] = Category::where('slug', $data['category_slug'])->value('id');
        }
        if (empty($data['category_id'])) {
            return response()->json(['message' => 'category_id ou category_slug est requis'], 422);
        }
        unset($data['category_slug']);

        // Le modèle SubCategory rendra le slug unique (mutateur/hook)
        $sub = SubCategory::create($data);

        $sub->load(['category:id,slug,name'])->loadCount('serviceOfferings');

        return response()->success(
            [$sub->toArray()],
            'Sous-catégorie créée avec succès'
        );
    }

    // PUT/PATCH /api/subcategories/{subCategory}  (ID ou slug si tu l’as prévu)
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

        // Résoudre category via slug si fourni
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
