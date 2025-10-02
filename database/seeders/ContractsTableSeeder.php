<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{ Contract, ContractTemplate, Meeting, User };

class ContractsTableSeeder extends Seeder
{
    public function run(): void
    {
        $provider = User::where('email','prestataire1@globalys.com')->firstOrFail();
        $client   = User::where('email','client1@globalys.com')->firstOrFail();
        $meeting  = Meeting::first();
        $tpl      = ContractTemplate::first();

        $contract = Contract::firstOrCreate(
            [
                'provider_id' => $provider->id,
                'client_id'   => $client->id,
                'status'      => Contract::STATUS_DRAFT,
            ],
            [
                'template_id' => $tpl?->id,
                'meeting_id'  => $meeting?->id,
                'title'       => 'Contrat de prestation — Projet Alpha',
                'body'        => '<p>Version initiale du contrat…</p>',
                'variables'   => ['price' => 100000, 'deliverables' => 'Rapport + Support'],
                'currency'    => 'XOF',
                'amount_subtotal' => 100000,
                'amount_tax'      => 0,
            ]
        );

        // Envoi à la signature
        $contract->sendForSignature($provider);

        $this->command->info("Contract seeded: #{$contract->id}");
    }
}
