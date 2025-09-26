<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Meeting;
use App\Models\MeetingSlot;
use App\Models\User;
use Illuminate\Support\Carbon;

class MeetingSlotsTableSeeder extends Seeder
{
    public function run(): void
    {
        $meeting = Meeting::first();
        if (!$meeting) return;

        $provider = User::where('email','provider@example.test')->first();

        // Deux créneaux : un accepté (sélectionné), un proposé
        $start1 = Carbon::now()->addDay()->setTime(10, 0);
        $end1   = (clone $start1)->addMinutes($meeting->duration_minutes ?? 30);

        $slot1 = MeetingSlot::firstOrCreate(
            [
                'meeting_id' => $meeting->id,
                'start_at'   => $start1,
                'end_at'     => $end1,
            ],
            [
                'proposed_by' => $provider?->id,
                'timezone'    => 'UTC',
                'location'    => $meeting->location,
                'status'      => 'accepted',
                'notes'       => 'Créneau principal',
            ]
        );

        $start2 = Carbon::now()->addDays(2)->setTime(15, 0);
        $end2   = (clone $start2)->addMinutes($meeting->duration_minutes ?? 30);

        MeetingSlot::firstOrCreate(
            [
                'meeting_id' => $meeting->id,
                'start_at'   => $start2,
                'end_at'     => $end2,
            ],
            [
                'proposed_by' => $provider?->id,
                'timezone'    => 'UTC',
                'location'    => $meeting->location,
                'status'      => 'proposed',
                'notes'       => 'Option alternative',
            ]
        );

        // Lier le slot accepté au meeting
        if (!$meeting->selected_slot_id) {
            $meeting->selected_slot_id = $slot1->id;
            $meeting->status = 'scheduled';
            $meeting->save();
        }

        $this->command->info("Meeting slots seeded for meeting #{$meeting->id}");
    }
}
