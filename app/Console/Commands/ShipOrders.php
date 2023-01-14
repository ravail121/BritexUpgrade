<?php

namespace App\Console\Commands;

use App\Model\Order;
use Illuminate\Console\Command;
use App\Http\Controllers\Api\V1\CronJobs\OrderController;

class ShipOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:ship {order?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ship orders';

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
        $orderController = new OrderController();
	    $orderId = $this->argument('order');
		if($orderId){
			$orderController->order($orderId);
		} else {
			$orders = Order::where('status', '1')->with(
				'subscriptions',
				'standAloneDevices',
				'standAloneSims',
				'customer',
				'invoice.invoiceItem',
				'payLog')->whereHas('subscriptions', function($subscription) {
				$subscription->where([['status', 'shipping'], ['sent_to_readycloud', 0 ], ['sent_to_shipping_easy', 0]]);
			})->orWhereHas('standAloneDevices', function($standAloneDevice) {
				$standAloneDevice->where([['status', 'shipping'], ['processed', 0 ]]);
			})->orWhereHas('standAloneSims', function($standAloneSim) {
				$standAloneSim->where([['status', 'shipping'], ['processed', 0 ]]);
			})->with('company')->get();
			$bar = $this->output->createProgressBar(count($orders));
			foreach($orders as $order){
				$this->info(' Starting for ' . $order->id);
				$orderController->order($order->id);
				$bar->advance();
			}
			$bar->finish();
		}
	}
}
