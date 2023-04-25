<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return User::get();
    }
# ##########################################################
    public function store()
    {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // Create a new user instance
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        // Return the created user
        return response()->json($user, 201);
    }
# ##########################################################
    public function update(Request $request, $id)
    {
        // Find the user by id
        $user = User::find($id);

        // Check if the user exists
        if ($user) {
            // Validate the request data
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:8',
            ]);

            // Update the user attributes
            $user->name = $request->name ?? $user->name;
            $user->email = $request->email ?? $user->email;
            $user->password = $request->password ? Hash::make($request->password) : $user->password;
            $user->save();

            // Return the updated user
            return response()->json($user, 200);
        } else {
            // Return an error message
            return response()->json(['message' => 'User not found'], 404);
        }
    }
# ##########################################################
    public function destroy($id)
    {
        // Find the user by id
        $user = User::find($id);

        // Check if the user exists
        if ($user) {
            // Delete the user
            $user->delete();

            // Return a success message
            return response()->json(['message' => 'User deleted'], 200);
        } else {
            // Return an error message
            return response()->json(['message' => 'User not found'], 404);
        }
    }
    # ##########################################################
    function show (int $id) {
        return User::findOrFail($id);
    }
}
