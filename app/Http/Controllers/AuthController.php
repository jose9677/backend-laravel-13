<?php

namespace App\Http\Controllers;

use App\Encryption\Encrypter;
use App\Http\Controllers\Controller;
use App\Mail\RegisterMailable;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        Mail::to($user->email)->queue(new RegisterMailable());

        DB::commit();
    } catch (Exception $e) {
        DB::rollBack();
        return $e->getMessage();
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
        
        if (!$user) {
            return response()->json(['error' => 'User does not exists in DB', 'code' => '002'], 401);
        }

        $active = User::where('identity', $request->identity)->value('active');
        
        if ($active == false) {
            return response()->json(['error' => 'Inactive User', 'code' => '003'], 400);
        }

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

    public function getUserById($identity)
    {
        $data = User::findOrFail($identity);

        return response()->json($data);
    }

    public function validateEmail(Request $request)
    {
        Log::info($request);
        $validator = Validator::make($request->all(),[
            'identity' => 'required|min:1|max:8',
            'otp' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $data = User::where([
            'otp' => $request->otp
        ])->get();

        if ($data->count()) {
            try {
            $user = new User();

            //ENVIAR CORREO DE AVISO DE ACTIVACION...
            $user->validateEmail($request);
            return response()->json(['message' => 'Email validated succesfull'], 200);
            
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }else {
            return response()->json(['error', 'Invalid code, please check it again', 'code' => '005'], 400);
        }
    }

    public function validateUser(Request $request)
    {
        Log::info($request);
        $validator = Validator::make($request->all(),[
            'identity' => 'required|min:1|max:8'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::where([
            'identity' => $request->identity
        ])->get();
    
        if (!$user->count()) {
            return response()->json(['error' => 'User does not exists in Data Base', 'code' => '002'], 400);
        }

        DB::beginTransaction();
        try {
            $user = new User();
            $data = User::where('identity', $request->identity)->first('email', 'api_token');
            //$app_url = env('APP_URL');
            $user = $user->validateUser($request);
            
            //Mail::to($data->email)->queue(new ValidarMailable($data->api_token, $app_url));
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }

        return response()->json(['message' => 'User Validation successfull'], 200);
    }

    public function changePassword(Request $request)
    {
        Log::info($request);
        $validator = Validator::make($request->all(),[
            'identity' => 'required|min:1|max:8',
            'password' => ['required',
                Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
        ],
            //'api_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::where([
            'identity' => $request->identity
        ])->get();

        if (!$user->count()) {
            return response()->json(['error' => 'User does not exists in DB', 'code' => '002'], 400);
        }

        DB::beginTransaction();
        try {
            $user = new User();
            //$email = User::where('identity', $request->identity)->value('email');
            $user->changePassword($request);

            //Mail::to($email)->queue(new CambioContraseñaMailable());
            DB::commit();
            return response()->json(['message' => 'Password renoved'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            $e->getMessage();
        }
    }

    public function activeUser($identity)
    {
        $user = User::where([
            'identity' => $identity
        ])->get();

        if (!$user->count()) {
            return response()->json(['error' => 'User does not exists in DB', 'code' => '002'], 400);
        }

        $data = User::query()->where('identity', $identity)->update(['active' => true]);

        return response()->json(['message' => 'User Actived successfull'], 200);
    }

    public function desactiveUser($identity)
    {
        $user = User::where([
            'identity' => $identity
        ])->get();

        if (!$user->count()) {
            return response()->json(['error' => 'User does not exists in DB', 'code' => '002'], 400);
        }

        $data = User::query()->where('identity', $identity)->update(['active' => false]);

        return response()->json(['message' => 'User Desactived successfull'], 200);
    }

    public function refreshOTP(Request $request)
    {
        Log::info($request);
        $validator = Validator::make($request->all(), [
            'identity' => 'required|min:1|max:8'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::where([
            'identity' => $request->identity
        ])->get();
    
        if (!$user->count()) {
            return response()->json(['error' => 'User does not exists in Data Base', 'code' => '002'], 400);
        }

        $otp = User::where('identity', $request->identity)->value('otp');
        
        if (!empty($otp)) {
            try {
            $new_otp = new User();
            
            //ENVIAR CORREO...

            $new_otp = $new_otp->refreshOTP($request);
            } catch (Exception $e) {
                return $e->getMessage();
            }   
        }else {
            return response()->json(['error' => 'This user does not need a OTP', 'code' => '004'], 400);
        }
        
        return response()->json([
            'message' => 'OTP Updated',
            'new_otp' => $new_otp,
            'identity' => $request->identity
            ], 200);
    }
}
