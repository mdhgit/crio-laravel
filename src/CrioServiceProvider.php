<?php

namespace Mydiabeteshome\Crio;

use Illuminate\Support\ServiceProvider;

class CrioServiceProvider extends ServiceProvider{
    
    public function boot(){

    }

    public function register(){
        $this->app->singleton(Crio::class,function(){
            return new Crio('client_id','bearer_token');
        });
    }
}