<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Meeting;

class MeetingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $provider = User::where('email','prestataire@globalys.com')->firstOrFail();
        $client   = User::where('email','client@globalys.com')->firstOrFail();

        $meeting = Meeting::firstOrCreate(
            [
                'provider_id' => $provider->id,
                'client_id'   => $client->id,
                'status'      => 'proposed',
            ],
            [
                'subject' => 'Préparation de contrat',
                'purpose' => 'pre_contract',
                'location_type' => 'online',
                'location' => 'https://meet.example.test/abc',
                'timezone' => 'UTC',
                'duration_minutes' => 45,
            ]
        );

        $this->command->info("Meeting seeded: #{$meeting->id}");
    }
}
