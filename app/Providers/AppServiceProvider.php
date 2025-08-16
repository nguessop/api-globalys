<?php

namespace App\Providers;


use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Configuration pour les environnements de production
        if ($this->app->environment('production')) {
            $this->app->singleton(
                \Illuminate\Contracts\Debug\ExceptionHandler::class,
                \App\Exceptions\Handler::class
            );
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Macros pour les réponses API standardisées
        Response::macro('success', function ($data = null, $message = null, $code = 200) {
            return Response::json([
                'success' => true,
                'data' => $data,
                'message' => $message ?? 'Opération réussie',
            ], $code);
        });

        Response::macro('error', function ($message = null, $errors = null, $code = 400) {
            return Response::json([
                'success' => false,
                'message' => $message ?? 'Une erreur est survenue',
                'errors' => $errors,
            ], $code);
        });

        // Observer pour les logs (exemple avec User)
        \App\Models\User::observe(\App\Observers\UserObserver::class);
    }
}
