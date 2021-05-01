<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\Adresse\Adresse;
use App\Models\Agence\Agence;
use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClientAuthController extends Controller
{

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
                $token = Auth::user()->createToken('ofalooclient', ['client:permission'])->plainTextToken;
                return [
                    'token' => $token,
                    'status' => 200
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
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logoutNoToken($user_id, $token_id)
    {
        //
        try {
            $user = User::find($user_id);
            if (Auth::check())
                Auth::user()->currentAccessToken()->delete();
            else $user->tokens()->where('id', $token_id)->delete();
            // Auth::logout();
            return [
                'message' => 'User successfully logged out, case: mislogged',
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
            if ($this->isEmailFree($request->email)['status'] === 'free') {

                $user = new User();
                $user->email = $request->email;
                $user->name = $request->name;
                $user->surname = $request->surname;
                $user->phone = $request->phone;
                $user->newsletter = $request->newsletter;
                $user->role = 'client';
                $user->status = 'active';
                $user->picture_link = 'default/user/user.png';
                $user->password = Hash::make($request->password);
                $user->save();
                return [
                    'message' => 'user created',
                    'status' => '201'
                ];
            }
            return [
                'message' => 'user already existed',
                'status' => '200'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'user not created',
                'status' => '500',
                'error' => $th
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
     * Check the specified resource email existence from api call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function isEmailFreeUsApi(Request $request)
    {
        //
        try {
            $agence = Agence::where('email', $request->email)
                ->where('email', '<>', $request->current)
                ->first();
            $user = User::where('email', $request->email)
                ->where('email', '<>', $request->current)
                ->first();
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
        // check role and permissions
        $user = User::find(1);
        return $user->role();
    }


    /**
     * Update the specified resource in storage via post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateInfosApi(Request $request)
    {
        // check role and permissions
        try {
            $user = User::find($request->id);
            if ($request->email !== $request->current)
                $user->email = $request->email;
            $user->name = $request->name;
            $user->surname = $request->surname;
            $user->phone = $request->phone;
            if ($user->adresse_id != null) {
                $adresse = Adresse::find($user->adresse_id);
                $adresse->adresse = $request->adresse;
                $adresse->pays = $request->pays;
                $adresse->ville = $request->ville;
                $adresse->cp =  $request->cp;
                $adresse->save();
            } else {
                $adresse = new Adresse();
                $adresse->adresse = $request->adresse;
                $adresse->pays =  $request->pays;
                $adresse->ville = $request->ville;
                $adresse->cp = $request->cp;
                if ($request->adresse  != null || $request->pays  != null || $request->ville != null || $request->cp  != null) {
                    $adresse->save();
                    $user->adresse_id = $adresse->id;
                }
            }
            $user->save();
            return [
                'message' => 'client updated',
                'status' => '201'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'error',
                'status' => '500'
            ];
        }
    }


    /**
     * Update the specified resource password in storage via post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePwdApi(Request $request)
    {
        // check role and permissions
        try {
            $user = User::find($request->id);
            if ($user->email == $request->email) {
                if (Hash::check($request->password, $user->password))
                    $user->password = Hash::make($request->password);
            }
            return [
                'message' => 'pwd updated',
                'status' => '201'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'error',
                'status' => '500'
            ];
        }
    }

    /**
     * check the specified resource password existence in storage via post.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function pwdExistence(Request $request)
    {
        // check role and permissions
        try {
            $user = User::find($request->id);
            if ($user->email == $request->email) {
                $pwd_existed = Hash::check($request->password, $user->password);
                if ($pwd_existed) {
                    return [
                        'message' => 'password existed',
                        'status' => '200'
                    ];
                }
                return [
                    'message' => 'password do not existed',
                    'status' => '404'
                ];
            }
            return [
                'message' => 'operation denied',
                'status' => '401'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'error',
                'status' => '500'
            ];
        }
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
        // check auth perm and role
        try {
            $user = User::find($id);
            if ($user) {
                $user->deleted = 'yes';
                $user->save();
            }
            return [
                'message' => 'user deleted',
                'status' => '200'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'user not deleted error',
                'status' => '500'
            ];
        }
    }
}
