<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Commission;
use App\Models\Booking;
use App\Models\User;

class CommissionSeed extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $provider = User::where('user_type', 'prestataire')->first();

            if (!$provider) {
                $this->command->warn('⚠️ Aucun prestataire trouvé pour le seed Commission.');
                return;
            }

            $booking1 = Booking::where('code', 'BK-1001')->first();
            $booking2 = Booking::where('code', 'BK-1002')->first();

            if ($booking1) {
                Commission::updateOrCreate(
                    ['booking_id' => $booking1->id],
                    [
                        'provider_id'       => $provider->id,
                        'subscription_id'   => null,
                        'base_amount'       => $booking1->total_amount,
                        'currency'          => 'XAF',
                        'commission_type'   => 'percent',
                        'commission_rate'   => 10.00,
                        'commission_fixed'  => null,
                        'amount'            => $booking1->total_amount * 0.10,
                        'status'            => 'pending',
                        'captured_at'       => null,
                        'settled_at'        => null,
                        'refunded_at'       => null,
                        'external_reference'=> null,
                        'notes'             => 'Commission 10% sur BK-1001',
                        'metadata'          => json_encode(['source' => 'seed']),
                    ]
                );
            }

            if ($booking2) {
                Commission::updateOrCreate(
                    ['booking_id' => $booking2->id],
                    [
                        'provider_id'       => $provider->id,
                        'subscription_id'   => null,
                        'base_amount'       => $booking2->total_amount,
                        'currency'          => 'XAF',
                        'commission_type'   => 'percent',
                        'commission_rate'   => 8.00,
                        'commission_fixed'  => null,
                        'amount'            => $booking2->total_amount * 0.08,
                        'status'            => 'settled',
                        'captured_at'       => Carbon::now()->subDays(2),
                        'settled_at'        => Carbon::now()->subDay(),
                        'refunded_at'       => null,
                        'external_reference'=> 'REF-'.uniqid(),
                        'notes'             => 'Commission 8% sur BK-1002',
                        'metadata'          => json_encode(['source' => 'seed']),
                    ]
                );
            }
        });
    }
}
