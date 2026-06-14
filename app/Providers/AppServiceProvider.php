<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
     */
    public function boot(): void
    {
        //
        view()->share('actual_url', asset('../assets/'));
        config(['app.actual_url' => asset('../assets/')]);

        view()->share('img_path', asset('../assets/uploads/'));
        config(['app.img_path' => asset('../assets/uploads/')]);


        view()->share('pdf_img', asset('../assets/uploads/pdf_logo/logo.jpg'));
        config(['app.pdf_img' => asset('../assets/uploads/pdf_logo/logo.jpg')]);



        view()->share('pusher_key', '713b914b10219f63d205');
        config(['app.pusher_key' => '713b914b10219f63d205']);
    }
}
