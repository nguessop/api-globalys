<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{ Contract, ContractPartie, User };

class ContractPartiesTableSeeder extends Seeder
{
    public function run(): void
    {
        $contract = Contract::first();
        if (!$contract) return;

        $provider = User::where('email','prestataire1@globalys.com')->firstOrFail();
        $client   = User::where('email','client1@globalys.com')->firstOrFail();

        // Prestataire
        ContractPartie::firstOrCreate(
            [
                'contract_id' => $contract->id,
                'role'        => ContractPartie::ROLE_PROVIDER,
            ],
            [
                'user_id' => $provider->id,
                'position' => 1,
                'display_name' => $provider->full_name ?: $provider->company_name,
                'email' => $provider->email,
                'phone' => $provider->phone,
                'company_name' => $provider->company_name,
                'require_signature' => true,
            ]
        );

        // Client
        ContractPartie::firstOrCreate(
            [
                'contract_id' => $contract->id,
                'role'        => ContractPartie::ROLE_CLIENT,
            ],
            [
                'user_id' => $client->id,
                'position' => 2,
                'display_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'require_signature' => true,
            ]
        );

        // (re)crée des signatures pending si besoin
        $contract->ensurePendingSignaturesForRequiredParties();

        $this->command->info("Contract parties seeded for contract #{$contract->id}");
    }
}
