<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\AvailabilitySlot;
use App\Models\ServiceOffering;
use App\Models\User;

class AvailabilitySlotSeed extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Récupérer un prestataire existant
            $provider = User::where('user_type', 'prestataire')->first();
            if (!$provider) {
                $this->command->warn('⚠️ Aucun prestataire trouvé pour le seed AvailabilitySlot.');
                return;
            }

            // Récupérer une offre de service existante
            $serviceOffering = ServiceOffering::first();
            if (!$serviceOffering) {
                $this->command->warn('⚠️ Aucune offre de service trouvée pour le seed AvailabilitySlot.');
                return;
            }

            // Créneau 1 : disponible demain
            AvailabilitySlot::updateOrCreate(
                [
                    'provider_id'          => $provider->id,
                    'service_offering_id'  => $serviceOffering->id,
                    'start_at'             => Carbon::now()->addDay()->setTime(9, 0, 0),
                ],
                [
                    'end_at'               => Carbon::now()->addDay()->setTime(11, 0, 0),
                    'timezone'             => 'Africa/Douala',
                    'capacity'             => 2,
                    'booked_count'         => 0,
                    'price_override'       => null,
                    'currency'             => 'XAF',
                    'is_recurring'         => false,
                    'recurrence_rule'      => null,
                    'status'               => 'available',
                    'notes'                => 'Créneau du matin',
                ]
            );

            // Créneau 2 : complet dans 3 jours
            AvailabilitySlot::updateOrCreate(
                [
                    'provider_id'          => $provider->id,
                    'service_offering_id'  => $serviceOffering->id,
                    'start_at'             => Carbon::now()->addDays(3)->setTime(14, 0, 0),
                ],
                [
                    'end_at'               => Carbon::now()->addDays(3)->setTime(16, 0, 0),
                    'timezone'             => 'Africa/Douala',
                    'capacity'             => 3,
                    'booked_count'         => 3,
                    'price_override'       => 20000,
                    'currency'             => 'XAF',
                    'is_recurring'         => false,
                    'recurrence_rule'      => null,
                    'status'               => 'full',
                    'notes'                => 'Après-midi complet',
                ]
            );

            // Créneau 3 : récurrent tous les lundis matin
            AvailabilitySlot::updateOrCreate(
                [
                    'provider_id'          => $provider->id,
                    'service_offering_id'  => $serviceOffering->id,
                    'start_at'             => Carbon::now()->next('Monday')->setTime(8, 0, 0),
                ],
                [
                    'end_at'               => Carbon::now()->next('Monday')->setTime(10, 0, 0),
                    'timezone'             => 'Africa/Douala',
                    'capacity'             => 1,
                    'booked_count'         => 0,
                    'price_override'       => null,
                    'currency'             => 'XAF',
                    'is_recurring'         => true,
                    'recurrence_rule'      => 'FREQ=WEEKLY;BYDAY=MO',
                    'status'               => 'available',
                    'notes'                => 'Lundi matin hebdo',
                ]
            );
        });
    }
}
