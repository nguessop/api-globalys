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
             | 1) Comptes de démo
             ------------------------------------------------ */
            $provider = User::where('email', 'prestataire@globalys.com')->first();
            $company  = User::where('email', 'entreprise@globalys.com')->first();

            /* ------------------------------------------------
             | 2) PRESTATAIRE (historique + actif)
             ------------------------------------------------ */
            if ($provider) {
                // Historique (trial terminé)
                Subscription::updateOrCreate(
                    [
                        'user_id'   => $provider->id,
                        'plan_code' => 'BASIC-TRIAL',
                        'status'    => 'cancelled',
                    ],
                    [
                        'plan_name'         => 'Basic Trial',
                        'price'             => 0,
                        'currency'          => 'XAF',
                        'start_date'        => Carbon::now()->subMonths(3)->startOfDay(),
                        'end_date'          => Carbon::now()->subMonths(2)->endOfMonth(),
                        'auto_renew'        => false,
                        'payment_method'    => null,
                        'payment_reference' => null,
                        'commission_type'   => 'percent',
                        'commission_rate'   => 12.00,
                        'commission_fixed'  => null,
                        'commission_notes'  => 'Trial terminé - seed',
                        'detail' => [
                            'period'      => '/mois',
                            'title'       => 'Basic Trial',
                            'subtitle'    => 'Essai gratuit 30 jours',
                            'old_price'   => null,
                            'popular'     => false,
                            'color'       => 'blue',
                            'badge'       => null,
                            'bullets'     => [
                                "Jusqu'à 5 services",
                                "10 réservations/mois",
                                "Support email",
                            ],
                            'limitations' => [
                                'Commission 12%',
                                'Pas de personnalisation',
                            ],
                        ],
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
                        'plan_name'         => 'Pro Gold',
                        'price'             => 25000.00,
                        'currency'          => 'XAF',
                        'start_date'        => Carbon::now()->startOfMonth(),
                        'end_date'          => null,
                        'auto_renew'        => true,
                        'payment_method'    => null,
                        'payment_reference' => null,
                        'commission_type'   => 'percent',
                        'commission_rate'   => 10.00,
                        'commission_fixed'  => null,
                        'commission_notes'  => 'Plan Gold - seed',
                        'detail' => [
                            'period'      => '/mois',
                            'title'       => 'Gold',
                            'subtitle'    => 'Pour les entreprises ambitieuses',
                            'old_price'   => 30000,
                            'popular'     => true,
                            'color'       => 'gold',
                            'badge'       => 'Plus populaire',
                            'bullets'     => [
                                'Services illimités',
                                'Réservations illimitées',
                                'Support 24/7',
                                'Analytics avancés',
                                'Multi-utilisateurs',
                                'Personnalisation avancée',
                            ],
                            'limitations' => [
                                'Commission 10%',
                            ],
                        ],
                    ]
                );

                if ($provider->subscription_id !== $providerActive->id) {
                    $provider->subscription_id = $providerActive->id;
                    $provider->save();
                }
            } else {
                $warn('⚠️ Prestataire de démo introuvable (prestataire@globalys.com)');
            }

            /* ------------------------------------------------
             | 3) ENTREPRISE (historique + actif)
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
                        'plan_name'         => 'Business Trial',
                        'price'             => 0,
                        'currency'          => 'XAF',
                        'start_date'        => Carbon::now()->subMonths(6)->startOfDay(),
                        'end_date'          => Carbon::now()->subMonths(5)->endOfMonth(),
                        'auto_renew'        => false,
                        'payment_method'    => null,
                        'payment_reference' => null,
                        'commission_type'   => 'percent',
                        'commission_rate'   => 12.50,
                        'commission_fixed'  => null,
                        'commission_notes'  => 'Trial Entreprise terminé - seed',
                        'detail' => [
                            'period'      => '/mois',
                            'title'       => 'Business Trial',
                            'subtitle'    => 'Essai gratuit 30 jours',
                            'old_price'   => null,
                            'popular'     => false,
                            'color'       => 'blue',
                            'badge'       => null,
                            'bullets'     => [
                                "Jusqu'à 5 services",
                                'Support email',
                            ],
                            'limitations' => [
                                'Commission 12.5%',
                            ],
                        ],
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
                        'plan_name'         => 'Business Silver',
                        'price'             => 45000.00,
                        'currency'          => 'XAF',
                        'start_date'        => Carbon::now()->startOfMonth(),
                        'end_date'          => null,
                        'auto_renew'        => true,
                        'payment_method'    => null,
                        'payment_reference' => null,
                        'commission_type'   => 'percent',
                        'commission_rate'   => 8.00,
                        'commission_fixed'  => null,
                        'commission_notes'  => 'Plan Silver - seed',
                        'detail' => [
                            'period'      => '/mois',
                            'title'       => 'Silver',
                            'subtitle'    => 'Idéal pour petites équipes',
                            'old_price'   => 55000,
                            'popular'     => false,
                            'color'       => 'purple',
                            'badge'       => null,
                            'bullets'     => [
                                'Jusqu’à 30 services',
                                '200 réservations/mois',
                                'Support prioritaire',
                                'Intégration calendrier',
                                'Pages personnalisables',
                            ],
                            'limitations' => [
                                'Commission 8%',
                            ],
                        ],
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
                        'plan_name'         => 'Basic Free',
                        'price'             => 0,
                        'currency'          => 'XAF',
                        'start_date'        => Carbon::now()->startOfDay(),
                        'end_date'          => null,
                        'auto_renew'        => true,
                        'payment_method'    => null,
                        'payment_reference' => null,
                        'commission_type'   => 'percent',
                        'commission_rate'   => 12.00,
                        'commission_fixed'  => null,
                        'commission_notes'  => 'Filet de sécurité - plan gratuit',
                        'detail' => [
                            'period'      => '/mois',
                            'title'       => 'Starter',
                            'subtitle'    => 'Parfait pour débuter',
                            'old_price'   => null,
                            'popular'     => false,
                            'color'       => 'blue',
                            'badge'       => null,
                            'bullets'     => [
                                "Jusqu'à 5 services",
                                '10 réservations/mois',
                                'Support email',
                                'Tableau de bord basique',
                                'Paiements sécurisés',
                            ],
                            'limitations' => [
                                'Commission 12%',
                                'Pas de personnalisation',
                            ],
                        ],
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
