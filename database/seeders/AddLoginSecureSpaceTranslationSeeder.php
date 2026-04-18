<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddLoginSecureSpaceTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update the login page tagline
        UiTranslation::updateOrCreate(
            ['key' => 'auth.ui.secure_space'],
            [
                'en_text' => 'Electronic document management.',
                'fr_text' => 'Gestion électronique de documents.',
                'ar_text' => 'إدارة الوثائق الإلكترونية.',
            ]
        );

        $this->command->info('Login page secure space translation updated successfully.');
    }
}
