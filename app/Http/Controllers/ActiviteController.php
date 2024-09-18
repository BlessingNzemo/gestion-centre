<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Form;
use App\Models\Hashtag;
use App\Models\Odcuser;
use App\Models\Activite;
use App\Models\Candidat;
use App\Models\Presence;
use App\Models\Categorie;
use App\Models\TypeEvent;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CourseraUsage;
use App\Models\CourseraMember;
use App\Models\CandidatAttribute;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\CourseraSpecialisation;
use Yajra\DataTables\Facades\DataTables;

class ActiviteController extends Controller
{

    public function index()
    {

        $activites = Activite::latest()->paginate(100);
        $typeEvent = TypeEvent::all();
        $categories = Categorie::all();
        $hashtag = Hashtag::all();


        try {

            foreach ($activites as $activite) {
                $message = Carbon::today();
                $startDate = Carbon::parse($activite->start_date);
                $endDate = Carbon::parse($activite->end_date);
                if ($message >= $startDate && $message <= $endDate) {

                    $activite->message = 'En cours';
                } elseif ($message < $startDate) {


                    $differenceInDays = $startDate->diffInDays($message);
                    $activite->message = "Jour j$differenceInDays ";
                } else {
                    $activite->message = 'Terminée';
                }
            }

            return view('activites.index', compact('activites', 'typeEvent', 'categories', 'hashtag',));
        } catch (\Exception $th) {
            return back()->withErrors(['error' => "An error occurred while creating the activity. $th"])->withInput();
        }
    }

    public function create()
    {
        $activites = Activite::all();
        $typeEvent = TypeEvent::all();
        $categories = Categorie::all();
        $forms = Form::all();
        $hashtag = Hashtag::all();

        return view('activites.create', compact('activites', 'typeEvent', 'categories', 'hashtag', 'forms'));
    }

