<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use App\Model\Customer;
use App\Model\Plan;
use App\Model\OrderCoupon;
use App\Model\OrderGroup;
use App\Model\Order;
use App\Model\Device;
use App\Model\Sim;
use App\Model\Addon;
use Carbon\Carbon;
use App\Model\CustomerCoupon;

class CouponController extends Controller
{

    const COUPON_CLASS = [
        'APPLIES_TO_ALL'                => 1,
        'APPLIES_TO_SPECIFIC_TYPES'     => 2,
        'APPLIES_TO_SPECIFIC_PRODUCT'   => 3
    ];

    const SPECIFIC_TYPES = [
        'PLAN'      =>  1,
        'DEVICE'    =>  2,
        'SIM'       =>  3,
        'ADDON'     =>  4
    ];

    const SUB_TYPES = [
        'NOT_LIMITED'   => 0,
        'VOICE'         => 1,
        'DATA'          => 2,
        'WEARABLE'      => 3,
        'MEMBERSHIP'    => 4,
        'DIGITS'        => 5,
        'CLOUD'         => 6
    ];

    const ORDER_GROUP = [
        'PLAN'      =>  'plans',
        'DEVICE'    =>  'devices',
        'SIM'       =>  'sims',
        'ADDON'     =>  'addons'
    ];

    protected $failedResponse;
    protected $totalSubscriptions;

    public function addCoupon(Request $request)
    {
        $coupon = Coupon::where('code', $request->code)->first();
        $order  = Order::find($request->order_id);
        
        if (!$coupon) { 

            return ['error' => 'Invalid coupon code']; 
        } elseif (!$this->couponIsValid($coupon)) {

            return ['error' => $this->failedResponse];
        } elseif (!$request->hash) {
            $this->failResponse = 'Please login first';
            return ['error' => $this->failResponse];
        } else {

            $customerHash          = $request->hash ? $request->hash : $order->customer->hash;
            $customer              = Customer::where('hash', $customerHash)->first();
            $alreadyUsed           = count(CustomerCoupon::where('customer_id', $customer->id)->where('coupon_id', $coupon->id)->get());

            if ($alreadyUsed) {
                return ['error' => 'You have already used this coupon'];
            }

            $billableSubscriptions = $customer->billableSubscriptions;
            $billablePlans = [];

            foreach ($billableSubscriptions as $subscription) {
                $billablePlans[] = Plan::find($subscription->plan_id);
            }

            if (!empty($coupon)) {
                
                $alreadyExists = OrderCoupon::where('order_id', $request->order_id)->get();
                $orderGroup    = OrderGroup::where('order_id', $request->order_id)->sum('plan_prorated_amt');

                if (count($alreadyExists) < 1) {

                    OrderCoupon::create([
                        'order_id' => $request->order_id,
                        'coupon_id' => $coupon->id
                    ]);

                }
                
                $coupon                 = $coupon;
                $couponProductTypes     = $coupon->couponProducts;
                $couponSpecificTypes    = $coupon->couponProductTypes;
                $orderGroupCart         = $request->orderGroupsCart;
                $billablePlans          = $billablePlans;
                
                $cartPlans              = isset($orderGroupCart['plans']['items']) ? ['items' => $orderGroupCart['plans']['items']]    : [];

                $appliedToAll       = $coupon['class'] == self::COUPON_CLASS['APPLIES_TO_ALL']              ?  $this->appliedToAll($coupon, $order->id) : 0;
                $appliedToTypes     = $coupon['class'] == self::COUPON_CLASS['APPLIES_TO_SPECIFIC_TYPES']   ?  $this->appliedToTypes($coupon, $couponSpecificTypes, $order->id, $orderGroupCart) : 0;
                $appliedToProducts  = $coupon['class'] == self::COUPON_CLASS['APPLIES_TO_SPECIFIC_PRODUCT'] ?  $this->appliedToProducts($coupon, $couponProductTypes, $order->id, $orderGroupCart) : 0;                
                
                
                if ($this->isApplicable($cartPlans, $billablePlans, $coupon)) {

                    $total = $appliedToAll['total'] + $appliedToTypes['total'] + $appliedToProducts['total'];

                    return ['total' => $total, 'code' => $coupon->code, 'applied_to' => [
                            'applied_to_all'        => $appliedToAll['applied_to'],
                            'applied_to_types'      => $appliedToTypes['applied_to'],
                            'applied_to_products'   => $appliedToProducts['applied_to'],
                        ]
                    ];


                } else {
                    
                    return ['error' => $this->failedResponse];
                
                }

            }
            
        }
        
    }

