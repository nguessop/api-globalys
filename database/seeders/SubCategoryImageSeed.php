<?php

namespace Database\Seeders;

use App\Models\SubCategory;
use App\Models\SubCategoryImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class SubCategoryImageSeed extends Seeder
{
    /**
     * Exécute le seed.
     */
    public function run(): void
    {
        // Quelques placeholders (URLs publiques)
        $placeholders = [
            // portraits / métiers / services
            'https://images.pexels.com/photos/1216589/pexels-photo-1216589.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/574071/pexels-photo-574071.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/4239146/pexels-photo-4239146.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/3184465/pexels-photo-3184465.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/1321736/pexels-photo-1321736.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/3990359/pexels-photo-3990359.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/4484076/pexels-photo-4484076.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/4792479/pexels-photo-4792479.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/3825586/pexels-photo-3825586.jpeg?auto=compress&cs=tinysrgb&w=800',
        ];

        SubCategory::query()
            ->with('images')
            ->orderBy('id')
            ->chunk(100, function ($subCategories) use ($placeholders) {
                /** @var \App\Models\SubCategory $sub */
                foreach ($subCategories as $sub) {
                    // (Optionnel) nettoyer les images existantes pour éviter les doublons
                    // $sub->images()->delete();

                    $count = rand(1, 3);
                    $picked = Arr::random($placeholders, $count);
                    if (!is_array($picked)) {
                        $picked = [$picked];
                    }

                    $order = 0;
                    foreach ($picked as $idx => $url) {
                        $order++;
                        SubCategoryImage::create([
                            'sub_category_id' => $sub->id,
                            'path'            => $url,            // stocké en URL (pas de fichier local)
                            'alt'             => $sub->name,
                            'is_primary'      => $idx === 0,      // la première comme image principale
                            'sort_order'      => $order,
                        ]);
                    }

                    // S'assure qu'il existe bien une image principale
                    if (!$sub->primaryImage()->exists() && $sub->images()->exists()) {
                        $first = $sub->images()->orderBy('sort_order')->first();
                        $first?->update(['is_primary' => true]);
                    }
                }
            });
    }
}