    public function store(Request $request)
    {

        $validatedData = $request->validate(
            [
                'title' => 'required|string|max:255',
                'categories' => 'required|exists:categories,id',
                'contents' => 'required|string',
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
                'form_id' => 'nullable|exists:forms,id',
                'location' => 'nullable|string|max:255',
                'hashtags' => 'nullable|array',
                'hashtags.*' => 'exists:hashtags,id',
                'typeEvent' => 'nullable|array',


                'typeEvent.*' => 'exists:type_events,id',
                'day' => 'required',
            ],

            [
                'title.required' => 'The title is required.',
                'day.required' => 'The title is required.',
                'title.string' => 'The title must be a string.',
                'title.max' => 'The title may not be greater than 255 characters.',
                'categories.required' => 'The category is required.',
                'categories.exists' => 'The selected category is invalid.',
                'contents.required' => 'The content is required.',
                'contents.string' => 'The content must be a string.',
                'startDate.required' => 'The start date is required.',
                'startDate.date' => 'The start date must be a valid date.',
                'endDate.required' => 'The end date is required.',
                'endDate.date' => 'The end date must be a valid date.',
                'endDate.after_or_equal' => 'The end date must be a date after or equal to the start date.',
                'form_id.exists' => 'The selected form is invalid.',
                'creator.max' => 'The creator may not be greater than 255 characters.',
                'location.string' => 'The location must be a string.',
                'location.max' => 'The location may not be greater than 255 characters.',
                'hashtags.array' => 'The hashtags must be an array.',
                'hashtags.*.exists' => 'The selected hashtag is invalid.',
                'typeEvent.array' => 'The type events must be an array.',
                'typeEvent.*.exists' => 'The selected type event is invalid.',
            ]
        );



        try {
            $activites = Activite::create([
                'title' => $validatedData['title'],
                'categorie_id' => $validatedData['categories'],
                'content' => $validatedData['contents'],
                'start_date' => $validatedData['startDate'],
                'end_date' => $validatedData['endDate'],
                'publishStatus' => false,
                'showInSlider' => false,
                "form" => $request->form,
                "thumbnail_url" => $request->thumbnailURL,
                'miniatureColor' => false,
                'showInCalendar' => false,
                'liveStatus' => false,
                'bookASeat' => false,
                'isEvents' => false,
                'creator' => false,
                'location' => $validatedData['location'],
                'number_day ' => $validatedData['day'],
            ]);







            if ($request->has('hashtags')) {
                $activites->hashtag()->attach($validatedData['hashtags']);
            }

            if ($request->has('typeEvent')) {
                $activites->typEvent()->attach($validatedData['typeEvent']);
            }

            return redirect()->route('activites.index')->with('success', 'Activite created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => "An error occurred while creating the activity. $e"])->withInput();
        }
    }


    public function show(Activite $activite)
    {

        // Trouver l'Activite correspondant et récupérer le champ '_id'
        $id = $activite->id;
        $activite_Id = $activite->_id;

        $odcusers = Odcuser::all(['id', '_id']);
        //recuperer les presents  et la date
        $presences = Presence::orderBy('id')->get();
        //recuperer les presents  et la date
        $presences = Presence::orderBy('id')->get();
        $test = Presence::all();



        // Récupérer les candidats liés à cette activité
        $candidats = Candidat::where('activite_id', $id)->with(['odcuser', 'candidat_attribute'])->get();

        if (count($candidats) > 0) {
            $candidatsData = [];
            $labels = [];
            foreach ($candidats as $candidat) {
                $candidatArray = $candidat->toArray();
                if ($candidat->candidat_attribute) {
                    foreach ($candidat->candidat_attribute as $attribute) {
                        $candidatArray[$attribute->label] = $attribute->value;
                        if (!in_array($attribute->label, $labels)) {
                            $labels[] = $attribute->label;
                        }
                    }
                }
                $candidatsData[] = $candidatArray;
            }
        } else {
            $candidatsData = null;
            $labels = null;
        }


        $participants = Candidat::where('activite_id', $id)->where('status', 'accept')->select('id', 'odcuser_id', 'activite_id', 'status')->with(['odcuser', 'candidat_attribute'])->get();

        $etiquettes = [];
        $participantsData = [];

        if (count($participants) > 0) {
            foreach ($participants as $participant) {
                $participantArray = $participant->toArray();

                if ($participant->candidat_attribute) {
                    foreach ($participant->candidat_attribute as $attribute) {
                        $participantArray[$attribute->label] = $attribute->value;
                        if (!in_array($attribute->label, $etiquettes)) {
                            $etiquettes[] = $attribute->label;
                        }
                    }
                }
                $participantsData[] = $participantArray;
            }
        } else {
            $participantsData = null;
            $etiquettes = null;
        }
        //recuperer les presents  et la date

        $presences = Presence::orderBy('id')->get();
        $activite = Activite::findOrFail($id);
        $dateDebut = Carbon::parse($activite->start_date);
        $dateFin = Carbon::parse($activite->end_date);

        $dates = [];
        $fullDates = [];
        for ($date = $dateDebut; $date->lte($dateFin); $date->addDay()) {
            if (!$date->isWeekend()) {
                $dates[] = $date->format('d-m');
                $fullDates[] = $date->format('Y-m-d');
            }
        }
        // dd($dates);
        $countdate = count($dates);

        $candidats_on_activity = Candidat::where('activite_id', $id)->where('status', 'accept')->with('odcuser')->get();
        $data = [];
        $pres = Presence::all()->pluck('candidat_id');
        foreach ($candidats_on_activity as $candidat) {
            $pres = Presence::where('candidat_id', $candidat->id)->get();
            try {
                $date = $pres->toArray();
                $presence_date = [];
                foreach ($pres->toArray() as $key => $date) {
                    $presence_date[] = date('Y-m-d', strtotime($date['date']));
                }
                $candidatsPresence = $candidat->toArray();
                $candidatsPresence['date'] = $presence_date;
                $candidatsPresence['candidat_id'] = $candidat->id;
                $candidatsPresence['odcuser'] = $candidat->odcuser;

                $data[] = $candidatsPresence;
            } catch (\Throwable $th) {
                //echo $th->getMessage();
            }
        }

        $id = $activite->id;







        $datachart = DB::table('candidats')
            ->join('odcusers', 'candidats.odcuser_id', '=', 'odcusers.id')
            ->join('activites', 'candidats.activite_id', '=', 'activites.id')
            ->where('candidats.activite_id', $id) // Utilisez l'ID de l'activité spécifique
            ->selectRaw("
        activites.title as activite,
        SUM(CASE WHEN odcusers.gender = 'female' THEN 1 ELSE 0 END) as total_filles,
        SUM(CASE WHEN odcusers.gender = 'male' THEN 1 ELSE 0 END) as total_garcons,
        COUNT(*) as total_candidats
    ")
            ->groupBy('activites.title')
            ->get();

        return view('activites.show', compact('participantsData', 'datachart', 'candidatsData', 'labels', 'data', 'activite', 'id', 'candidats', 'activite_Id', 'odcusers', 'fullDates', 'dates', 'countdate', 'presences'));
    }


    public function edit(Activite $activite)
    {
        $typeEvent = TypeEvent::all();
        $categories = Categorie::has('articles')->get();
        $hashtag = Hashtag::has('activite')->get();
        return view('activites.edit', compact('activite', 'typeEvent', 'categories', 'hashtag'));
    }

    public function update(Request $request, Activite $activite)
    {
        $validatedData = $request->validate(
            [
                'title' => 'required|string|max:255',
                'categories' => 'required|exists:categories,id',
                'contents' => 'required|string',
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
                'form_id' => 'nullable|exists:forms,id',
                'location' => 'nullable|string|max:255',
                'hashtags' => 'nullable|array',
                'hashtags.*' => 'exists:hashtags,id',
                'typeEvent' => 'nullable|array',
                'typeEvent.*' => 'exists:type_events,id',
                'thumbnailURL' => 'nullable|url',


            ],

            [
                'title.required' => 'The title is required.',
                'title.string' => 'The title must be a string.',
                'title.max' => 'The title may not be greater than 255 characters.',
                'categories.required' => 'The category is required.',
                'categories.exists' => 'The selected category is invalid.',
                'contents.required' => 'The content is required.',
                'contents.string' => 'The content must be a string.',
                'startDate.required' => 'The start date is required.',
                'startDate.date' => 'The start date must be a valid date.',
                'endDate.required' => 'The end date is required.',
                'endDate.date' => 'The end date must be a valid date.',
                'endDate.after_or_equal' => 'The end date must be a date after or equal to the start date.',
                'form_id.exists' => 'The selected form is invalid.',
                'creator.max' => 'The creator may not be greater than 255 characters.',
                'location.string' => 'The location must be a string.',
                'location.max' => 'The location may not be greater than 255 characters.',
                'hashtags.array' => 'The hashtags must be an array.',
                'hashtags.*.exists' => 'The selected hashtag is invalid.',
                'typeEvent.array' => 'The type events must be an array.',
                'typeEvent.*.exists' => 'The selected type event is invalid.',
            ]
        );

        try {
            $activite->update([
                'title' => $validatedData['title'],
                'categorie_id' => $validatedData['categories'],
                'contents' => $validatedData['contents'],
                'start_date' => $validatedData['startDate'],
                'end_date' => $validatedData['endDate'],
                'location' => $validatedData['location'],
                "thumbnail_url" => $request->thumbnailURL,
            ]);



            if ($request->has('hashtags')) {
                $activite->hashtag()->sync($validatedData['hashtags']);
            }

            if ($request->has('typeEvent')) {
                $activite->typEvent()->sync($validatedData['typeEvent']);
            }




            return redirect()->route('activites.index')
                ->with('success', 'Activite updated successfully.');
        } catch (\Exception $th) {
            return back()->withErrors(['error' => "An error occurred while creating the activity. $th"])->withInput();
        }
    }

    public function destroy(Activite $activite)
    {
        $url = env('API_URL');
        try {


            $activite->delete();
            $id = $activite->_id;

            // Supprimer la liaison avec le formulaire de l'activité sur l'API

            $requette = Http::timeout(1000)
                ->post("$url/events/delete/$id");
            return redirect()->route('activites.index')
                ->with('success', 'Activite deleted successfully.');
        } catch (\Exception $th) {
            return back()->withErrors(['error' => "An error occurred while creating the activity. $th"])->withInput();
        }
    }

    public function encours()
    {
        $today = Carbon::today();
        $activites = Activite::where('start_date', '<=', $today)->where('end_date', '>=', $today)->get();
        return view('encours', compact('activites'));
    }

    public function chartActivity(Request $request)
    {
        $participationData = Candidat::selectRaw("SUM(CASE WHEN odcusers.gender = 'male' THEN 1 ELSE 0 END) as hommes")
            ->selectRaw("SUM(CASE WHEN odcusers.gender = 'female' THEN 1 ELSE 0 END) as femmes")
            ->join('odcusers', 'odcusers.id', '=', 'candidats.odcuser_id')
            ->whereBetween('candidats.created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->first();

        $hommes = $participationData->hommes;
        $femmes = $participationData->femmes;


        $data = Activite::selectRaw("DATE_FORMAT(start_date, '%Y-%m-%d') as date, count(*) as aggregate, title,id")
            ->whereDate('start_date', '>=', now()->subDays(30))
            ->groupBy('date', 'title', 'id')
            ->paginate(4);
        $location = Auth::user()->location;

        $activites = Activite::all();
        $activityForWeekend = Activite::whereRaw('(start_date BETWEEN ? AND ?)
                                      OR (end_date BETWEEN ? AND ?)
                                      OR (start_date <= ? AND end_date >= ?)', [
            Carbon::now()->subDays(Carbon::now()->dayOfWeek),
            Carbon::now()->addDays(5 - Carbon::now()->dayOfWeek),
            Carbon::now()->subDays(Carbon::now()->dayOfWeek),
            Carbon::now()->addDays(5 - Carbon::now()->dayOfWeek),
            Carbon::now()->subDays(Carbon::now()->dayOfWeek),
            Carbon::now()->addDays(5 - Carbon::now()->dayOfWeek),
        ])->get();


        $month = 4;
        $year = 2024;

        $requestActivityperiode = Activite::selectRaw("date_format(createdAt, '%Y-%m-%d') as date, count(*) as aggregate")
            ->whereMonth('createdAt', $month)
            ->whereYear('createdAt', $year)
            ->groupBy('date')
            ->get();

        $user = Odcuser::all();


        return view('dashboard', compact('activites', 'user', 'data', 'hommes', 'femmes', "activityForWeekend", 'requestActivityperiode'));
    }

    public function coursera_rapport()
    {
        $membersMonths = CourseraMember::selectRaw('MONTH(join_date) as month, COUNT(*) as count')
            ->whereYear('join_date', date('Y'))
            ->groupBy('month')->orderBy('month')->get();

        $labels = [];
        $mydata = [];
        $colors = [
            '#FF6384',
            '#36A2EB',
            '#c9625b',
            '#cf72fa',
            '#f83d3d',
            '#fa43cc',
            '#ADD478',
            '#fcc737',
            '#ADD813',
            '#36d4fc',
            '#c92daf',
            '#FF7890'
        ];

        for ($i = 1; $i <= 12; $i++) {
            $month = date('F', mktime(0, 0, 0, $i, 1));
            $count = 0;
            foreach ($membersMonths as $member) {
                if ($member->month == $i) {
                    $count = $member->count;
                    break;
                }
            }

            array_push($labels, $month);
            array_push($mydata, $count);
        }

        $datasets = [
            [
                'label' => "member join by month",
                'data' => $mydata,
                'backgroundColor' => $colors
            ]
        ];



        $coursera_members = DB::table('coursera_members')
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when member_state = 'MEMBER' then 1 end) as members")
            ->selectRaw("count(case when member_state = 'INVITED' then 1 end) as invites")
            ->first();


        $coursera_usages = DB::table('coursera_usages')
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when completed = 'Yes' then 1 end) as completed")
            ->selectRaw("count(case when completed = 'No' then 1 end) as noCompleted")
            ->first();

    

        $specialisations = CourseraSpecialisation::where('specialization_completion_time', '<', now())->count();

        $specialisationsCount = DB::table('coursera_specialisations')                    
                     ->select('specialisaton_name')->count();

        $completedSpecialisations = CourseraSpecialisation::where('completed', 'Yes')->count();
        $completedUsages = CourseraUsage::where('completed', 'Yes')->count();
        $getCompletedUsages = CourseraUsage::where('completed', 'Yes')->paginate(25);
        $uncompletedSpecialisations = CourseraSpecialisation::where('completed', 'NO')->count();
        $uncompletedUsages = CourseraUsage::where('completed', 'NO')->count();
        $deletedUsages = CourseraUsage::where('removed_from_program', 'Yes')->count();


        $usagesEncourrs = DB::table('coursera_usages')
            ->where('class_start_time', '<=', now())
            ->where('class_end_time', '>=', now())->count();


        return view('coursera.coursera_rapports', compact('datasets', 
                                                            'labels', "coursera_members", 
                                                            "specialisationsCount", 
                                                            "coursera_usages", 
                                                            "specialisations",
                                                            "completedSpecialisations",
                                                            "uncompletedSpecialisations",
                                                            "deletedUsages",
                                                            "completedUsages",
                                                            "uncompletedUsages",
                                                            "getCompletedUsages"
                                                            ));

        
    }


