<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Booking;
use App\Models\User;

class BookingSeed extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // On prend un client et un prestataire existants
            $client    = User::where('user_type', 'client')->first();
            $provider  = User::where('user_type', 'prestataire')->first();

            if (!$client || !$provider) {
                $this->command->warn('⚠️ Aucun client ou prestataire trouvé pour le seed Booking.');
                return;
            }

            // Réservation 1
            Booking::updateOrCreate(
                ['code' => 'BK-1001'],
                [
                    'service_offering_id' => 1, // à ajuster selon tes données
                    'client_id'           => $client->id,
                    'quantity'            => 2,
                    'unit_price'          => 15000,
                    'total_price'         => 30000,
                    'status'              => 'confirmed',
                    'notes_client'        => 'Merci d’arriver à l’heure.',
                    'notes_provider'      => 'Prévoir le matériel nécessaire.',
                    'cancellation_reason' => null,
                    'cancelled_at'        => null,
                    'provider_id'         => $provider->id,
                    'start_at'            => Carbon::now()->addDays(2),
                    'end_at'              => Carbon::now()->addDays(2)->addHours(2),
                    'city'                => 'Douala',
                    'address'             => 'Bonapriso',
                    'currency'            => 'XAF',
                    'subtotal'            => 30000,
                    'tax_rate'            => 0,
                    'tax_amount'          => 0,
                    'discount_amount'     => 0,
                    'total_amount'        => 30000,
                    'payment_status'      => 'paid',
                ]
            );

            // Réservation 2
            Booking::updateOrCreate(
                ['code' => 'BK-1002'],
                [
                    'service_offering_id' => 1,
                    'client_id'           => $client->id,
                    'quantity'            => 1,
                    'unit_price'          => 20000,
                    'total_price'         => 20000,
                    'status'              => 'completed',
                    'notes_client'        => 'Très satisfait.',
                    'notes_provider'      => null,
                    'cancellation_reason' => null,
                    'cancelled_at'        => null,
                    'provider_id'         => $provider->id,
                    'start_at'            => Carbon::now()->subDays(3),
                    'end_at'              => Carbon::now()->subDays(3)->addHours(1),
                    'city'                => 'Yaoundé',
                    'address'             => 'Bastos',
                    'currency'            => 'XAF',
                    'subtotal'            => 20000,
                    'tax_rate'            => 0,
                    'tax_amount'          => 0,
                    'discount_amount'     => 0,
                    'total_amount'        => 20000,
                    'payment_status'      => 'paid',
                ]
            );
        });
    }
}
