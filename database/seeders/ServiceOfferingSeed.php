<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\{ User, SubCategory, ServiceOffering };

class ServiceOfferingSeed extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('service_offerings')) {
            $this->command?->warn("Table service_offerings absente → rien à faire.");
            return;
        }

        // Colonnes présentes dans ta base
        $cols = Schema::getColumnListing('service_offerings');
        $has  = fn (string $c) => in_array($c, $cols, true);
        $set  = function(array &$arr, string $k, $v) use ($has) {
            if ($has($k)) $arr[$k] = $v;
        };

        // Un provider (ou création rapide)
        $provider = User::query()->where('account_type', 'entreprise')->first()
            ?? User::query()->first()
            ?? User::factory()->create([
                'first_name'   => 'Demo',
                'last_name'    => 'Provider',
                'email'        => 'provider+'.Str::random(6).'@example.test',
                'account_type' => 'entreprise',
            ]);

        // Une sous-catégorie si présente
        $subCategory = class_exists(SubCategory::class) ? SubCategory::query()->first() : null;

        $created = 0;
        ServiceOffering::unguard();

        for ($i = 1; $i <= 70; $i++) {   // ← objectif 70
            $data = [];

            // Références
            $set($data, 'provider_id', $provider->id);
            if ($subCategory) $set($data, 'sub_category_id', $subCategory->id);

            // Libellés uniques
            $title = "Offre de démo #$i";
            $slug  = 'offre-demo-'.Str::lower(Str::random(10));
            $set($data, 'title', $title);
            $set($data, 'name', $title);
            $set($data, 'slug', $slug);
            $set($data, 'description', "Service de démonstration $i créé par le seeder.");

            // Prix (quelques variations)
            $basePrice = rand(5000, 30000);
            $set($data, 'price_amount', $basePrice);      // schéma price_amount + price_unit
            $set($data, 'price_unit', 'service');
            $set($data, 'price_currency', 'XOF');

            $set($data, 'unit_price', $basePrice);        // schéma unit_price
            $set($data, 'price', $basePrice);             // schéma price

            // Devise / statut / dates
            $set($data, 'currency', 'XOF');
            $set($data, 'status', 'active');
            $set($data, 'published_at', Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23)));

            // Champs fréquents non-nullables
            $set($data, 'capacity', rand(1, 5));
            $set($data, 'coverage_km', rand(0, 20));
            $set($data, 'min_delay_hours', rand(0, 24));
            $set($data, 'max_delay_hours', rand(24, 72));
            $set($data, 'duration_minutes', [30, 45, 60, 90, 120][array_rand([30,45,60,90,120])]);
            $set($data, 'featured', rand(0, 1));
            $set($data, 'is_verified', rand(0, 1));

            // Choix d’unicité : un slug unique force la création
            $unique = $has('slug') ? ['slug' => $slug]
                : ($has('title') ? ['title' => $title]
                    : ($has('name')  ? ['name'  => $title] : ['provider_id' => $provider->id, 'description' => $data['description'] ?? Str::uuid()->toString()]));

            try {
                $offering = ServiceOffering::firstOrCreate($unique, $data);
                $created++;
                $this->command?->info("Service offering #$i prêt (id={$offering->id}).");
            } catch (\Throwable $e) {
                $this->command?->warn("Échec création service_offering #$i : ".$e->getMessage());
            }
        }
        ServiceOffering::reguard();
        $this->command?->info("✅ Seed terminé : {$created} enregistrements demandés (objectif: 70).");
    }
}
