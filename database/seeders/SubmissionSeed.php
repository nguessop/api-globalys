<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubmissionSeed extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $submissions = [
            [
                'sub_category_id' => 1, // Avocats, notaires, huissiers
                'user_id' => 3, // un client particulier
                'data' => json_encode([
                    'nom' => 'Nguessop',
                    'prenom' => 'Christel',
                    'sexe' => 'M',
                    'date_naissance' => '1988-05-12',
                    'cni' => '1122334455',
                    'justificatif_domicile' => 'facture ENEO',
                    'annees_exercice' => 8,
                    'diplome' => 'Master en droit du travail'
                ]),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => 5, // Téléconsultation médicale
                'user_id' => 4, // un client particulier
                'data' => json_encode([
                    'nom' => 'Marie',
                    'prenom' => 'Pro',
                    'sexe' => 'F',
                    'date_naissance' => '1990-03-15',
                    'cni' => '5566778899',
                    'diplome_medecine' => 'Doctorat en médecine',
                    'licence_medicale' => 'MED-2025-0098',
                    'annees_experience' => 5
                ]),
                'status' => 'in_progress',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => 12, // Logistique & Transport
                'user_id' => 5, // une entreprise cliente
                'data' => json_encode([
                    'entreprise' => 'LogiFast SARL',
                    'rccm' => 'RC/DLA/2020/B123',
                    'nif' => '100200300',
                    'licence_transport' => 'MT-TRANS-2025-07',
                    'zone_operation' => 'Douala – Yaoundé',
                    'vehicules' => 15
                ]),
                'status' => 'completed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sub_category_id' => 22, // Restauration (traiteurs)
                'user_id' => 6, // un particulier client
                'data' => json_encode([
                    'nom' => 'Paul',
                    'prenom' => 'Etienne',
                    'cni' => '9988776655',
                    'demande' => 'Organisation d’un mariage 150 invités',
                    'menu' => 'Buffet africain + cocktails',
                    'delai' => '3 mois'
                ]),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        DB::table('submissions')->insert($submissions);
    }
}
