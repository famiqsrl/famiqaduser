<?php
declare(strict_types=1);

namespace Famiq\ActiveDirectoryUser;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para integrar FamiqADUser en Laravel.
 */
class FamiqADUserServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios del paquete.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa el paquete cuando la aplicación se ejecuta en consola.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\ExportConfigCommand::class,
                Commands\GetUserInfoCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/ldap.php' => config_path('ldap.php'),
            ], 'famiqaduser-config');
        }
    }
}
