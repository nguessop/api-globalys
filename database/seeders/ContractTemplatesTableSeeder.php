<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractTemplate;
use App\Models\User;

class ContractTemplatesTableSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email','prestataire@globalys.com')->first();

        ContractTemplate::firstOrCreate(
            ['code' => 'standard-presta', 'version' => 1, 'locale' => 'fr'],
            [
                'title' => 'Contrat de prestation standard',
                'description' => 'Modèle générique pour prestation de service.',
                'body' => '<h1>Contrat</h1><p>Conditions générales...</p>',
                'variables' => ['price' => 'number', 'deliverables' => 'string'],
                'visibility' => 'both',
                'require_provider_signature' => true,
                'require_client_signature' => true,
                'created_by' => $author?->id,
                'is_active' => true,
            ]
        );

        $this->command->info('Contract template seeded.');
    }
}
