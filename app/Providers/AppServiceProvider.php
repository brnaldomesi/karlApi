<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
//        header("Access-Control-Allow-Origin: *");
//        header("Access-Control-Allow-Method: POST, GET, OPTIONS");
//        header('Access-Control-Allow-Headers', 'X-Requested-With');
    	if ($this->app->environment() == 'local') {
	        //$this->app->register('Wn\Generators\CommandsServiceProvider');
	    }
    }
}
