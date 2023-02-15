<?php

namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;
use App\Http\Controllers\Api\V1\Traits\ApiConnect;
use App\Http\Controllers\Api\V1\Traits\BulkOrderTrait;

class ValidZipCode implements Rule
{
	use BulkOrderTrait, ApiConnect;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
	    return $this->isZipCodeValidInUltra($request->get('zip_code'), $requestCompany);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is not valid.';
    }
}
