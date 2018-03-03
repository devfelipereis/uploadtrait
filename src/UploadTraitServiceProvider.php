<?php

namespace DevFelipeReis\UploadTrait;

use Illuminate\Support\ServiceProvider;

class UploadTraitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__.'/route.php');
    }
}