    protected function couponIsValid($coupon) 
    {
        if ($coupon['company_id'] != \Request::get('company')->id) {
            $this->failedResponse = 'Coupon is Invalid';
            return false;
        }
        if ($coupon['active']) {
            if ($coupon['multiline_restrict_plans'] && !count($coupon->multilinePlanTypes)) {
                $this->failedResponse = 'Multiline plan data missing';
                return false;
            }
            if ($coupon['class'] == self::COUPON_CLASS['APPLIES_TO_SPECIFIC_TYPES'] && !count($coupon->couponProductTypes)) {
                $this->failedResponse = 'Coupon product types data missing';
                return false;
            }
            if ($coupon['class'] == self::COUPON_CLASS['APPLIES_TO_SPECIFIC_PRODUCT'] && !count($coupon->couponProducts)) {
                $this->failedResponse = 'Coupon products data missing';
                return false;
            }
            return true;
        } else {
            $this->failedResponse = 'Coupon is not active';
            return false;
        }
    }

    protected function isApplicable($cartPlans, $billablePlans, $coupon)
    {
        
        $isApplicable    = true;
        $this->failedResponse = '';
        $restrictedBillablePlans  = [];
        $restrictedCartPlans      = [];

        if ($coupon['multiline_restrict_plans']) {
            $multilineRestrict  = $coupon->multilinePlanTypes->pluck('plan_type');
            foreach ($billablePlans as $plan) {
                if ($multilineRestrict->contains($plan['type'])) {
                    $restrictedBillablePlans[] = $plan;
                }
            }
            if (isset($cartPlans['items'])) {

                foreach ($cartPlans['items'] as $plan) {
                    if ($multilineRestrict->contains($plan['type'])) {
                        $restrictedCartPlans[] = $plan;
                    }
                }
            }
            $totalSubscriptions = array_merge($restrictedBillablePlans, $restrictedCartPlans);
        } else {
            $totalSubscriptions = array_merge($cartPlans, $billablePlans);
        }

        if ($coupon['multiline_min']) {
            if (count($totalSubscriptions) < $coupon['multiline_min']) {

                $this->failedResponse = 'Minimum subscription requirements not met';
                return false;

            } 
        } 
        if ($coupon['multiline_max']) {

            if (count($totalSubscriptions) > $coupon['multiline_max']) {

                $this->failedResponse = 'Maximum subscription requirements not met';
                return false;

            }
        }

        return $this->couponCanBeUsed($coupon);

    }

    protected function couponCanBeUsed($coupon)
    {
        $isApplicable       = true;

        $today              = Carbon::now();
        
        $couponStartDate    = Carbon::parse($coupon['start_date']);
        
        $couponExpiryDate   = Carbon::parse($coupon['end_date']);

        $this->failedResponse = '';

        if ($couponStartDate && $today < $couponStartDate) {

            $this->failedResponse = 'Coupon is valid from '.$couponStartDate;
            return false;

        } elseif ($couponExpiryDate && ($today >= $couponExpiryDate)) {

            $this->failedResponse = 'Coupon expired on '.$couponExpiryDate;
            return false;

        }

        return $this->couponNotReachedMaxLimit($coupon);

    }

    protected function couponNotReachedMaxLimit($coupon)
    {
        $this->failedResponse = '';
        $maxLimitNotReached = true;

        if ($coupon['num_uses'] >= $coupon['max_uses']) {

            $this->failedResponse = 'Coupon has reached maximum usage';
            return false;

        }
        
        return $maxLimitNotReached;

    }


