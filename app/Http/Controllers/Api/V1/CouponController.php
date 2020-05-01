<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use App\Model\OrderCoupon;
use App\Model\Order;
use Carbon\Carbon;
use Exception;
use App\Model\Customer;
use App\Model\Subscription;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;
use App\Model\Plan;

class CouponController extends Controller
{
    use InvoiceCouponTrait;

    const SPECIFIC_TYPES = [
        'PLAN'      =>  1,
        'DEVICE'    =>  2,
        'SIM'       =>  3,
        'ADDON'     =>  4
    ];

    const FIXED_PERC_TYPES = [
        'fixed'      => 1,
        'percentage' => 2
    ];

    const PLAN_TYPE = [
        'Voice'     => 1,
        'Data'      => 2
    ];

    protected $failedResponse;
    protected $totalTaxableAmount = [0];

    public function addCoupon(Request $request)
    {
        try {
            // Request from cart plans
            if ($request->for_plans) {
                $codes = [];
                foreach ($request->data_for_plans as $data) {
                    if ($data['coupon_id']) {
                        $coupon = Coupon::find($data['coupon_id']);
                        $codes[] = [
                            'coupon' => [
                                'info' => $this->checkEligibleProducts($coupon),
                                'code'    => $coupon->code
                            ],
                            'order_group_id' => $data['order_group_id'],
                            'plan'  => Plan::find($data['plan_id'])->name
                        ];
                    }
                }
                return ['coupon_data' => $codes];
            }

            // Request from cart tooltip
            $coupon = Coupon::where('code', $request->code)->first();
            if ($request->only_details) {
                return ['coupon_amount_details' => $this->checkEligibleProducts($coupon)];
            }

            // Regulator textbox request
            if (!$this->couponIsValid($coupon)) {
                return ['error' => $this->failedResponse];
            }
            if ($request->subscription_id) {
                return $this->ifAddedFromAdmin($request, $coupon);
            } else {
                return $this->ifAddedByCustomer($request, $coupon);
            }

        } catch (Exception $e) {
            \Log::info($e->getMessage().' on line number: '.$e->getLine().' in CouponController');
            return [ 'total' => 0,'error' => 'Server error' ];
        }    
    }

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

    protected function isApplicable($order, $customer, $coupon, $admin = false)
    {
        if ($admin) {
            $totalSubscriptions = $customer->billableSubscriptionsForCoupons->count();
        } else {
            $accountSubscriptions   = $customer->billableSubscriptionsForCoupons;
            $cartSubscriptions      = $order->allOrderGroup->where('plan_id', '!=', null);
            $totalSubscriptions     = $accountSubscriptions->count() + $cartSubscriptions->count();

            // $autoAddCoupon      = Plan::where('auto_add_coupon_id', $coupon->id);        
            // if ($autoAddCoupon->count()) {
            //     $eligibleOrder = $this->ifAutoAdd($autoAddCoupon, $order->allOrderGroup);
            //     if (!$eligibleOrder) {
            //         $this->failedResponse = 'Coupon not available';
            //         return false;
            //     }
            // }
            $multilineRestrict  = $coupon->multiline_restrict_plans ? $coupon->multilinePlanTypes->pluck('plan_type')->toArray() : null;
            if ($multilineRestrict) {
                $totalIds = array_merge($accountSubscriptions->pluck('plan_id')->toArray(), $cartSubscriptions->pluck('plan_id')->toArray());
                $eligibleSubs = array_filter($cartSubscriptions->pluck('plan_id')->toArray(), function ($id) use ($multilineRestrict) {
                    $plan = Plan::find($id);
                    if (in_array($plan->type, $multilineRestrict)) {
                        return $plan->id;
                    }
                });
                if (!count($eligibleSubs)) {
                    $this->failedResponse = 'Requirements not met';
                    return false;
                }
            }
        }
        if ($coupon['multiline_min'] && $totalSubscriptions < $coupon['multiline_min']) {
            $this->failedResponse = 'Min subscriptions required: '.$coupon['multiline_min'];
            return false;
        } 
        if ($coupon['multiline_max'] && $totalSubscriptions > $coupon['multiline_max']) {
            $this->failedResponse = 'Max subscriptions required: '.$coupon['multiline_max'];
            return false;
        }
        return $this->couponCanBeUsed($coupon);
    }

