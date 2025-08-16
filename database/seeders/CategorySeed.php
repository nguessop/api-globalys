<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeed extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Services techniques & artisanaux',
                'icon'        => 'Wrench',
                'color_class' => 'from-orange-500 to-amber-600',
                'description' => 'Électricité, plomberie, maçonnerie, menuiserie, etc.',
            ],
            [
                'name'        => 'Nettoyage & hygiène',
                'icon'        => 'Broom',
                'color_class' => 'from-emerald-500 to-green-600',
                'description' => 'Domicile, bureaux, désinfection, blanchisserie.',
            ],
            [
                'name'        => 'Beauté & bien-être',
                'icon'        => 'Heart',
                'color_class' => 'from-rose-500 to-pink-600',
                'description' => 'Coiffure, esthétique, massage, onglerie, maquillage.',
            ],
            [
                'name'        => 'Services à la personne',
                'icon'        => 'Users',
                'color_class' => 'from-sky-500 to-blue-600',
                'description' => 'Garde d’enfants, assistance, cours particuliers.',
            ],
            [
                'name'        => 'Événementiel',
                'icon'        => 'Calendar',
                'color_class' => 'from-fuchsia-500 to-purple-600',
                'description' => 'Photo/vidéo, déco, traiteur, sono, animation.',
            ],
            [
                'name'        => 'Informatique & digital',
                'icon'        => 'Monitor',
                'color_class' => 'from-indigo-500 to-blue-700',
                'description' => 'Dev web/app, design, marketing, maintenance IT.',
            ],
            [
                'name'        => 'Automobile & transports',
                'icon'        => 'Car',
                'color_class' => 'from-gray-600 to-slate-700',
                'description' => 'Mécanique, carrosserie, lavage, chauffeur, location.',
            ],
            [
                'name'        => 'Maison & habitat',
                'icon'        => 'Home',
                'color_class' => 'from-teal-500 to-cyan-600',
                'description' => 'Jardinage, déménagement, sécurité, domotique.',
            ],
            [
                'name'        => 'Santé & bien-être spécialisé',
                'icon'        => 'Shield',
                'color_class' => 'from-red-500 to-rose-600',
                'description' => 'Paramédical, nutrition, fitness, thérapies.',
            ],
            [
                'name'        => 'Administration & pro',
                'icon'        => 'FileText',
                'color_class' => 'from-yellow-500 to-amber-600',
                'description' => 'Comptabilité, juridique, traduction, RH.',
            ],
            [
                'name'        => 'Éducation & formation',
                'icon'        => 'GraduationCap',
                'color_class' => 'from-blue-500 to-indigo-600',
                'description' => 'Cours, préparation examens, formations pro.',
            ],
            [
                'name'        => 'Art & loisirs',
                'icon'        => 'Palette',
                'color_class' => 'from-violet-500 to-purple-700',
                'description' => 'Musique, peinture, artisanat, fleurs.',
            ],
        ];

        DB::transaction(function () use ($categories) {
            foreach ($categories as $row) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($row['name'])],
                    $row
                );
            }
        });
    }
}
