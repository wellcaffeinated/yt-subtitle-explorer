<?php

return function($app){

    $base = __DIR__;
    
    if (is_dir($app['ytse.root'])){

        copy($base.'/app', $app['ytse.root'].'/app');
    }
};