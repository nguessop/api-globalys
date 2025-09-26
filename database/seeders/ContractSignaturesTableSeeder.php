<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{ Contract, ContractPartie, ContractSignature, User };

class ContractSignaturesTableSeeder extends Seeder
{
    public function run(): void
    {
        $contract = Contract::first();
        if (!$contract) return;

        // Faire signer le prestataire pour obtenir un état "partially_signed"
        $provPartie = $contract->providerParty();
        if ($provPartie && !$provPartie->isSigned()) {
            $actor = User::where('email','prestataire@globalys.com')->first();
            $provPartie->sign($actor, '127.0.0.1', 'Seeder/1.0', ContractSignature::METHOD_CLICK);
        }

        // Laisse la partie client en pending pour l'exemple
        $contract->refreshSignatureStatus();

        $this->command->info("Contract signatures seeded (provider signed).");
    }
}
