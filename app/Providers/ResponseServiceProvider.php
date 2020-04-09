<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register the application's response macros.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('success', function ($message,$data = [],$code = 200) {
            return Response::json(['status' => true,'message' => $message, 'data' => $data],$code);
        });

        Response::macro('fail', function ($message,$code = 500,$errors=[]) {
            return Response::json(['status' => false,'message' => $message,'error' => $errors],$code);
        });

    }
}
    
     

