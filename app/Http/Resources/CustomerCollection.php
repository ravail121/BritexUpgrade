<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
	/**
	 * Transform the resource collection into an array.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	public function toArray($request)
	{
		return [
			'status'            => 'success',
			'data'              => CustomerResource::collection($this->collection),
			'current'           => $this->currentPage(),
			'per_page'          => $this->perPage(),
			'total'             => $this->total()
		];
	}
}
