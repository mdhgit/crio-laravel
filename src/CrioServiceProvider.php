<?php

namespace Mydiabeteshome\Crio;

use Illuminate\Support\ServiceProvider;

class CrioServiceProvider extends ServiceProvider{
    
    public function boot(){
        //dd('It works');
    }

    public function register(){
        $this->app->singleton(Crio::class,function(){
            return new Crio('test','test');
        });
    }
}