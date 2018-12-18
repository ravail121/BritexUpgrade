<?php

namespace App\Support\Responses;

trait APIResponse {

    /**
     * Generate API response
     * @param  string $status  success || fail
     * @param  array $content data or errors
     * @param  string $message Message Detail
     * @param  string $error Error
     * @return array
     */
    protected function generateResponse($status, $message = null, $content = null, $errorCode = null, $httpCode = null)
    {
        $r = compact('status');
        $key = $status == 'success' ? 'data' : 'errors';
        if($content) $r[$key] = $content;
        if($message) $r += compact('message');
        if($errorCode) $r['error_code'] = $errorCode;
        return $httpCode ? response()->json($r, $httpCode) : response()->json($r);
    }

    /**
     * Generate Success Response
     * @param  array  $data
     * @param  string $message
     * @return array
     */
    protected function successResponse($message=null, $data=null, $httpCode = 200)
    {
        // if(!$httpCode) $httpCode = 200;
        return $this->generateResponse('success', $message, $data, null, $httpCode);
    }

    /**
     * Generate Fail Response
     * @param  array  $data
     * @param  string $message
     * @return array
     */
    protected function failResponse($message=null, $errors=null, $errorCode = null, $httpCode = 401)
    {
        // if(!$httpCode) $httpCode = 401;
        return $this->generateResponse('fail', $message, $errors, $errorCode, $httpCode);
    }
}