    protected function couponCanBeUsed($coupon)
    {
        $today              = Carbon::now();
        $couponStartDate    = $coupon->start_date ? Carbon::parse($coupon->start_date) : null;
        $couponExpiryDate   = $coupon->end_date ? Carbon::parse($coupon->end_date) : null;
        if ($couponStartDate && $today < $couponStartDate) {
            $this->failedResponse = 'Starts: '.$couponStartDate;
            return false;
        } elseif ($couponExpiryDate && ($today >= $couponExpiryDate)) {
            $this->failedResponse = 'Expired: '.$couponExpiryDate;
            return false;
        } elseif ($coupon['num_uses'] >= $coupon['max_uses']) {
            $this->failedResponse = 'Not available anymore';
            return false;
        } 
        return true;
    }

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

    public function ifAddedByCustomer($request, $coupon)
    {
        $order  = Order::find($request->order_id);
        $customer = Customer::find($request->customer_id);
        $couponEligibleFor = $this->checkEligibleProducts($coupon);
        
        OrderCoupon::updateOrCreate([
            'order_id' => $order->id,
            'coupon_id' => $coupon->id
        ]);
        $stateTax = isset($customer->stateTax->rate) ? $customer->stateTax->rate : 0;
        $appliedToAll       = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_ALL']              ?  $this->appliedToAll($coupon, $order, $stateTax) : 0;
        $appliedToTypes     = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']   ?  $this->appliedToTypes($coupon, $order, $stateTax) : 0;
        $appliedToProducts  = $coupon['class'] == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT'] ?  $this->appliedToProducts($coupon, $order, $stateTax) : 0;                
        
        if ($this->isApplicable($order, $customer, $coupon)) {
            $total = $appliedToAll['total'] + $appliedToTypes['total'] + $appliedToProducts['total'];
            return [
                'total' => $total, 
                'code' => $coupon->code,
                'coupon_type' => $coupon->class,
                'percentage' => $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'],
                'applied_to' => [
                    'applied_to_all'        => $appliedToAll['applied_to'],
                    'applied_to_types'      => $appliedToTypes['applied_to'],
                    'applied_to_products'   => $appliedToProducts['applied_to'],
                ],
                'coupon_amount_details' => $couponEligibleFor,
                'coupon_tax' => number_format(array_sum($this->totalTaxableAmount), 2) * $stateTax / 100
            ];
        } else {
            return ['error' => $this->failedResponse];
        }
    }

