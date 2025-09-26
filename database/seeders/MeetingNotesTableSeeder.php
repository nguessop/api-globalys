<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\User;

class MeetingNotesTableSeeder extends Seeder
{
    public function run(): void
    {
        $meeting = Meeting::first();
        if (!$meeting) return;

        $author = User::where('email','provider@example.test')->first();

        MeetingNote::firstOrCreate(
            [
                'meeting_id' => $meeting->id,
                'body'       => 'Points à valider : délais, paiement, responsabilités.',
            ],
            [
                'author_id'  => $author?->id,
                'visibility' => 'internal',
                'meta'       => ['tags' => ['todo','contrat']],
            ]
        );

        $this->command->info("Meeting note seeded for meeting #{$meeting->id}");
    }
}
