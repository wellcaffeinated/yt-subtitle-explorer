<?php

return function($app){
    
    $test = $app['ytse.root'] . '/blarg.txt';

    if(is_dir($app['ytse.root']){

        touch($test);
    }
};