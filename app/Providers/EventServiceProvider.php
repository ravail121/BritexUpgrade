<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [

        'App\Events\BusinessVerificationCreated' => [
            'App\Listeners\SendEmail',
        ],

        'App\Events\BusinessVerificationApproved' => [
            'App\Listeners\SendApprovalEmail',
        ],

        'App\Events\InvoiceGenerated' => [
            'App\Listeners\SendEmailWithInvoice',
        ],

        'App\Events\MonthlyInvoice' => [
            'App\Listeners\SendMonthlyInvoiceMail',
        ],

        'App\Events\ForgotPassword' => [
            'App\Listeners\SendEmailWithPasswordHash',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
