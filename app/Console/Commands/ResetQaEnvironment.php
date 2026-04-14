<?php

namespace App\Console\Commands;

use Database\Seeders\QaEnvironmentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetQaEnvironment extends Command
{
    protected $signature = 'ged:reset-qa
        {--force : Obligatoire en production (efface toute la base)}';

    protected $description = 'migrate:fresh + rôles/permissions + structure et comptes QA (base propre pour tests)';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('En production, ajoutez --force pour confirmer l’effacement complet de la base.');

            return self::FAILURE;
        }

        if ($this->input->isInteractive()
            && ! $this->confirm('Cette commande exécute migrate:fresh et supprime toutes les données. Continuer ?', ! app()->environment('production'))) {
            $this->warn('Annulé.');

            return self::SUCCESS;
        }

        $this->info('migrate:fresh…');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->output->write(Artisan::output());

        $this->info('Rôles & permissions…');
        Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        $this->output->write(Artisan::output());

        $this->info('Données QA…');
        Artisan::call('db:seed', ['--class' => QaEnvironmentSeeder::class, '--force' => true]);
        $this->output->write(Artisan::output());

        $this->newLine();
        $this->info('Terminé. Comptes (mot de passe: '.QaEnvironmentSeeder::DEFAULT_PASSWORD.'):');
        $this->table(
            ['Email', 'Rôle'],
            [
                ['master@qa.locaged.test', 'master'],
                ['direction@qa.locaged.test', 'Direction'],
                ['it@qa.locaged.test', 'IT Admin'],
                ['chef.pole@qa.locaged.test', 'Chef de Pôle'],
                ['approbateur@qa.locaged.test', 'Approbateur'],
                ['agent@qa.locaged.test', 'Agent'],
            ]
        );
        $this->warn('À utiliser uniquement sur un environnement de test.');

        return self::SUCCESS;
    }
}
