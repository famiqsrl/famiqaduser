<?php

namespace Famiq\ActiveDirectoryUser;

use Illuminate\Support\ServiceProvider;

class FamiqADUserServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
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
