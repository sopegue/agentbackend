<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
        return User::find($id)->properties_saved()->get();
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
     * Update the specified resource saved list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function savedManage($prop, $what, $id, $email)
    {
        try {
            // check role and permissions
            $user = User::find($id);
            if ($user && Auth::user()->id == $user->id && $user->email == $email) {
                if ($prop == "update_sold") {
                    if ($what == "yes" || $what == "no")
                        $user->retired_sold = $what;
                } else if ($prop == "update_rent") {
                    if ($what == "yes" || $what == "no")
                        $user->retired_rent = $what;
                }
                $user->save();
                return [
                    'message' => 'saved list may be updated if api correct',
                    'status' => '200'
                ];
            }
            return [
                'message' => 'saved list not updated or permission denied',
                'status' => '404'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'property not added',
                'status' => '500',
                'error' => $th
            ];
        }
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
