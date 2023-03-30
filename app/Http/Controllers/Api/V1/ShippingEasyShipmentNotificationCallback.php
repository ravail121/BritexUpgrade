<?php

namespace App\Http\Controllers\Api\V1;


use Carbon\Carbon;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Events\ShippingNumber;
use App\Model\SubscriptionLog;
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
									'sim_card_num'  => $productOptions->sim_card_num ?? '',
								];
								if (property_exists($productOptions, 'imei_no')){
									$table_data['device_imei'] = $productOptions->imei_no;
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
								if (property_exists($productOptions, 'imei_no')) {
									$deviceImei                  = $productOptions->imei_no ?? 'null';
									$table_data[ 'imei' ] = $deviceImei;
									if ( $table->subscription_id ) {
										$subscription = Subscription::whereId( $table->subscription_id )->first();
										if($subscription){
											$currentDeviceImei = $subscription->device_imei;
											$subscription->update( [ 'device_imei' => $deviceImei ] );
											SubscriptionLog::create( [
												'subscription_id'   => $subscription->id,
												'company_id'        => $table->customer->company->id,
												'customer_id'       => $table->customer->id,
												'description'       => 'Replacement Device shipped',
												'category'          => SubscriptionLog::CATEGORY['device-imei-changed'],
												'old_product'       => $currentDeviceImei,
												'new_product'       => $deviceImei
											]);
										}
									}
								}
								$table->update( $table_data );

								$request->headers->set( 'authorization', $table->customer->company->api_key );
								event( new ShippingNumber( $tracking_num, $table ) );
							}
						} elseif ( $subString == 'SIM' ) {
							$table = CustomerStandaloneSim::whereId( $partNumId )->with( 'sim', 'customer.company' )->first();
							if ( $table ) {
								$table_data = [
									'status'        => CustomerStandaloneSim::STATUS[ 'complete' ],
									'shipping_date' => $date,
									'tracking_num'  => $tracking_num,
								];
								if (property_exists($productOptions, 'sim_card_num')) {
									$simNum                  = $productOptions->sim_card_num ?? 'null';
									$table_data[ 'sim_num' ] = $simNum;
									if ( $table->subscription_id ) {
										$subscription = Subscription::whereId( $table->subscription_id )->first();
										if($subscription){
											$currentSimNum = $subscription->sim_card_num;
											$subscription->update( [ 'sim_card_num' => $simNum ] );
											SubscriptionLog::create( [
												'subscription_id'   => $subscription->id,
												'company_id'        => $table->customer->company->id,
												'customer_id'       => $table->customer->id,
												'description'       => 'Replacement SIM shipped',
												'category'          => SubscriptionLog::CATEGORY['sim-num-changed'],
												'old_product'       => $currentSimNum,
												'new_product'       => $simNum
											]);
										}
									}
								}
								$table->update($table_data);
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