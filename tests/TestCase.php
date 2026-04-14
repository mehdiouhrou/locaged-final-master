<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Garantit APP_ENV=testing avant le chargement de .env (utile sans config cachée).
     */
    public function createApplication(): Application
    {
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Après `php artisan config:cache`, bootstrap/cache/config.php fige souvent env=local.
         * VerifyCsrfToken n'exempte alors plus les tests → 419 sur les POST.
         * On retire le middleware uniquement dans ce cas (sinon on garde le chemin Laravel standard).
         */
        if (! $this->app->runningUnitTests()) {
            $this->withoutMiddleware(ValidateCsrfToken::class);
        }
    }
}