    protected function appliedToAll($coupon, $id)
    {
        $order              = Order::find($id);
        $orderGroup         = OrderGroup::where('order_id', $id)->get();
        $planAmount         = [];
        $deviceAmount       = [];
        $simAmount          = [];
        $addonAmount        = [];
        $isPercentage       = $coupon['fixed_or_perc'] == 2 ? true : false;
        $itemsAmount        = 0;
        $countItems         = [];
        $multilineRestrict  = $coupon['multiline_restrict_plans'] == 1 ? $coupon->multilinePlanTypes->pluck('plan_type') : null;
        $orderCouponProduct = [];
            
        foreach ($orderGroup as $og) {

            if ($og->plan_id) {

                $plan           = Plan::find($og->plan_id);

                $planType       = $plan->type;

                if ($multilineRestrict) {

                    if ($planType == $multilineRestrict[0]) {

                        $planAmount[]           = $og->plan_prorated_amt ? $og->plan_prorated_amt : $plan->amount_recurring;

                        $countItems[]           = $og->plan_id;

                        $orderCouponProduct[]   = [
                            'order_product_type'    => self::SPECIFIC_TYPES['PLAN'],
                            'order_product_id'      => $og->plan_id,
                            'amount'                => $coupon['amount'],
                            'order'                 => $order
                        ];
                        
                    }

                } else {

                    $planAmount[]   = $og->plan_prorated_amt ? $og->plan_prorated_amt : $plan->amount_recurring;

                    $countItems[]   = $og->plan_id;

                    $orderCouponProduct[]   = [
                        'order_product_type'    => self::SPECIFIC_TYPES['PLAN'],
                        'order_product_id'      => $og->plan_id,
                        'amount'                => $coupon['amount'],
                        'order'                 => $order
                    ];

                }

                
                
            }

            if ($og->device_id) {

                $device         = Device::find($og->device_id);

                $deviceAmount[] = $og->plan_id ? $device->amount_w_plan : $device->amount;

                $countItems[]   = $og->device_id;

                $orderCouponProduct[]   = [
                    'order_product_type'    => self::SPECIFIC_TYPES['DEVICE'],
                    'order_product_id'      => $og->device_id,
                    'amount'                => $coupon['amount'],
                    'order'                 => $order
                ];

            }

            if ($og->sim_id) {

                $sim            = Sim::find($og->sim_id);

                $simAmount[]    = $og->plan_id ? $sim->amount_w_plan : $sim->amount_alone; 
                
                $countItems[]   = $og->sim_id;

                $orderCouponProduct[]   = [
                    'order_product_type'    => self::SPECIFIC_TYPES['SIM'],
                    'order_product_id'      => $og->sim_id,
                    'amount'                => $coupon['amount'],
                    'order'                 => $order
                ];
                
            }

            $addonIds   = [];

            if ($og->plan_id) {

                foreach ($og->order_group_addon as $addon) {
      
                    $addonIds[] = $addon->addon_id;

                } 

            }

            foreach ($addonIds as $id) {
                
                $addon = Addon::find($id);

                $addonAmount[] = $order->addonProRate($id) > 0 ? $order->addonProRate($id) : $addon->amount_recurring;

                $countItems[]  = $id;

                $orderCouponProduct[]   = [
                    'order_product_type'    => self::SPECIFIC_TYPES['ADDON'],
                    'order_product_id'      => $id,
                    'amount'                => $coupon['amount'],
                    'order'                 => $order
                ];

            }
            
        }
        

        $itemsAmount = array_sum($planAmount) + array_sum($deviceAmount) + array_sum($simAmount) + array_sum($addonAmount);

        $total       = $isPercentage ? $itemsAmount * $coupon['amount'] / 100 : $coupon['amount'] * count($countItems);
        
        $this->orderCoupon($orderCouponProduct, $order);
        return (['total' => $total, 'applied_to' => $orderCouponProduct, 'order' => $order]);

    }


    protected function ifPlanApplicable($multilineRestrict, $plans)
    {

        $applicablePlans = [];
        
        if ($multilineRestrict) {
            
            foreach ($plans as $plan) {
                
                $planType = $plan['type'];
                
                if ($multilineRestrict->contains($planType)) {
                    
                    $applicablePlans[] = $plan;

                }

            }

        }

       return $applicablePlans;

    }

