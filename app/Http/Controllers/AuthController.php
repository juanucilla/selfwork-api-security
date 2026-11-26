<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse {

    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email',
        'password' => 'required|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-=+])[A-Za-z\d!@#$%^&*()_\-=+]{8,20}$/',
        'c_password' => 'required|same:password',
    ]);

    if($validator->fails()){
        return response()->json(['error' =>  $validator->errors()], 403);
    }

    $input = $request->only(['name', 'email', 'password']);

    $input['password'] = bcrypt($input['password']);
    $user = User::create($input);

    $success['token'] =  $user->createToken('MyApp')->plainTextToken;
    $success['name'] =  $user->name;

    return response()->json([
        'data' => $success,
        'links' => [
            'self' => [
                'href' => url('/api/register'),
                'method' => 'POST'
            ],
            'all_books' => [
                'href' => url('/api/books'),
                'method' => 'GET'
                ]
            ]
        ]);
    }

    public function login(Request $request): JsonResponse {

    if(!Auth::attempt(['email' => $request->email, 'password' => $request->password])){
        return response()->json(['error' =>  ['Unauthorised']], 403);
    }

    $user = Auth::user();

    $user->tokens()->delete();
    $success['token'] = $user->createToken('MyApp', ['*'], now()->addDays(7))->plainTextToken;
    $success['name'] =  $user->name;

    return response()->json([
        'data' => $success,
        'links' => [
            'self' => [
                'href' => url('/api/login'),
                'method' => 'POST'
            ],
            'all_books' => [
                'href' => url('/api/books'),
                'method' => 'GET'
                ]
            ]
        ]);
    }

    public function passwordRecovery(Request $request){
        if(!$user = User::where('email',$request->email)->first()){
            return response()->json(['error' =>  ['Unauthorised']]);
        }

        return response()->json([
            'data' => 'SMS sent',
            'links' => [
                'self' => [
                    'href' => url('/api/login'),
                    'method' => 'POST'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                    ]
                    ]
                ]);
    }

    function getUserInfo() {

        if(!$user = Auth::user()){
            return response()->json(['error' =>  ['Unauthorised']], 401);
        }

        return response()->json([
            'data' => $user,
            'links' => [
                'self' => [
                    'href' => url('/api/user'),
                    'method' => 'GET'
                ],
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                    ]
                ]
            ]);
        }

    public function updateEmail(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        $user->email = $request->email;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully.',
            'links' => [
                'all_books' => [
                    'href' => url('/api/books'),
                    'method' => 'GET'
                    ]
                    ]
                ]);
    }
}
