<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use App\Mail\EmailVerification;
use App\Models\User;
use App\Models\Verification\Verification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class VerificationController extends Controller
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
            Mail::to($request->email)->send(new EmailVerification($request->mail));
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
                if ($user->email_verified_at == null) {
                    $verif = Verification::where('user_id', $user->id)->first();
                    if ($verif) {
                        if (Hash::check($hash, $verif->hash)) {
                            $seconds = Carbon::now()->timestamp - $verif->created_at->timestamp;
                            if ($seconds <= 3600) {
                                $user->email_verified_at = Carbon::now()->timestamp;
                                $user->save();
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
