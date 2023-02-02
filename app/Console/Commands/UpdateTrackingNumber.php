<?php

namespace App\Console\Commands;

use App\Model\Order;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Api\V1\CronJobs\OrderDataController;

/**
 * Class UpdateTrackingNumber
 */
class UpdateTrackingNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:tracking {order?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Order Tracking Number';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

	    $orderDataController = new OrderDataController();
		$request = new Request();
	    $orderId = $this->argument('order');
	    if($orderId){
		    $orderDataController->order($orderId, $request);
	    } else {
		    $orders = Order::whereHas('subscriptions', function(Builder $subscription) {
			    $subscription->where([['status', 'shipping'], ['sent_to_readycloud', 1]])->whereNull('tracking_num');

		    })->whereHas('company', function(Builder $company){
			    $company->whereNotNull('readycloud_api_key');

		    })->orWhereHas('standAloneDevices', function(Builder $standAloneDevice) {
			    $standAloneDevice->where([['status', 'shipping'], ['processed', 1]])->whereNull('tracking_num');

		    })->orWhereHas('standAloneSims', function(Builder $standAloneSim) {
			    $standAloneSim->where([['status', 'shipping'], ['processed', 1]])->whereNull('tracking_num');
		    })->with('company')->orderBy('order_num', 'desc')->get();
		    $bar = $this->output->createProgressBar(count($orders));
		    foreach($orders as $order){
			    $this->info(' Starting for ' . $order->id);
			    $orderDataController->order($order->id, $request);
			    $bar->advance();
		    }
		    $bar->finish();
	    }
    }
}
