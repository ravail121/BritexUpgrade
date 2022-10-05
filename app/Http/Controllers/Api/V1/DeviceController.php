<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Plan;
use App\Model\Device;
use App\Model\DefaultImei;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Class DeviceController
 *
 * @package App\Http\Controllers\Api\V1
 */
class DeviceController extends Controller
{

	/**
	 * DeviceController constructor.
	 */
	public function  __construct(){
		$this->content = array();
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function get(Request $request)
	{
		$company = RequestFacade::get('company')->load([
			'visibleDevices.device_image',
			'visibleDevices.device_to_carrier',
			'visibleSims'
		]);
		
		$visibleDevices = $company->visibleDevices;
		$visibleSims    = $company->visibleSims;

		if ($request->plan_id) {
			$plan      = Plan::find($request->plan_id);
			if($carrierId = $request->input('carrier_id')){
				if ($carrierId != 0) {
					$visibleDevices = $visibleDevices->whereIn('carrier_id', [$carrierId, '0']);
				}
			}
			$visibleDevices = $visibleDevices->where('type', $plan->type)->where('associate_with_plan', '!=', 0);
			$visibleSims    = [];
		}


		$sims = array();

		foreach($visibleSims as $sim){
			array_push($sims, array(
				'id'          => $sim->id,
				'amount'      => $sim->amount_alone,
				'company_id'  => $sim->company_id,
				'carrier_id'  => $sim->carrier_id,
				'name'        => $sim->name,
				'description' => $sim->description,
				'image'       => $sim->image,
				'show'		  => $sim->show,
			));
		}

		$this->content['devices'] = $visibleDevices;
		$this->content['sims']    = $sims;

		return response()->json($this->content);
	}

	/**
	 * @param Request $request
	 * @param         $id
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function find(Request $request, $id){
		$this->content = Device::find($id);
        return response()->json($this->content);
	}


	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getImei(Request $request)
	{
		$imeiNumber = DefaultImei::where('type', $request->plan_type)->get();
		if ($imeiNumber) {
			foreach ($imeiNumber as $default) {

				if ($default->os == 'none') {
					$default->os = 'none (basic phone)';
				}
				$data = [
					'sort'      =>  $default->sort,
					'os'        =>  strtoupper($default->os),
					'imei_code' =>  $default->code,
				];
				array_push($this->content, $data);
			}
		}

		$this->sortOS();
		
		return response()->json($this->content);
	}

	/**
	 * Sort OS
	 */
	protected function sortOS()
	{
		$order = [];

		foreach ($this->content as $key => $row) {
			$order[$key] = $row['sort'];
		}
		array_multisort($order, SORT_ASC, $this->content);

	}
}
