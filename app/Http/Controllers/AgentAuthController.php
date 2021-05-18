<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Agence\AgenceController;
use App\Http\Controllers\Controller;
use App\Models\Adresse\Adresse;
use App\Models\Agence\Agence;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AgentAuthController extends Controller
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
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        //
        try {
            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials, $request->rememberme)) {
                // Authentication passed...

                // check if user has agent role
                if (Auth::user()->role == "agent") {
                    $token = Auth::user()->createToken('ofalooagent', ['agent:permission'])->plainTextToken;
                    return [
                        'token' => $token,
                        'status' => 200
                    ];
                }
                Auth::logout();
                return [
                    'message' => 'credentials incorrects',
                    'status' => 404
                ];
            }
            return [
                'message' => 'credentials incorrects',
                'status' => 404
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'An error occurs',
                'status' => 500
            ];
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        //
        try {
            Auth::user()->currentAccessToken()->delete();
            // Auth::logout();
            return [
                'message' => 'User successfully logged out',
                'status' => 200
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'An error occurs',
                'status' => 500
            ];
        }
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
