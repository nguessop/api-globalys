<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeed extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            /* ---------------------------
             | 1) Rôles de base
             --------------------------- */
            $roles = [
                'admin',
                'client',
                'prestataire',
                'entreprise',
            ];

            $roleMap = [];
            foreach ($roles as $r) {
                $role = Role::firstOrCreate(['name' => $r], ['name' => $r]);
                $roleMap[$r] = $role->id;
            }

            /* ---------------------------
             | 2) Admin
             --------------------------- */
            User::updateOrCreate(
                ['email' => 'admin@globalys.com'],
                [
                    'first_name'          => 'Admin',
                    'last_name'           => 'System',
                    'phone'               => '+237600000001',
                    'password'            => Hash::make('password'), // ⚠️ à changer en prod
                    'preferred_language'  => 'français',
                    'country'             => 'Cameroun',
                    'account_type'        => 'particulier',
                    'user_type'           => 'client',
                    'role_id'             => $roleMap['admin'] ?? null,
                    'accepts_terms'       => true,
                    'wants_newsletter'    => false,
                ]
            );

            /* ---------------------------
             | 3) Client (particulier)
             --------------------------- */
            User::updateOrCreate(
                ['email' => 'client@globalys.com'],
                [
                    'first_name'          => 'Jean',
                    'last_name'           => 'Client',
                    'phone'               => '+237600000002',
                    'password'            => Hash::make('password'),
                    'preferred_language'  => 'français',
                    'country'             => 'Cameroun',
                    'account_type'        => 'particulier',
                    'user_type'           => 'client',
                    'gender'              => 'Homme',
                    'birthdate'           => '1990-01-01',
                    'job'                 => 'Professeur',
                    'personal_address'    => 'Douala',
                    'role_id'             => $roleMap['client'] ?? null,
                    'accepts_terms'       => true,
                    'wants_newsletter'    => true,
                ]
            );

            /* ---------------------------
             | 4) Prestataire (particulier)
             --------------------------- */
            User::updateOrCreate(
                ['email' => 'prestataire@globalys.com'],
                [
                    'first_name'          => 'Marie',
                    'last_name'           => 'Pro',
                    'phone'               => '+237600000003',
                    'password'            => Hash::make('password'),
                    'preferred_language'  => 'français',
                    'country'             => 'Cameroun',
                    'account_type'        => 'particulier',
                    'user_type'           => 'prestataire',
                    'gender'              => 'Femme',
                    'birthdate'           => '1988-05-12',
                    'job'                 => 'Esthéticienne',
                    'personal_address'    => 'Yaoundé',
                    'role_id'             => $roleMap['prestataire'] ?? null,
                    'accepts_terms'       => true,
                    'wants_newsletter'    => false,
                ]
            );

            /* ---------------------------
             | 5) Entreprise (sans abonnement)
             --------------------------- */
            User::updateOrCreate(
                ['email' => 'entreprise@globalys.com'],
                [
                    'first_name'              => 'Entreprise',
                    'last_name'               => 'SARL',
                    'phone'                   => '+237600000004',
                    'password'                => Hash::make('password'),
                    'preferred_language'      => 'français',
                    'country'                 => 'Cameroun',
                    'account_type'            => 'entreprise',
                    'user_type'               => 'prestataire', // si l’entreprise vend des services
                    'role_id'                 => $roleMap['entreprise'] ?? null,

                    // Champs entreprise
                    'company_name'            => 'Global Services',
                    'sector'                  => 'Nettoyage industriel',
                    'tax_number'              => 'CMU123456789',
                    'website'                 => 'https://globalservices.cm',
                    'company_description'     => 'Entreprise spécialisée dans les services de propreté.',
                    'company_address'         => 'Zone industrielle Bassa',
                    'company_city'            => 'Douala',
                    'company_size'            => '11-50',
                    'preferred_contact_method' => 'Email',

                    'accepts_terms'           => true,
                    'wants_newsletter'        => true,
                ]
            );
        });
    }
}
