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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
            $property->user_id = 3; //Auth::user()->id
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
                'data' => ['status' => '404']
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
                'data' => ['status' => '404']
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
    public function propAgApi($id)
    {
        //
        try {
            return new PropertyCollection(Propertie::where('user_id', $id)->get());
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
    public function viewedApi(Request $request)
    {
        //
        try {
            return new PropertyCollection(Propertie::whereIn('id', $request->viewed)->get());
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
            if ($idso != [])
                $results->whereIn('id', $idso);
            if ($idse != [])
                $results->whereIn('id', $idse);
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
            return $th;
            // return ['message' => 'error, can not retrieve data'];
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

            $unchanged = explode(',', $request->unchanged_files);
            if ($unchanged != [""]) {
                $current = Image::WhereNotIn('file_link', $unchanged)
                    ->where('property_id', $request->id)
                    ->get();
                if (!$current->isEmpty()) {
                    foreach ($current as $key => $value) {
                        Storage::disk('public')->delete($value->file_link);
                        $value->delete();
                    }
                }
            } else {
                $current = Image::where('property_id', $request->id)->get();
                if (!$current->isEmpty()) {
                    foreach ($current as $key => $value) {
                        Storage::disk('public')->delete($value->file_link);
                        $value->delete();
                    }
                }
            }

            if ($request->has('file')) {
                foreach ($request->file as $key => $value) {
                    $image = new Image();
                    $image->property_id = $property->id;
                    // Auth::user()->id instead of 3
                    $path = $value->store('user/3/properties', 'public');
                    $image->file_link = $path;
                    $image->save();
                }
            }

            $principal = Image::where(['property_id' => $request->id, 'principal' => 'yes'])->first();
            if (!$principal) {
                $current = Image::where('property_id', $request->id)->first();
                $current->principal = 'yes';
                $current->save();
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
                'status' => '201'
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
                    $property->save();
                } else if ($as === "unrented") {
                    $property->rent = 'no';
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
