<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class CustomerResource extends Resource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	public function toArray($request)
	{
		return [
			'id'        => $this->id,
			'name'      => $this->full_name,
			'company'   => $this->company_name,
			'hash'      => $this->hash
		];
	}
}
