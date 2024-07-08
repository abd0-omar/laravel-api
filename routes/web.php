<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Models\User; // Ensure the correct namespace for User model

Route::get('/', function () {
    return view('welcome');
});

Route::get('/setup', function () {
    $credentials = [
        'email' => 'hamada@yahoo.com',
        'password' => 'p_a$$word_hamada123'
    ];

    if (!Auth::attempt($credentials)) {
        // Create a new user
        $user = new User();
        $user->name = 'admin';
        $user->email = $credentials['email'];
        $user->password = Hash::make($credentials['password']);
        $user->save();

        // Attempt authentication again
        if (Auth::attempt($credentials)) {
            /** @var \App\Models\User $user **/
            $user = Auth::user();

            // Create tokens
            $adminToken = $user->createToken('admin-token', ['create', 'update', 'delete']);
            $updateToken = $user->createToken('update-token', ['create', 'update']);
            $basicToken = $user->createToken('basic-token');

            // Return the plain text tokens
            return [
                'admin' => $adminToken->plainTextToken,
                'update' => $updateToken->plainTextToken,
                'basic' => $basicToken->plainTextToken,
            ];
        } else {
            return response()->json(['error' => 'Authentication failed after user creation'], 401);
        }
    } else {
        return response()->json(['message' => 'User already exists or authenticated'], 200);
    }
});
