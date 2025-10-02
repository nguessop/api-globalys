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
        // 1) Parents (groupes racine)
        $parents = [
            'pro' => [
                'name'        => 'Services Professionnels & Juridiques',
                'icon'        => 'Scale',
                'color_class' => 'from-yellow-600 to-amber-700',
                'description' => 'Juridique, expertise, RH, job seeker.',
            ],
            'sante' => [
                'name'        => 'Santé & Bien-être',
                'icon'        => 'HeartPulse',
                'color_class' => 'from-rose-600 to-pink-700',
                'description' => 'Santé, résidence, hygiène, beauté, sport.',
            ],
            'tli' => [
                'name'        => 'Transport, Logistique & Immobilier',
                'icon'        => 'Truck',
                'color_class' => 'from-indigo-600 to-blue-700',
                'description' => 'Auto, transit, logistique, BTP, immobilier, déco, tourisme.',
            ],
            'finance' => [
                'name'        => 'Finance, Assurance & Commerce',
                'icon'        => 'CreditCard',
                'color_class' => 'from-emerald-600 to-green-700',
                'description' => 'Fintech, banques, assurances, marketplace, event, restauration.',
            ],
            'com' => [
                'name'        => 'Communication & Connaissance',
                'icon'        => 'Megaphone',
                'color_class' => 'from-purple-600 to-violet-700',
                'description' => 'Académie, e-learning, publicité, communication, culture & arts.',
            ],
            'innovation' => [
                'name'        => 'Secteurs Stratégiques & Innovation',
                'icon'        => 'Cpu',
                'color_class' => 'from-blue-600 to-cyan-700',
                'description' => 'Agro, énergie & environnement, technologie & innovation.',
            ],
        ];

        // 2) Enfants (les 30 catégories finales)
        $children = [
            // ⚖ Pro & Juridique
            ['parent' => 'pro', 'name' => 'Juridique / Contentieux Globs', 'icon' => 'Scale',        'color' => 'from-yellow-600 to-amber-700',  'desc' => 'Avocats, notaires, huissiers, conseils juridiques, médiation.'],
            ['parent' => 'pro', 'name' => 'Expert Globs',                   'icon' => 'Briefcase',    'color' => 'from-slate-600 to-gray-700',    'desc' => 'Audit financier, expertise immobilière, stratégie, certification.'],
            ['parent' => 'pro', 'name' => 'RH Globs',                       'icon' => 'Users',        'color' => 'from-blue-600 to-indigo-700',   'desc' => 'Recrutement, gestion de talents, portage salarial.'],
            ['parent' => 'pro', 'name' => 'Job Seeker Globs',               'icon' => 'UserSearch',   'color' => 'from-green-600 to-emerald-700', 'desc' => 'Candidats, freelances, missions ponctuelles.'],

            // 🏥 Santé & Bien-être
            ['parent' => 'sante', 'name' => 'Medical Globs',                'icon' => 'Stethoscope',  'color' => 'from-red-600 to-pink-700',      'desc' => 'Médecins, téléconsultation, pharmacies, laboratoires.'],
            ['parent' => 'sante', 'name' => 'Résidence Globs',              'icon' => 'Home',         'color' => 'from-orange-500 to-amber-600',  'desc' => 'Maisons de retraite, centres de soins spécialisés.'],
            ['parent' => 'sante', 'name' => 'Cleanmer Globs',               'icon' => 'Broom',        'color' => 'from-emerald-500 to-green-600', 'desc' => 'Ménage, désinfection, blanchisserie, pressing.'],
            ['parent' => 'sante', 'name' => 'Mode & Beauté Globs',          'icon' => 'Scissors',     'color' => 'from-rose-500 to-pink-600',     'desc' => 'Coiffure, esthétique, cosmétiques, habillement.'],
            ['parent' => 'sante', 'name' => 'Sport & Bien-être Globs',      'icon' => 'Dumbbell',     'color' => 'from-indigo-500 to-blue-600',   'desc' => 'Coaching sportif, fitness, nutrition, équipements.'],

            // 🚗 Transport, Logistique & Immobilier
            ['parent' => 'tli', 'name' => 'Auto Services Globs',            'icon' => 'Car',          'color' => 'from-gray-600 to-slate-700',    'desc' => 'Mécanique, pièces détachées, dépannage.'],
            ['parent' => 'tli', 'name' => 'Transit / Douane Globs',         'icon' => 'Package',      'color' => 'from-teal-500 to-cyan-600',     'desc' => 'Dédouanement, transit, import/export.'],
            ['parent' => 'tli', 'name' => 'Logistique & Transport Globs',   'icon' => 'Truck',        'color' => 'from-purple-600 to-violet-700', 'desc' => 'Livraison, fret, supply chain, VTC.'],
            ['parent' => 'tli', 'name' => 'BTP Globs',                      'icon' => 'Hammer',       'color' => 'from-orange-600 to-red-700',    'desc' => 'Construction, électricité, plomberie, artisanat.'],
            ['parent' => 'tli', 'name' => 'Immobilier / Foncier Globs',     'icon' => 'Building',     'color' => 'from-indigo-600 to-purple-700', 'desc' => 'Vente, location, gestion immobilière.'],
            ['parent' => 'tli', 'name' => 'Décoration Globs',               'icon' => 'Paintbrush',   'color' => 'from-pink-600 to-rose-700',     'desc' => 'Architecture d’intérieur, ameublement, décoration.'],
            ['parent' => 'tli', 'name' => 'Tourisme & Hôtellerie Globs',    'icon' => 'Plane',        'color' => 'from-blue-500 to-teal-600',     'desc' => 'Réservations, voyages, hôtels, loisirs.'],

            // 💳 Finance, Assurance & Commerce
            ['parent' => 'finance', 'name' => 'Fintech Globs',              'icon' => 'CreditCard',   'color' => 'from-purple-500 to-indigo-600', 'desc' => 'Mobile money, wallets, crowdfunding, crypto.'],
            ['parent' => 'finance', 'name' => 'Banking Globs',              'icon' => 'Banknote',     'color' => 'from-green-600 to-emerald-700', 'desc' => 'Banques, crédits, microfinance, néobanques.'],
            ['parent' => 'finance', 'name' => 'Assurance Globs',            'icon' => 'ShieldCheck',  'color' => 'from-yellow-500 to-orange-600', 'desc' => 'Assurance santé, auto, habitation, risques.'],
            ['parent' => 'finance', 'name' => 'Marketplace Globs',          'icon' => 'ShoppingCart', 'color' => 'from-pink-500 to-red-600',      'desc' => 'E-commerce, B2C, C2C, comparateurs.'],
            ['parent' => 'finance', 'name' => 'Event Globs',                'icon' => 'Calendar',     'color' => 'from-fuchsia-500 to-purple-600','desc' => 'Organisation d’événements, traiteurs, salles.'],
            ['parent' => 'finance', 'name' => 'Restauration Globs',         'icon' => 'Utensils',     'color' => 'from-red-500 to-orange-600',    'desc' => 'Restaurants, fast-foods, traiteurs, livraison repas.'],

            // 📢 Communication & Connaissance
            ['parent' => 'com', 'name' => 'Académie Globs',                 'icon' => 'GraduationCap','color' => 'from-indigo-500 to-blue-700',   'desc' => 'Formations, certifications, coaching.'],
            ['parent' => 'com', 'name' => 'E-Learning Globs',               'icon' => 'Laptop',       'color' => 'from-cyan-500 to-blue-600',     'desc' => 'MOOC, plateformes, cours particuliers.'],
            ['parent' => 'com', 'name' => 'Publicité Globs',                'icon' => 'Megaphone',    'color' => 'from-yellow-500 to-red-600',    'desc' => 'Affichage, campagnes digitales, influence.'],
            ['parent' => 'com', 'name' => 'Communication Globs',            'icon' => 'MessageCircle','color' => 'from-green-500 to-emerald-600',  'desc' => 'Relations presse, community management.'],
            ['parent' => 'com', 'name' => 'Culture & Arts Globs',           'icon' => 'Palette',      'color' => 'from-violet-500 to-purple-700', 'desc' => 'Musique, cinéma, artisanat, design.'],

            // 🌱 Innovation
            ['parent' => 'innovation', 'name' => 'Agro Globs',              'icon' => 'Leaf',         'color' => 'from-green-600 to-lime-700',    'desc' => 'Agriculture, agroalimentaire, bio, coopératives.'],
            ['parent' => 'innovation', 'name' => 'Énergie & Environnement Globs','icon' => 'Zap',   'color' => 'from-yellow-600 to-green-600',  'desc' => 'Énergies renouvelables, eau, déchets, RSE.'],
            ['parent' => 'innovation', 'name' => 'Technologie & Innovation Globs','icon' => 'Cpu',  'color' => 'from-blue-600 to-indigo-700',   'desc' => 'Développement logiciel, IA, cybersécurité, IoT.'],
        ];

        DB::transaction(function () use ($parents, $children) {
            // Créer/mettre à jour les parents
            $parentIds = [];
            foreach ($parents as $key => $row) {
                $slug = Str::slug($row['name']);
                /** @var \App\Models\Category $cat */
                $cat = Category::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'parent_id'   => null,
                        'name'        => $row['name'],
                        'icon'        => $row['icon'] ?? null,
                        'color_class' => $row['color_class'] ?? null,
                        'description' => $row['description'] ?? null,
                    ]
                );
                $parentIds[$key] = $cat->id;
            }

            // Créer/mettre à jour les 30 catégories enfants
            foreach ($children as $c) {
                $parentKey = $c['parent'];
                $parentId  = $parentIds[$parentKey] ?? null;

                $name  = $c['name'];
                $slug  = Str::slug($name);

                Category::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'parent_id'   => $parentId,
                        'name'        => $name,
                        'icon'        => $c['icon'] ?? null,
                        'color_class' => $c['color'] ?? null,
                        'description' => $c['desc'] ?? null,
                    ]
                );
            }
        });
    }
}
