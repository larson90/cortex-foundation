<?php

declare(strict_types=1);

Route::domain(domain())->group(function () {
    Route::name('adminarea.')
         ->namespace('Cortex\Foundation\Http\Controllers\Adminarea')
         ->middleware(['web', 'nohttpcache', 'can:access-adminarea'])
         ->prefix(config('cortex.foundation.route.locale_prefix') ? '{locale}/'.config('cortex.foundation.route.prefix.adminarea') : config('cortex.foundation.route.prefix.adminarea'))->group(function () {

            // Adminarea Home route
             Route::get('/')->name('home')->uses('HomeController@index');
         });

    Route::name('frontarea.')
         ->middleware(['web', 'nohttpcache'])
         ->namespace('Cortex\Foundation\Http\Controllers\Frontarea')
         ->prefix(config('cortex.foundation.route.locale_prefix') ? '{locale}/'.config('cortex.foundation.route.prefix.frontarea') : config('cortex.foundation.route.prefix.frontarea'))->group(function () {

            // Homepage Routes
             Route::get('/')->name('home')->uses('HomeController@index');
         });
});
