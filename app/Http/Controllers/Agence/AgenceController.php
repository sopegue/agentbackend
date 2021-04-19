<?php

namespace App\Http\Controllers\Agence;

use App\Http\Controllers\Controller;
use App\Models\Adresse\Adresse;
use App\Models\Agence\Agence;
use App\Models\User;
use Illuminate\Http\Request;

class AgenceController extends Controller
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
     * Store a newly created resource in storage from agent.
     *
     * @return \Illuminate\Http\Response
     */
    public function storeFromAgent($agencer, $user_id)
    {
        //
        try {
            $adresse = new Adresse();
            $agence = new Agence();

            $adresse->pays = "Côte d'Ivoire";
            $adresse->ville = $agencer['ville'];
            $adresse->adresse = $agencer['adr'];
            if ($agencer['cp'] != '') {
                $adresse->cp = $agencer['cp'];
            }
            $adresse->save();

            $agence->adresse_id = $adresse->id;
            $agence->user_id = $user_id;
            $agence->email = $agencer['email'];
            $agence->name = $agencer['name'];
            $agence->type = $agencer['type'];
            if ($agencer['fiscal'] != '') {
                $agence->fiscal = $agencer['fiscal'];
            }
            if ($agencer['phone'] != '') {
                $agence->phone = $agencer['phone'];
            }
            $agence->old_agence = $agencer['old_agence'];
            $agence->save();
            return [
                'message' => 'agence added',
                'status' => '201'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'agence not added',
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
     * Check the specified resource email existence.
     *
     * @param  string  $email
     * @return \Illuminate\Http\Response
     */
    public function isEmailFree($email)
    {
        //
        try {
            $user = User::where('email', $email)->firstOrfail();
            $agence = Agence::where('email', $email)->firstOrfail();
            return ['status' => 'taken'];
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
            return ['status' => 'error'];
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
