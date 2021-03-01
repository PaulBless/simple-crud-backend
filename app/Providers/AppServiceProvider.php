<?php

namespace App\Providers;

use App\Services\Contracts\ProductContract;
use App\Services\Contracts\UserContract;
use App\Services\ProductService;
use App\Services\UserService;
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
        $this->app->bind(UserContract::class, UserService::class);
        $this->app->bind(ProductContract::class, ProductService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
