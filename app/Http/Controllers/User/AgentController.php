<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Agence\AgenceController;
use App\Http\Controllers\Controller;
use App\Models\Adresse\Adresse;
use App\Models\Agence\Agence;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AgentController extends Controller
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
        try {
            $user = new User();
            $adresse = new Adresse();

            $adresse->pays = $request->agent['pays'];
            if ($request->agent['adresse'] != '') {
                $adresse->adresse = $request->agent['adresse'];
            }
            if ($request->agent['ville'] != '') {
                $adresse->ville = $request->agent['ville'];
            }
            if ($request->agent['cp'] != '') {
                $adresse->cp = $request->agent['cp'];
            }
            $adresse->save();

            $user->adresse_id = $adresse->id;
            $user->email = $request->agent['email'];

            $user->main_email = $request->agence['email_principal'];

            $user->name = $request->agent['name'];
            $user->surname = $request->agent['surname'];
            $user->newsletter = 'no';
            $user->role = 'agent';
            $user->status = 'verifying';
            $user->picture_link = 'default/user/user.png';
            $user->password = Hash::make($request->agent['pwd']);
            if ($request->agent['phone'] != '') {
                $user->phone = $request->agent['phone'];
            }
            $user->save();

            $agenceController = new AgenceController();
            $agence = $agenceController->storeFromAgent($request->agence, $user->id);
            if ($agence['status'] == '201') return [
                'message' => 'agent added',
                'status' => '201'
            ];
            return [
                'message' => 'agent not added',
                'status' => '500'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'error, agent not added',
                'status' => '500'
            ];
        }
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
     * Check the specified resource email existence.
     *
     * @param  string  $email
     * @return \Illuminate\Http\Response
     */
    public function isEmailFree($email)
    {
        //
        try {
            $agence = Agence::where('email', $email)->first();
            $user = User::where('email', $email)->first();
            if ($agence || $user)
                return ['status' => 'taken'];
            else return ['status' => 'free'];
        } catch (\Throwable $th) {
            return ['status' => 'free'];
        }
    }

    /**
     * Check the specified resource email existence from api call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function isEmailFreeApi(Request $request)
    {
        //
        try {
            $agence = Agence::where('email', $request->email)->first();
            $user = User::where('email', $request->email)->first();
            if ($agence || $user)
                return ['status' => 'taken'];
            else return ['status' => 'free'];
            return ['status' => 'taken'];
        } catch (\Throwable $th) {
            return ['status' => 'free'];
        }
    }

    /**
     * Check the specified resource role.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkClientRole()
    {
        //
        $user = User::find(1);
        return $user->role();
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
