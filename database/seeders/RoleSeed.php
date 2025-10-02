<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeed extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $roles = [
            [
                'name'  => 'super_admin',
                'label' => 'Super Administrateur (contrôle total de GLOBALYS)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'  => 'admin',
                'label' => 'Administrateur (modération, support, finance)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'  => 'prestataire_individuel',
                'label' => 'Prestataire Particulier (indépendant : avocat, coiffeur, chauffeur…)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'  => 'prestataire_entreprise',
                'label' => 'Prestataire Entreprise (cabinet, clinique, agence, société)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'  => 'client_particulier',
                'label' => 'Client Particulier (B2C / C2C : consomme les services)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'  => 'client_entreprise',
                'label' => 'Client Entreprise (B2B : sociétés qui achètent des services)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'  => 'partenaire_strategique',
                'label' => 'Partenaire Stratégique (banques, assurances, institutions publiques)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}
