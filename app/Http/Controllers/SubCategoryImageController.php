<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use App\Models\SubCategoryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *   name="SubCategory Images",
 *   description="Gestion des images liées aux sous-catégories"
 * )
 *
 * @OA\Schema(
 *   schema="SubCategoryImage",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=10),
 *   @OA\Property(property="sub_category_id", type="integer", example=3),
 *   @OA\Property(property="path", type="string", example="subcategories/abc123.jpg"),
 *   @OA\Property(property="url", type="string", example="https://example.com/storage/subcategories/abc123.jpg"),
 *   @OA\Property(property="alt", type="string", nullable=true, example="Photo de la sous-catégorie"),
 *   @OA\Property(property="is_primary", type="boolean", example=true),
 *   @OA\Property(property="sort_order", type="integer", example=1),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class SubCategoryImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index', 'show']);
    }

    /**
     * @OA\Get(
     *   path="/api/subcategories/{subCategory}/images",
     *   tags={"SubCategory Images"},
     *   summary="Lister les images d'une sous-catégorie",
     *   @OA\Parameter(
     *     name="subCategory", in="path", required=true,
     *     description="Slug ou ID selon ton binding",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200, description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="sub_category_id", type="integer"),
     *       @OA\Property(property="primary_image_url", type="string", nullable=true),
     *       @OA\Property(
     *         property="images", type="array",
     *         @OA\Items(ref="#/components/schemas/SubCategoryImage")
     *       )
     *     )
     *   )
     * )
     */
    public function index(SubCategory $subCategory)
    {
        $images = $subCategory->images()->get();

        return response()->success([
            'sub_category_id'   => $subCategory->id,
            'primary_image_url' => $subCategory->primary_image_url,
            'images'            => $images,
        ], 'Images listées');
    }

    /**
     * @OA\Get(
     *   path="/api/subcategories/{subCategory}/images/{image}",
     *   tags={"SubCategory Images"},
     *   summary="Afficher une image",
     *   @OA\Parameter(name="subCategory", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="image", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/SubCategoryImage")),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(SubCategory $subCategory, SubCategoryImage $image)
    {
        abort_unless($image->sub_category_id === $subCategory->id, 404);
        return response()->success($image, 'Détail image');
    }

    /**
     * @OA\Post(
     *   path="/api/subcategories/{subCategory}/images",
     *   tags={"SubCategory Images"},
     *   summary="Ajouter des images (upload et/ou URLs). Option 'replace' pour remplacer la liste.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subCategory", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(
     *     required=false,
     *     content={
     *       "multipart/form-data"=@OA\MediaType(
     *         mediaType="multipart/form-data",
     *         @OA\Schema(
     *           type="object",
     *           @OA\Property(property="replace", type="boolean", example=false),
     *           @OA\Property(property="primary_index", type="integer", minimum=0, example=0),
     *           @OA\Property(property="alt", type="string", example="Image principale"),
     *           @OA\Property(
     *             property="images",
     *             type="array",
     *             @OA\Items(type="string", format="binary")
     *           )
     *         )
     *       ),
     *       "application/json"=@OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *           type="object",
     *           @OA\Property(property="replace", type="boolean", example=false),
     *           @OA\Property(property="primary_index", type="integer", minimum=0, example=0),
     *           @OA\Property(property="alt", type="string", example="Image principale"),
     *           @OA\Property(
     *             property="image_urls",
     *             type="array",
     *             @OA\Items(type="string", format="uri")
     *           )
     *         )
     *       )
     *     }
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function store(Request $request, SubCategory $subCategory)
    {
        $data = $request->validate([
            'replace'        => ['sometimes','boolean'],
            'images'         => ['sometimes','array'],
            'images.*'       => ['file','image','max:4096'],
            'image_urls'     => ['sometimes','array'],
            'image_urls.*'   => ['string','max:2048'],
            'primary_index'  => ['sometimes','integer','min:0'],
            'alt'            => ['sometimes','nullable','string','max:255'],
        ]);

        DB::transaction(function () use ($subCategory, $data) {
            if (!empty($data['replace'])) {
                foreach ($subCategory->images as $img) {
                    if ($img->path && !str_starts_with($img->path, 'http')) {
                        Storage::disk('public')->delete($img->path);
                    }
                    $img->delete();
                }
            }

            $order   = (int) ($subCategory->images()->max('sort_order') ?? 0);
            $created = [];

            if (!empty($data['images'])) {
                foreach ($data['images'] as $file) {
                    $stored = $file->store('subcategories', 'public');
                    $order++;
                    $created[] = $subCategory->images()->create([
                        'path'       => $stored,
                        'alt'        => $data['alt'] ?? null,
                        'is_primary' => isset($data['primary_index']) && (int)$data['primary_index'] === count($created),
                        'sort_order' => $order,
                    ]);
                }
            }

            if (!empty($data['image_urls'])) {
                foreach ($data['image_urls'] as $url) {
                    $order++;
                    $created[] = $subCategory->images()->create([
                        'path'       => $url,
                        'alt'        => $data['alt'] ?? null,
                        'is_primary' => isset($data['primary_index']) && (int)$data['primary_index'] === count($created),
                        'sort_order' => $order,
                    ]);
                }
            }

            if (!$subCategory->primaryImage()->exists() && $subCategory->images()->exists()) {
                $first = $subCategory->images()->orderBy('sort_order')->first();
                $first?->update(['is_primary' => true]);
            }
        });

        return response()->success(
            $subCategory->load(['images','primaryImage']),
            'Images enregistrées'
        );
    }

    /**
     * @OA\Patch(
     *   path="/api/subcategories/{subCategory}/images/{image}",
     *   tags={"SubCategory Images"},
     *   summary="Mettre à jour une image (alt, is_primary, sort_order, remplacement fichier/URL)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subCategory", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="image", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=false,
     *     content={
     *       "multipart/form-data"=@OA\MediaType(
     *         mediaType="multipart/form-data",
     *         @OA\Schema(
     *           type="object",
     *           @OA\Property(property="alt", type="string", nullable=true),
     *           @OA\Property(property="is_primary", type="boolean"),
     *           @OA\Property(property="sort_order", type="integer", minimum=0),
     *           @OA\Property(property="image", type="string", format="binary")
     *         )
     *       ),
     *       "application/json"=@OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *           type="object",
     *           @OA\Property(property="alt", type="string", nullable=true),
     *           @OA\Property(property="is_primary", type="boolean"),
     *           @OA\Property(property="sort_order", type="integer", minimum=0),
     *           @OA\Property(property="path", type="string", description="URL de remplacement")
     *         )
     *       )
     *     }
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, SubCategory $subCategory, SubCategoryImage $image)
    {
        abort_unless($image->sub_category_id === $subCategory->id, 404);

        $data = $request->validate([
            'alt'        => ['sometimes','nullable','string','max:255'],
            'is_primary' => ['sometimes','boolean'],
            'sort_order' => ['sometimes','integer','min:0'],
            'image'      => ['sometimes','file','image','max:4096'],
            'path'       => ['sometimes','string','max:2048'],
        ]);

        DB::transaction(function () use ($subCategory, $image, $data, $request) {
            if ($request->hasFile('image')) {
                if ($image->path && !str_starts_with($image->path, 'http')) {
                    Storage::disk('public')->delete($image->path);
                }
                $stored = $request->file('image')->store('subcategories', 'public');
                $image->path = $stored;
            } elseif (array_key_exists('path', $data)) {
                if ($image->path && !str_starts_with($image->path, 'http')) {
                    Storage::disk('public')->delete($image->path);
                }
                $image->path = $data['path'];
            }

            if (array_key_exists('alt', $data)) {
                $image->alt = $data['alt'];
            }
            if (array_key_exists('sort_order', $data)) {
                $image->sort_order = (int) $data['sort_order'];
            }

            if (array_key_exists('is_primary', $data) && $data['is_primary']) {
                $subCategory->images()->where('id', '!=', $image->id)->update(['is_primary' => false]);
                $image->is_primary = true;
            }

            $image->save();
        });

        return response()->success($image->fresh(), 'Image mise à jour');
    }

    /**
     * @OA\Post(
     *   path="/api/subcategories/{subCategory}/images/reorder",
     *   tags={"SubCategory Images"},
     *   summary="Réordonner les images",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subCategory", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(
     *     required=true,
     *     content={
     *       "application/json"=@OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *           type="object",
     *           @OA\Property(
     *             property="orders",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=12),
     *               @OA\Property(property="sort_order", type="integer", example=3)
     *             )
     *           )
     *         )
     *       )
     *     }
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function reorder(Request $request, SubCategory $subCategory)
    {
        $data = $request->validate([
            'orders'               => ['required','array','min:1'],
            'orders.*.id'          => ['required','integer','exists:sub_category_images,id'],
            'orders.*.sort_order'  => ['required','integer','min:0'],
        ]);

        DB::transaction(function () use ($subCategory, $data) {
            $ids = collect($data['orders'])->pluck('id')->all();
            $count = SubCategoryImage::whereIn('id', $ids)
                ->where('sub_category_id', $subCategory->id)->count();
            if ($count !== count($ids)) {
                abort(422, 'Certaines images ne correspondent pas à cette sous-catégorie.');
            }

            foreach ($data['orders'] as $item) {
                SubCategoryImage::where('id', $item['id'])->update([
                    'sort_order' => (int) $item['sort_order'],
                ]);
            }
        });

        return response()->success($subCategory->images()->get(), 'Ordre mis à jour');
    }

    /**
     * @OA\Post(
     *   path="/api/subcategories/{subCategory}/images/{image}/primary",
     *   tags={"SubCategory Images"},
     *   summary="Définir l'image principale",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subCategory", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="image", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function setPrimary(SubCategory $subCategory, SubCategoryImage $image)
    {
        abort_unless($image->sub_category_id === $subCategory->id, 404);

        DB::transaction(function () use ($subCategory, $image) {
            $subCategory->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return response()->success($subCategory->load(['images','primaryImage']), 'Image principale définie');
    }

    /**
     * @OA\Delete(
     *   path="/api/subcategories/{subCategory}/images/{image}",
     *   tags={"SubCategory Images"},
     *   summary="Supprimer une image",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="subCategory", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="image", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Supprimé"),
     *   @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy(SubCategory $subCategory, SubCategoryImage $image)
    {
        abort_unless($image->sub_category_id === $subCategory->id, 404);

        if ($image->path && !str_starts_with($image->path, 'http')) {
            Storage::disk('public')->delete($image->path);
        }

        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $next = $subCategory->images()->orderBy('sort_order')->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return response()->success(null, 'Image supprimée');
    }
}
