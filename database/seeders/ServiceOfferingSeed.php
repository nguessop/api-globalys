<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ServiceOffering;
use App\Models\User;
use App\Models\SubCategory;

class ServiceOfferingSeed extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Récupère un prestataire existant
            $provider = User::where('user_type', 'prestataire')->first();
            if (!$provider) {
                $this->command->warn('⚠️ Aucun prestataire trouvé pour le seed ServiceOffering.');
                return;
            }

            // Récupère une sous-catégorie existante
            $subCategory = SubCategory::first();
            if (!$subCategory) {
                $this->command->warn('⚠️ Aucune sous-catégorie trouvée pour le seed ServiceOffering.');
                return;
            }

            // Exemple 1 : Service de ménage
            ServiceOffering::updateOrCreate(
                [
                    'provider_id'     => $provider->id,
                    'sub_category_id' => $subCategory->id,
                    'title'           => 'Service de ménage à domicile',
                ],
                [
                    'description'       => 'Nettoyage complet de votre domicile, incluant sols, vitres et poussière.',
                    'price_amount'      => 15000,
                    'price_unit'        => 'service',
                    'currency'          => 'XAF',
                    'tax_rate'          => 0,
                    'discount_amount'   => 0,
                    'city'              => 'Douala',
                    'country'           => 'CM',
                    'address'           => 'Bonapriso',
                    'coverage_km'       => 15,
                    'on_site'           => true,
                    'at_provider'       => false,
                    'lat'               => 4.0511,
                    'lng'               => 9.7679,
                    'min_delay_hours'   => 24,
                    'max_delay_hours'   => 72,
                    'duration_minutes'  => 120,
                    'capacity'          => 1,
                    'status'            => 'active',
                    'published_at'      => Carbon::now(),
                    'featured'          => true,
                    'is_verified'       => true,
                    'avg_rating'        => 4.8,
                    'ratings_count'     => 12,
                    'views_count'       => 45,
                    'bookings_count'    => 8,
                    'favorites_count'   => 5,
                    'attachments'       => json_encode(['photo1.jpg', 'photo2.jpg']),
                    'metadata'          => json_encode(['langues' => ['fr', 'en']]),
                ]
            );

            // Exemple 2 : Réparation d’appareils électroménagers
            ServiceOffering::updateOrCreate(
                [
                    'provider_id'     => $provider->id,
                    'sub_category_id' => $subCategory->id,
                    'title'           => 'Réparation d’appareils électroménagers',
                ],
                [
                    'description'       => 'Réparation rapide et garantie de vos appareils ménagers.',
                    'price_amount'      => 10000,
                    'price_unit'        => 'hour',
                    'currency'          => 'XAF',
                    'tax_rate'          => 0,
                    'discount_amount'   => 0,
                    'city'              => 'Yaoundé',
                    'country'           => 'CM',
                    'address'           => 'Bastos',
                    'coverage_km'       => 20,
                    'on_site'           => true,
                    'at_provider'       => true,
                    'lat'               => 3.8480,
                    'lng'               => 11.5021,
                    'min_delay_hours'   => 12,
                    'max_delay_hours'   => 48,
                    'duration_minutes'  => 90,
                    'capacity'          => 2,
                    'status'            => 'active',
                    'published_at'      => Carbon::now(),
                    'featured'          => false,
                    'is_verified'       => true,
                    'avg_rating'        => 4.5,
                    'ratings_count'     => 7,
                    'views_count'       => 30,
                    'bookings_count'    => 4,
                    'favorites_count'   => 3,
                    'attachments'       => json_encode(['photo3.jpg']),
                    'metadata'          => json_encode(['garantie' => '3 mois']),
                ]
            );
        });
    }
}
