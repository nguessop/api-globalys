<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{ Contract, ContractEvent, User };

class ContractEventsTableSeeder extends Seeder
{
    public function run(): void
    {
        $contract = Contract::first();
        if (!$contract) return;

        $actor = User::where('email','prestataire@globalys.com')->first();

        // Quelques événements supplémentaires d’exemple
        ContractEvent::firstOrCreate(
            ['contract_id' => $contract->id, 'type' => 'viewed'],
            ['actor_user_id' => $actor?->id, 'channel' => 'web', 'message' => 'Contrat consulté par le prestataire.']
        );

        ContractEvent::firstOrCreate(
            ['contract_id' => $contract->id, 'type' => 'reminder_sent'],
            ['actor_user_id' => $actor?->id, 'channel' => 'system', 'message' => 'Relance envoyée au client.']
        );

        $this->command->info("Contract events seeded for contract #{$contract->id}");
    }
}
