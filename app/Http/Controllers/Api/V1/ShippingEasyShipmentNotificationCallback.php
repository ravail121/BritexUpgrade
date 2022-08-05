<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Events\ShippingNumber;
use App\Http\Controllers\Controller;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Events\SubcriptionStatusChanged;

/**
 * Class PortingController
 *
 * @package App\Http\Controllers\Api\V1
 */
class ShippingEasyShipmentNotificationCallback extends Controller
{
	/**
	 * @param $boxdetail
	 * @param $boxes
	 * @param $request
	 */
	public function updateOrderShipment(Request $request)
	{
		try {
			$date = Carbon::today();
			if ( $request->has( 'shipment' ) ) {
				$shipment     = $request->shipment;
				$tracking_num = $shipment->tracking_number;
				$orders       = $shipment->orders;
				foreach ( $orders as $order ) {
					$lineItems = $order->recipients[ 0 ]->line_items;
					foreach ( $lineItems as $lineItem ) {
						$subString = substr( $lineItem->sku, 0, 3 );
						$partNumId = subStr( $lineItem->sku, 4 );
						$productOptions = $lineItem->product_options;
						if ( $subString == 'SUB' ) {
							$table = Subscription::whereId( $partNumId )->with( 'customer.company', 'device', 'sim' )->first();
							if ( $table ) {
								$table_data = [
									'status'        => 'for-activation',
									'shipping_date' => $date,
									'tracking_num'  => $tracking_num,
									'sim_card_num'  => $productOptions['sim_card_num'],
								];
								if (strlen($productOptions['imei_no']) > 0){
									$table_data['device_imei'] = $productOptions['imei_no'];
								}
								$table->update( $table_data );
								$table[ 'customer' ] = $table->customerRelation;

								$request->headers->set( 'authorization', $table->customerRelation->company->api_key );
								event( new ShippingNumber( $tracking_num, $table ) );
								event( new SubcriptionStatusChanged( $table->id ) );
							}
						} elseif ( $subString == 'DEV' ) {
							$table = CustomerStandaloneDevice::whereId( $partNumId )->with( 'device', 'customer.company' )->first();
							if ( $table ) {
								$table_data = [
									'status'        => CustomerStandaloneDevice::STATUS[ 'complete' ],
									'shipping_date' => $date,
									'tracking_num'  => $tracking_num
								];
								if (strlen($productOptions['imei_no']) > 0){
									$table_data['device_imei'] = $productOptions['imei_no'];
								}
								$table->update( $table_data );
								$request->headers->set( 'authorization', $table->customer->company->api_key );
								event( new ShippingNumber( $tracking_num, $table ) );
							}
						} elseif ( $subString == 'SIM' ) {
							$table = CustomerStandaloneSim::whereId( $partNumId )->with( 'sim', 'customer.company' )->first();
							if ( $table ) {
								$table->update( [
									'status'        => CustomerStandaloneSim::STATUS[ 'complete' ],
									'shipping_date' => $date,
									'tracking_num'  => $tracking_num,
									'sim_num'       => $productOptions['sim_card_num'],
								] );
								$request->headers->set( 'authorization', $table->customer->company->api_key );
								event( new ShippingNumber( $tracking_num, $table ) );
							}
						}
					}
				}

			}
		} catch ( \Exception $e ) {
			\Log::error( $e->getMessage() );
		}
	}
}