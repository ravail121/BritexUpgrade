<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\SubscriptionAddon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class ProcessController extends BaseController
{
    public function processSubscriptions()
    {
    	$this->processDowngrades();
    	$this->processAddonRemovals();

    	return $this->respond(['message' => 'Processed Successfully']);
    }


    protected function processDowngrades()
    {
    	$subscriptions = Subscription::todayEqualsDowngradeDate()->get();

    	foreach ($subscriptions as $subscription) {
    		$subscription->update([
	    		'upgrade_downgrade_status' => 'for-downgrade',
	    		'old_plan_id'    		   => $subscription->plan_id,
	    		'plan_id'        		   => $subscription->new_plan_id,
	    		'new_plan_id'    		   => null,
	    		'downgrade_date' 		   => '',
    		]);

    		// Need to add row in 'subscription_log' with category="downgrade-" => NOT CLEARED
    	}
    	return true;
    }

    protected function processAddonRemovals()
    {
    	SubscriptionAddon::todayEqualsRemovalDate()->update([
    		'status' => 'for-removal',
    	]);
    	return true;
    }
}
