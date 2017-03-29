<?php

namespace Taskr\Http\Controllers\Resources;

use \stdClass;
use Illuminate\Support\Facades\Auth;
use Taskr\Http\Controllers\Controller;
use Taskr\Repositories\Tasks;
use Taskr\Repositories\Users;
use Illuminate\Support\Facades\Validator;
use Taskr\Task;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

/**
 * Class TasksController
 *
 * @package Taskr\Http\Controllers\Resources
 */
class TasksController extends Controller
{
    protected $tasksRepo;
    protected $usersRepo;

    public function __construct(Tasks $tasks, Users $users)
    {
        $this->tasksRepo = $tasks;
        $this->usersRepo = $users;
    }

    /*
    |--------------------------------------------------------------------------
    | View Methods
    |--------------------------------------------------------------------------
    |
    | These methods should return views with the appropriate data bind to it.
    |
    */
    public function index()
    {
        $user = new stdClass();
        if (Auth::check()) {
            $id = Auth::id();
            $user = $this->usersRepo->getUser($id);
        }
        $tasks = $this->tasksRepo->all();
        return view('tasks.index', compact('tasks', 'user'));
    }

    public function show(Task $task)
    {
        $user = new stdClass();
        if (Auth::check()) {
            $id = Auth::id();
            $user = $this->usersRepo->getUser($id);
        }
        return view('tasks.show', compact('task', 'user'));
    }

    public function create()
    {
        $generic_tasks = (object)DB::select("select * from generic_tasks");
        return view('tasks.create', compact('generic_tasks'));
    }

    public function edit(Task $task)
    {
        $generic_tasks = (object)DB::select("select * from generic_tasks");
        return view('tasks.edit', compact('task', 'generic_tasks'));
    }

    public function search($query)
    {
        $tasks = Task::where('title','LIKE', "%$query%")->get();
        //->orWhere('created_at','=', "$query")->orWhere('category','LIKE',"%$query%")->orWhere('start_date','=',"$query")->orWhere('end_date','=',"%$query%")->get();
        
        return view('tasks.search')->withDetails($tasks)->withQuery($query);
        // if(count($tasks) > 0){
        //     return view('tasks.search')->withDetails($tasks)->withQuery($query);
        // }
        // else {
        //     return view ('tasks.search')->withMessage('No such task found. Try searching again!');
        // };

        // $keyword= Input::get('q');

        // $task = Task::find($keyword);

        // return view::make('tasks.search', compact('tasks', 'user'));

        // //$task->show();
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Methods
    |--------------------------------------------------------------------------
    |
    | These methods should not return any views but instead process the
    | appropriate actions instead.
    |
    */
    public function update(Request $request, Task $task)
    {
        $this->validateTask($request);
        $this->tasksRepo->update($task->id, 
            $request->input('title'),
            $request->input('description'),
            $request->input('category'),
            $request->input('start_date'),
            $request->input('end_date'));
        return redirect('/tasks');
    }

    public function updateStatus($id, $status)
    {
        if ($status >= 0 && $status <= 2) {
            $this->tasksRepo->updateStatus($id, $status);
        }
        return back();
    }

    public function destroy($id)
    {
        $this->tasksRepo->delete($id);
        return redirect('/tasks');
    }

    private function validateTask(Request $request) {
        $this->validate($request, [
            'title' => 'required|max:15',
            'description' => 'required',
            'category' => 'required',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:today'
        ]);
    }

    public function store(Request $request)
    {
        $this->validateTask($request);
        $defaultStatus = 0;
        $this->tasksRepo->insert($request->input('title'),
            $request->input('description'),
            $request->input('category'),
            Auth::id(),
            $defaultStatus,
            $request->input('start_date'),
            $request->input('end_date'));
        return redirect('/tasks');
    }
}