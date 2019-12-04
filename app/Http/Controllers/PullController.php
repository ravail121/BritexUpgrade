<?php

namespace App\Http\Controllers;

use SSH;
use Illuminate\Http\Request;

class PullController extends Controller
{
    public function gitPull()
    {
        $command = [
            'cd '.env('PATH_PROJECT').' && git pull',
            'sudo systemctl restart nginx && sudo service apache2 restart && sudo service php7.0-fpm restart && php artisan config:cache && php artisan cache:clear && php artisan config:clear && php artisan view:clear && php artisan route:clear'
        ];

        SSH::run($command, function($line) {
            echo $line.PHP_EOL;
        });
    }
}
