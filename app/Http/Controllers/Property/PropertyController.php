<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Resources\MultiOptions\MultiOptionsResource;
use App\Http\Resources\Property\PropertyAgentResource;
use App\Http\Resources\Property\PropertyCollection;
use App\Http\Resources\Property\PropertyResource;
use App\Models\Adresse\Adresse;
use App\Models\Agence\Agence;
use App\Models\Image\Image;
use App\Models\Link\Link;
use App\Models\Options\Multioption;
use App\Models\Options\Option;
use App\Models\Property\Propertie;
use App\Models\Property\Save;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        // return new PropertyCollection(Propertie::all());
        // Cache::forget('properties');
        return Cache::rememberForever('properties', function () {
            return new PropertyCollection(Propertie::all());
        });
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // check if(Auth::user() has agent role and can add property before adding)
        try {
            $adresse = new Adresse();
            $adresse->adresse = $request->adresse;
            $adresse->ville = $request->ville;
            $adresse->cp = $request->cp;
            $adresse->save();

            $property = new Propertie();
            $property->user_id = Auth::user()->id;
            $property->adresse_id = $adresse->id;
            $property->type = $request->type;
            $property->taille = $request->taille;
            $property->price_fixed = $request->prix_fix;
            $property->price_min = $request->prix_min;
            $property->price_max = $request->prix_max;
            $property->negociable = $request->negociable;
            $property->proposition = $request->proposition;
            $property->location_freq = $request->frequence_location;
            $property->percentage_part = $request->percentage_part;
            $property->informations = $request->infos;
            $property->bed = $request->pieces;
            $property->bath = $request->bath;
            $property->garage = $request->garage;
            $property->save();

            foreach ($request->file as $key => $value) {
                $data = explode(',', $request->desc);
                $image = new Image();
                $image->property_id = $property->id;
                $image->desc = array_key_exists($key, $data) &&  $data[$key] !== "" ? $data[$key]  : null;
                // Auth::user()->id instead of 3
                $path = $value->store('user/' . Auth::user()->id . '/properties', 'azure');
                $image->file_link = $path;
                $image->index = $key;
                if ($request->principale == $key)
                    $image->principal = 'yes';
                $image->save();
            }

            if ($request->has('yt') || $request->has('tiktok') || $request->has('insta') || $request->has('fb')) {
                $link = new Link();
                $link->property_id = $property->id;
                if ($request->has('yt'))
                    $link->yt_link = $request->yt;
                if ($request->has('tiktok'))
                    $link->tiktok_link = $request->tiktok;
                if ($request->has('insta'))
                    $link->insta_link = $request->insta;
                if ($request->has('fb'))
                    $link->fb_link = $request->fb;
                $link->save();
            }

            if ($request->indoor != null) {
                $indoor = explode(',', $request->indoor);
                $options = Option::whereIn('title', $indoor)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            }
            if ($request->outdoor != null) {
                $outdoor = explode(',', $request->outdoor);
                $options = Option::whereIn('title', $outdoor)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            }
            if ($request->energy != null) {
                $energy = explode(',', $request->energy);
                $options = Option::whereIn('title', $energy)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            }

            Cache::forget('properties');
            return [
                'message' => 'property added',
                'status' => '201',
                'id' => $property->id
            ];
        } catch (\Throwable $th) {
            Propertie::destroy($property->id);
            return $th;
            return [
                'message' => 'property not added',
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
        try {
            return new PropertyResource(Propertie::findOrFail($id));
        } catch (\Throwable $th) {
            return [
                'message' => 'not found',
                'data' => []
            ];
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showAuth($id)
    {
        //
        try {
            return new PropertyResource(Propertie::findOrFail($id));
        } catch (\Throwable $th) {
            return [
                'message' => 'not found',
                'data' => []
            ];
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showOwn($id)
    {
        // check if auth user before show
        try {
            return new PropertyAgentResource(Propertie::findOrFail($id));
        } catch (\Throwable $th) {
            return [
                'message' => 'not found',
                'data' => []
            ];
        }
    }

    /**
     * Show the specified resource by type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showByType(Request $request)
    {
        //
        return new PropertyCollection(Propertie::where('type', $request->type)
            ->orderByDesc('visites')
            ->take(7)
            ->get());
    }

    /**
     * Show the specified resource by type skip 6.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showByTypeSkip(Request $request)
    {
        //
        return new PropertyCollection(Propertie::where('type', $request->type)
            ->orderByDesc('visites')
            ->skip(6)
            ->take(6)
            ->get());
    }

    /**
     * Show the specified resource by filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function visitApi($id)
    {
        //
        try {
            $property = Propertie::findOrFail($id);
            $property->visites = $property->visites + 1;
            $property->save();
            Cache::forget('properties');
            return $property->visites;
        } catch (\Throwable $th) {
            //throw $th;
            return 0;
        }
    }

    /**
     * Show the specified resource by filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function propAgApi($id, $exclude)
    {
        //
        try {
            return new PropertyCollection(Propertie::where('user_id', $id)
                ->where('id', '<>', $exclude)
                ->orderByDesc('created_at')
                ->take(10)
                ->get());
        } catch (\Throwable $th) {
            //throw $th;
            return [
                "message" => "an error occurs",
                "status" => "500"
            ];
        }
    }

    /**
     * Show the specified resource by filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function propAgCountApi($id, $exclude)
    {
        //
        try {
            return Propertie::where('user_id', $id)
                ->where('id', '<>', $exclude)
                ->count();
        } catch (\Throwable $th) {
            //throw $th;
            return [
                "message" => "an error occurs",
                "status" => "500"
            ];
        }
    }

    /**
     * Show the specified resource by filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function propVilleFirstApi($ville, $id)
    {
        //
        try {
            return Propertie::whereHas('adresse', function (Builder $query) use ($ville, $id) {
                $query->where('ville', $ville);
            })->where('id', '<>', $id)
                ->count();
        } catch (\Throwable $th) {
            return 0;
        }
    }

    /**
     * Show the specified resource by filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function propVilleApi($ville, $id)
    {
        //
        try {
            return new PropertyCollection(Propertie::whereHas('adresse', function (Builder $query) use ($ville, $id) {
                $query->where('ville', $ville);
            })->where('id', '<>', $id)
                ->orderByDesc('visites')
                ->take(10)
                ->get());
        } catch (\Throwable $th) {
            return $th;
            return [
                "message" => "an error occurs",
                "status" => "500"
            ];
        }
    }

    /**
     * Show the specified resource by filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function viewedApi(Request $request)
    {
        //
        try {
            $c = collect();
            foreach ($request->viewed as $key => $value) {
                $view = Propertie::find($value);
                if ($view)
                    $c->add($view);
            }
            return new PropertyCollection($c);
        } catch (\Throwable $th) {
            //throw $th;
            return [
                "message" => "an error occurs",
                "status" => "500"
            ];
        }
    }

    /**
     * Increment property visites.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showByFilter(Request $request)
    {
        //
    }

    /**
     * Show the specified resource by type skip 6.
     *
     * @param  string  $key
     * @return \Illuminate\Http\Response
     */
    public function searchKeyApi($key)
    {
        //
        $results = [];
        $presearches = Adresse::whereHas('property', function (Builder $query) use ($key) {
            $query->where('adresse', 'like',  $key . '%');
            $query->orWhere('adresse', 'like',  '%' . $key . '%');
            $query->orWhere('adresse', 'like',  '%' . $key);
            $query->orWhere('ville', 'like',  $key . '%');
            $query->orWhere('ville', 'like',  '%' . $key . '%');
            $query->orWhere('ville', 'like',  '%' . $key);
            $query->orWhereIn('adresse', [$key]);
            $query->orWhereIn('ville', [$key]);
        })->orderByDesc('created_at')
            ->take(20)
            ->get();
        if (!$presearches->isEmpty()) {
            foreach ($presearches as $key => $value) {
                array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
            }
        } else {
            $searches = Adresse::has('property')
                ->selectRaw('*, INSTR(?, `adresse`) as `co`', [$key])
                ->having('co', '>', [0])
                ->get();

            if (!$searches->isEmpty()) {
                foreach ($searches as $key => $value) {
                    array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
                }
            } else {
                $searches = Adresse::has('property')
                    ->selectRaw('*, INSTR(?, `ville`) as `co`', [$key])
                    ->having('co', '>', [0])
                    ->get();

                if (!$searches->isEmpty()) {
                    foreach ($searches as $key => $value) {
                        array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
                    }
                } else {
                    $searches = Adresse::has('property')
                        ->selectRaw('*, levenshtein(?, `adresse`) as `diff`', [$key])
                        ->havingBetween('diff', [0, 4])
                        ->orderBy('diff')
                        ->take(20)
                        ->get()
                        ->reject(function ($value, $key) {
                            return $value->adresse == null;
                        });
                    if (!$searches->isEmpty()) {
                        foreach ($searches as $key => $value) {
                            array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
                        }
                    } else {
                        $villes = Adresse::has('property')
                            ->selectRaw('*, levenshtein(?, `ville`) as `diff`', [$key])
                            ->havingBetween('diff', [0, 4])
                            ->orderBy('diff')
                            ->take(20)
                            ->get()
                            ->reject(function ($value, $key) {
                                return $value->ville == null;
                            });
                        if (!$villes->isEmpty()) {
                            foreach ($villes as $key => $value) {
                                array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
                            }
                        }
                    }
                }
            }
        }
        return
            ["adresse" => $results];
        // return $searches;
        // return new PropertyCollection(Propertie::take(2)->get());
    }


    public function searchKeyApiAgent($key, $id)
    {
        //
        $results = [];
        $ids = [];
        $prop = Propertie::where('user_id', $id)->get();
        if ($prop->isNotEmpty()) {
            foreach ($prop as $key => $value) {
                array_push($ids, $value->adresse_id);
            }
        }
        // return $ids;
        if ($ids != []) {
            $presearches = Adresse::whereIn('id', $ids)
                ->orWhere('adresse', 'like',  $key . '%')
                ->orWhere('adresse', 'like',  '%' . $key . '%')
                ->orWhere('adresse', 'like',  '%' . $key)
                ->orWhere('ville', 'like',  $key . '%')
                ->orWhere('ville', 'like',  '%' . $key . '%')
                ->orWhere('ville', 'like',  '%' . $key)
                ->orWhereIn('adresse', [$key])
                ->orWhereIn('ville', [$key])
                ->orderByDesc('created_at')
                ->take(20)
                ->get();
        } else {
            return
                ["adresse" => $results];
        }
        if (!$presearches->isEmpty()) {
            foreach ($presearches as $key => $value) {
                array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
            }
        } else {
            $searches = Adresse::whereIn('id', $ids)
                ->selectRaw('*, INSTR(?, `adresse`) as `co`', [$key])
                ->having('co', '>', [0])
                ->get();

            if (!$searches->isEmpty()) {
                foreach ($searches as $key => $value) {
                    array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
                }
            } else {
                $searches = Adresse::whereIn('id', $ids)
                    ->selectRaw('*, INSTR(?, `ville`) as `co`', [$key])
                    ->having('co', '>', [0])
                    ->get();

                if (!$searches->isEmpty()) {
                    foreach ($searches as $key => $value) {
                        array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
                    }
                } else {
                    $searches = Adresse::whereIn('id', $ids)
                        ->selectRaw('*, levenshtein(?, `adresse`) as `diff`', [$key])
                        ->havingBetween('diff', [0, 4])
                        ->orderBy('diff')
                        ->take(20)
                        ->get()
                        ->reject(function ($value, $key) {
                            return $value->adresse == null;
                        });
                    if (!$searches->isEmpty()) {
                        foreach ($searches as $key => $value) {
                            array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
                        }
                    } else {
                        $villes = Adresse::whereIn('id', $ids)
                            ->selectRaw('*, levenshtein(?, `ville`) as `diff`', [$key])
                            ->havingBetween('diff', [0, 4])
                            ->orderBy('diff')
                            ->take(20)
                            ->get()
                            ->reject(function ($value, $key) {
                                return $value->ville == null;
                            });
                        if (!$villes->isEmpty()) {
                            foreach ($villes as $key => $value) {
                                array_push($results, ["adresse" => $value->adresse, "ville" => $value->ville]);
                            }
                        }
                    }
                }
            }
        }
        return
            ["adresse" => $results];
        // return $searches;
        // return new PropertyCollection(Propertie::take(2)->get());
    }


    /**
     * Show the specified resource by type skip 6.
     *
     * @param  string  $key
     * @return \Illuminate\Http\Response
     */
    public function favPropApi($key, $sort)
    {
        //
        try {

            $results = Propertie::query();
            $house = [
                'Studio',
                'Maison',
                'Appartement',
                'Villa',
                'Haut-Standing',
                'Bureau',
                'Magasin',
                'Terrain',
            ];
            if (in_array($sort, $house)) {
                $house = $this->__unshift($house, $sort);
            }
            $sort = str_replace("-", " ", $sort);
            $ids = [];
            $user = User::find($key);
            $prop = $user->properties_saved;
            if ($prop->isNotEmpty()) {
                foreach ($prop as $key => $value) {
                    array_push($ids, $value->id);
                }
            } else {
                return ["data" => []];
            }
            if ($ids != []) {
                if ($user->retired_sold == "yes" && $user->retired_rent == "no") {
                    $results->whereIn('id', $ids)
                        ->where('sold', '<>', 'yes');
                }
                if ($user->retired_rent == "yes" && $user->retired_sold == "no") {
                    $results->whereIn('id', $ids)
                        ->where('rent', '<>', 'yes');
                }
                if ($user->retired_rent == "yes" && $user->retired_sold == "yes") {
                    $results->whereIn('id', $ids)
                        ->where('rent', '<>', 'yes')
                        ->where('sold', '<>', 'yes');
                }
                if ($user->retired_sold == "no" && $user->retired_rent == "no") {
                    $results->whereIn('id', $ids);
                }
                if ($sort == "Le plus pertinent") {
                    $results->orderByDesc('visites');
                } else
                if ($sort == "Le plus ancien") {
                    $results->orderBy('created_at');
                } else
                if ($sort == "Le plus récent") {
                    $results->orderByDesc('created_at');
                } else
                if ($sort == "Prix croissant") {
                    $results->orderBy('price_fixed');
                } else
                if ($sort == "Prix décroissant") {
                    $results->orderByDesc('price_fixed');
                } else {
                    $res = implode("','", $house);
                    // return 'FIELD(type, ' . $res . ')';
                    $results->orderByRaw("FIELD(type,  '$res')");
                }

                return new PropertyCollection($results->paginate());
            } else  return ["data" => []];
        } catch (\Throwable $th) {
            // return $th;
            return ["data" => []];
        }
        // return $searches;
        // return new PropertyCollection(Propertie::take(2)->get());
    }

    function __unshift(&$array, $value)
    {
        $key = array_search($value, $array);
        if ($key) unset($array[$key]);
        array_unshift($array, $value);
        return $array;
    }

    /**
     * Show the specified resource by filter with search.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchApi(Request $request)
    {
        //

        try {

            $results = Propertie::query();
            if ($request->has('q')) {
                if ($request->q == "super-agent") {
                    $us_id = [];
                    $user =  Agence::where('super', 'yes')->pluck('user_id');
                    if ($user->isNotEmpty()) {
                        foreach ($user as $key => $value) {
                            # code...
                            array_push($us_id, $value);
                        }
                    }
                    if ($us_id != [])
                        $results->whereIn('user_id', $us_id);
                    else {
                        return ["data" => []];
                    }
                }
            }
            if ($request->has('ofloowa')) {
                try {
                    $try = Propertie::where('user_id', $request->ofloowa)->firstOrFail();
                    $results->where('user_id', $request->ofloowa);
                } catch (\Throwable $th) {
                    //throw $th;
                    return ["data" => []];
                }
            }
            $what = $request->filter['what'];
            $sort = $request->sort;
            $search = $request->search;
            $idprop = [];
            $idag = [];
            if ($what == 'Acheter' || $what == 'Louer') {
                if ($search != null && $search != "") {
                    $adresse1 = Adresse::whereHas('property', function (Builder $query) use ($search) {
                        $query->where('adresse', 'like',  $search . '%');
                        $query->orWhere('adresse', 'like',  '%' . $search . '%');
                        $query->orWhere('adresse', 'like',  '%' . $search);
                        $query->orWhere('ville', 'like',  $search . '%');
                        $query->orWhere('ville', 'like',  '%' . $search . '%');
                        $query->orWhere('ville', 'like',  '%' . $search);
                        $query->orWhereIn('adresse', [$search]);
                    })->get();
                    if (!$adresse1->isEmpty()) {
                        foreach ($adresse1 as $key => $value) {
                            array_push($idprop, $value->id);
                        }
                    } else {

                        $searches = Adresse::has('property')
                            ->selectRaw('*, INSTR(?, `adresse`) as `co`', [$search])
                            ->having('co', '>', [0])
                            ->get();

                        if (!$searches->isEmpty()) {
                            foreach ($searches as $key => $value) {
                                array_push($idprop, $value->id);
                            }
                        } else {
                            $searches = Adresse::has('property')
                                ->selectRaw('*, INSTR(?, `ville`) as `co`', [$search])
                                ->having('co', '>', [0])
                                ->get();

                            if (!$searches->isEmpty()) {
                                foreach ($searches as $key => $value) {
                                    array_push($idprop, $value->id);
                                }
                            } else {
                                $searches = Adresse::has('property')
                                    ->selectRaw('*, levenshtein(?, `adresse`) as `diff`', [$search])
                                    ->havingBetween('diff', [0, 4])
                                    ->get()
                                    ->reject(function ($value, $key) {
                                        return $value->adresse == null;
                                    });
                                if (!$searches->isEmpty()) {
                                    foreach ($searches as $key => $value) {
                                        array_push($idprop, $value->id);
                                    }
                                } else {
                                    $villes = Adresse::has('property')
                                        ->selectRaw('*, levenshtein(?, `ville`) as `diff`', [$search])
                                        ->havingBetween('diff', [0, 4])
                                        ->get()
                                        ->reject(function ($value, $key) {
                                            return $value->ville == null;
                                        });
                                    if (!$villes->isEmpty()) {
                                        foreach ($villes as $key => $value) {
                                            array_push($idprop, $value->id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($idprop != [])
                        $results->whereIn('adresse_id', $idprop);
                    else {
                        return ["data" => []];
                    }
                    if ($results->count() == 0)
                        return ["data" => []];

                    // return "not null";
                }
                // return $results->get();
            } else if ($what == 'Agent') {
                if ($search != null && $search != "") {
                    $ag = Agence::where('name', 'like', $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search)
                        ->get();
                    if (!$ag->isEmpty()) {
                        foreach ($ag as $key => $value) {
                            array_push($idag, $value->user_id);
                        }
                    } else {

                        $searches = Agence::selectRaw('*, INSTR(?, `name`) as `co`', [$search])
                            ->having('co', '>', [0])
                            ->get();

                        if (!$searches->isEmpty()) {
                            foreach ($searches as $key => $value) {
                                array_push($idag, $value->user_id);
                            }
                        } else {
                            $agences = Agence::selectRaw('*, levenshtein(?, `name`) as `diff`', [$search])
                                ->havingBetween('diff', [0, 4])
                                ->get();
                            if (!$agences->isEmpty()) {
                                foreach ($agences as $key => $value) {
                                    array_push($idag, $value->user_id);
                                }
                            }
                        }
                    }

                    if ($idag != [])
                        $results->whereIn('user_id', $idag);
                    else {
                        return ["data" => []];
                    }
                    if ($results->count() == 0)
                        return ["data" => []];
                }
            } else {
                if ($search != null && $search != "") {
                    $adresse1 = Adresse::whereHas('property', function (Builder $query) use ($search) {
                        $query->where('adresse', 'like',  $search . '%');
                        $query->orWhere('adresse', 'like',  '%' . $search . '%');
                        $query->orWhere('adresse', 'like',  '%' . $search);
                        $query->orWhere('ville', 'like',  $search . '%');
                        $query->orWhere('ville', 'like',  '%' . $search . '%');
                        $query->orWhere('ville', 'like',  '%' . $search);
                        $query->orWhereIn('adresse', [$search]);
                    })->get();
                    if (!$adresse1->isEmpty()) {
                        foreach ($adresse1 as $key => $value) {
                            array_push($idprop, $value->id);
                        }
                    } else {
                        $ag = Agence::where('name', 'like', $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search)
                            ->get();
                        if (!$ag->isEmpty()) {
                            foreach ($ag as $key => $value) {
                                array_push($idag, $value->user_id);
                            }
                        } else {

                            $searches = Adresse::has('property')
                                ->selectRaw('*, INSTR(?, `adresse`) as `co`', [$search])
                                ->having('co', '>', [0])
                                ->get();

                            if (!$searches->isEmpty()) {
                                foreach ($searches as $key => $value) {
                                    array_push($idprop, $value->id);
                                }
                            } else {
                                $searches = Adresse::has('property')
                                    ->selectRaw('*, INSTR(?, `ville`) as `co`', [$search])
                                    ->having('co', '>', [0])
                                    ->get();

                                if (!$searches->isEmpty()) {
                                    foreach ($searches as $key => $value) {
                                        array_push($idprop, $value->id);
                                    }
                                } else {

                                    $searches = Agence::selectRaw('*, INSTR(?, `name`) as `co`', [$search])
                                        ->having('co', '>', [0])
                                        ->get();

                                    if (!$searches->isEmpty()) {
                                        foreach ($searches as $key => $value) {
                                            array_push($idag, $value->user_id);
                                        }
                                    } else {
                                        $searches = Adresse::has('property')
                                            ->selectRaw('*, levenshtein(?, `adresse`) as `diff`', [$search])
                                            ->havingBetween('diff', [0, 4])
                                            ->get()
                                            ->reject(function ($value, $key) {
                                                return $value->adresse == null;
                                            });
                                        if (!$searches->isEmpty()) {
                                            foreach ($searches as $key => $value) {
                                                array_push($idprop, $value->id);
                                            }
                                        } else {
                                            $villes = Adresse::has('property')
                                                ->selectRaw('*, levenshtein(?, `ville`) as `diff`', [$search])
                                                ->havingBetween('diff', [0, 4])
                                                ->get()
                                                ->reject(function ($value, $key) {
                                                    return $value->ville == null;
                                                });
                                            if (!$villes->isEmpty()) {
                                                foreach ($villes as $key => $value) {
                                                    array_push($idprop, $value->id);
                                                }
                                            } else {
                                                $agences = Agence::selectRaw('*, levenshtein(?, `name`) as `diff`', [$search])
                                                    ->havingBetween('diff', [0, 4])
                                                    ->get();
                                                if (!$agences->isEmpty()) {
                                                    foreach ($agences as $key => $value) {
                                                        array_push($idag, $value->user_id);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($idprop != [])
                        $results->whereIn('adresse_id', $idprop);
                    else if ($idag != [])
                        $results->whereIn('user_id', $idag);
                    else {
                        return ["data" => []];
                    }
                    if ($results->count() == 0)
                        return ["data" => []];
                }
            }

            //

            $array = $request->filter['achat_location']['multiple'];
            $achat_loc = [];
            if (in_array("Tous types d'achats", $array))
                $achat_loc = ['Vente totale', 'Vente partielle'];
            if (in_array("Tous types de locations", $array))
                $achat_loc = ['Location totale', 'Location partielle'];
            if (in_array("Achat total", $array) || in_array("Acheter une part", $array)) {
                if (in_array("Achat total", $array))
                    array_push($achat_loc, 'Vente totale');
                if (in_array("Acheter une part", $array))
                    array_push($achat_loc, 'Vente partielle');
            }
            if (in_array("Location totale", $array) || in_array("Louer une part", $array)) {
                if (in_array("Location totale", $array))
                    array_push($achat_loc, 'Location totale');
                if (in_array("Louer une part", $array))
                    array_push($achat_loc, 'Location partielle');
            }
            if ($achat_loc == []) {
                $achat_loc = ['Vente totale', 'Vente partielle', 'Location totale', 'Location partielle'];
            }

            // 

            $p_min = 0;
            $p_max = 0;
            if ($request->filter['search_price']["min"] != 0 || $request->filter['search_price']["max"] != 0) {
                if ($request->filter['search_price']["min"] != 0) {
                    $p_min = $request->filter['search_price']["min"];
                }
                if ($request->filter['search_price']["max"] != 0) {
                    $p_max = $request->filter['search_price']["max"];
                }
            }

            if ($request->filter['type_loc']["tous"] == "Tous types") {
                $type_loc = [];
            } else {
                $type_loc = [$request->filter['type_loc']["tous"]];
            }

            $part = $request->filter['part']["tous"];

            $date = $request->filter['date']["date"];
            $when = $request->filter['date']["tous"];



            //
            $prop = $request->filter['property']["multiple"];
            if (in_array("Tous types", $prop) || $prop == [] || in_array("Tous types de propriétés", $prop)) {
                $prop = [
                    'Studio',
                    'Maison',
                    'Appartement',
                    'Villa',
                    'Haut-Standing',
                    'Bureau',
                    'Magasin',
                    'Terrain',
                ];
            }


            $ava = $request->filter['availability']["multiple"];
            if (in_array("Tous types", $ava) || $ava == []) {
                $ava = [];
            } else {
                $ava = ['no_sold', 'no_rent'];
            }


            // 91
            $garage = $request->filter['garage']["tous_search"];
            $bath =  $request->filter['bath']["tous"];
            $bed = $request->filter['bed']["tous_search"];
            // 91


            $t_min = 0;
            $t_max = 0;
            if ($request->filter['taille']["min"] != 0 || $request->filter['taille']["max"] != 0) {
                if ($request->filter['taille']["min"] != 0) {
                    $t_min = $request->filter['taille']["min"];
                }
                if ($request->filter['taille']["max"] != 0) {
                    $t_max = $request->filter['taille']["max"];
                }
            }


            $indoor = $request->filter['indoor']['multiple'];
            if (in_array("Tous types", $indoor) || $indoor == []) {
                $indoor = [];
                $idsi = [];
            } else {
                $options = Option::whereIn('title', $indoor)->get();
                $idsi = [];
                foreach ($options as $key => $value) {
                    array_push($idsi, $value->id);
                }
                $multioptions = Multioption::whereIn('option_id', $idsi)->get();
                $idsi = [];
                if (!$multioptions->isEmpty()) {
                    foreach ($multioptions as $key => $value) {
                        array_push($idsi, $value->property_id);
                    }
                }
            }


            $outdoor = $request->filter['outdoor']['multiple'];
            if (in_array("Tous types", $outdoor) || $outdoor == []) {
                $outdoor = [];
                $idso = [];
            } else {
                $options = Option::whereIn('title', $outdoor)->get();
                $idso = [];
                foreach ($options as $key => $value) {
                    array_push($idso, $value->id);
                }
                $multioptions = Multioption::whereIn('option_id', $idso)->get();
                $idso = [];
                if (!$multioptions->isEmpty()) {
                    foreach ($multioptions as $key => $value) {
                        array_push($idso, $value->property_id);
                    }
                }
            }


            $energy = $request->filter['energy']['multiple'];
            if (in_array("Tous types", $energy) || $energy == []) {
                $energy = [];
                $idse = [];
            } else {
                $options = Option::whereIn('title', $energy)->get();
                $idse = [];
                foreach ($options as $key => $value) {
                    array_push($idse, $value->id);
                }
                $multioptions = Multioption::whereIn('option_id', $idse)->get();
                $idse = [];
                if (!$multioptions->isEmpty()) {
                    foreach ($multioptions as $key => $value) {
                        array_push($idse, $value->property_id);
                    }
                }
            }

            $results->whereIn('proposition', $achat_loc);
            $results->whereIn('type', $prop);
            $results->where('garage', '>=', $garage)
                ->where('bath', '>=', $bath)
                ->where('bed', '>=', $bed);

            $has_loc = ((in_array("Location totale", $achat_loc) || in_array("Location partielle", $achat_loc)) && $type_loc != []);
            // return $has_loc;
            if ($has_loc)
                $results->whereIn('location_freq', $type_loc);

            if ($p_min != 0 && $p_max != 0) {
                $results
                    ->whereBetween('price_fixed', [$p_min, $p_max]);
            } else if ($p_min == 0 && $p_max != 0) {
                $results
                    ->where('price_fixed', '<=', $p_max);
            } else if ($p_max == 0 && $p_min != 0) {
                $results
                    ->where('price_fixed', '>=', $p_min);
            }

            if ($t_min != 0 && $t_max != 0) {
                $results
                    ->whereBetween('taille', [$t_min, $t_max]);
            } else if ($t_min == 0 && $t_max != 0) {
                $results
                    ->where('taille', '<=', $t_max);
            } else if ($t_max == 0 && $t_min != 0) {
                $results
                    ->where('taille', '>=', $t_min);
            }

            $has_part = (in_array("Location partielle", $achat_loc) || in_array("Vente partielle", $achat_loc));
            if ($has_part)
                $results->where('percentage_part', '>=', $part);

            // do for date if has_loc

            if ($ava != [])
                $results->where('sold', 'no')
                    ->where('rent', 'no');

            if ($idsi != [])
                $results->whereIn('id', $idsi);
            else if ($indoor != []) return ["data" => []];
            if ($idso != [])
                $results->whereIn('id', $idso);
            else if ($outdoor != []) return ["data" => []];
            if ($idse != [])
                $results->whereIn('id', $idse);
            else if ($energy != []) return ["data" => []];
            if ($sort == 'Le plus pertinent')
                $results->orderByDesc('visites');
            if ($sort == 'Prix croissant')
                $results->orderBy('price_fixed');
            if ($sort == 'Prix décroissant')
                $results->orderByDesc('price_fixed');
            if ($sort == 'Le plus récent')
                $results->orderByDesc('created_at');
            if ($sort == 'Le plus ancien')
                $results->orderBy('created_at');


            return new PropertyCollection($results->paginate());
        } catch (\Throwable $th) {
            // return $th;
            return [
                'message' => 'error, can not retrieve data',
                'data' => [],
                'status' => '500'
            ];
        }
    }
    public function searchApiAgent(Request $request)
    {

        try {

            $results = Propertie::query();
            $idprop = [];
            $results->where('user_id', $request->user);

            if (!empty($request['query'])) {
                // search
                if (
                    array_key_exists("search", $request['query'])
                ) {
                    $search = is_array($request['query']['search']) ? $request['query']['search'][0] : $request['query']['search'];
                } else $search = null;
                //
                if (
                    array_key_exists("tri", $request['query'])
                ) {
                    $sort = is_array($request['query']['tri']) ? $request['query']['tri'][0] : $request['query']['tri'];
                } else $sort = 'plus-recent';
                //
                if (
                    array_key_exists("t_type", $request['query'])
                ) {
                    $t_type = is_array($request['query']['t_type']) ? $request['query']['t_type'] : [$request['query']['t_type']];
                    for ($i = 0; $i < count($t_type); $i++) {
                        # code...
                        $t_type[$i] = str_replace("--", " ", $t_type[$i]);
                    }
                } else $t_type = ['all'];
                //
                if (
                    array_key_exists("t_prop", $request['query'])
                ) {
                    $t_prop = is_array($request['query']['t_prop']) ? $request['query']['t_prop'] : [$request['query']['t_prop']];
                } else $t_prop = ['all'];
                // return $t_prop;
                //
                if (
                    array_key_exists("t_carac", $request['query'])
                ) {
                    $t_carac = is_array($request['query']['t_carac']) ? $request['query']['t_carac'] : [$request['query']['t_carac']];
                    for ($i = 0; $i < count($t_carac); $i++) {
                        # code...
                        $t_carac[$i] = str_replace("--", " ", $t_carac[$i]);
                    }
                } else $t_carac = ['all'];
                //
                if (
                    array_key_exists("t_dispo", $request['query'])
                ) {
                    $t_dispo = is_array($request['query']['t_dispo']) ? $request['query']['t_dispo'] : [$request['query']['t_dispo']];
                } else $t_dispo = ['all'];
                //
                if (
                    array_key_exists("bed", $request['query'])
                ) {
                    $req = $request['query']['bed'];
                    $bed = is_numeric($req) ? is_int($req) ? $req : (int)$req : -1;
                } else $bed = -1;
                // return $bed;

                if (
                    array_key_exists("p_min", $request['query'])
                ) {
                    $req = $request['query']['p_min'];
                    $p_min = is_numeric($req) ? is_int($req) ? $req : (int)$req : -1;
                } else $p_min = -1;

                if (
                    array_key_exists("p_max", $request['query'])
                ) {
                    $req = $request['query']['p_max'];
                    $p_max = is_numeric($req) ? is_int($req) ? $req : (int)$req : -1;
                } else $p_max = -1;

                //

                if (
                    array_key_exists("s_min", $request['query'])
                ) {
                    $req = $request['query']['s_min'];
                    $s_min = is_numeric($req) ? is_int($req) ? $req : (int)$req : -1;
                } else $s_min = -1;

                if (
                    array_key_exists("s_max", $request['query'])
                ) {
                    $req = $request['query']['s_max'];
                    $s_max = is_numeric($req) ? is_int($req) ? $req : (int)$req : -1;
                } else $s_max = -1;

                // 


                if ($search != null) {
                    if ($search != "") {
                        $adresse1 = Adresse::whereHas('property', function (Builder $query) use ($search) {
                            $query->where('adresse', 'like',  $search . '%');
                            $query->orWhere('adresse', 'like',  '%' . $search . '%');
                            $query->orWhere('adresse', 'like',  '%' . $search);
                            $query->orWhere('ville', 'like',  $search . '%');
                            $query->orWhere('ville', 'like',  '%' . $search . '%');
                            $query->orWhere('ville', 'like',  '%' . $search);
                            $query->orWhereIn('adresse', [$search]);
                        })->get();
                        if (!$adresse1->isEmpty()) {
                            foreach ($adresse1 as $key => $value) {
                                array_push($idprop, $value->id);
                            }
                        } else {

                            $searches = Adresse::has('property')
                                ->selectRaw('*, INSTR(?, `adresse`) as `co`', [$search])
                                ->having('co', '>', [0])
                                ->get();

                            if (!$searches->isEmpty()) {
                                foreach ($searches as $key => $value) {
                                    array_push($idprop, $value->id);
                                }
                            } else {
                                $searches = Adresse::has('property')
                                    ->selectRaw('*, INSTR(?, `ville`) as `co`', [$search])
                                    ->having('co', '>', [0])
                                    ->get();

                                if (!$searches->isEmpty()) {
                                    foreach ($searches as $key => $value) {
                                        array_push($idprop, $value->id);
                                    }
                                } else {
                                    $searches = Adresse::has('property')
                                        ->selectRaw('*, levenshtein(?, `adresse`) as `diff`', [$search])
                                        ->havingBetween('diff', [0, 4])
                                        ->get()
                                        ->reject(function ($value, $key) {
                                            return $value->adresse == null;
                                        });
                                    if (!$searches->isEmpty()) {
                                        foreach ($searches as $key => $value) {
                                            array_push($idprop, $value->id);
                                        }
                                    } else {
                                        $villes = Adresse::has('property')
                                            ->selectRaw('*, levenshtein(?, `ville`) as `diff`', [$search])
                                            ->havingBetween('diff', [0, 4])
                                            ->get()
                                            ->reject(function ($value, $key) {
                                                return $value->ville == null;
                                            });
                                        if (!$villes->isEmpty()) {
                                            foreach ($villes as $key => $value) {
                                                array_push($idprop, $value->id);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ($idprop != [])
                            $results->whereIn('adresse_id', $idprop);
                        else {
                            return ["data" => []];
                        }
                        if ($results->count() == 0)
                            return ["data" => []];
                    }
                }
                if ($t_type != ['all']) {
                    $results->whereIn('proposition', $t_type);
                }
                if ($t_prop != ['all']) {
                    $results->whereIn('type', $t_prop);
                }
                $mop = [];
                $op = [];
                if ($t_carac != ['all']) {

                    $options = Option::whereIn('title', $t_carac)->get();
                    foreach ($options as $key => $value) {
                        array_push($op, $value->id);
                    }
                    $multioptions = Multioption::whereIn('option_id', $op)->get();

                    if (!$multioptions->isEmpty()) {
                        foreach ($multioptions as $key => $value) {
                            array_push($mop, $value->property_id);
                        }
                    }
                }
                if ($mop != [])
                    $results->whereIn('id', $mop);
                else if ($t_carac != ['all']) return ["data" => []];

                if ($t_dispo != ['all']) {
                    if (in_array("Vendue(s)", $t_dispo)) {
                        if (in_array("Louée(s)", $t_dispo)) {
                            $results->where('sold', 'yes')
                                ->orWhere('rent', 'yes');
                        } else
                            $results->where('sold', 'yes');
                    }
                    if (in_array("Louée(s)", $t_dispo)) {
                        if (in_array("Vendue(s)", $t_dispo)) {
                            $results->where('rent', 'yes')
                                ->orWhere('sold', 'yes');
                        } else
                            $results->where('rent', 'yes');
                    }
                } else {
                    $results->where('sold', 'no')
                        ->where('rent', 'no');
                }
                //
                if ($p_min > -1 && $p_max > -1) {
                    $results
                        ->whereBetween('price_fixed', [$p_min, $p_max]);
                } else if ($p_min == -1 && $p_max != -1) {
                    $results
                        ->where('price_fixed', '<=', $p_max);
                } else if ($p_max == -1 && $p_min != -1) {
                    $results
                        ->where('price_fixed', '>=', $p_min);
                }

                if ($s_min > -1 && $s_max > -1) {
                    $results
                        ->whereBetween('taille', [$s_min, $s_max]);
                } else if ($s_min == -1 && $s_max != -1) {
                    $results
                        ->where('taille', '<=', $s_max);
                } else if ($s_max == -1 && $s_min != -1) {
                    $results
                        ->where('taille', '>=', $s_min);
                }
                $results->where('bed', '>=', $bed);
                if ($sort == 'prix-croissant')
                    $results->orderBy('price_fixed');
                if ($sort == 'prix-decroissant')
                    $results->orderByDesc('price_fixed');
                if ($sort == 'plus-recent')
                    $results->orderByDesc('created_at');
                if ($sort == 'plus-ancien')
                    $results->orderBy('created_at');
            } else {
                $results->orderByDesc('created_at');
            }
            return new PropertyCollection($results->paginate());
        } catch (\Throwable $th) {
            return $th;
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
        // check if(Auth::user() and check if user owned property
        // before update)
        try {
            $property = Propertie::find($request->id);
            $adresse = $property->adresse;
            $adresse->adresse = $request->adresse;
            $adresse->ville = $request->ville;
            $adresse->cp = $request->cp;
            $adresse->save();

            $property->adresse_id = $adresse->id;
            $property->type = $request->type;
            $property->taille = $request->taille;
            $property->price_fixed = $request->prix_fix;
            $property->price_min = $request->prix_min;
            $property->price_max = $request->prix_max;
            $property->negociable = $request->negociable;
            $property->proposition = $request->proposition;
            $property->location_freq = $request->frequence_location;
            $property->percentage_part = $request->percentage_part;
            $property->informations = $request->infos;
            $property->bed = $request->pieces;
            $property->bath = $request->bath;
            $property->garage = $request->garage;
            $property->save();

            foreach ($request->file as $key => $value) {
                $image = new Image();
                $image->property_id = $property->id;
                // Auth::user()->id instead of 3
                $path = $value->store('user/3/properties', 'public');
                $image->file_link = $path;
                if ($request->principale == $key)
                    $image->principal = 'yes';
                $image->save();
            }

            if ($request->has('yt') || $request->has('tiktok') || $request->has('insta') || $request->has('fb')) {
                $link = new Link();
                $link->property_id = $property->id;
                if ($request->has('yt'))
                    $link->yt_link = $request->yt;
                if ($request->has('tiktok'))
                    $link->tiktok_link = $request->tiktok;
                if ($request->has('insta'))
                    $link->insta_link = $request->insta;
                if ($request->has('fb'))
                    $link->fb_link = $request->fb;
                $link->save();
            }

            if ($request->indoor != null) {
                $indoor = explode(',', $request->indoor);
                $options = Option::whereIn('title', $indoor)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            }
            if ($request->outdoor != null) {
                $outdoor = explode(',', $request->outdoor);
                $options = Option::whereIn('title', $outdoor)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            }
            if ($request->energy != null) {
                $energy = explode(',', $request->energy);
                $options = Option::whereIn('title', $energy)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            }

            Cache::forget('properties');

            return [
                'message' => 'property added',
                'status' => '201'
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
     * Update the specified resource in storage via post call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateApi(Request $request)
    {
        // check role and permissions
        try {
            $property = Propertie::find($request->id);
            $adresse = $property->adresse;
            $adresse->adresse = $request->adresse;
            $adresse->ville = $request->ville;
            $adresse->cp = $request->cp;
            $adresse->save();

            $property->type = $request->type;
            $property->taille = $request->taille;
            $property->price_fixed = $request->prix_fix;
            $property->price_min = $request->prix_min;
            $property->price_max = $request->prix_max;
            $property->negociable = $request->negociable;
            $property->proposition = $request->proposition;
            $property->location_freq = $request->frequence_location;
            $property->percentage_part = $request->percentage_part;
            $property->informations = $request->infos;
            $property->bed = $request->pieces;
            $property->bath = $request->bath;
            $property->garage = $request->garage;
            $property->save();

            $indexes = [];
            $data = explode(',', $request->desc);
            foreach ($data as $key => $value) {
                array_push($indexes, $key);
                // return 'file' . $key;
                $filing = 'file' . $key;
                if ($request->hasFile($filing)) {
                    $current = Image::where('index', $key)
                        ->where('property_id', $request->id)
                        ->first();
                    $id = Auth::user()->id;
                    if ($current) {
                        // modification
                        Storage::disk('azure')->delete($current->file_link);
                        $path = $request->file($filing)->store('user/' . $id . '/properties', 'azure');
                        $current->file_link = $path;
                        $current->desc = array_key_exists($key, $data) &&  $data[$key] !== "" ? $data[$key]  : null;
                        if ($request->principale == $key) {
                            $pr = Image::where('principal', 'yes')->first();
                            if ($pr) {
                                $pr->principal = 'no';
                                $pr->save();
                            }
                            $current->principal = 'yes';
                        }
                        $current->save();
                    } else {
                        // return $request->file($filing);

                        $image = new Image();
                        $image->property_id = $property->id;
                        $image->desc = array_key_exists($key, $data) &&  $data[$key] !== "" ? $data[$key]  : null;
                        $path = $request->file($filing)->store('user/' . $id . '/properties', 'azure');
                        $image->file_link = $path;
                        $image->index = $key;
                        if ($request->principale == $key) {
                            $pr = Image::where('principal', 'yes')->first();
                            if ($pr) {
                                $pr->principal = 'no';
                                $pr->save();
                            }
                            $image->principal = 'yes';
                        }
                        $image->save();
                    }
                } else {
                    $current = Image::where('index', $key)
                        ->where('property_id', $request->id)
                        ->first();
                    if ($current) {
                        $current->desc = array_key_exists($key, $data) &&  $data[$key] !== "" ? $data[$key]  : null;
                        if ($request->principale == $key) {
                            if ($current->principal != 'yes') {
                                $pr = Image::where('principal', 'yes')->first();
                                if ($pr) {
                                    $pr->principal = 'no';
                                    $pr->save();
                                }
                                $current->principal = 'yes';
                            }
                        }
                        $current->save();
                    }
                }
            }
            if ($request->has('yt') || $request->has('tiktok') || $request->has('insta') || $request->has('fb')) {
                $linker = Link::where('property_id', $property->id)->first();
                if ($linker) {
                    if ($request->has('yt'))
                        $linker->yt_link = $request->yt;
                    if ($request->has('tiktok'))
                        $linker->tiktok_link = $request->tiktok;
                    if ($request->has('insta'))
                        $linker->insta_link = $request->insta;
                    if ($request->has('fb'))
                        $linker->fb_link = $request->fb;
                    $linker->save();
                } else {
                    $link = new Link();
                    $link->property_id = $property->id;
                    if ($request->has('yt'))
                        $link->yt_link = $request->yt;
                    if ($request->has('tiktok'))
                        $link->tiktok_link = $request->tiktok;
                    if ($request->has('insta'))
                        $link->insta_link = $request->insta;
                    if ($request->has('fb'))
                        $link->fb_link = $request->fb;
                    $link->save();
                }
            } else {
                $linker = Link::where('property_id', $property->id)->first();
                if ($linker)
                    $linker->delete();
            }

            if ($request->indoor != null) {
                foreach ($property->multioptions as $key => $value) {
                    $indoor = Option::find($value->option_id);
                    if ($indoor->type == "indoor") $value->delete();
                }
                $indoor = explode(',', $request->indoor);
                $options = Option::whereIn('title', $indoor)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            } else {
                foreach ($property->multioptions as $key => $value) {
                    $indoor = Option::find($value->option_id);
                    if ($indoor->type == "indoor") $value->delete();
                }
            }


            if ($request->outdoor != null) {
                foreach ($property->multioptions as $key => $value) {
                    $outdoor = Option::find($value->option_id);
                    if ($outdoor->type == "outdoor") $value->delete();
                }
                $outdoor = explode(',', $request->outdoor);
                $options = Option::whereIn('title', $outdoor)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            } else {
                foreach ($property->multioptions as $key => $value) {
                    $outdoor = Option::find($value->option_id);
                    if ($outdoor->type == "outdoor") $value->delete();
                }
            }


            if ($request->energy != null) {
                foreach ($property->multioptions as $key => $value) {
                    $energy = Option::find($value->option_id);
                    if ($energy->type == "energie") $value->delete();
                }
                $energy = explode(',', $request->energy);
                $options = Option::whereIn('title', $energy)->get();
                foreach ($options as $key => $value) {
                    $multioptions = new Multioption();
                    $multioptions->option_id = $value->id;
                    $property->multioptions()->save($multioptions);
                }
            } else {
                foreach ($property->multioptions as $key => $value) {
                    $energy = Option::find($value->option_id);
                    if ($energy->type == "energie") $value->delete();
                }
            }

            Cache::forget('properties');

            return [
                'message' => 'property updated',
                'status' => '201',
                'id' => $property->id
            ];
        } catch (\Throwable $th) {
            return $th;
        }
    }

    /**
     * Mark or unmark as sold or rent.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function soldOrRent($mark, $as, $id)
    {
        // check role and permissions
        try {
            $property = Propertie::find($id);
            if ($mark === "sell") {
                if ($as === "sold") {
                    $property->sold = 'yes';
                    $property->save();
                } else if ($as === "unsold") {
                    $property->sold = 'no';
                    $property->save();
                }
            } else if ($mark === "rent") {
                if ($as === "rented") {
                    $property->rent = 'yes';
                    $property->deb_loc =  Carbon::now()->timestamp;
                    $property->save();
                } else if ($as === "unrented") {
                    $property->rent = 'no';
                    $property->deb_loc =  null;
                    $property->fin_loc =  null;
                    $property->save();
                }
            }
            return [
                'message' => $as,
                'status' => '200'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'no change',
                'status' => '500'
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
        // check role and permissions
        try {
            $property = Propertie::find($id);
            foreach ($property->images as $key => $value) {
                Storage::disk('public')->delete($value->file_link);
                $value->delete();
            }
            $link = Link::where('property_id', $id)->first();
            if ($link)
                $link->delete();

            $multioptions = MultiOption::where('property_id', $id)->get();
            if (!$multioptions->isEmpty()) {
                foreach ($multioptions as $key => $value) {
                    $value->delete();
                }
            }

            $saved = Save::where('property_id', $id)->get();
            if (!$saved->isEmpty()) {
                foreach ($saved as $key => $value) {
                    $value->delete();
                }
            }

            $adresse = $property->adresse_id;
            $property->delete();
            Adresse::destroy($adresse);
            return [
                'message' => 'property deleted',
                'status' => '200'
            ];
        } catch (\Throwable $th) {
            return [
                'message' => 'no deletion',
                'status' => '500'
            ];
        }
    }
}
