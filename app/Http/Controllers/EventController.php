<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EventController extends Controller
{
    public function index() {

        $search = request('search');



        if($search) {

            $events = Event::where([
                ['title', 'like', '%'.$search.'%']
            ])->get();

        } else {
            $events = Event::all();
        }

        return view('welcome',['events' => $events, 'search' => $search]);
    }

    public function create(){
        return view('events.create');
    }

    public function contato(){
        $nome = "Lucas";
        return view('contato', ['nome' => $nome]);
    }

    public function produtos(){
        {

            $busca = request('search');

            return view('produtos', ['busca' => $busca]);
        }
    }

    public function produtosid($id = null){
        {
            return view('produtocomid', ['id' => $id]);
        }
    }

    public function store(Request $request){

        $event = new Event;

        $event->title = $request->title;
        $event->description = $request->description;
        $event->city = $request->city;
        $event->date = $request->date;
        $event->private = $request->private;
        $event->items = $request->items;


        // Upload de imagem
        if($request->hasFile('image') && $request->file('image')->isValid()){

            $requestImage = $request->image;
            $extension = $requestImage->extension();

            $imageName = md5($requestImage->GetClientOriginalname() . strtotime("Now")) . "." . $extension;
            $requestImage->move(public_path('img/events'), $imageName);

            $event->image = $imageName;
        };

        $user = auth()->user();
        $event->user_id = $user->id;
        $event->save();

        return redirect ('/')->with('msg', 'Evento criado com sucesso!');
    }

    public function show($id){

        $event = Event::findOrFail($id);

        $user = auth()->user();
        $hasUserJoined = false;

        if($user) {
            $userEvents = $user->eventsAsParticipant->toArray();

            foreach($userEvents as $userEvent){
                if($userEvent['id'] == $id) {
                    $hasUserJoined = true;
                }
            }
        }
        $eventOwner = User::where('id', $event->user_id)->first()->toArray();

        return view('events.show', ['event' => $event, 'eventOwner' => $eventOwner, 'hasUserJoined' => $hasUserJoined]);
    }

    public function dashboard() {

        $user = auth()->user();

        $events = $user->events;

        $eventsAsParticipant = $user->eventsAsParticipant;

        return view('events.dashboard', ['events' => $events, 'eventsasparticipant' => $eventsAsParticipant]);
    }

    public function destroy($id) {

        // Event::findOrFail($id)->delete();
        Event::findOrFail($id)->delete();

        return redirect('/dashboard')->with('msg', 'Evento excluido com sucesso!');

    }

    public function edit($id) {
        $user = auth()->user();

        $event = Event::findOrFail($id);

        if($user->id != $event->user_id) {
            return redirect('/dashboard');
        }

        return view('events.edit', ['event' => $event]);
    }

    public function update(Request $request, $image){

        $data = $request->all();

        if($request->hasFile('image') && $request->file('image')->isValid()){

            $requestImage = $request->image;
            $extension = $requestImage->extension();

            $imageName = md5($requestImage->GetClientOriginalname() . strtotime("Now")) . "." . $extension;
            $requestImage->move(public_path('img/events'), $imageName);

            $data['image'] = $imageName;
        };

        Event::findOrFail($request->id)->update($data);

        return redirect('/dashboard')->with('msg', 'Evento editado com sucesso!');
    }

    public function joinEvent($id) {

        $user = auth()->user();

        $user->eventsAsParticipant()->attach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Sua presença está confirmada no evento ' . $event->title);
    }

    public function leaveEvent($id){
        $user = auth()->user();

        $user->eventsAsParticipant()->detach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Você saiu com sucesso do evento ' . $event->title);
    }
}
