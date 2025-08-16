<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        $this->call(\Database\Seeders\CategorySeed::class);
        $this->call(\Database\Seeders\SubCategorySeed::class);

        $this->call(\Database\Seeders\RoleSeed::class);
        $this->call(\Database\Seeders\UserSeed::class);
        $this->call(\Database\Seeders\ServiceOfferingSeed::class);
        $this->call(\Database\Seeders\SubscriptionSeed::class);
        $this->call(\Database\Seeders\BookingSeed::class);
        $this->call(\Database\Seeders\PaymentSeed::class);
        $this->call(\Database\Seeders\ReviewSeed::class);
        $this->call(\Database\Seeders\AvailabilitySlotSeed::class);
    }
}
