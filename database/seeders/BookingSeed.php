<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\{ User, ServiceOffering, Booking };

class BookingSeed extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('bookings')) {
            $this->command?->warn("Table bookings absente → rien à faire.");
            return;
        }

        $target = 30; // on veut 30 bookings au total

        // 1) S'assurer d'avoir des offres (au moins 5 pour varier)
        $offerCount = ServiceOffering::count();
        if ($offerCount < 5) {
            $this->call(\Database\Seeders\ServiceOfferingSeed::class);
        }

        // Rechargement après éventuel seed
        $offerings = ServiceOffering::query()->inRandomOrder()->get();
        if ($offerings->isEmpty()) {
            $this->command?->warn("Impossible de récupérer des service_offerings. Booking sauté.");
            return;
        }

        // 2) Déterminer combien créer (on complète jusqu'au target)
        $existing = Booking::count();
        $toCreate = max(0, $target - $existing);
        if ($toCreate === 0) {
            $this->command?->info("Déjà $existing bookings (objectif=$target). Rien à créer.");
            return;
        }

        $now = Carbon::now();

        // 3) Templates variés (comme ton code)
        $templates = [
            [
                'status'         => Booking::STATUS_PENDING,
                'payment_status' => Booking::PAYMENT_UNPAID,
                'start_at'       => fn() => $now->copy()->addDay()->setTime(9, 0),
                'end_at'         => fn() => $now->copy()->addDay()->setTime(10, 0),
                'tax_rate'       => 0,
                'city'           => 'Douala',
                'address'        => 'Bonapriso',
                'notes_client'   => 'Besoin de devis',
            ],
            [
                'status'         => Booking::STATUS_CONFIRMED,
                'payment_status' => Booking::PAYMENT_PAID,
                'start_at'       => fn() => $now->copy()->addDays(2)->setTime(14, 0),
                'end_at'         => fn() => $now->copy()->addDays(2)->setTime(16, 0),
                'tax_rate'       => 19.25,
                'city'           => 'Yaoundé',
                'address'        => 'Bastos',
                'notes_client'   => 'Urgent',
            ],
            [
                'status'         => Booking::STATUS_IN_PROGRESS,
                'payment_status' => Booking::PAYMENT_PARTIAL,
                'start_at'       => fn() => $now->copy()->subMinutes(30),
                'end_at'         => fn() => $now->copy()->addMinutes(30),
                'tax_rate'       => 0,
                'city'           => 'Douala',
                'address'        => 'Akwa',
            ],
            [
                'status'         => Booking::STATUS_COMPLETED,
                'payment_status' => Booking::PAYMENT_PAID,
                'start_at'       => fn() => $now->copy()->subDay()->setTime(10, 0),
                'end_at'         => fn() => $now->copy()->subDay()->setTime(12, 0),
                'tax_rate'       => 19.25,
                'city'           => 'Douala',
                'address'        => 'Bonamoussadi',
            ],
        ];

        // 4) Création répartie sur plusieurs offres
        $created = 0;
        Booking::unguard();

        for ($i = 0; $i < $toCreate; $i++) {
            // Choisir une offre différente à chaque fois (cycle si < $toCreate)
            $offering = $offerings[$i % $offerings->count()];

            // Provider depuis l’offre (ou fallback)
            $provider = User::find($offering->provider_id)
                ?? User::query()->where('user_type', 'prestataire')->first()
                ?? User::query()->where('account_type', 'entreprise')->first()
                ?? User::factory()->create([
                    'first_name' => 'Demo', 'last_name' => 'Provider',
                    'email' => 'provider+'.uniqid()."@example.test",
                    'account_type' => 'entreprise',
                    'user_type' => 'prestataire',
                ]);

            if (!$offering->provider_id) {
                $offering->provider_id = $provider->id;
                $offering->save();
            }

            // Client distinct du provider
            $client = User::query()
                ->where('id', '!=', $provider->id)
                ->where(function ($q) {
                    $q->where('user_type', 'client')
                        ->orWhere('account_type', 'particulier');
                })
                ->inRandomOrder()
                ->first();

            if (!$client) {
                $client = User::factory()->create([
                    'first_name' => 'Client', 'last_name' => 'Demo',
                    'email' => 'client+'.uniqid()."@example.test",
                    'account_type' => 'particulier',
                    'user_type' => 'client',
                ]);
            }

            // Prix & devise depuis l’offre (fallback si schéma différent)
            $unitPrice = $offering->price_amount
                ?? $offering->unit_price
                ?? $offering->price
                ?? 10000;

            $currency  = $offering->currency
                ?? $offering->price_currency
                ?? 'XAF';

            // Template à appliquer
            $tpl = $templates[$i % count($templates)];

            try {
                Booking::create([
                    'service_offering_id' => $offering->id,
                    'client_id'           => $client->id,
                    'provider_id'         => $provider->id,

                    'quantity'            => 1 + ($i % 3),
                    'unit_price'          => $unitPrice,
                    'currency'            => $currency,

                    'tax_rate'            => $tpl['tax_rate'],
                    'status'              => $tpl['status'],
                    'payment_status'      => $tpl['payment_status'],

                    'start_at'            => ($tpl['start_at'])(),
                    'end_at'              => ($tpl['end_at'])(),
                    'city'                => $tpl['city'],
                    'address'             => $tpl['address'],
                    'notes_client'        => $tpl['notes_client'] ?? null,
                    'notes_provider'      => $tpl['notes_provider'] ?? null,
                    // subtotal/tax/total calculés par le modèle (boot creating) si présent
                ]);

                $created++;
            } catch (\Throwable $e) {
                $this->command?->warn("Échec création booking #".($i+1)." pour offering {$offering->id} : ".$e->getMessage());
            }
        }

        Booking::reguard();
        $this->command?->info("✅ Bookings créés: +{$created} (total=".Booking::count().", objectif=$target).");
    }
}
