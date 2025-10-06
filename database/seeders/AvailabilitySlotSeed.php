<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AvailabilitySlot;
use App\Models\ServiceOffering;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AvailabilitySlotSeed extends Seeder
{
    public function run(): void
    {
        // ⚠️ On suppose que ServiceOffering a bien une colonne provider_id (-> users.id)
        // et que des services existent déjà (seedés avant).
        $services = ServiceOffering::query()
            ->select(['id', 'provider_id'])
            ->whereNotNull('provider_id')
            ->inRandomOrder()
            ->get();

        if ($services->isEmpty()) {
            $this->command->warn('⚠️ Aucun service (ServiceOffering) trouvé avec un provider_id. Seed les services avant.');
            return;
        }

        $timezones = ['Africa/Douala', 'UTC', 'Europe/Paris'];
        $now = Carbon::now();

        // On génère des créneaux par service (3 à 6 créneaux sur les 2 prochaines semaines)
        foreach ($services as $service) {
            $slotsToCreate = rand(3, 6);

            for ($i = 0; $i < $slotsToCreate; $i++) {
                // Jour entre J+1 et J+14
                $start = (clone $now)
                    ->addDays(rand(1, 14))
                    ->setTime(rand(8, 18), [0, 30][rand(0, 1)]);

                // Durée 1 à 3h
                $end = (clone $start)->addHours(rand(1, 3));

                // Capacité et remplissage cohérents
                $capacity = rand(1, 5);
                $booked = rand(0, $capacity); // peut être 0

                // Eventuel override tarifaire
                $priceOverride = rand(0, 1) ? rand(10000, 80000) : null;

                AvailabilitySlot::create([
                    'service_offering_id' => $service->id,
                    'provider_id'         => $service->provider_id,
                    'start_at'            => $start,
                    'end_at'              => $end,
                    'timezone'            => $timezones[array_rand($timezones)],

                    'capacity'            => $capacity,
                    'booked_count'        => $booked,

                    'price_override'      => $priceOverride,
                    'currency'            => $priceOverride ? 'XAF' : null,

                    'is_recurring'        => false,
                    'recurrence_rule'     => null,

                    'status'              => $booked >= $capacity ? 'full' : 'available',
                    'notes'               => 'Créneau seed ' . Str::upper(Str::random(4)),
                ]);
            }
        }

        $this->command->info('✅ AvailabilitySlotSeeder : créneaux générés depuis les services (provider_id) sans dépendre des rôles.');
    }
}