    protected function appliedToAll($coupon, $order, $tax)
    {
        $isPercentage       = $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $coupon->multiline_restrict_plans ? $coupon->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $countItems         = 0;
        $totalDiscount      = 0;
        $orderGroups        = $order->allOrderGroup;
        
        foreach ($orderGroups as $og) {
            // plan charges
            if ($og->plan_id) {
                $planData = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                if ($planData['discount'] == 0 || !$planData['discount']) continue;
                $totalDiscount += $planData['discount'];
                $orderCouponProduct[] = $planData['products'];
                $planData['discount'] ? $countItems++ : null;
            }
            // device charges
            if ($og->device_id) {
                $deviceData = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                $totalDiscount += $deviceData['discount'];
                $orderCouponProduct[] = $deviceData['products'];
                $deviceData['discount'] ? $countItems++ : null;
            }
            // sim charges
            if ($og->sim_id) {
                $simData = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                if ($simData['discount'] == 0 || !$simData['discount']) continue;
                $totalDiscount += $simData['discount'];
                $orderCouponProduct[] = $simData['products'];
                $simData['discount'] ? $countItems++ : null;
            }
            // addon charges
            if ($og->plan_id) {
                foreach ($og->addons as $addon) {
                    $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                    if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                    $totalDiscount += $addonData['discount'];
                    $orderCouponProduct[] = $addonData['products'];
                    $addonData['discount'] ? $countItems++ : null;
                }
            }
        }
        $total = $isPercentage ? $totalDiscount : $coupon->amount * $countItems;
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total'      => str_replace(',', '', number_format($total, 2)), 
            'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : [],
            'amount'     => $coupon->amount
        ]);
    }

    protected function appliedToTypes($couponMain, $order, $tax)
    {
        $isPercentage       = $couponMain->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $couponMain->multiline_restrict_plans ? $couponMain->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $totalDiscount      = 0;

        foreach ($couponMain->couponProductTypes as $coupon) {
            foreach ($order->allOrderGroup as $og) {
                // For Device types
                if ($coupon->type == self::SPECIFIC_TYPES['DEVICE'] && $og->device_id) {
                    $amountForDevices    = $coupon->amount;
                    $deviceData         = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                    if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                    $totalDiscount      += $deviceData['discount'];
                    $orderCouponProduct[] = $deviceData['products'];
                }
                // For Plan types
                if ($coupon->type == self::SPECIFIC_TYPES['PLAN'] && $og->plan_id) {
                    $amountForPlans     = $coupon->amount;
                    if ($coupon->sub_type && $og->plan->type != $coupon->sub_type) continue;
                    $planData           = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                    if ($planData['discount'] == 0 || !$planData['discount']) continue;
                    $totalDiscount += $planData['discount'];
                    $orderCouponProduct[] = $planData['products'];
                }
                // For Sim types
                if ($coupon['type'] == self::SPECIFIC_TYPES['SIM'] && $og->sim_id) {
                    $amountForSims      = $coupon->amount;
                    $simData            = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                    if ($simData['discount'] == 0 || !$simData['discount']) continue;
                    $totalDiscount += $simData['discount'];
                    $orderCouponProduct[] = $simData['products'];
                }
                //For Addon types
                if ($coupon['type'] == self::SPECIFIC_TYPES['ADDON'] && $og->addons->count()) {
                    foreach ($og->addons as $addon) {
                        $amountForAddons   = $coupon->amount;
                        $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                        if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                        $totalDiscount += $addonData['discount'];
                        $orderCouponProduct[] = $addonData['products'];
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total' => str_replace(',', '', $totalDiscount),
            'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : [],
            'amount'    => [
                'plan'  => isset($amountForPlans) ? $amountForPlans : 0,
                'device'=> isset($amountForDevices) ? $amountForDevices : 0,
                'sims'  => isset($amountForSims) ? $amountForSims : 0,
                'addons'=> isset($amountForAddons) ? $amountForAddons : 0,
            ]
        ]);
    }

    protected function appliedToProducts($couponMain, $order, $tax)
    {
        $isPercentage       = $couponMain['fixed_or_perc'] == self::FIXED_PERC_TYPES['percentage'];
        $multilineRestrict  = $couponMain['multiline_restrict_plans'] == 1 ? $couponMain->multilinePlanTypes->pluck('plan_type')->toArray() : null;
        $totalDiscount      = 0;

        foreach ($couponMain->couponProducts as $coupon) {
            foreach ($order->allOrderGroup as $og) {
                // For plans
                if ($coupon->product_type == self::SPECIFIC_TYPES['PLAN'] && $coupon->product_id == $og->plan_id) {
                    $planData = $this->couponForPlans($og, $isPercentage, $coupon, $tax);
                    if ($planData['discount'] == 0 || !$planData['discount']) continue;
                    $totalDiscount += $planData['discount'];
                    $orderCouponProduct[] = $planData['products'];
                }
                // For devices
                if ($coupon->product_type == self::SPECIFIC_TYPES['DEVICE'] && $coupon->product_id == $og->device_id) {
                    $deviceData = $this->couponForDevice($og, $isPercentage, $coupon, $tax);
                    if (!$deviceData['discount'] || $deviceData['discount'] == 0) continue;
                    $totalDiscount += $deviceData['discount'];
                    $orderCouponProduct[] = $deviceData['products'];
                }
                // For Sims
                if ($coupon->product_type == self::SPECIFIC_TYPES['SIM'] && $coupon->product_id == $og->sim_id) {
                    $simData = $this->couponForSims($og, $isPercentage, $coupon, $tax);
                    if ($simData['discount'] == 0 || !$simData['discount']) continue;
                    $totalDiscount += $simData['discount'];
                    $orderCouponProduct[] = $simData['products'];
                }
                // For Addons
                if ($coupon->product_type == self::SPECIFIC_TYPES['ADDON'] && $og->addons->count()) {
                    foreach ($og->addons as $addon) {
                        if ($addon->id != $coupon->product_id) continue;
                        $addonData = $this->couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax);
                        if ($addonData['discount'] == 0 || !$addonData['discount']) continue;
                        $totalDiscount += $addonData['discount'];
                        $orderCouponProduct[] = $addonData['products'];
                    }
                }
            }
        }
        isset($orderCouponProduct) ? $this->orderCoupon($orderCouponProduct, $order) : null;
        return ([
            'total' => str_replace(',', '', $totalDiscount),
            'applied_to' => isset($orderCouponProduct) ? $orderCouponProduct : [],
        ]);
    }

    protected function orderCouponProducts($productType, $productId, $couponAmount, $discount, $orderGroupId)
    {
        return [
            'order_product_type'    => $productType,
            'order_product_id'      => $productId,
            'amount'                => $couponAmount,
            'discount'              => $discount,
            'order_group_id'        => $orderGroupId,
        ];
    }

    protected function orderCoupon($data, $order)
    {
        $order->orderCoupon->orderCouponProduct()->delete();
        if (count($data) && isset($order->orderCoupon)) {
            foreach ($data as $product) {
                $order->orderCoupon->orderCouponProduct()->create([
                    'order_product_type'    => $product['order_product_type'],
                    'order_product_id'      => $product['order_product_id'],
                    'amount'                => $product['amount']
                ]);
            }
        }
    }

    protected function couponForDevice($og, $isPercentage, $coupon, $tax)
    {
        $deviceAmount = $og->plan_id ? $og->device->amount_w_plan : $og->device->amount;
        // $deviceAmount += $og->device->taxable ? $deviceAmount * $tax / 100 : 0;
        $deviceDiscount = ($isPercentage ? $coupon->amount * $deviceAmount / 100 : $coupon->amount);
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['DEVICE'], $og->device_id, $coupon->amount, $deviceDiscount, $og->id);
        $this->totalTaxableAmount[] = $og->device->taxable ? $deviceAmount - $deviceDiscount: 0;
        return ['discount' => $deviceDiscount, 'products' => $orderCouponProduct];
    }

    protected function couponForPlans($og, $isPercentage, $coupon, $tax)
    {
        $planAmount = $og->plan_prorated_amt ?: $og->plan->amount_recurring;
        $planAmount += $og->plan->amount_onetime ?: 0;
        // $planAmount += $og->plan->taxable && $tax ? $planAmount * $tax / 100 : 0;
        // $planAmount += $og->plan->getRegualtoryAmount($og->plan->id, $planAmount);
        $planDiscount = ($isPercentage ? $coupon->amount * $planAmount / 100 : $coupon->amount);
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['PLAN'], $og->plan_id, $coupon->amount, $planDiscount, $og->id);
        $this->totalTaxableAmount[] = $og->plan->taxable ? $planAmount - $planDiscount: 0;
        return ['discount' => $planDiscount, 'products' => $orderCouponProduct];
    }

    protected function couponForSims($og, $isPercentage, $coupon, $tax)
    {
        $simAmount = $og->plan_id ? $og->sim->amount_w_plan : $og->sim->amount_alone;
        // $simAmount += $og->sim->taxable ? $simAmount * $tax / 100 : 0;
        $simDiscount =  ($isPercentage ? $coupon->amount * $simAmount / 100 : $coupon->amount);
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['SIM'], $og->sim_id, $coupon->amount, $simDiscount, $og->id);
        $this->totalTaxableAmount[] = $og->sim->taxable ? $simAmount - $simDiscount: 0;
        return ['discount' => $simDiscount, 'products' => $orderCouponProduct];
    }
    
    
    protected function couponForAddons($order, $addon, $isPercentage, $coupon, $og, $tax)
    {
        $addonAmount = $order->addonProRate($addon->id) ?: $addon->amount_recurring;
        // $addonAmount += $addon->taxable ? $addonAmount * $tax / 100 : 0;
        $addonDiscount  = ($isPercentage ? $coupon->amount * $addonAmount / 100 : $coupon->amount);
        $orderCouponProduct = $this->orderCouponProducts(self::SPECIFIC_TYPES['ADDON'], $addon->id, $coupon->amount, $addonDiscount, $og->id);
        $this->totalTaxableAmount[] = $addon->taxable ? $addonAmount - $addonDiscount : 0;
        return ['discount' => $addonDiscount, 'products' => $orderCouponProduct];
    }

    public function removeCoupon(Request $request)
    {
        $order = Order::find($request->order_id);
        if (isset($order->orderCoupon->id)) {
            $order->orderCoupon->delete();
        }
    }

    public function checkEligibleProducts($coupon) 
    {
        $planRestriciton = [];
        $isPercentage    = $coupon->fixed_or_perc == self::FIXED_PERC_TYPES['percentage'] ? true : false;
        if ($coupon->multiline_restrict_plans && $coupon->multilinePlanTypes->count()) {
            foreach ($coupon->multilinePlanTypes as $type) {
                if ($type->plan_type == self::PLAN_TYPE['Voice']) {
                    $planRestriciton[] = 'Voice';
                } elseif ($type->plan_type == self::PLAN_TYPE['Data']) {
                    $planRestriciton[] = 'Data';
                }
            }
        }
        
        $planRestriciton = count($planRestriciton) ? implode(', ',$planRestriciton) : false;
        if ($coupon->class == Coupon::CLASSES['APPLIES_TO_ALL']) {
            $amount = $isPercentage == false ? '$'.$coupon->amount : $coupon->amount.'%';
            return [
                'details'            => implode('<br>', [
                    $amount. ' off on all products',
                    $planRestriciton ? 'Plan restriction :'.$planRestriciton : null
                ])
            ];
        } elseif ($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_TYPES']) {
            $couponTypes = $coupon->couponProductTypes;
            foreach ($couponTypes as $type) {
                $amount = $type->amount;
                $type_amount =  $isPercentage == false ? '$'.$amount : $amount.'%';
                $plans[]   = $type->type == self::SPECIFIC_TYPES['PLAN']   ? $type_amount.' off on plans'   : '';
                $devices[] = $type->type == self::SPECIFIC_TYPES['DEVICE'] ? $type_amount.' off on devices' : '';
                $sims[]    = $type->type == self::SPECIFIC_TYPES['SIM']    ? $type_amount.' off on sims'    : '';
                $addons[]  = $type->type == self::SPECIFIC_TYPES['ADDON']  ? $type_amount.' off on addons'  : '';
            }
            return [
                'details' => implode('<br>', array_filter(
                    [
                        implode('', $plans), 
                        implode('', $devices), 
                        implode('', $sims), 
                        implode('', $addons), 
                        $planRestriciton ? 'Plan restriction :'.$planRestriciton : null, 
                        $coupon->num_cycles ? 'Cycles: Coupon applies for '. $coupon->num_cycles. ' billing cycles' : 'Cycles: Infinite coupon'
                    ], 
                'strlen'))
            ];
        } elseif ($coupon->class == Coupon::CLASSES['APPLIES_TO_SPECIFIC_PRODUCT']) {
            $couponProducts = $coupon->couponProducts;
            $plans   = [];
            $devices = [];
            $sims   = [];
            $addons = [];
            foreach ($couponProducts as $product) {
                $amount = $product->amount;
                $amount =  $isPercentage == false ? '$'.$amount : $amount.'%';
                $plans[]   = $product->product_type == self::SPECIFIC_TYPES['PLAN']   ? $amount.' off on plan '. $product->plan->name   : '';
                $devices[] = $product->product_type == self::SPECIFIC_TYPES['DEVICE'] ? $amount. ' off on device '. $product->device->name : '';
                $sims[]    = $product->product_type == self::SPECIFIC_TYPES['SIM']    ? $amount. ' off on sim '. $product->sim->name    : '';
                $addons[]  = $product->product_type == self::SPECIFIC_TYPES['ADDON']  ? $amount. ' off on addon '. $product->addon->name  : '';
            }

            return [
                'details' => implode('<p></p><br>', array_filter(
                    [
                        implode(', ', array_filter($plans, 'strlen')), 
                        implode(', ', array_filter($devices, 'strlen')), 
                        implode(', ', array_filter($sims, 'strlen')), 
                        implode(', ', array_filter($addons, 'strlen')), 
                        $planRestriciton ? 'Plan restriction :'.$planRestriciton : null, 
                        $coupon->num_cycles ? 'Cycles: Coupon applies for '. $coupon->num_cycles. ' billing cycles' : 'Cycles: Infinite coupon'
                    ], 
                'strlen'))
            ];
        }
    }

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

}