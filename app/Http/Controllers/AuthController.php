<?php

namespace App\Http\Controllers;

use App\Helpers\JWT;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Prompts\Key;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:users,name',
            'phone_number' => 'required|string|unique:users,phone_number',
        ]);
    
        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('name') || $errors->has('phone_number')) {
                return response()->json(['message' => 'Nama atau Nomor Hp sudah digunakan'], 422);
            }
    
            return response()->json(['errors' => $errors], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make('password123'),
        ]);
    
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ]);
    }
    
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json(['message' => 'Nomor Hp tidak ditemukan'], 401);
        }

        $payload = [
            'sub' => $user->id,
            'name' => $user->name,
            'phone_number' => $user->phone_number,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $key = env('JWT_SECRET', 'LazismuDIY_ICT');
        $token = JWT::encode($payload, $key);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ],
        ]);
    }

    public function registerAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:users,name',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Admin::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Admin registered successfully',
            'user' => $user,
        ]);
    }

    public function loginAdmin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Admin::where('name', $request->name)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $payload = [
            'sub' => $user->id,
            'name' => $user->name,
            'phone_number' => $user->phone_number,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        $key = env('JWT_SECRET', 'LazismuDIY_ICT');
        $token = JWT::encode($payload, $key);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ],
        ]);
    }


    public function getMe(Request $request)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        $token = str_replace('Bearer ', '', $authHeader);
    
        try {
            $key = env('JWT_SECRET', 'LazismuDIY_ICT');
            $decoded = JWT::decode($token, $key);
    
            $user = User::find($decoded->sub);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
    
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ]);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }
    }
}
