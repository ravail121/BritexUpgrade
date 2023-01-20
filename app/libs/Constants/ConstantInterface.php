<?php
namespace App\libs\Constants;

interface ConstantInterface
{
    // USED IN PaymentController
    const TRAN_INVOICE     = "BriteX";
    const TRAN_AMOUNT      = '1.00';
    const TRAN_BILLCOUNTRY = "USA";
    const TRAN_FALSE       = false;
    const TRAN_TRUE        = true;


    // USED IN MonthlyInvoiceController

    const TAX_TRUE         = 1;
    const TAX_FALSE        = 0;

    const INVOICE_ITEM_TYPES = [
        'plan_charges'     => 1,
        'feature_charges'  => 2,
        'one_time_charges' => 3,
        'usage_charges'    => 4,
        'regulatory_fee'   => 5,
        'coupon'           => 6,
        'taxes'            => 7,
        'manual'           => 8,
        'payment'          => 9,
	    'refund'           => 10,
	    'surcharge'        => 11,
    ];

    const INVOICE_ITEM_PRODUCT_TYPES = [
        'plan'   => 'plan',
        'addon'  => 'addon',
        'device' => 'device',
        'sim'    => 'sim',
    ];

    const STATUS = [
        'closed_and_unpaid' => 0,
        'pending_payment'   => 1,
        'closed_and_paid'   => 2,
    ];

    const INVOICE_TYPES = [
        'monthly'  => 1,
        'one_time' => 2,
    ];

    /**
     * Used in both Sim and Device models
     */
    const SHOW_COLUMN_VALUES = [
        'not-visible'             => 0,
        'visible-and-orderable'   => 1,
        'visible-and-unorderable' => 2,
	    'not-visible-at-all'      => 3
    ];
}