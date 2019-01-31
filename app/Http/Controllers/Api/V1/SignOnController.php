<?php

namespace App\Http\Controllers\Api\V1;

use Auth;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SignOnController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function signOn(Request $request)
    {
        $email=$request->email;
        if (filter_var($email, FILTER_VALIDATE_INT)) {
            $mail = Customer::whereId($email)->get(['email']);
            if(!isset($mail[0])){
                return null;
            }
            $email=$mail[0]['email'];

        }     
        $userdata = array(
            'email'  => $email,
            'password' => $request->password
        );

        
        if(Auth::validate($userdata))
        {
            $user = Customer::whereEmail($email)->get(['id','hash']);
            // return $user[0];
            return response()->json($user[0]);
        }
        else{
            return null;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
