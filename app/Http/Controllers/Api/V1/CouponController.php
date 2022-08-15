<?php
namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Coupon;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;

/**
 * Class CouponController
 *
 * @package App\Http\Controllers\Api\V1
 */
class CouponController extends Controller
{
    use InvoiceCouponTrait;

	/**
	 *
	 */
	const SPECIFIC_TYPES = [
        'PLAN'      =>  1,
        'DEVICE'    =>  2,
        'SIM'       =>  3,
        'ADDON'     =>  4
    ];

	/**
	 *
	 */
	const FIXED_PERC_TYPES = [
        'fixed'      => 1,
        'percentage' => 2
    ];

	/**
	 *
	 */
	const PLAN_TYPE = [
        'Voice'     => 1,
        'Data'      => 2
    ];

	/**
	 * @var
	 */
	protected $failedResponse;

	/**
	 * @var int[]
	 */
	protected $totalTaxableAmount = [0];

	/**
	 * @param Request $request
	 *
	 * @return array|array[]|string[]|null
	 */
	public function addCoupon(Request $request)
    {
        
        try {
            // Request from cart plans
            if ($request->for_plans) {
                // dd($request->all());
                $codes = [];
                foreach ($request->data_for_plans as $data) {
                    if ($data['coupon_id']) {
                        $coupon = Coupon::find($data['coupon_id']);
                        $codes[] = [
                            'coupon' => [
                                'info'      => $this->checkEligibleProducts($coupon),
                                'code'      => $coupon->code
                            ],
                            'order_group_id'    => $data['order_group_id'],
                            'plan'              => Plan::find($data['plan_id'])->name
                        ];
                    }
                }
                return ['coupon_data' => $codes];
            }
            
            // Request from cart tooltip
            $coupon = Coupon::where('code', $request->code)->first();
            
            if ($request->only_details) {
                return [
                	'coupon_amount_details' => $this->checkEligibleProducts($coupon)
                ];
            }

	        $order_id = $request->input('order_id');
            // Regulator textbox request
            if (!$this->couponIsValid($coupon)) {
	            return [
	            	'error'     => $this->failedResponse
	            ];
            }

            if ($request->subscription_id) {
                return $this->ifAddedFromAdmin($request, $coupon);
            } else {
                
	            /**
	             * Check if the coupon are stackable
	             */
	            if(!$this->couponAreStackableAndUnused($coupon, $order_id)){
		            return [
			            'error'     => $this->failedResponse
		            ];
	            }
                
                return $this->ifAddedByCustomer($request, $coupon);
            }

        } catch (Exception $e) {
            \Log::info($e->getMessage() . ' on line number: '.$e->getLine() . ' in CouponController');
            return [
            	'total' => 0,
	            'error' => 'Server error'
            ];
        }    
    }

	/**
	 * @param $coupon
	 *
	 * @return bool
	 */
	protected function couponIsValid($coupon)
    {
        if (!$coupon || $coupon['company_id'] != \Request::get('company')->id) {
            $this->failedResponse = 'Coupon is invalid';
            return false;
        }
        if ($coupon['active']) {
            if ($coupon['multiline_restrict_plans'] && !count($coupon->multilinePlanTypes)) {
                $this->failedResponse = 'Multiline plan data missing';
                return false;
            }
            if ($coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES'] && !count($coupon->couponProductTypes)) {
                $this->failedResponse = 'Coupon product types data missing';
                return false;
            }
            if ($coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT'] && !count($coupon->couponProducts)) {
                $this->failedResponse = 'Coupon products data missing';
                return false;
            }
            return true;
        } else {
            $this->failedResponse = 'Not active';
            return false;
        }
    }

	/**
	 * @param $request
	 * @param $coupon
	 *
	 * @return array|string[]|null
	 */
	public function ifAddedFromAdmin($request, $coupon)
    {
        $subscription = Subscription::find($request->subscription_id);
        $customer = $subscription->customerRelation;
        if ($subscription->subscriptionCoupon->where('coupon_id', $coupon->id)->count()) {
            $this->failedResponse = 'Coupon already used for this subscription';
            return ['error' => $this->failedResponse];
        }
        if (!$this->isApplicable(false, $customer, $coupon, true)) {
            return ['error' => $this->failedResponse];
        }
        $insert = $this->insertIntoTables($coupon, $customer->id, [$subscription->id], true);
        return $insert;
    }

	/**
	 * @param $request
	 * @param $coupon
	 *
	 * @return array
	 */
    public function ifAddedByCustomer($request, $coupon)
    {
        $order  = Order::find($request->order_id);
        return $this->ifAddedByCustomerFunction($order->id, $coupon);
    }

	/**
	 * @param Request $request
     * @return array
	 */
	public function removeCoupon(Request $request)
    {
    	try {
		    $order = Order::find($request->order_id);
		    $couponCode = $request->get('coupon_code');
		    $coupon = Coupon::where('code', $couponCode)->first();
            $coupon->num_uses=$coupon->num_uses-1;
            $coupon->save();
		    $couponToRemove = $order->orderCoupon->where('coupon_id', $coupon->id)->first();
		    return $couponToRemove ? ['status' => $couponToRemove->delete()] : ['status' => false];
	    } catch(Exception $e) {
		    \Log::info( $e->getMessage() . ' on line number: ' . $e->getLine() . ' in CouponController remove' );
		    return ['status' => false];
	    }

    }

	/**
	 * @param $autoAddCoupon
	 * @param $orderGroups
	 *
	 * @return bool
	 */
	protected function ifAutoAdd($autoAddCoupon, $orderGroups)
    {
        if ($autoAddCoupon->count()) {
            $eligiblePlans = $autoAddCoupon->pluck('id')->toArray();
            $orderPlanIds  = $orderGroups->where('plan_id', '!=', null)->pluck('plan_id')->toArray();
            $eligibleOgs   = array_filter($orderPlanIds, function($id) use ($eligiblePlans) {
                if (in_array($id, $eligiblePlans)) {
                    return $id;
                }
            });
            if (!count($eligibleOgs)) {
                return false;
            }
        }
        return true;
    }

	/**
	 * Check if the coupon in the order are stackable or used
	 * @param $coupon
	 * @param $order_id
	 *
	 * @return bool
	 */
    protected function couponAreStackableAndUnused( $coupon, $order_id )
    {
	    $notStackableCouponCode = '';
	    $alreadyUsedCouponCode = '';
		$order = Order::find($order_id);
		$orderCoupons = $order->orderCoupon;

		if(count($orderCoupons) && $coupon->stackable !== 1) {
			$this->failedResponse = "Coupon {$coupon->code} is not stackable. If you still wish to add this coupon, remove the existing coupons and try again";
			return false;
		}

	    foreach ($orderCoupons as $orderCoupon ) {
	    	if($orderCoupon->coupon_id === $coupon->id){
			    $alreadyUsedCouponCode = $coupon->code;
			    break;
		    }
			if($orderCoupon->coupon->stackable !== 1){
				$notStackableCouponCode = $orderCoupon->code;
				break;
			}
	    }
	    if($alreadyUsedCouponCode){
		    $this->failedResponse = "Coupon $alreadyUsedCouponCode is already used.";
		    return false;
	    }

	    if($notStackableCouponCode){
		    $this->failedResponse = "Coupon $notStackableCouponCode is not stackable. If you still wish to add this coupon, remove the existing coupons and try again";
			return false;
	    }

	    return true;
    }

}