<?php

namespace App\Providers;

use App\Http\Controllers\BotmanController;
use App\Http\Controllers\TelegramController;
use App\Logic\Integrations\ChatGPT3Client;
use App\Logic\Manager;
use App\Logic\Repositories\Cache\RedisCache;
use App\Logic\Repositories\DB\UserRepositoryEloquent;
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
        $this->app->when([BotmanController::class, TelegramController::class])
            ->needs(Manager::class)
            ->give(function (){
                return new Manager(new ChatGPT3Client(), new UserRepositoryEloquent(), new RedisCache());
            });
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
