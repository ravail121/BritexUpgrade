<?php

namespace App\Providers;

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

	    'App\Events\InvoiceGenerated' => [
            'App\Listeners\SendEmailWithInvoice',
        ],

	    'App\Events\PaymentFailed' => [
            'App\Listeners\SendEmailforPaymentFailed',
        ],

	    'App\Events\AccountSuspended' => [
            'App\Listeners\SendAccountPastDueMail',
        ],

	    'App\Events\ForgotPassword' => [
            'App\Listeners\SendEmailWithPasswordHash',
        ],

	    'App\Events\AutoPayStatus' => [
            'App\Listeners\SendEmailWithAutoPayStatus',
        ],

	    'App\Events\AutoPayReminder' => [
            'App\Listeners\SendEmailToRemindAutoPay',
        ],

	    'App\Events\InvoiceAutoPaid' => [
            'App\Listeners\SendEmailToRemindInvoiceAutoPaid',
        ],

	    'App\Events\FailToAutoPaidInvoice' => [
            'App\Listeners\SendEmailToRemindFailToAutoPaidInvoice',
        ],

	    'App\Events\SendRefundInvoice' => [
            'App\Listeners\SendEmailForRefund',
        ],

	    'App\Events\SupportEmail' => [
            'App\Listeners\SendSupportEmail'
        ],

	    'App\Events\ShippingNumber' => [
            'App\Listeners\SendMailForShippingNumber'
        ],

	    'App\Events\SubcriptionStatusChanged' => [
            'App\Listeners\SendEmailForChangedSubcriptionStatus'
        ],

	    'App\Events\PortPending' => [
            'App\Listeners\SendPortStatusMail'
        ],

	    'App\Events\UpgradeDowngradeInvoice' => [
            'App\Listeners\SendUpgradeDowngradeInvoice'
        ],

	    'App\Events\AccountUnsuspended' => [
            'App\Listeners\SendAccountUnsuspendedMail',
        ],

	    'App\Events\InvoiceEmail' => [
            'App\Listeners\SendInvoiceMail',
        ],

	    'App\Events\ReportNullSubscriptionStartDate' => [
	        'App\Listeners\SendEmailForNullSubscriptionStartDateCustomerRecord'
        ],

        'App\Events\SendMailData' => [
	        'App\Listeners\SendMailFired'
        ],

	    'App\Events\CreditCardExpirationReminder' => [
		    'App\Listeners\SendEmailForCreditCardExpirationReminder'
	    ],

	    'App\Events\SubscriptionForReactivation' => [
		    'App\Listeners\SendEmailForSubscriptionForReactivation'
	    ],

	    'App\Events\ReportSchedulerStatus' => [
		    'App\Listeners\SendEmailForSchedulerStatus'
	    ],

	    'App\Events\ActivationError' => [
		    'App\Listeners\SendEmailForActivationError'
	    ]
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
