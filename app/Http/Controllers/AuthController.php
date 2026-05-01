<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name'     => 'required|string|max:255',
        'email'    => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
    ]);

    // Generamos el token de una vez para que el usuario quede logueado tras registrarse
    $token = $user->createToken('angular_app')->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'Usuario creado exitosamente',
        'data'    => [
            'user'  => $user,
            'token' => $token
        ]
    ], 201);
}

    /**
     * Maneja la autenticación y entrega de token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            //'device_name' => 'required', // Útil para identificar de dónde viene el token
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Generamos el token. En Sanctum, el token es "texto plano" una sola vez.
        $token = $user->createToken($request->device_name)->plainTextToken;
        
        //$token = $user->createToken($request->email)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario autenticado correctamente',
            'data' => [
                'user' => $user,
                'token' => $token, // Este es el que el cliente debe guardar
            ],
        ], 200);
    }

    /**
     * Revocación de tokens (Logout).
     */
    public function logout(Request $request)
    {
        // Borra el token actual que se está usando para la petición
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada y token eliminado.']);
    }
}
