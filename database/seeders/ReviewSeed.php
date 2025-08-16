<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Review;
use App\Models\User;
use App\Models\ServiceOffering;
use App\Models\Booking;

class ReviewSeed extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Récupération d'un client et d'un prestataire
            $client = User::where('user_type', 'client')->first();
            $provider = User::where('user_type', 'prestataire')->first();

            if (!$client || !$provider) {
                $this->command->warn('⚠️ Aucun client ou prestataire trouvé pour le seed Review.');
                return;
            }

            // Récupération d'une offre de service
            $serviceOffering = ServiceOffering::first();
            if (!$serviceOffering) {
                $this->command->warn('⚠️ Aucune offre de service trouvée pour le seed Review.');
                return;
            }

            // Récupération d'une réservation associée (si disponible)
            $booking = Booking::where('client_id', $client->id)
                ->where('provider_id', $provider->id)
                ->first();

            // Avis 1 : 5 étoiles
            Review::updateOrCreate(
                [
                    'client_id'           => $client->id,
                    'provider_id'         => $provider->id,
                    'service_offering_id' => $serviceOffering->id,
                    'booking_id'          => $booking ? $booking->id : null,
                ],
                [
                    'rating'      => 5,
                    'comment'     => 'Prestation impeccable, très professionnel et ponctuel.',
                    'is_approved' => true,
                    'created_at'  => Carbon::now()->subDays(2),
                    'updated_at'  => Carbon::now()->subDays(2),
                ]
            );

            // Avis 2 : 4 étoiles
            Review::updateOrCreate(
                [
                    'client_id'           => $client->id,
                    'provider_id'         => $provider->id,
                    'service_offering_id' => $serviceOffering->id,
                    'booking_id'          => null, // pas de lien avec une réservation
                ],
                [
                    'rating'      => 4,
                    'comment'     => 'Bon service, mais un léger retard à l’arrivée.',
                    'is_approved' => true,
                    'created_at'  => Carbon::now()->subDays(1),
                    'updated_at'  => Carbon::now()->subDays(1),
                ]
            );
        });
    }
}
