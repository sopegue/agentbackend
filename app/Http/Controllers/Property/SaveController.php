<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\Save;
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
    public function savedManage(Request $request)
    {
        try {
            // check role and permissions
            $user = User::find($request->id);
            if ($user && $user->email == $request->email) {
                if ($request->prop == "update_sold") {
                    if ($request->what == "yes" || $request->what == "no")
                        $user->retired_sold = $request->what;
                } else if ($request->prop == "update_rent") {
                    if ($request->what == "yes" || $request->what == "no")
                        $user->retired_rent = $request->what;
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
     * Update the specified resource saved list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function saveProp(Request $request)
    {
        try {
            // check role and permissions
            $save = User::where(['property_id' => $request->prop, 'user_id' => $request->user])->first();
            if (!$save) {
                $newsave = new Save();
                $newsave->property_id = $request->prop;
                $newsave->user_id = $request->user;
                $newsave->save();
                return [
                    'message' => 'prop saved',
                    'status' => '200'
                ];
            }
            return [
                'message' => 'prop already saved or bad user',
                'status' => '200'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'property not saved',
                'status' => '500',
                'error' => $th
            ];
        }
    }

    /**
     * Update the specified resource saved list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function unsaveProp(Request $request)
    {
        try {
            // check role and permissions
            $save = User::where(['property_id' => $request->prop, 'user_id' => $request->user])->first();
            if ($save) {
                $save->delete();
                return [
                    'message' => 'prop saved deleted',
                    'status' => '200'
                ];
            }
            return [
                'message' => 'can not unsaved what is not saved',
                'status' => '200'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'property not saved',
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
