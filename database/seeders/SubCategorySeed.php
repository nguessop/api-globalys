<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubCategorySeed extends Seeder
{
    public function run(): void
    {
        $subCategories = [
            'services-techniques-artisanaux' => [
                'Électricité',
                'Plomberie',
                'Maçonnerie',
                'Menuiserie',
                'Serrurerie',
                'Climatisation & réfrigération',
                'Peinture',
                'Réparation électroménager',
                'Entretien de piscines',
                'Chauffage & énergie solaire',
            ],
            'nettoyage-hygiene' => [
                'Nettoyage à domicile',
                'Entretien de bureaux',
                'Désinfection et dératisation',
                'Blanchisserie / Repassage',
                'Nettoyage de véhicules',
            ],
            'beaute-bien-etre' => [
                'Coiffure (à domicile ou en salon)',
                'Soins esthétiques',
                'Massage et relaxation',
                'Onglerie / manucure',
                'Maquillage professionnel',
            ],
            'services-a-la-personne' => [
                'Garde d’enfants',
                'Assistance personnes âgées',
                'Cours particuliers',
            ],
            'evenementiel' => [
                'Photographie',
                'Vidéo',
                'Décoration',
                'Traiteur',
                'Sonorisation',
                'Animation',
            ],
            'informatique-digital' => [
                'Développement web',
                'Développement mobile',
                'Design graphique',
                'Marketing digital',
                'Maintenance IT',
            ],
            'automobile-transports' => [
                'Mécanique auto',
                'Carrosserie',
                'Lavage auto',
                'Chauffeur privé',
                'Location de véhicules',
            ],
            'maison-habitat' => [
                'Jardinage',
                'Déménagement',
                'Sécurité',
                'Domotique',
            ],
            'sante-bien-etre-specialise' => [
                'Paramédical',
                'Nutrition',
                'Fitness',
                'Thérapies',
            ],
            'administration-pro' => [
                'Comptabilité',
                'Juridique',
                'Traduction',
                'Ressources humaines',
            ],
            'education-formation' => [
                'Cours de soutien',
                'Préparation examens',
                'Formations professionnelles',
            ],
            'art-loisirs' => [
                'Musique',
                'Peinture',
                'Artisanat',
                'Fleurs',
            ],
        ];

        DB::transaction(function () use ($subCategories) {
            foreach ($subCategories as $categorySlug => $names) {
                $category = Category::where('slug', $categorySlug)->first();

                if (!$category) {
                    if ($this->command) {
                        $this->command->warn("⚠️ Catégorie introuvable : {$categorySlug}");
                    } else {
                        echo "⚠️ Catégorie introuvable : {$categorySlug}\n";
                    }
                    continue;
                }

                foreach ($names as $name) {
                    // 1) Slug de base
                    $base = Str::slug($name);
                    $slug = $base;
                    $i = 2;

                    // 2) Rendre le slug UNIQUE (globalement)
                    while (SubCategory::where('slug', $slug)->exists()) {
                        $slug = $base . '-' . $i;
                        $i++;
                    }

                    // 3) updateOrCreate PAR (category_id + name)
                    SubCategory::updateOrCreate(
                        ['category_id' => $category->id, 'name' => $name],
                        [
                            'slug'            => $slug,   // valeur unique calculée ci-dessus
                            'icon'            => null,
                            'providers_count' => 0,
                            'average_price'   => null,
                            'description'     => null,
                        ]
                    );
                }
            }
        });
    }
}
