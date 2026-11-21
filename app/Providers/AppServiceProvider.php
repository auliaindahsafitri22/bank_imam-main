<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Ringkasan tampil di halaman awal dashboard (di atas widget statistik bawaan).
        View::composer('crudbooster::statistic_builder.show', function ($view) {
            $r = request()->route();
            if ($r && $r->getActionMethod() === 'getShowDashboard') {
                $view->with('ringkasanDashboard', \App\Support\RingkasanDashboard::html());
            }
        });

        // Pengaturan ukuran huruf/ikon/kontras untuk SELURUH halaman CMS (CrudBooster).
        View::composer('crudbooster::admin_template', function ($view) {
            $f = $view->getFactory();
            $f->startPush('head');
            echo '<script>window.A11Y_URL = ' . json_encode(url(config('crudbooster.ADMIN_PATH', 'admin') . '/pengaturan_tampilan')) . ';</script>'
               . '<link rel="stylesheet" href="' . asset('css/aksesibilitas.css') . '">'
               . '<script src="' . asset('js/aksesibilitas.js') . '"></script>';
            $f->stopPush();
        });
    }
}