    protected function appliedToTypes($couponMain, $couponSpecificTypes, $id, $orderGroupCart)
    {
        
        $order              = Order::find($id);
        $orderGroup         = OrderGroup::where('order_id', $id)->get();
        $isPercentage       = $couponMain['fixed_or_perc'] == 2 ? true : false;
        $couponAmount       = [];
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type') : null;
        $orderGroupPlans    = isset($orderGroupCart['plans']['items']) ? $orderGroupCart['plans']['items'] : [];
        $ifPlanApplicable   = $this->ifPlanApplicable($multilineRestrict, $orderGroupPlans);
        $plans              = $couponMain   ['multiline_restrict_plans'] > 0 ? $ifPlanApplicable : $orderGroupPlans;
        $orderCouponProduct = [];
    
        foreach ($couponSpecificTypes as $coupon) {
            //For plan types
            if ($coupon['type'] == self::SPECIFIC_TYPES['PLAN']) {

                $isLimited = $coupon['sub_type'] > 0 ? $coupon['sub_type'] : false;

                if (isset($orderGroupCart['plans']['items'])) {

                    foreach ($plans as $plan) {
                    
                        $isProrated = $order->planProRate($plan['id']);
                        
                        $plan       = Plan::find($plan['id']);
    
                        if ($isLimited) {
    
                            if ($plan['type'] == $isLimited) {
        
                                $planAmount     = $isProrated ? $isProrated : $plan->amount_recurring;
    
                                $couponAmount[] = $isPercentage ? $coupon['amount'] * $planAmount / 100 : $coupon['amount'];

                                $orderCouponProduct[]   = [
                                    'order_product_type'    => self::SPECIFIC_TYPES['PLAN'],
                                    'order_product_id'      => $plan->id,
                                    'amount'                => $coupon['amount'],
                                    'order'                 => $order
                                ];

                            }
    
                        } else {
                           
                            $planAmount     = $isProrated ? $isProrated : $plan->amount_recurring;
    
                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $planAmount / 100 : $coupon['amount'];

                            $orderCouponProduct[]   = [
                                'order_product_type'    => self::SPECIFIC_TYPES['PLAN'],
                                'order_product_id'      => $plan->id,
                                'amount'                => $coupon['amount'],
                                'order'                 => $order
                            ];
                            
                        }
    
                    }

                }

            }
            //For Device types
            if ($coupon['type'] == self::SPECIFIC_TYPES['DEVICE']) {

                if (isset($orderGroupCart['devices']['items'])) {

                    foreach ($orderGroupCart['devices']['items'] as $device) {

                        $orderGroup     = OrderGroup::find($device['order_group_id']);

                        $deviceAmount   = $orderGroup->plan_id ? $device['amount_w_plan'] : $device['amount'];

                        $couponAmount[] = $isPercentage ? $coupon['amount'] * $deviceAmount / 100 : $coupon['amount'];

                        $orderCouponProduct[]   = [
                            'order_product_type'    => self::SPECIFIC_TYPES['DEVICE'],
                            'order_product_id'      => $device['id'],
                            'amount'                => $coupon['amount'],
                            'order'                 => $order
                        ];

                    }

                }

            }
            //For Sim types
            if ($coupon['type'] == self::SPECIFIC_TYPES['SIM']) {

                if (isset($orderGroupCart['sims']['items'])) {

                    foreach ($orderGroupCart['sims']['items'] as $sim) {

                        $orderGroup     = OrderGroup::find($sim['order_group_id']);

                        $simAmount      = $orderGroup->plan_id ? $sim['amount_w_plan'] : $sim['amount_alone'];

                        $couponAmount[] = $isPercentage ? $coupon['amount'] * $simAmount / 100 : $coupon['amount'];

                        $orderCouponProduct[]   = [
                            'order_product_type'    => self::SPECIFIC_TYPES['SIM'],
                            'order_product_id'      => $sim['id'],
                            'amount'                => $coupon['amount'],
                            'order'                 => $order
                        ];

                    }
                }

            }

            //For Addon types
            $orderGroupIds = [];
            if ($coupon['type'] == self::SPECIFIC_TYPES['ADDON'] && isset($orderGroupCart['addons']['items'])) {

                if (isset($orderGroupCart['addons']['items'])) {

                    foreach ($orderGroupCart['addons']['items'] as $item) {
                        
                        $orderGroupAddon = OrderGroup::find($item['order_group_id'])->order_group_addon;

                        foreach ($orderGroupAddon as $addon) {

                            $isProrated     = $order->addonProRate($addon->addon_id);

                            $addonAmount    = $isProrated ? $isProrated : Addon::find($addon->addon_id)->amount_recurring;

                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $addonAmount / 100 : $coupon['amount'];

                            $orderCouponProduct[]   = [
                                'order_product_type'    => self::SPECIFIC_TYPES['ADDON'],
                                'order_product_id'      => $addon['id'],
                                'amount'                => $coupon['amount'],
                                'order'                 => $order
                            ];

                        }
                        
                    }

                }

            }

        }
        $this->orderCoupon($orderCouponProduct, $order);
        return (['total' => array_sum($couponAmount), 'applied_to' => $orderCouponProduct, 'order' => $order]);

    }

