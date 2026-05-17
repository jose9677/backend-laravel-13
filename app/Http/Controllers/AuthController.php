<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
{
     Log::info($request);
        $validator = Validator::make($request->all(), [
            'identity' => 'required|min:1|max:8',
            'p_a' => 'required|string',
            's_a' => 'nullable|string',
            'p_n' => 'required|string',
            's_n' => 'nullable|string',
            'email' => 'required|email',
            
            'password' => [ 'required',
                            Password::min(8)
                            ->letters()
                            ->mixedCase()
                            ->numbers()
                            ->symbols()
                          ],
            'id_rol' => 'required'
        ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $exists = User::where([
        "identity" => $request->identity
    ])->get();

    if ($exists->count()) {
        return response()->json(['error' => 'User exists in DB', 'code' => '001'], 401);
    }

    DB::beginTransaction();
    
    try {
        
        $user = new User();
        $user = $user->register($request);

        // Generamos el token de una vez para que el usuario quede logueado tras registrarse
        $token = $user->createToken('angular_app')->plainTextToken;

        DB::commit();
    } catch (\Throwable $th) {
        DB::rollBack();
    }

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
            'identity' => 'required|min:1|max:8',
            'password' => 'required',
            'device_name' => 'required', // Útil para identificar de dónde viene el token
        ]);

        $user = User::where('identity', $request->identity)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'identity' => ['Las credenciales son incorrectas.'],
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

    public function detailsUser()
    {
        $data = User::all();

        return response()->json($data);
    }
}
