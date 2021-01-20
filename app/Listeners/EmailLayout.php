<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Model\EmailTemplate;

/**
 * Trait EmailLayout
 *
 * @package App\Listeners
 */
trait EmailLayout
{
	/**
	 * @param EmailTemplate $emailTemplate
	 * @param               $data
	 * @param               $dataRow
	 *
	 * @return array
	 */
	public function makeEmailLayout(EmailTemplate $emailTemplate, $data, $dataRow)
    {
        if(filter_var($emailTemplate->to, FILTER_VALIDATE_EMAIL)){
                $email = $emailTemplate->to;
            }else{
                $email = $data->email;
            }

            $names = array();
            $column = preg_match_all('/\[(.*?)\]/s', $emailTemplate->body, $names);
            $table = null;
            $replaceWith = null;

            foreach ($names[1] as $key => $name) {
                $dynamicField = explode("__",$name);
                if($table != $dynamicField[0]){
                    if(isset($dataRow[$dynamicField[0]])){
                        $data = $dataRow[$dynamicField[0]]; 
                        $table = $dynamicField[0];
                    }else{
                        unset($names[0][$key]);
                        continue;
                    }
                }
                $replaceWith[$key] = isset($data->{$dynamicField[1]})?$data->{$dynamicField[1]}:$names[0][$key];
            }
        $body = $emailTemplate->body($names[0], $replaceWith);

        return [
            'email'  => $email,
            'body'   => $body
        ];
    }

	/**
	 * @param $fields
	 * @param $data
	 * @param $body
	 *
	 * @return string|string[]
	 */
	public function addFieldsToBody($fields, $data, $body)
    {
       return str_replace($fields, $data, $body);
    }

	/**
	 * @param $date
	 *
	 * @return string
	 */
	public function getDateFormated($date)
    {
        if($date){
            return Carbon::parse($date)->format('m/d/Y');   
        }
        return 'NA';
    }

}
