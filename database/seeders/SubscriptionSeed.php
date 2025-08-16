<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionSeed extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Helper console
            $warn = function (string $msg) {
                if (isset($this->command)) {
                    $this->command->warn($msg);
                }
            };

            /* ------------------------------------------------
             | 1) Récupérer quelques comptes de démo (si présents)
             ------------------------------------------------ */
            $provider = User::where('email', 'prestataire@globalys.com')->first();
            $company  = User::where('email', 'entreprise@globalys.com')->first();

            /* ------------------------------------------------
             | 2) Seed pour le PRESTATAIRE (historique + actif)
             ------------------------------------------------ */
            if ($provider) {
                // Historique (ex: trial terminé → cancelled)
                Subscription::updateOrCreate(
                    [
                        'user_id'   => $provider->id,
                        'plan_code' => 'BASIC-TRIAL',
                        'status'    => 'cancelled',
                    ],
                    [
                        'plan_name'        => 'Basic Trial',
                        'price'            => 0,
                        'currency'         => 'XAF',
                        'start_date'       => Carbon::now()->subMonths(3)->startOfDay(),
                        'end_date'         => Carbon::now()->subMonths(2)->endOfMonth(),
                        'auto_renew'       => false,
                        'payment_method'   => null,
                        'payment_reference'=> null,
                        'commission_type'  => 'percent',
                        'commission_rate'  => 12.00,     // decimal(5,2)
                        'commission_fixed' => null,      // si percent, laisser null
                        'commission_notes' => 'Trial terminé - seed',
                    ]
                );

                // Actif
                $providerActive = Subscription::updateOrCreate(
                    [
                        'user_id'   => $provider->id,
                        'plan_code' => 'PRO-GOLD',
                        'status'    => 'active',
                    ],
                    [
                        'plan_name'        => 'Pro Gold',
                        'price'            => 25000.00,  // decimal(10,2)
                        'currency'         => 'XAF',
                        'start_date'       => Carbon::now()->startOfMonth(),
                        'end_date'         => null,       // reconduction tacite
                        'auto_renew'       => true,
                        'payment_method'   => null,
                        'payment_reference'=> null,
                        'commission_type'  => 'percent',
                        'commission_rate'  => 10.00,      // 10%
                        'commission_fixed' => null,
                        'commission_notes' => 'Plan Gold - seed',
                    ]
                );

                // Pointer users.subscription_id vers l’actif (si la colonne existe)
                if ($provider->subscription_id !== $providerActive->id) {
                    $provider->subscription_id = $providerActive->id;
                    $provider->save();
                }
            } else {
                $warn('⚠️ Prestataire de démo introuvable (prestataire@globalys.com)');
            }

            /* ------------------------------------------------
             | 3) Seed pour l’ENTREPRISE (historique + actif)
             ------------------------------------------------ */
            if ($company) {
                // Historique (trial annulé)
                Subscription::updateOrCreate(
                    [
                        'user_id'   => $company->id,
                        'plan_code' => 'BIZ-TRIAL',
                        'status'    => 'cancelled',
                    ],
                    [
                        'plan_name'        => 'Business Trial',
                        'price'            => 0,
                        'currency'         => 'XAF',
                        'start_date'       => Carbon::now()->subMonths(6)->startOfDay(),
                        'end_date'         => Carbon::now()->subMonths(5)->endOfMonth(),
                        'auto_renew'       => false,
                        'payment_method'   => null,
                        'payment_reference'=> null,
                        'commission_type'  => 'percent',
                        'commission_rate'  => 12.50,
                        'commission_fixed' => null,
                        'commission_notes' => 'Trial Entreprise terminé - seed',
                    ]
                );

                // Actif
                $companyActive = Subscription::updateOrCreate(
                    [
                        'user_id'   => $company->id,
                        'plan_code' => 'BIZ-SILVER',
                        'status'    => 'active',
                    ],
                    [
                        'plan_name'        => 'Business Silver',
                        'price'            => 45000.00,
                        'currency'         => 'XAF',
                        'start_date'       => Carbon::now()->startOfMonth(),
                        'end_date'         => null,
                        'auto_renew'       => true,
                        'payment_method'   => null,
                        'payment_reference'=> null,
                        'commission_type'  => 'percent',
                        'commission_rate'  => 8.00,
                        'commission_fixed' => null,
                        'commission_notes' => 'Plan Silver - seed',
                    ]
                );

                if ($company->subscription_id !== $companyActive->id) {
                    $company->subscription_id = $companyActive->id;
                    $company->save();
                }
            } else {
                $warn('⚠️ Entreprise de démo introuvable (entreprise@globalys.com)');
            }

            /* ------------------------------------------------
             | 4) Filet de sécurité (plan gratuit actif)
             |    Pour tout prestataire/entreprise sans abonnement ACTIF
             |    et sans subscription_id.
             ------------------------------------------------ */
            $targets = User::query()
                ->where(function ($w) {
                    $w->where('user_type', 'prestataire')
                        ->orWhere('account_type', 'entreprise');
                })
                ->whereDoesntHave('subscriptions', function ($q) {
                    $q->where('status', 'active');
                })
                ->get();

            foreach ($targets as $u) {
                $active = Subscription::updateOrCreate(
                    [
                        'user_id'   => $u->id,
                        'plan_code' => 'BASIC-FREE',
                        'status'    => 'active',
                    ],
                    [
                        'plan_name'        => 'Basic Free',
                        'price'            => 0,
                        'currency'         => 'XAF',
                        'start_date'       => Carbon::now()->startOfDay(),
                        'end_date'         => null,
                        'auto_renew'       => true,
                        'payment_method'   => null,
                        'payment_reference'=> null,
                        'commission_type'  => 'percent',
                        'commission_rate'  => 12.00,
                        'commission_fixed' => null,
                        'commission_notes' => 'Filet de sécurité - plan gratuit',
                    ]
                );

                if ($u->subscription_id !== $active->id) {
                    $u->subscription_id = $active->id;
                    $u->save();
                }
            }
        });
    }
}
