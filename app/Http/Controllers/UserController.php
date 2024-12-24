<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{


    public function getUsers(Request $request)
    {



        if ($request->wantsJson()) {


            $data = User::with('roles')->get();

            return DataTables::of($data)
                ->addColumn('created_at', function($user) {
                    return $user->created_at->format('Y-m-d H:i:s');
                })
                ->make(true);
        }

        return response()->json(['message' => 'This endpoint only responds to AJAX requests.'], 400);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::with('roles')->get();

        if ($request->wantsJson()) {
            return response()->json($users, 200);
        }

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $roles = Role::all();

        if ($request->wantsJson()) {
            return response()->json($roles, 200);
        }

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'roles' => 'required|array|min:1',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'email_verification_token' => Str::random(10),
        ]);

        $user->roles()->sync($request->roles);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'User created successfully.', 'user' => $user], 201);
        }

        return redirect()->route('users.index')->with('message', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  User  $user
     * @return
     */


    public function show(Request $request, User $user)
    {
        $user->load('roles');
        
        if ($request->wantsJson()) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'phone_number' => $user->phone_number,
                'phone_verified_at' => $user->phone_verified_at,
                'roles' => $user->roles,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ]);
        }

        return view('users.show', compact('user'));
    }
    public function showOld(Request $request, User $user = null)
    {

        if(!$user){
          $user = $request->user();
          $user->load('roles');
        }



        if ($request->wantsJson()) {


            return response()->json($user, 200);
        }

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, User $user)
    {
        $roles = Role::all();

        if ($request->wantsJson()) {
            return response()->json(['user' => $user, 'roles' => $roles], 200);
        }

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'roles' => 'required|array|min:1',
        ]);

        $user->update([
            'name' => $request->name,
        ]);

        $user->roles()->sync($request->roles);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'User updated successfully.', 'user' => $user], 200);
        }

        return redirect()->route('users.index')->with('message', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        $user->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'User deleted successfully.'], 200);
        }

        return redirect()->route('users.index')->with('message', 'User deleted successfully.');
    }

    public function updateRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array'
        ]);

        $user->roles()->sync($request->roles);

        return response()->json([
            'message' => 'Roles updated successfully',
            'roles' => $user->roles()->get()
        ]);
    }
}
