<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeed extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $users = [
            // ================= SUPER ADMIN =================
            [
                'first_name' => 'Christel',
                'last_name' => 'Nguesso',
                'email' => 'superadmin@globalys.com',
                'phone' => '+237690000001',
                'password' => Hash::make('password'),
                'preferred_language' => 'français',
                'country' => 'Cameroun',
                'company_city' => 'Douala',
                'account_type' => 'individual',
                'role_id' => 1, // super_admin
                'subscription_id' => null,

                // Champs entreprise
                'company_name' => null,
                'sector' => null,
                'tax_number' => null,
                'website' => null,
                'company_logo' => null,
                'company_description' => null,
                'company_address' => null,
                'company_size' => null,
                'preferred_contact_method' => null,

                // Champs particulier
                'gender' => 'Homme',
                'birthdate' => null,
                'job' => 'Fondateur',
                'personal_address' => null,

                'user_type' => null,
                'profile_photo' => null,
                'accepts_terms' => true,
                'wants_newsletter' => true,
                'profile_views' => 0,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ================= ADMIN =================
            [
                'first_name' => 'Amina',
                'last_name' => 'Moukou',
                'email' => 'admin@globalys.com',
                'phone' => '+237690000002',
                'password' => Hash::make('password'),
                'preferred_language' => 'français',
                'country' => 'Cameroun',
                'company_city' => 'Yaoundé',
                'account_type' => 'individual',
                'role_id' => 2, // admin
                'subscription_id' => null,

                // Champs entreprise
                'company_name' => null,
                'sector' => null,
                'tax_number' => null,
                'website' => null,
                'company_logo' => null,
                'company_description' => null,
                'company_address' => null,
                'company_size' => null,
                'preferred_contact_method' => null,

                // Champs particulier
                'gender' => 'Femme',
                'birthdate' => null,
                'job' => 'Responsable Support',
                'personal_address' => null,

                'user_type' => null,
                'profile_photo' => null,
                'accepts_terms' => true,
                'wants_newsletter' => false,
                'profile_views' => 0,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ================= PRESTATAIRE PARTICULIER =================
            [
                'first_name' => 'Jean',
                'last_name' => 'Mbappe',
                'email' => 'prestataire1@globalys.com',
                'phone' => '+237690000003',
                'password' => Hash::make('password'),
                'preferred_language' => 'français',
                'country' => 'Cameroun',
                'company_city' => 'Douala',
                'account_type' => 'individual',
                'role_id' => 3, // prestataire_particulier
                'subscription_id' => 1,

                // Champs entreprise
                'company_name' => null,
                'sector' => null,
                'tax_number' => null,
                'website' => null,
                'company_logo' => null,
                'company_description' => null,
                'company_address' => null,
                'company_size' => null,
                'preferred_contact_method' => null,

                // Champs particulier
                'gender' => 'Homme',
                'birthdate' => '1990-05-12',
                'job' => 'Avocat Indépendant',
                'personal_address' => 'Akwa',

                'user_type' => 'prestataire_particulier',
                'profile_photo' => null,
                'accepts_terms' => true,
                'wants_newsletter' => false,
                'profile_views' => 10,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ================= PRESTATAIRE ENTREPRISE =================
            [
                'first_name' => 'Cabinet',
                'last_name' => 'Juridique',
                'email' => 'prestataire2@globalys.com',
                'phone' => '+237690000004',
                'password' => Hash::make('password'),
                'preferred_language' => 'français',
                'country' => 'Cameroun',
                'company_city' => 'Douala',
                'account_type' => 'business',
                'role_id' => 4, // prestataire_entreprise
                'subscription_id' => 2,

                // Champs entreprise
                'company_name' => 'Cabinet Droit & Associés',
                'sector' => 'Services Juridiques',
                'tax_number' => 'RC123456',
                'website' => 'https://cabinetjuridique.com',
                'company_logo' => null,
                'company_description' => 'Cabinet spécialisé en droit des affaires.',
                'company_address' => 'Bonanjo',
                'company_size' => '10-50',
                'preferred_contact_method' => 'email',

                // Champs particulier
                'gender' => null,
                'birthdate' => null,
                'job' => null,
                'personal_address' => null,

                'user_type' => 'prestataire_entreprise',
                'profile_photo' => null,
                'accepts_terms' => true,
                'wants_newsletter' => true,
                'profile_views' => 30,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ================= CLIENT PARTICULIER =================
            [
                'first_name' => 'Sarah',
                'last_name' => 'Etoa',
                'email' => 'client1@globalys.com',
                'phone' => '+237690000005',
                'password' => Hash::make('password'),
                'preferred_language' => 'français',
                'country' => 'Cameroun',
                'company_city' => 'Yaoundé',
                'account_type' => 'individual',
                'role_id' => 5, // client_particulier
                'subscription_id' => null,

                // Champs entreprise
                'company_name' => null,
                'sector' => null,
                'tax_number' => null,
                'website' => null,
                'company_logo' => null,
                'company_description' => null,
                'company_address' => null,
                'company_size' => null,
                'preferred_contact_method' => null,

                // Champs particulier
                'gender' => 'Femme',
                'birthdate' => '1995-08-23',
                'job' => 'Étudiante',
                'personal_address' => 'Biyem-Assi',

                'user_type' => 'client_particulier',
                'profile_photo' => null,
                'accepts_terms' => true,
                'wants_newsletter' => true,
                'profile_views' => 5,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ================= CLIENT ENTREPRISE =================
            [
                'first_name' => 'Société',
                'last_name' => 'TechCM',
                'email' => 'client2@globalys.com',
                'phone' => '+237690000006',
                'password' => Hash::make('password'),
                'preferred_language' => 'français',
                'country' => 'Cameroun',
                'company_city' => 'Douala',
                'account_type' => 'business',
                'role_id' => 6, // client_entreprise
                'subscription_id' => 3,

                // Champs entreprise
                'company_name' => 'TechCM SARL',
                'sector' => 'Technologie',
                'tax_number' => 'RC654321',
                'website' => 'https://techcm.com',
                'company_logo' => null,
                'company_description' => 'Entreprise de services numériques.',
                'company_address' => 'Bonamoussadi',
                'company_size' => '50-200',
                'preferred_contact_method' => 'phone',

                // Champs particulier
                'gender' => null,
                'birthdate' => null,
                'job' => null,
                'personal_address' => null,

                'user_type' => 'client_entreprise',
                'profile_photo' => null,
                'accepts_terms' => true,
                'wants_newsletter' => false,
                'profile_views' => 20,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ================= PARTENAIRE STRATÉGIQUE =================
            [
                'first_name' => 'Banque',
                'last_name' => 'Afrique',
                'email' => 'partenaire@globalys.com',
                'phone' => '+237690000007',
                'password' => Hash::make('password'),
                'preferred_language' => 'français',
                'country' => 'Cameroun',
                'company_city' => 'Yaoundé',
                'account_type' => 'partner',
                'role_id' => 7, // partenaire_strategique
                'subscription_id' => null,

                // Champs entreprise
                'company_name' => 'Banque Afrique Centrale',
                'sector' => 'Finance',
                'tax_number' => 'BNQ777',
                'website' => 'https://banqueafrique.com',
                'company_logo' => null,
                'company_description' => 'Institution financière partenaire de GLOBALYS.',
                'company_address' => 'Avenue Kennedy',
                'company_size' => '1000+',
                'preferred_contact_method' => 'email',

                // Champs particulier
                'gender' => null,
                'birthdate' => null,
                'job' => null,
                'personal_address' => null,

                'user_type' => 'partenaire_strategique',
                'profile_photo' => null,
                'accepts_terms' => true,
                'wants_newsletter' => true,
                'profile_views' => 100,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('users')->insert($users);
    }
}