    protected function appliedToProducts($couponMain, $couponProductTypes, $id, $orderGroupCart)
    {

        $order              = Order::find($id);
        $orderGroup         = OrderGroup::where('order_id', $id)->get();
        $isPercentage       = $couponMain['fixed_or_perc'] == 2 ? true : false;
        $couponAmount       = [];
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type') : null;
        $orderGroupPlans    = isset($orderGroupCart['plans']['items']) ? $orderGroupCart['plans']['items'] : [];
        $ifPlanApplicable   = $this->ifPlanApplicable($multilineRestrict, $orderGroupPlans);
        $plans              = $couponMain['multiline_restrict_plans'] > 0 ? $ifPlanApplicable : $orderGroupPlans;
        $orderCouponProduct = [];

        foreach ($couponProductTypes as $coupon) {

            $productId = $coupon['product_id'];

            if ($coupon['product_type'] == self::SPECIFIC_TYPES['PLAN']) {

                if (isset($orderGroupCart['plans']['items'])) {

                    foreach ($plans as $plan) {
                    
                        if ($plan['id'] == $productId) {
                            
                            $isProrated     = $order->planProRate($plan['id']);
    
                            $planAmount     = $isProrated ? $isProrated : Plan::find($plan['id'])->amount_recurring;
                            
                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $planAmount / 100 : $coupon['amount'];

                            $orderCouponProduct[]   = [
                                'order_product_type'    => self::SPECIFIC_TYPES['PLAN'],
                                'order_product_id'      => $plan['id'],
                                'amount'                => $coupon['amount'],
                                'order'                 => $order
                            ];
    
                        }
    
                    }

                }

            }

            if ($coupon['product_type'] == self::SPECIFIC_TYPES['DEVICE']) {

                if (isset($orderGroupCart['devices']['items'])) {

                    foreach ($orderGroupCart['devices']['items'] as $device) {

                        if ($device['id'] == $productId) {

                            $orderGroup     = OrderGroup::find($device['order_group_id']);

                            $deviceAmount   = $orderGroup->plan_id ? $device['amount_w_plan'] : $device['amount'];

                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $deviceAmount / 100 : $coupon['amount'];

                            $orderCouponProduct[]   = [
                                'order_product_type'    => self::SPECIFIC_TYPES['DEVICE'],
                                'order_product_id'      => $device['id'],
                                'amount'                => $coupon['amount'],
                                'order'                 => $order
                            ];

                        }

                    }

                }

            }

            if ($coupon['product_type'] == self::SPECIFIC_TYPES['SIM']) {

                if (isset($orderGroupCart['sims']['items'])) {

                    foreach ($orderGroupCart['sims']['items'] as $sim) {

                        if ($sim['id'] == $productId) {

                            $orderGroup     = OrderGroup::find($sim['order_group_id']);

                            $simAmount      = $orderGroup->plan_id ? $sim['amount_w_plan'] : $sim['amount_alone'];

                            $couponAmount[] = $isPercentage ? $coupon['amount'] * $simAmount / 100 : $coupon['amount'];

                            $orderCouponProduct[]   = [
                                'order_product_type'    => self::SPECIFIC_TYPES['SIM'],
                                'order_product_id'      => $sim['id'],
                                'amount'                => $coupon['amount'],
                                'order'                 => $order
                            ];

                        }

                    }

                }


            }
            
            $orderGroupIds = [];

            if ($coupon['product_type'] == self::SPECIFIC_TYPES['ADDON'] && isset($orderGroupCart['addons']['items'])) {

                if (isset($orderGroupCart['addons']['items'])) {

                    foreach ($orderGroupCart['addons']['items'] as $item) {
                        
                        $orderGroupAddon = OrderGroup::find($item['order_group_id'])->order_group_addon;

                        foreach ($orderGroupAddon as $addon) {

                            if ($addon->addon_id == $productId) {

                                $isProrated     = $order->addonProRate($addon->addon_id);

                                $addonAmount    = $isProrated ? $isProrated : Addon::find($addon->addon_id)->amount_recurring;

                                $couponAmount[] = $isPercentage ? $coupon['amount'] * $addonAmount / 100 : $coupon['amount'];

                                $orderCouponProduct[]   = [
                                    'order_product_type'    => self::SPECIFIC_TYPES['ADDON'],
                                    'order_product_id'      => $addon->addon_id,
                                    'amount'                => $coupon['amount'],
                                    'order'                 => $order
                                ];

                            }
                            
                        }

                    }

                }
                
            }

        }
        $this->orderCoupon($orderCouponProduct, $order);
        return (['total' => array_sum($couponAmount), 'applied_to' => $orderCouponProduct, 'order' => $order]);
    }

    protected function removeCoupon(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->orderCoupon->orderCouponProduct()->delete();
        $order->orderCoupon()->delete();

    }

    protected function orderCoupon($data, $order)
    {
        $order->orderCoupon->orderCouponProduct()->delete();

        if ($data) {
                
            foreach ($data as $product) {
                
                if (isset($product['order']['id'])) {
                   
                    $order->orderCoupon->orderCouponProduct()->create([
                        'order_product_type'    => $product['order_product_type'],
                        'order_product_id'      => $product['order_product_id'],
                        'amount'                => $product['amount']
                    ]);

                }

            }

        }

    }

}
