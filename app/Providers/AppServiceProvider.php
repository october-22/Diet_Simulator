<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator; //対策：送信しようとしている情報は保護されません。

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * ローカル環境ではない場合にのみHTTPSを強制
     */
    public function boot(UrlGenerator $url): void
    {
        if (config('app.env') !== 'local') {
            $url->forceScheme('https');
        }
    }
    
}
