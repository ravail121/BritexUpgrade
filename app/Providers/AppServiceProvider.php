<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Notifications\DynamicSmtpMailChannel;
use Illuminate\Notifications\Channels\MailChannel;

/**
 * Class AppServiceProvider
 *
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Blade::directive('date', function ($date) {
            return "<?php echo date('m/d/Y', strtotime($date)); ?>";
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //$this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
	    $this->app->bind(
		    MailChannel::class,
		    DynamicSmtpMailChannel::class
	    );
    }
}