    public function showInCalendar(Request $request, $id)
    {

        $status = Activite::find($id);
        $url = env('API_URL');
        if ($status->show_in_calendar == false) {
            $status->show_in_calendar = true;
            $status->save();
            try {

                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/calendar/$id", $check);

                return redirect()->route('activites.index')->with('success', 'Activite created successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        } else {
            $status->show_in_calendar = false;
            $status->save();

            try {
                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/calendar/$id", $check);
                return redirect()->route('activites.index')->with('success', 'Activite created successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        }
    }

    public function bookInSeat(Request $request, $id)
    {

        $status = Activite::find($id);
        $url = env('API_URL');
        if ($status->book_in_seat == false) {
            $status->book_in_seat = true;
            $status->save();
            try {

                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/form/$id", $check);

                return redirect()->route('activites.index')
                    ->with('success', 'Form active  successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        } else {
            $status->show_in_calendar = false;
            $status->save();

            try {
                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/form/$id", $check);

                return redirect()->route('activites.index')
                    ->with('success', 'Form Desactive  successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        }
    }

    public function isEvent(Request $request, $id)
    {

        $status = Activite::find($id);
        $url = env('API_URL');
        if ($status->show_in_calendar == false) {
            $status->show_in_calendar = true;
            $status->save();
            try {

                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/calendar/$id", $check);

                return redirect()->route('activites.index')
                    ->with('success', 'isEvent active  successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        } else {
            $status->show_in_calendar = false;
            $status->save();

            try {
                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/calendar/$id", $check);

                return redirect()->route('activites.index')
                    ->with('success', 'isEvent Desactive  successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        }
    }

    public function status(Request $request, $id)
    {

        $status = Activite::find($id);
        $url = env('API_URL');
        if ($status->status == false) {
            $status->status = true;
            $status->save();
            try {

                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/status/calendar/$id", $check);

                return redirect()->route('activites.index')
                    ->with('success', 'Status active  successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        } else {
            $status->status = false;
            $status->save();

            try {
                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/status/$id", $check);

                return redirect()->route('activites.index')
                    ->with('success', 'Activite Desactive in calendar successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        }
    }

    public function showInSlider(Request $request, $id)
    {

        $status = Activite::find($id);
        $url = env('API_URL');
        if ($status->show_in_slider == false) {
            $status->show_in_slider = true;
            $status->save();
            try {

                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/slide/$id", $check);

                return redirect()->route('activites.index')
                    ->with('success', 'Activite active in slide successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        } else {
            $status->show_in_slider = false;
            $status->save();

            try {
                $id = $status->_id;
                $valbool = boolval($request->status);
                $check = [
                    "action" => $valbool
                ];

                $requette = Http::timeout(1000)
                    ->post("$url/events/slide/$id", $check);

                return redirect()->route('activites.index')
                    ->with('success', 'Activite Desactive in slide successfully.');
            } catch (\Exception $th) {
                return response()->json(['success' => false, 'message' => 'Request failed', 'error' => $th->getMessage()], 500);
            }
        }
    }


    public function getActivites(Request $request)
    {
        $query = Activite::query();


        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        $activites = $query->get();



        return response()->json($activites);
    }

    public function weeekActivites()
    {


        return view('dashboard', compact('week'));
    }

    public function search(Request $request)
    {
        $searchTerm = $request->input('search');
        $activites = Activite::where('title', 'LIKE', "%{$searchTerm}%")
            ->take(4)
            ->latest()
            ->get();




        return response()->json($activites);
    }

    public function getActivitiesData(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        $query = Activite::selectRaw("date_format(createdAt, '%Y-%m-%d') as date, count(*) as aggregate")
            ->whereYear('createdAt', $year);


        if ($month && $month !== 'all') {
            $query->whereMonth('createdAt', $month);
        }

        $activities = $query->groupBy('date')->get();

        return response()->json($activities);
    }
}
