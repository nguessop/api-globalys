<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Meeting;

class MeetingsTableSeeder extends Seeder
{
    public function run(): void
    {
        // On prend par exemple le prestataire particulier et le client particulier
        $provider = User::where('email','prestataire1@globalys.com')->firstOrFail();
        $client   = User::where('email','client1@globalys.com')->firstOrFail();

        $meeting = Meeting::firstOrCreate(
            [
                'provider_id' => $provider->id,
                'client_id'   => $client->id,
                'status'      => 'proposed',
            ],
            [
                'subject'          => 'Préparation de contrat',
                'purpose'          => 'pre_contract',
                'location_type'    => 'online',
                'location'         => 'https://meet.globalys.com/abc',
                'timezone'         => 'Africa/Douala',
                'duration_minutes' => 45,
            ]
        );

        $this->command->info("Meeting seeded: #{$meeting->id} entre {$provider->email} et {$client->email}");
    }
}
