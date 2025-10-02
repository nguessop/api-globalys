<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubCategorySeed extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $subCategories = [
            [
                'category_id'     => 7,
                'name'            => 'Avocats, notaires, huissiers',
                'slug'            => Str::slug('Avocats, notaires, huissiers'),
                'icon'            => 'gavel',
                'providers_count' => 50,
                'average_price'   => '50000 FCFA / dossier',
                'description'     => 'Services juridiques par avocats, notaires et huissiers agréés.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'date_naissance', 'type' => 'date'],
                        ['name' => 'cni_passport', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                        ['name' => 'annees_experience', 'type' => 'number'],
                        ['name' => 'diplome_droit', 'type' => 'file'],
                        ['name' => 'carte_professionnelle', 'type' => 'file'],
                        ['name' => 'casier_judiciaire', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                        ['name' => 'siege_social', 'type' => 'text'],
                        ['name' => 'agrement_ministere_justice', 'type' => 'file'],
                        ['name' => 'annee_creation', 'type' => 'number'],
                        ['name' => 'nombre_associes', 'type' => 'number'],
                        ['name' => 'representant_legal', 'type' => 'text'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 7,
                'name'            => 'Conseils en droit du travail, fiscalité, famille',
                'slug'            => Str::slug('Conseils en droit du travail, fiscalité, famille'),
                'icon'            => 'balance-scale',
                'providers_count' => 40,
                'average_price'   => '35000 FCFA / consultation',
                'description'     => 'Conseils spécialisés en droit du travail, fiscalité et droit de la famille.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplomes', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_conseil', 'type' => 'file'],
                        ['name' => 'agrement_fiscal', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 7,
                'name'            => 'Gestion des contentieux et litiges',
                'slug'            => Str::slug('Gestion des contentieux et litiges'),
                'icon'            => 'file-contract',
                'providers_count' => 30,
                'average_price'   => '60000 FCFA / affaire',
                'description'     => 'Prise en charge et suivi des litiges et procédures contentieuses.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 7,
                'name'            => 'Médiation et arbitrage',
                'slug'            => Str::slug('Médiation et arbitrage'),
                'icon'            => 'handshake',
                'providers_count' => 25,
                'average_price'   => '40000 FCFA / session',
                'description'     => 'Services de médiation et arbitrage pour résolution des litiges.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'formation_mediation', 'type' => 'file'],
                        ['name' => 'annees_experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'agrement_ordre_mediateurs', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 8,
                'name'            => 'Audit financier & comptable',
                'slug'            => Str::slug('Audit financier & comptable'),
                'icon'            => 'calculator',
                'providers_count' => 35,
                'average_price'   => '70000 FCFA / mission',
                'description'     => 'Audit des comptes, contrôle et certification financière.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'diplome_comptable', 'type' => 'file'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_experts_comptables', 'type' => 'file'],
                        ['name' => 'assurance_rc', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 8,
                'name'            => 'Expertise immobilière & foncière',
                'slug'            => Str::slug('Expertise immobilière & foncière'),
                'icon'            => 'home',
                'providers_count' => 20,
                'average_price'   => '80000 FCFA / expertise',
                'description'     => 'Évaluation et expertise de biens immobiliers et fonciers.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'formation_geometre_notaire', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_fonciere', 'type' => 'file'],
                        ['name' => 'agrement_cadastre', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 8,
                'name'            => 'Conseil en stratégie & management',
                'slug'            => Str::slug('Conseil en stratégie & management'),
                'icon'            => 'briefcase',
                'providers_count' => 45,
                'average_price'   => '60000 FCFA / consultation',
                'description'     => 'Cabinets et experts en stratégie et management.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_gestion', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'cabinet_agree', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 8,
                'name'            => 'Certification qualité (ISO, normes)',
                'slug'            => Str::slug('Certification qualité (ISO, normes)'),
                'icon'            => 'check-circle',
                'providers_count' => 15,
                'average_price'   => '120000 FCFA / certification',
                'description'     => 'Services de certification qualité ISO et autres normes.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'diplomes_specialises', 'type' => 'file'],
                        ['name' => 'experience_iso', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'agrement_certificateur', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next
            [
                'category_id'     => 9,
                'name'            => 'Cabinet de recrutement',
                'slug'            => Str::slug('Cabinet de recrutement'),
                'icon'            => 'users',
                'providers_count' => 30,
                'average_price'   => '80000 FCFA / mission',
                'description'     => 'Cabinets spécialisés dans le recrutement et la gestion de carrières.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'cv', 'type' => 'file'],
                        ['name' => 'diplomes_rh', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'agrement_inspection_travail', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 9,
                'name'            => 'Gestion de talents & outsourcing',
                'slug'            => Str::slug('Gestion de talents & outsourcing'),
                'icon'            => 'user-check',
                'providers_count' => 25,
                'average_price'   => '60000 FCFA / prestation',
                'description'     => 'Services d’outsourcing et gestion externalisée des talents.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'cv', 'type' => 'file'],
                        ['name' => 'diplomes', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_outsourcing', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 9,
                'name'            => 'Conseil en organisation RH',
                'slug'            => Str::slug('Conseil en organisation RH'),
                'icon'            => 'clipboard-list',
                'providers_count' => 20,
                'average_price'   => '50000 FCFA / étude',
                'description'     => 'Conseil en organisation et optimisation des ressources humaines.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_gestion_rh', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'cabinet_rh_agree', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 9,
                'name'            => 'Portage salarial',
                'slug'            => Str::slug('Portage salarial'),
                'icon'            => 'briefcase',
                'providers_count' => 15,
                'average_price'   => '40000 FCFA / mois',
                'description'     => 'Solutions de portage salarial pour freelances et consultants.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'cv', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_portage', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 4
            [
                'category_id'     => 10,
                'name'            => 'Candidatures freelance',
                'slug'            => Str::slug('Candidatures freelance'),
                'icon'            => 'user',
                'providers_count' => 60,
                'average_price'   => 'Variable selon profil',
                'description'     => 'Profils freelances disponibles pour diverses missions.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'date_naissance', 'type' => 'date'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'telephone', 'type' => 'text'],
                        ['name' => 'email', 'type' => 'email'],
                        ['name' => 'cv', 'type' => 'file'],
                        ['name' => 'diplomes', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 10,
                'name'            => 'Offres d’emploi temporaires',
                'slug'            => Str::slug('Offres d’emploi temporaires'),
                'icon'            => 'briefcase',
                'providers_count' => 40,
                'average_price'   => 'Salaire journalier',
                'description'     => 'Offres d’emplois à durée déterminée ou temporaires.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'cv', 'type' => 'file'],
                        ['name' => 'diplomes', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 10,
                'name'            => 'Missions ponctuelles (jobbing)',
                'slug'            => Str::slug('Missions ponctuelles jobbing'),
                'icon'            => 'tasks',
                'providers_count' => 55,
                'average_price'   => '15000 FCFA / mission',
                'description'     => 'Petites missions ponctuelles et jobs rapides.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'telephone', 'type' => 'text'],
                        ['name' => 'email', 'type' => 'email'],
                        ['name' => 'cv', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 10,
                'name'            => 'Mise en relation prestataires ↔ entreprises',
                'slug'            => Str::slug('Mise en relation prestataires entreprises'),
                'icon'            => 'link',
                'providers_count' => 70,
                'average_price'   => 'Commission sur prestation',
                'description'     => 'Plateformes mettant en relation prestataires et entreprises.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'telephone', 'type' => 'text'],
                        ['name' => 'email', 'type' => 'email'],
                        ['name' => 'cv', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 5
            [
                'category_id'     => 11,
                'name'            => 'Téléconsultation',
                'slug'            => Str::slug('Téléconsultation'),
                'icon'            => 'stethoscope',
                'providers_count' => 40,
                'average_price'   => '10000 FCFA / consultation',
                'description'     => 'Consultations médicales à distance avec médecins généralistes et spécialistes.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'date_naissance', 'type' => 'date'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_medecine', 'type' => 'file'],
                        ['name' => 'certificat_ordre', 'type' => 'file'],
                        ['name' => 'annees_experience', 'type' => 'number'],
                        ['name' => 'photo', 'type' => 'file'],
                        ['name' => 'licence_medicale', 'type' => 'text'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_ministere_sante', 'type' => 'file'],
                        ['name' => 'assurance_medicale', 'type' => 'file'],
                        ['name' => 'siege_social', 'type' => 'text'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 11,
                'name'            => 'Pharmacies & laboratoires',
                'slug'            => Str::slug('Pharmacies & laboratoires'),
                'icon'            => 'capsules',
                'providers_count' => 35,
                'average_price'   => 'Variable selon service',
                'description'     => 'Pharmacies et laboratoires agréés pour médicaments et analyses.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_pharmacie', 'type' => 'file'],
                        ['name' => 'licence_pharmacie', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_pharmaceutique', 'type' => 'file'],
                        ['name' => 'registre_pharmacie', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 11,
                'name'            => 'Médecins généralistes & spécialistes',
                'slug'            => Str::slug('Médecins généralistes & spécialistes'),
                'icon'            => 'user-md',
                'providers_count' => 55,
                'average_price'   => '15000 FCFA / consultation',
                'description'     => 'Médecins généralistes et spécialistes offrant soins directs.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_medecine', 'type' => 'file'],
                        ['name' => 'inscription_ordre', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                        ['name' => 'photo', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 11,
                'name'            => 'Urgences médicales & cliniques privées',
                'slug'            => Str::slug('Urgences médicales & cliniques privées'),
                'icon'            => 'hospital',
                'providers_count' => 20,
                'average_price'   => '25000 FCFA / urgence',
                'description'     => 'Cliniques privées et services d’urgences médicales.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'directeur_medical', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_sanitaire', 'type' => 'file'],
                        ['name' => 'autorisation_exploitation', 'type' => 'file'],
                        ['name' => 'assurance_rc', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 6
            [
                'category_id'     => 12,
                'name'            => 'Maisons de retraite',
                'slug'            => Str::slug('Maisons de retraite'),
                'icon'            => 'home',
                'providers_count' => 15,
                'average_price'   => '200000 FCFA / mois',
                'description'     => 'Établissements pour personnes âgées avec encadrement médical.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'directeur_medical', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_sanitaire', 'type' => 'file'],
                        ['name' => 'autorisation_exploitation', 'type' => 'file'],
                        ['name' => 'annee_ouverture', 'type' => 'number'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 12,
                'name'            => 'Centres de soins spécialisés',
                'slug'            => Str::slug('Centres de soins spécialisés'),
                'icon'            => 'clinic-medical',
                'providers_count' => 10,
                'average_price'   => 'Variable selon soin',
                'description'     => 'Centres spécialisés dans certains types de soins médicaux.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'directeur_medical', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_specialite', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_sanitaire', 'type' => 'file'],
                        ['name' => 'autorisation_exploitation', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 12,
                'name'            => 'Hébergement médicalisé',
                'slug'            => Str::slug('Hébergement médicalisé'),
                'icon'            => 'bed',
                'providers_count' => 12,
                'average_price'   => '150000 FCFA / mois',
                'description'     => 'Structures offrant hébergement et suivi médical permanent.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'directeur_medical', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_sanitaire', 'type' => 'file'],
                        ['name' => 'autorisation_exploitation', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 12,
                'name'            => 'Services de maintien à domicile',
                'slug'            => Str::slug('Services de maintien à domicile'),
                'icon'            => 'hands-helping',
                'providers_count' => 20,
                'average_price'   => '30000 FCFA / visite',
                'description'     => 'Services d’assistance médicale et sociale à domicile.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'formation_medicale', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_services_sante', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 7
            [
                'category_id'     => 13,
                'name'            => 'Services de ménage et entretien',
                'slug'            => Str::slug('Services de ménage et entretien'),
                'icon'            => 'broom',
                'providers_count' => 60,
                'average_price'   => '10000 FCFA / prestation',
                'description'     => 'Ménage et entretien à domicile ou en entreprise.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'casier_judiciaire', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'agrement_hygiene', 'type' => 'file'],
                        ['name' => 'assurance', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 13,
                'name'            => 'Désinfection & dératisation',
                'slug'            => Str::slug('Désinfection & dératisation'),
                'icon'            => 'spray-can',
                'providers_count' => 25,
                'average_price'   => '20000 FCFA / intervention',
                'description'     => 'Services de désinfection, dératisation et assainissement.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'agrement_hygiene', 'type' => 'file'],
                        ['name' => 'assurance', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 13,
                'name'            => 'Nettoyage industriel',
                'slug'            => Str::slug('Nettoyage industriel'),
                'icon'            => 'industry',
                'providers_count' => 18,
                'average_price'   => '50000 FCFA / prestation',
                'description'     => 'Nettoyage d’usines, entrepôts et environnements industriels.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'casier_judiciaire', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'agrement_industrie', 'type' => 'file'],
                        ['name' => 'assurance', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 13,
                'name'            => 'Blanchisserie & pressing',
                'slug'            => Str::slug('Blanchisserie & pressing'),
                'icon'            => 'tshirt',
                'providers_count' => 30,
                'average_price'   => '5000 FCFA / lot',
                'description'     => 'Services de blanchisserie et pressing pour particuliers et entreprises.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_commerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 8
            [
                'category_id'     => 14,
                'name'            => 'Coiffure, esthétique, spa',
                'slug'            => Str::slug('Coiffure, esthétique, spa'),
                'icon'            => 'cut',
                'providers_count' => 80,
                'average_price'   => '10000 FCFA / prestation',
                'description'     => 'Services de coiffure, soins esthétiques et spa.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'certificat_cap', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                        ['name' => 'photo', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'autorisation_commerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 14,
                'name'            => 'Maquillage professionnel',
                'slug'            => Str::slug('Maquillage professionnel'),
                'icon'            => 'paint-brush',
                'providers_count' => 50,
                'average_price'   => '15000 FCFA / prestation',
                'description'     => 'Maquilleurs et maquilleuses professionnelles pour tout événement.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'certificat_esthetique', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 14,
                'name'            => 'Vente de cosmétiques & produits capillaires',
                'slug'            => Str::slug('Vente de cosmétiques & produits capillaires'),
                'icon'            => 'shopping-bag',
                'providers_count' => 40,
                'average_price'   => 'Variable selon produit',
                'description'     => 'Vente et distribution de cosmétiques et produits capillaires.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'autorisation_vente_cosmetique', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 14,
                'name'            => 'Stylisme & habillement',
                'slug'            => Str::slug('Stylisme & habillement'),
                'icon'            => 'tshirt',
                'providers_count' => 45,
                'average_price'   => '20000 FCFA / modèle',
                'description'     => 'Stylisme, confection et vente de vêtements et accessoires.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_commerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 9
            [
                'category_id'     => 15,
                'name'            => 'Coaching sportif',
                'slug'            => Str::slug('Coaching sportif'),
                'icon'            => 'dumbbell',
                'providers_count' => 35,
                'average_price'   => '15000 FCFA / séance',
                'description'     => 'Coachs sportifs diplômés pour accompagnement personnalisé.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_sport', 'type' => 'file'],
                        ['name' => 'certificat_medical', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 15,
                'name'            => 'Salles de sport & fitness',
                'slug'            => Str::slug('Salles de sport & fitness'),
                'icon'            => 'heartbeat',
                'providers_count' => 20,
                'average_price'   => '20000 FCFA / mois',
                'description'     => 'Clubs de sport, fitness et musculation.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_mairie', 'type' => 'file'],
                        ['name' => 'assurance_locaux', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 15,
                'name'            => 'Nutrition & diététique',
                'slug'            => Str::slug('Nutrition & diététique'),
                'icon'            => 'apple-alt',
                'providers_count' => 25,
                'average_price'   => '10000 FCFA / consultation',
                'description'     => 'Experts en nutrition et diététique pour programmes alimentaires.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_nutrition', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 15,
                'name'            => 'Vente d’équipements sportifs',
                'slug'            => Str::slug('Vente d’équipements sportifs'),
                'icon'            => 'shopping-cart',
                'providers_count' => 40,
                'average_price'   => 'Variable selon produit',
                'description'     => 'Vente de matériel et équipements sportifs.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_commerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 10
            [
                'category_id'     => 16,
                'name'            => 'Garages & mécanique',
                'slug'            => Str::slug('Garages & mécanique'),
                'icon'            => 'wrench',
                'providers_count' => 50,
                'average_price'   => '20000 FCFA / intervention',
                'description'     => 'Garages et ateliers de mécanique automobile.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_mecanicien', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_garage', 'type' => 'file'],
                        ['name' => 'agrement_assurance_auto', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 16,
                'name'            => 'Pièces détachées auto',
                'slug'            => Str::slug('Pièces détachées auto'),
                'icon'            => 'cogs',
                'providers_count' => 35,
                'average_price'   => 'Variable selon pièce',
                'description'     => 'Vente de pièces détachées pour véhicules.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_commerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 16,
                'name'            => 'Assurance & contrôle technique',
                'slug'            => Str::slug('Assurance & contrôle technique'),
                'icon'            => 'shield-alt',
                'providers_count' => 20,
                'average_price'   => '30000 FCFA / véhicule',
                'description'     => 'Services d’assurance automobile et centres de contrôle technique.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_assurance', 'type' => 'file'],
                        ['name' => 'centre_controle_agree', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 16,
                'name'            => 'Dépannage & remorquage',
                'slug'            => Str::slug('Dépannage & remorquage'),
                'icon'            => 'truck',
                'providers_count' => 15,
                'average_price'   => '10000 FCFA / intervention',
                'description'     => 'Services de dépannage et de remorquage automobiles.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'permis_conduire', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_transport', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 11
            [
                'category_id'     => 17,
                'name'            => 'Dédouanement & formalités',
                'slug'            => Str::slug('Dédouanement & formalités'),
                'icon'            => 'file-invoice',
                'providers_count' => 25,
                'average_price'   => '50000 FCFA / opération',
                'description'     => 'Services de dédouanement et formalités douanières.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'agrement_transitaire', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_douane', 'type' => 'file'],
                        ['name' => 'autorisation_portuaire', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 17,
                'name'            => 'Import / export',
                'slug'            => Str::slug('Import / export'),
                'icon'            => 'exchange-alt',
                'providers_count' => 30,
                'average_price'   => 'Variable selon volume',
                'description'     => 'Sociétés spécialisées dans l’import et l’export de marchandises.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_import_export', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 17,
                'name'            => 'Transit de marchandises',
                'slug'            => Str::slug('Transit de marchandises'),
                'icon'            => 'truck-moving',
                'providers_count' => 20,
                'average_price'   => 'Variable selon trajet',
                'description'     => 'Transit et acheminement des marchandises par voie terrestre, aérienne ou maritime.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_transit', 'type' => 'file'],
                        ['name' => 'autorisation_portuaire', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 17,
                'name'            => 'Entreposage sous douane',
                'slug'            => Str::slug('Entreposage sous douane'),
                'icon'            => 'warehouse',
                'providers_count' => 12,
                'average_price'   => '70000 FCFA / mois',
                'description'     => 'Entreposage de marchandises dans des zones sous contrôle douanier.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_entreposage', 'type' => 'file'],
                        ['name' => 'autorisation_douane', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 12
            [
                'category_id'     => 18,
                'name'            => 'Livraison urbaine & express',
                'slug'            => Str::slug('Livraison urbaine & express'),
                'icon'            => 'motorcycle',
                'providers_count' => 60,
                'average_price'   => '2000 FCFA / course',
                'description'     => 'Services de livraison rapide en milieu urbain.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'permis_conduire', 'type' => 'file'],
                        ['name' => 'assurance_vehicule', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_transport', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 18,
                'name'            => 'Fret & cargo maritime/aérien',
                'slug'            => Str::slug('Fret & cargo maritime aérien'),
                'icon'            => 'ship',
                'providers_count' => 18,
                'average_price'   => 'Variable selon volume',
                'description'     => 'Transport de fret par voie maritime ou aérienne.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_fret', 'type' => 'file'],
                        ['name' => 'agrement_transport', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 18,
                'name'            => 'Services VTC & taxis',
                'slug'            => Str::slug('Services VTC & taxis'),
                'icon'            => 'taxi',
                'providers_count' => 50,
                'average_price'   => 'Variable selon trajet',
                'description'     => 'Chauffeurs privés, VTC et taxis agréés.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'permis_conduire', 'type' => 'file'],
                        ['name' => 'casier_judiciaire', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_vtc_taxi', 'type' => 'file'],
                        ['name' => 'assurance_flottes', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 18,
                'name'            => 'Gestion supply chain',
                'slug'            => Str::slug('Gestion supply chain'),
                'icon'            => 'project-diagram',
                'providers_count' => 15,
                'average_price'   => '100000 FCFA / contrat',
                'description'     => 'Gestion et optimisation de la chaîne logistique.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'cabinet_supplychain', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 13
            [
                'category_id'     => 19,
                'name'            => 'Construction & génie civil',
                'slug'            => Str::slug('Construction & génie civil'),
                'icon'            => 'hammer',
                'providers_count' => 40,
                'average_price'   => '300000 FCFA / projet',
                'description'     => 'Entreprises et artisans spécialisés en construction et génie civil.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_ingenieur_artisan', 'type' => 'file'],
                        ['name' => 'assurance_decennale', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_btp', 'type' => 'file'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_urbanisme', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 19,
                'name'            => 'Électricité, plomberie, climatisation',
                'slug'            => Str::slug('Électricité, plomberie, climatisation'),
                'icon'            => 'bolt',
                'providers_count' => 60,
                'average_price'   => '15000 FCFA / intervention',
                'description'     => 'Artisans spécialisés en électricité, plomberie et climatisation.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_technique', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_btp', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 19,
                'name'            => 'Architectes & bureaux d’étude',
                'slug'            => Str::slug('Architectes & bureaux d’étude'),
                'icon'            => 'drafting-compass',
                'providers_count' => 25,
                'average_price'   => '200000 FCFA / plan',
                'description'     => 'Architectes et bureaux d’études techniques agréés.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_architecte', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_architecture', 'type' => 'file'],
                        ['name' => 'agrement_architectural', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 19,
                'name'            => 'Artisanat & second œuvre',
                'slug'            => Str::slug('Artisanat & second œuvre'),
                'icon'            => 'paint-roller',
                'providers_count' => 50,
                'average_price'   => 'Variable selon mission',
                'description'     => 'Travaux de second œuvre : peinture, carrelage, menuiserie, etc.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_artisan', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_artisanat', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 14
            [
                'category_id'     => 20,
                'name'            => 'Vente et location',
                'slug'            => Str::slug('Vente et location'),
                'icon'            => 'home',
                'providers_count' => 70,
                'average_price'   => 'Variable selon bien',
                'description'     => 'Agences et particuliers pour la vente et location immobilière.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'carte_professionnelle', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                        ['name' => 'age', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_agence_immobiliere', 'type' => 'file'],
                        ['name' => 'agrement_foncier', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 20,
                'name'            => 'Gestion immobilière',
                'slug'            => Str::slug('Gestion immobilière'),
                'icon'            => 'building',
                'providers_count' => 30,
                'average_price'   => '10% du loyer',
                'description'     => 'Administrateurs de biens et agences de gestion locative.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_gestion_immobiliere', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 20,
                'name'            => 'Syndic de copropriété',
                'slug'            => Str::slug('Syndic de copropriété'),
                'icon'            => 'users-cog',
                'providers_count' => 12,
                'average_price'   => 'Variable selon immeuble',
                'description'     => 'Syndics de copropriété agréés pour gestion d’immeubles.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_syndic', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 20,
                'name'            => 'Estimation foncière',
                'slug'            => Str::slug('Estimation foncière'),
                'icon'            => 'map',
                'providers_count' => 20,
                'average_price'   => '50000 FCFA / expertise',
                'description'     => 'Experts agréés en estimation foncière et cadastrale.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'carte_professionnelle', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_expert_foncier', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 15
            [
                'category_id'     => 21,
                'name'            => 'Architecture d’intérieur',
                'slug'            => Str::slug('Architecture d’intérieur'),
                'icon'            => 'ruler-combined',
                'providers_count' => 20,
                'average_price'   => '150000 FCFA / projet',
                'description'     => 'Architectes d’intérieur spécialisés dans l’aménagement des espaces.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                        ['name' => 'age', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 21,
                'name'            => 'Ameublement sur mesure',
                'slug'            => Str::slug('Ameublement sur mesure'),
                'icon'            => 'couch',
                'providers_count' => 25,
                'average_price'   => 'Variable selon meuble',
                'description'     => 'Fabrication et vente de meubles sur mesure.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_artisanat', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 21,
                'name'            => 'Décoration événementielle',
                'slug'            => Str::slug('Décoration événementielle'),
                'icon'            => 'balloon',
                'providers_count' => 30,
                'average_price'   => '100000 FCFA / événement',
                'description'     => 'Prestataires spécialisés en décoration pour événements privés et professionnels.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_evenementiel', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 21,
                'name'            => 'Vente d’objets décoratifs',
                'slug'            => Str::slug('Vente d’objets décoratifs'),
                'icon'            => 'gift',
                'providers_count' => 40,
                'average_price'   => 'Variable selon produit',
                'description'     => 'Boutiques et créateurs d’objets décoratifs.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_commerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 16
            [
                'category_id'     => 22,
                'name'            => 'Réservations hôtels & voyages',
                'slug'            => Str::slug('Réservations hôtels & voyages'),
                'icon'            => 'hotel',
                'providers_count' => 50,
                'average_price'   => 'Variable selon destination',
                'description'     => 'Agences spécialisées dans la réservation d’hôtels et de voyages.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'gerant', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_tourisme', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_tourisme', 'type' => 'file'],
                        ['name' => 'agrement_ministere_tourisme', 'type' => 'file'],
                        ['name' => 'assurance', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 22,
                'name'            => 'Agences de tourisme',
                'slug'            => Str::slug('Agences de tourisme'),
                'icon'            => 'map-marked-alt',
                'providers_count' => 35,
                'average_price'   => 'Variable selon service',
                'description'     => 'Agences de voyage et de tourisme pour circuits organisés.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_agence_tourisme', 'type' => 'file'],
                        ['name' => 'agrement_ministere_tourisme', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 22,
                'name'            => 'Loisirs & activités culturelles',
                'slug'            => Str::slug('Loisirs & activités culturelles'),
                'icon'            => 'theater-masks',
                'providers_count' => 30,
                'average_price'   => 'Variable selon activité',
                'description'     => 'Prestataires d’activités de loisirs et sorties culturelles.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 22,
                'name'            => 'Locations saisonnières',
                'slug'            => Str::slug('Locations saisonnières'),
                'icon'            => 'key',
                'providers_count' => 40,
                'average_price'   => 'Variable selon logement',
                'description'     => 'Locations meublées de courte durée pour tourisme et affaires.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'proprietaire', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_location', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 17
            [
                'category_id'     => 23,
                'name'            => 'Mobile money',
                'slug'            => Str::slug('Mobile money'),
                'icon'            => 'mobile-alt',
                'providers_count' => 70,
                'average_price'   => 'Variable selon transaction',
                'description'     => 'Prestataires de services de paiement mobile et transfert d’argent.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                        ['name' => 'telephone', 'type' => 'text'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_emi_psp', 'type' => 'file'],
                        ['name' => 'agrement_banque_centrale', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 23,
                'name'            => 'Agrégateurs de paiements',
                'slug'            => Str::slug('Agrégateurs de paiements'),
                'icon'            => 'layer-group',
                'providers_count' => 25,
                'average_price'   => 'Variable selon volume',
                'description'     => 'Sociétés proposant des solutions d’agrégation de paiements multicanaux.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_agregateur', 'type' => 'file'],
                        ['name' => 'agrement_banque_centrale', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 23,
                'name'            => 'Wallets électroniques',
                'slug'            => Str::slug('Wallets électroniques'),
                'icon'            => 'wallet',
                'providers_count' => 40,
                'average_price'   => 'Variable selon service',
                'description'     => 'Solutions de portefeuilles électroniques pour particuliers et entreprises.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'photo', 'type' => 'file'],
                        ['name' => 'telephone', 'type' => 'text'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_wallet', 'type' => 'file'],
                        ['name' => 'agrement_banque_centrale', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 23,
                'name'            => 'Crowdfunding & crypto-actifs',
                'slug'            => Str::slug('Crowdfunding & crypto-actifs'),
                'icon'            => 'coins',
                'providers_count' => 15,
                'average_price'   => 'Variable selon plateforme',
                'description'     => 'Plateformes de financement participatif et de gestion de crypto-actifs.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                        ['name' => 'licence_crypto', 'type' => 'file'],
                        ['name' => 'agrement_regulateur', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 18
            [
                'category_id'     => 24,
                'name'            => 'Banque classique & néobanque',
                'slug'            => Str::slug('Banque classique & néobanque'),
                'icon'            => 'university',
                'providers_count' => 20,
                'average_price'   => 'Variable selon produit',
                'description'     => 'Banques traditionnelles et néobanques offrant divers services financiers.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'date_naissance', 'type' => 'date'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                        ['name' => 'revenus', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                        ['name' => 'agrement_bancaire', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 24,
                'name'            => 'Crédit & microfinance',
                'slug'            => Str::slug('Crédit & microfinance'),
                'icon'            => 'hand-holding-usd',
                'providers_count' => 30,
                'average_price'   => 'Variable selon montant',
                'description'     => 'Institutions de microfinance et services de crédit.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                        ['name' => 'revenus', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                        ['name' => 'agrement_microfinance', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 24,
                'name'            => 'Comptes épargne & dépôt',
                'slug'            => Str::slug('Comptes épargne & dépôt'),
                'icon'            => 'piggy-bank',
                'providers_count' => 25,
                'average_price'   => 'Variable selon banque',
                'description'     => 'Ouverture et gestion de comptes épargne et dépôt.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 24,
                'name'            => 'Gestion patrimoniale',
                'slug'            => Str::slug('Gestion patrimoniale'),
                'icon'            => 'chart-line',
                'providers_count' => 15,
                'average_price'   => 'Variable selon contrat',
                'description'     => 'Services de gestion de patrimoine et d’investissement.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'revenus', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                        ['name' => 'licence_gestion_patrimoine', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 19
            [
                'category_id'     => 25,
                'name'            => 'Assurance santé',
                'slug'            => Str::slug('Assurance santé'),
                'icon'            => 'heartbeat',
                'providers_count' => 30,
                'average_price'   => '15000 FCFA / mois',
                'description'     => 'Compagnies d’assurances proposant des couvertures santé.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'sexe', 'type' => 'select'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                        ['name' => 'revenus', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_assurance', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 25,
                'name'            => 'Assurance auto',
                'slug'            => Str::slug('Assurance auto'),
                'icon'            => 'car',
                'providers_count' => 25,
                'average_price'   => '20000 FCFA / véhicule',
                'description'     => 'Assureurs automobiles agréés.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                        ['name' => 'revenus', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_assurance_auto', 'type' => 'file'],
                        ['name' => 'agrement_assurance', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 25,
                'name'            => 'Assurance habitation',
                'slug'            => Str::slug('Assurance habitation'),
                'icon'            => 'house-damage',
                'providers_count' => 20,
                'average_price'   => '10000 FCFA / mois',
                'description'     => 'Produits d’assurance habitation pour particuliers et entreprises.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_assurance_habitation', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 25,
                'name'            => 'Couverture risques professionnels',
                'slug'            => Str::slug('Couverture risques professionnels'),
                'icon'            => 'briefcase-medical',
                'providers_count' => 18,
                'average_price'   => 'Variable selon contrat',
                'description'     => 'Couvertures pour les risques liés aux activités professionnelles.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'justificatif_domicile', 'type' => 'file'],
                        ['name' => 'revenus', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_assurance_pro', 'type' => 'file'],
                        ['name' => 'agrement_assurance', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 20
            [
                'category_id'     => 26,
                'name'            => 'Vente en ligne multi-produits',
                'slug'            => Str::slug('Vente en ligne multi-produits'),
                'icon'            => 'shopping-basket',
                'providers_count' => 50,
                'average_price'   => 'Variable selon produit',
                'description'     => 'Plateformes et boutiques e-commerce multi-produits.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'telephone', 'type' => 'text'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_commerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 26,
                'name'            => 'Plateformes B2C & C2C',
                'slug'            => Str::slug('Plateformes B2C & C2C'),
                'icon'            => 'globe',
                'providers_count' => 35,
                'average_price'   => 'Commission selon vente',
                'description'     => 'Plateformes de mise en relation directe entre vendeurs et acheteurs.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'age', 'type' => 'number'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'telephone', 'type' => 'text'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_commerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 26,
                'name'            => 'Comparateurs de prix',
                'slug'            => Str::slug('Comparateurs de prix'),
                'icon'            => 'balance-scale',
                'providers_count' => 12,
                'average_price'   => 'Publicité / affiliation',
                'description'     => 'Plateformes de comparaison de prix entre différents vendeurs.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 26,
                'name'            => 'Solutions e-commerce intégrées',
                'slug'            => Str::slug('Solutions e-commerce intégrées'),
                'icon'            => 'cart-plus',
                'providers_count' => 20,
                'average_price'   => 'Variable selon service',
                'description'     => 'Prestataires proposant des solutions clés en main pour e-commerce.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_ecommerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 21
            [
                'category_id'     => 27,
                'name'            => 'Organisation d’événements',
                'slug'            => Str::slug('Organisation d’événements'),
                'icon'            => 'calendar-alt',
                'providers_count' => 30,
                'average_price'   => '200000 FCFA / événement',
                'description'     => 'Organisateurs professionnels d’événements privés ou publics.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_evenementiel', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 27,
                'name'            => 'Location de salles',
                'slug'            => Str::slug('Location de salles'),
                'icon'            => 'door-open',
                'providers_count' => 20,
                'average_price'   => '50000 FCFA / jour',
                'description'     => 'Prestataires offrant des salles pour divers événements.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_location', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 27,
                'name'            => 'Traiteurs & services cocktails',
                'slug'            => Str::slug('Traiteurs & services cocktails'),
                'icon'            => 'utensils',
                'providers_count' => 40,
                'average_price'   => '5000 FCFA / personne',
                'description'     => 'Services de restauration et cocktails pour événements.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 27,
                'name'            => 'Prestataires son & lumière',
                'slug'            => Str::slug('Prestataires son & lumière'),
                'icon'            => 'music',
                'providers_count' => 25,
                'average_price'   => '100000 FCFA / événement',
                'description'     => 'Prestataires techniques pour sonorisation et éclairage.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_son_lumiere', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 22
            [
                'category_id'     => 28,
                'name'            => 'Restaurants & fast-foods',
                'slug'            => Str::slug('Restaurants & fast-foods'),
                'icon'            => 'hamburger',
                'providers_count' => 60,
                'average_price'   => 'Variable selon menu',
                'description'     => 'Restaurants, fast-foods et établissements alimentaires.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'restaurateur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'haccp', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_restauration', 'type' => 'file'],
                        ['name' => 'agrement_sanitaire', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 28,
                'name'            => 'Livraison de repas',
                'slug'            => Str::slug('Livraison de repas'),
                'icon'            => 'biking',
                'providers_count' => 30,
                'average_price'   => '2000 FCFA / livraison',
                'description'     => 'Services de livraison de repas à domicile ou en entreprise.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'livreur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'permis_conduire', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 28,
                'name'            => 'Traiteurs privés & entreprises',
                'slug'            => Str::slug('Traiteurs privés & entreprises'),
                'icon'            => 'concierge-bell',
                'providers_count' => 25,
                'average_price'   => '15000 FCFA / menu',
                'description'     => 'Traiteurs spécialisés pour entreprises et particuliers.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'haccp', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 28,
                'name'            => 'Plateformes de commande en ligne',
                'slug'            => Str::slug('Plateformes de commande en ligne'),
                'icon'            => 'laptop',
                'providers_count' => 20,
                'average_price'   => 'Commission / commande',
                'description'     => 'Solutions digitales de commande et livraison de repas.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_ecommerce', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 23
            [
                'category_id'     => 29,
                'name'            => 'Formations professionnelles',
                'slug'            => Str::slug('Formations professionnelles'),
                'icon'            => 'chalkboard-teacher',
                'providers_count' => 40,
                'average_price'   => '50000 FCFA / module',
                'description'     => 'Formations techniques et professionnelles dans divers secteurs.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'formateur', 'type' => 'text'],
                        ['name' => 'diplome', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'agrement_formation', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 29,
                'name'            => 'Certifications',
                'slug'            => Str::slug('Certifications'),
                'icon'            => 'certificate',
                'providers_count' => 20,
                'average_price'   => '80000 FCFA / certification',
                'description'     => 'Instituts délivrant des certifications professionnelles.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'formateur', 'type' => 'text'],
                        ['name' => 'diplome', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'agrement_certification', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 29,
                'name'            => 'Ateliers métiers',
                'slug'            => Str::slug('Ateliers métiers'),
                'icon'            => 'tools',
                'providers_count' => 25,
                'average_price'   => '20000 FCFA / atelier',
                'description'     => 'Ateliers pratiques pour apprendre des métiers spécifiques.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'formateur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 29,
                'name'            => 'Coaching & mentoring',
                'slug'            => Str::slug('Coaching & mentoring'),
                'icon'            => 'user-graduate',
                'providers_count' => 30,
                'average_price'   => '30000 FCFA / séance',
                'description'     => 'Coaching individuel et mentoring professionnel.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'coach', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 24
            [
                'category_id'     => 30,
                'name'            => 'MOOC',
                'slug'            => Str::slug('MOOC'),
                'icon'            => 'laptop-code',
                'providers_count' => 20,
                'average_price'   => 'Variable selon plateforme',
                'description'     => 'Cours en ligne ouverts et massifs pour divers domaines.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'formateur', 'type' => 'text'],
                        ['name' => 'diplome', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_numerique', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 30,
                'name'            => 'Plateformes en ligne',
                'slug'            => Str::slug('Plateformes en ligne'),
                'icon'            => 'desktop',
                'providers_count' => 18,
                'average_price'   => 'Variable selon abonnement',
                'description'     => 'Plateformes numériques de formation et apprentissage.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_numerique', 'type' => 'file'],
                        ['name' => 'agrement_numerique', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 30,
                'name'            => 'Cours particuliers',
                'slug'            => Str::slug('Cours particuliers'),
                'icon'            => 'book-reader',
                'providers_count' => 35,
                'average_price'   => '10000 FCFA / heure',
                'description'     => 'Cours personnalisés pour élèves et étudiants.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'formateur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 30,
                'name'            => 'Webinaires & masterclass',
                'slug'            => Str::slug('Webinaires & masterclass'),
                'icon'            => 'video',
                'providers_count' => 22,
                'average_price'   => '15000 FCFA / session',
                'description'     => 'Sessions en ligne interactives avec experts et formateurs.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'formateur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 25
            [
                'category_id'     => 31,
                'name'            => 'Affichage & panneaux',
                'slug'            => Str::slug('Affichage & panneaux'),
                'icon'            => 'ad',
                'providers_count' => 20,
                'average_price'   => 'Variable selon emplacement',
                'description'     => 'Prestataires spécialisés dans l’affichage urbain et panneaux publicitaires.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_publicite', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 31,
                'name'            => 'Campagnes digitales',
                'slug'            => Str::slug('Campagnes digitales'),
                'icon'            => 'bullhorn',
                'providers_count' => 35,
                'average_price'   => 'Variable selon budget',
                'description'     => 'Agences spécialisées en campagnes digitales (Facebook Ads, Google Ads).',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'consultant', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_marketing', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 31,
                'name'            => 'Influenceurs & placement produit',
                'slug'            => Str::slug('Influenceurs & placement produit'),
                'icon'            => 'user-friends',
                'providers_count' => 25,
                'average_price'   => 'Variable selon audience',
                'description'     => 'Mise en relation avec influenceurs pour campagnes marketing.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'influenceur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'reseaux_sociaux', 'type' => 'url'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 31,
                'name'            => 'Marketing de rue',
                'slug'            => Str::slug('Marketing de rue'),
                'icon'            => 'street-view',
                'providers_count' => 15,
                'average_price'   => 'Variable selon prestation',
                'description'     => 'Prestataires en street marketing et distribution de flyers.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_marketing', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 26
            [
                'category_id'     => 32,
                'name'            => 'Relations presse',
                'slug'            => Str::slug('Relations presse'),
                'icon'            => 'newspaper',
                'providers_count' => 18,
                'average_price'   => '100000 FCFA / campagne',
                'description'     => 'Services de relations presse et communication institutionnelle.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'consultant', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_communication', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 32,
                'name'            => 'Community management',
                'slug'            => Str::slug('Community management'),
                'icon'            => 'comments',
                'providers_count' => 30,
                'average_price'   => '50000 FCFA / mois',
                'description'     => 'Gestion des réseaux sociaux pour marques et entreprises.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'community_manager', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 32,
                'name'            => 'Communication interne & externe',
                'slug'            => Str::slug('Communication interne & externe'),
                'icon'            => 'project-diagram',
                'providers_count' => 20,
                'average_price'   => 'Variable selon entreprise',
                'description'     => 'Cabinets spécialisés dans la communication globale.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_communication', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 32,
                'name'            => 'Branding & storytelling',
                'slug'            => Str::slug('Branding & storytelling'),
                'icon'            => 'paint-brush',
                'providers_count' => 22,
                'average_price'   => 'Variable selon projet',
                'description'     => 'Création et développement d’identité visuelle et narrative des marques.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'consultant', 'type' => 'text'],
                        ['name' => 'portfolio', 'type' => 'file'],
                        ['name' => 'cni', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 27
            [
                'category_id'     => 33,
                'name'            => 'Musique & concerts',
                'slug'            => Str::slug('Musique & concerts'),
                'icon'            => 'music',
                'providers_count' => 25,
                'average_price'   => 'Variable selon artiste',
                'description'     => 'Artistes, groupes et promoteurs d’événements musicaux.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'artiste', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_spectacle', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 33,
                'name'            => 'Cinéma & audiovisuel',
                'slug'            => Str::slug('Cinéma & audiovisuel'),
                'icon'            => 'video',
                'providers_count' => 15,
                'average_price'   => 'Variable selon projet',
                'description'     => 'Studios et sociétés de production cinématographique et audiovisuelle.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_audiovisuel', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 33,
                'name'            => 'Artisanat d’art',
                'slug'            => Str::slug('Artisanat d’art'),
                'icon'            => 'hand-paper',
                'providers_count' => 20,
                'average_price'   => 'Variable selon œuvre',
                'description'     => 'Artisans d’art et créateurs indépendants.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'artisan', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 33,
                'name'            => 'Design graphique',
                'slug'            => Str::slug('Design graphique'),
                'icon'            => 'pen-nib',
                'providers_count' => 30,
                'average_price'   => '25000 FCFA / projet',
                'description'     => 'Graphistes spécialisés dans la conception visuelle et numérique.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'designer', 'type' => 'text'],
                        ['name' => 'portfolio', 'type' => 'file'],
                        ['name' => 'cni', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 28
            [
                'category_id'     => 34,
                'name'            => 'Agriculture & élevage',
                'slug'            => Str::slug('Agriculture & élevage'),
                'icon'            => 'tractor',
                'providers_count' => 50,
                'average_price'   => 'Variable selon produit',
                'description'     => 'Producteurs agricoles et éleveurs.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'agriculteur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'titre_foncier', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_agricole', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 34,
                'name'            => 'Agroalimentaire',
                'slug'            => Str::slug('Agroalimentaire'),
                'icon'            => 'apple-alt',
                'providers_count' => 35,
                'average_price'   => 'Variable selon transformation',
                'description'     => 'Entreprises de transformation et distribution agroalimentaire.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_agroalimentaire', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 34,
                'name'            => 'Produits bio & circuits courts',
                'slug'            => Str::slug('Produits bio & circuits courts'),
                'icon'            => 'leaf',
                'providers_count' => 20,
                'average_price'   => 'Variable selon produit',
                'description'     => 'Producteurs et distributeurs bio favorisant les circuits courts.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'producteur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 34,
                'name'            => 'Coopératives agricoles',
                'slug'            => Str::slug('Coopératives agricoles'),
                'icon'            => 'users',
                'providers_count' => 15,
                'average_price'   => 'Cotisation annuelle variable',
                'description'     => 'Groupements de producteurs et coopératives agricoles.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_cooperative', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 29
            [
                'category_id'     => 35,
                'name'            => 'Fourniture électricité & eau',
                'slug'            => Str::slug('Fourniture électricité & eau'),
                'icon'            => 'bolt',
                'providers_count' => 25,
                'average_price'   => 'Variable selon contrat',
                'description'     => 'Fournisseurs de services d’électricité et d’eau.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'nom', 'type' => 'text'],
                        ['name' => 'prenom', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_energie', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 35,
                'name'            => 'Énergies renouvelables',
                'slug'            => Str::slug('Énergies renouvelables'),
                'icon'            => 'solar-panel',
                'providers_count' => 30,
                'average_price'   => 'Variable selon installation',
                'description'     => 'Prestataires en solaire, hydro, éolien et autres énergies vertes.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'ingenieur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_energie', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'agrement_ministere_energie', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 35,
                'name'            => 'Gestion des déchets',
                'slug'            => Str::slug('Gestion des déchets'),
                'icon'            => 'trash',
                'providers_count' => 20,
                'average_price'   => 'Variable selon contrat',
                'description'     => 'Sociétés spécialisées dans le traitement et la gestion des déchets.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'licence_gestion_dechets', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 35,
                'name'            => 'Projets écologiques & RSE',
                'slug'            => Str::slug('Projets écologiques & RSE'),
                'icon'            => 'leaf',
                'providers_count' => 15,
                'average_price'   => 'Variable selon projet',
                'description'     => 'Initiatives vertes et projets de responsabilité sociétale.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                        ['name' => 'agrement_ecologique', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            // next 30
            [
                'category_id'     => 36,
                'name'            => 'Développement logiciel & apps',
                'slug'            => Str::slug('Développement logiciel & apps'),
                'icon'            => 'code',
                'providers_count' => 50,
                'average_price'   => '200000 FCFA / projet',
                'description'     => 'Développeurs et sociétés spécialisées en logiciels et applications.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'developpeur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_it', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 36,
                'name'            => 'Cybersécurité',
                'slug'            => Str::slug('Cybersécurité'),
                'icon'            => 'shield-alt',
                'providers_count' => 20,
                'average_price'   => 'Variable selon mission',
                'description'     => 'Experts en cybersécurité et protection des systèmes d’information.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'expert', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_it', 'type' => 'file'],
                        ['name' => 'experience', 'type' => 'number'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'licence_cybersecurite', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 36,
                'name'            => 'Intelligence artificielle',
                'slug'            => Str::slug('Intelligence artificielle'),
                'icon'            => 'robot',
                'providers_count' => 15,
                'average_price'   => '300000 FCFA / projet',
                'description'     => 'Prestataires spécialisés en IA et machine learning.',
                'form_schema'     => json_encode([
                    'KYC' => [
                        ['name' => 'ingenieur', 'type' => 'text'],
                        ['name' => 'cni', 'type' => 'file'],
                        ['name' => 'diplome_it', 'type' => 'file'],
                        ['name' => 'portfolio', 'type' => 'file'],
                    ],
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'brevets', 'type' => 'file'],
                        ['name' => 'agrement_tic', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'category_id'     => 36,
                'name'            => 'Solutions cloud & IoT',
                'slug'            => Str::slug('Solutions cloud & IoT'),
                'icon'            => 'cloud',
                'providers_count' => 25,
                'average_price'   => 'Variable selon abonnement',
                'description'     => 'Prestataires cloud computing et Internet des objets.',
                'form_schema'     => json_encode([
                    'KYB' => [
                        ['name' => 'rccm', 'type' => 'text'],
                        ['name' => 'nif', 'type' => 'text'],
                        ['name' => 'statuts', 'type' => 'file'],
                        ['name' => 'agrement_tic', 'type' => 'file'],
                    ]
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ];
        DB::table('sub_categories')->insert($subCategories);
    }
}
