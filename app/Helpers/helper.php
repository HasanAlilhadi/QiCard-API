<?php


use JetBrains\PhpStorm\NoReturn;

if (!function_exists('ddh')) {
#[NoReturn] function ddh(...$var): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        dd($var);
    }
}
