<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\User;

class PaymentSeed extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // On prend un booking existant
            $booking1 = Booking::first();
            $booking2 = Booking::skip(1)->first();

            if (!$booking1 || !$booking2) {
                $this->command->warn('⚠️ Pas assez de bookings pour créer des paiements.');
                return;
            }

            // Paiement 1 : mobile money réussi
            Payment::updateOrCreate(
                ['reference' => 'PMT-' . $booking1->code],
                [
                    'booking_id'      => $booking1->id,
                    'client_id'       => $booking1->client_id,
                    'provider_id'     => $booking1->provider_id,
                    'amount'          => $booking1->total_amount,
                    'currency'        => 'XAF',
                    'processor_fee'   => 500,
                    'net_amount'      => $booking1->total_amount - 500,
                    'method'          => 'mobile_money',
                    'gateway'         => 'orange_money',
                    'idempotency_key' => uniqid('pay1_', true),
                    'external_id'     => 'EXT-' . uniqid(),
                    'status'          => 'succeeded',
                    'authorized_at'   => Carbon::now()->subMinutes(10),
                    'captured_at'     => Carbon::now()->subMinutes(5),
                    'payload'         => json_encode(['transaction_id' => 'TRX123456']),
                    'metadata'        => json_encode(['source' => 'seed']),
                ]
            );

            // Paiement 2 : échec par carte bancaire
            Payment::updateOrCreate(
                ['reference' => 'PMT-' . $booking2->code],
                [
                    'booking_id'      => $booking2->id,
                    'client_id'       => $booking2->client_id,
                    'provider_id'     => $booking2->provider_id,
                    'amount'          => $booking2->total_amount,
                    'currency'        => 'XAF',
                    'processor_fee'   => null,
                    'net_amount'      => null,
                    'method'          => 'card',
                    'gateway'         => 'stripe',
                    'idempotency_key' => uniqid('pay2_', true),
                    'external_id'     => 'EXT-' . uniqid(),
                    'status'          => 'failed',
                    'failure_code'    => 'card_declined',
                    'failure_message' => 'Carte refusée par la banque',
                    'payload'         => json_encode(['error' => 'card_declined']),
                    'metadata'        => json_encode(['source' => 'seed']),
                ]
            );
        });
    }
}
