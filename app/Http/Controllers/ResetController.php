<?php

namespace App\Http\Controllers;

use App\Mail\Reinitialisation;
use App\Models\Reset\Reset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ResetController extends Controller
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function verify($email, $hash)
    {
        //
        try {
            $user = User::where('email', $email)->first();
            if ($user) {
                $verif = Reset::where('user_id', $user->id)->first();
                if ($verif) {
                    if (Hash::check($hash, $verif->hash)) {
                        $seconds = Carbon::now()->timestamp - $verif->created_at->timestamp;
                        if ($seconds <= 3600) {
                            return [
                                'status' => '200',
                                'message' => 'verified'
                            ];
                        }
                        return [
                            'status' => '1997',
                            'message' => 'expired'
                        ];
                    } else {
                        return [
                            'status' => '1997',
                            'message' => 'expired'
                        ];
                    }
                } else {
                    return [
                        'status' => '401',
                        'message' => 'forbidden'
                    ];
                }
            }
            return [
                'status' => '404',
                'message' => 'not found'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => '500',
                'error' => "can't generate token"
            ];
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reset($email, $hash, $pwd)
    {
        //
        try {
            $user = User::where('email', $email)->first();
            if ($user) {
                $verif = Reset::where('user_id', $user->id)->first();
                if ($verif) {
                    if (Hash::check($hash, $verif->hash)) {
                        $seconds = Carbon::now()->timestamp - $verif->created_at->timestamp;
                        if ($seconds <= 3600) {
                            if (Hash::check($pwd, $user->password)) {
                                return [
                                    'status' => '302',
                                    'message' => 'same password'
                                ];
                            } else {
                                $user->password = Hash::make($pwd);
                                $user->save();
                                return [
                                    'status' => '200',
                                    'message' => 'verified'
                                ];
                            }
                        }
                        return [
                            'status' => '1997',
                            'message' => 'expired time',
                        ];
                    } else {
                        return [
                            'status' => '1997',
                            'message' => 'expired hash unmatch'
                        ];
                    }
                } else {
                    return [
                        'status' => '401',
                        'message' => 'forbidden'
                    ];
                }
            }
            return [
                'status' => '404',
                'message' => 'not found'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => '500',
                'error' => "can't generate token"
            ];
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function hashes($email)
    {
        //
        try {
            $user = User::where('email', $email)->first();
            if ($user) {
                $verif = Reset::where('user_id', $user->id)->first();
                $newverif = new Reset();
                $newverif->user_id = $user->id;
                $hash = bin2hex(random_bytes(24));
                $newverif->hash = Hash::make($hash);
                if ($verif) {
                    $verif->delete();
                }
                $newverif->save();
                return [
                    'status' => '200',
                    'token' => $hash,
                    'name' => $user->name
                ];
            }
            return [
                'status' => '404',
                'message' => 'not found'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => '500',
                'error' => "can't generate token"
            ];
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendmail(Request $request)
    {
        //
        try {
            // return $request->email;
            Mail::to($request->email)->send(new Reinitialisation($request->mail));
            return [
                'status' => '200',
                'message' => 'message sent'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => '500',
                'message' => 'message not sent'
            ];
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
