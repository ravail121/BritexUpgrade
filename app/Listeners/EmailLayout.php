<?php

namespace App\Listeners;


use App\Model\EmailTemplate;

trait EmailLayout 
{
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
                $replaceWith[$key] = $data->{$dynamicField[1]} ?: $names[0][$key];
            }
        $body = $emailTemplate->body($names[0], $replaceWith);

        return [
            'email'  => $email,
            'body'   => $body
        ];
    }

    public function addFieldsToBody($fields, $data, $body)
    {
       return str_replace($fields, $data, $body);
    }

}
