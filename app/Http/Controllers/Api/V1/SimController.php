<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\OrderGroup;
use App\Model\DeviceToSim;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

/**
 * Class SimController
 *
 * @package App\Http\Controllers\Api\V1
 */
class SimController extends BaseController
{

	/**
	 * SimController constructor.
	 */
	public function __construct()
	{
		$this->content = array();
	}

	/**
	 * @param $og
	 * @param $plan
	 *
	 * @return array
	 */
	private function getSimsByOg($og, $plan)
	{
		$company = \Request::get('company');

		$sims = [];
		$_sims = [];
		$carrier_id = $plan->carrier_id;
		$device_id = $og->device_id;

		if($device_id != 0){

			$device_to_sims =  DeviceToSim::with(['sim'])->where('device_id', $device_id)
			                              ->whereHas('sim', function($query) use ($company, $carrier_id) {
				                              $query->where(
					                              [
						                              ['carrier_id', $carrier_id],
						                              ['company_id', $company->id]
					                              ]
				                              );

			                              })
			                              ->get();
			if (!count($device_to_sims)) {
				$sims = Sim::where([
					['carrier_id', $carrier_id],
					['company_id', $company->id]
				])->get();
			} else {
				foreach ($device_to_sims as $sim) {
					array_push($sims, $sim->sim);
				}
			}
		}else{
			$_sims = Sim::where(
				[
					['carrier_id', $carrier_id],
					['company_id', $company->id],
				]
			)->get();
		}

		foreach ($_sims as $sim) {
			array_push($sims, $sim);
		}

		return $sims;

	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function get(Request $request)
	{

		if(!$request->input('order_hash')){
			return $this->respondError('Invalid order hash');
		}

		$order_hash = $request->input('order_hash');
		$sims = [];
		$plan_id = $request->input('plan_id');

		$order = Order::where('hash', $order_hash)->get();
		if(count($order) < 1){
			return $this->respondError('Invalid order hash');
		}
		$order = $order[0];

		$plan = Plan::where('id', $plan_id)->get();
		if(count($plan) < 1){
			return $this->respondError('Invalid plan');
		}
		$plan = $plan[0];



		$order_group = OrderGroup::with(['order', 'sim', 'device', 'plan'])->where('id', $order->active_group_id)->get();

		if (count($order_group)) {

			$og = $order_group[0];

			if($og->sim_id == 0) {
				$sims = $this->getSimsByOg($og, $plan);

			} else {
				$_sims = $this->getSimsByOg($og, $plan);
				$sims = $_sims;

				// check if og->sim_id is in $_sims. i.e. already selected
				foreach ($_sims as $sim) {
					if($sim->id == $og->sim_id){
						$sim['selected'] = 1;
						array_push($sims, $sim);
					}
				}

			}
		} else {
			$sims = $this->getSimsByCarrierId($plan);
		}
		$this->content = $sims;
		return response()->json($this->content);
	}

	/**
	 * @param Request $request
	 * @param         $id
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function find(request $request, $id)
	{
		$this->content = Sim::find($id);
		return response()->json($this->content);
	}

	/**
	 * @param $plan
	 *
	 * @return array
	 */
	private function getSimsByCarrierId($plan)
	{
		$sims = [];
		$company = \Request::get('company');

		$_sims = Sim::where(
			[
				['carrier_id', $plan->carrier_id],
				['company_id', $company->id],
			]
		)->get();

		foreach ($_sims as $sim) {
			array_push($sims, $sim);
		}
		return $sims;
	}
	public function getUsage(Request $request){
		$validate = $this->validate_input($request->all(), [
			'sim'         => 'required|integer',
			'type'         => 'required|integer',
			'date'         => 'required|integer',
		]);
		if($validate){
			return $validate;
		}

		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => 'http://137.184.122.121/getApi.php',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => array('sim' => $request->sim,'type' => $request->type,'date' => $request->date),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$response = json_decode($response);
		return response()->json($response);
	}
}