<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

#[Fillable(
    [ 'identity',
        'p_a',
        's_a',
        'p_n',
        's_n',
        'email',
        'password',
        'active',
        'email_active',
        'id_rol',
        'otp',
        'api_token'
    ])
]

#[Hidden(
    [
    'password', 
    'remember_token'
    ])
]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function getAllPermittedActionIds()
    {
        $rol_actions = RolAction::where('id_rol', $this->id_rol)
                                ->pluck('id_action')
                                ->toArray();

        $user_actions = UserAction::where('identity', $this->identity)
                                ->pluck('id_action')
                                ->toArray();

        return array_unique(array_merge($rol_actions, $user_actions));
    }

    public function register($request)
    {
          $user = User::create([
            'identity' => $request->identity,
            'p_a' => Str::upper($request->p_a),
            's_a' => Str::upper($request->s_a),
            'p_n' => Str::upper($request->p_n),
            's_n' => Str::upper($request->s_n),
            'email' => $request->email,
            'password' => $request->password,
            'active' => false,
            'email_active' => false,
            'id_rol' => $request->id_rol,
            'otp' => $this->generateOTP()
        ]);

        return $user;
    }

    protected function generateOtp()
    {
        $lenght = 6;

        $characters = '0123456789';

        $charactersLenght = strlen($characters);

        $randomString = '';

        for ($i = 0; $i < $lenght; $i++) { 
            $randomString .= $characters[rand(0, $charactersLenght -1)];
        }

        return $randomString;
    }

    public function refreshOTP(Request $request)
    {
            $new_otp = $this->generateCodOtp();

            $query = User::query()->where('identity', $request->identity)->update(['otp' => $new_otp]);
            $email = User::where('identity', $request->identity)->value('email');
            
            return $new_otp;
    }

    public function validateEmail(Request $request)
    {
        $email = User::where('otp', $request->otp)->value('email');
        User::query()->where('otp', $request->otp)->update(['otp' => null, 'email_active' => true]);
    }

     public function validateUser(Request $request)
    {
        $data = User::query()->where('identity', $request->identity)->first(['email_active', 'otp']);
        
        if ($data->email_active == true && empty($data->otp)) {
            $api_token = Str::random(50);
            $user = User::query()->where('identity', $request->identity)->update(['api_token' => $api_token]);   
        }
        //FALTA LA ACCION EN CASO DE NO CUMPLIR ESTA CONDICION...
        //PUEDE SER UN METODO DE REINICIAR USUARIO...
    }

    public function changePassword(Request $request)
    {
        $new_pass = User::query()->where('identity', $request->identity)->update(['password' => Hash::make($request->password)]);
    }

    protected $table = 'users';
    protected $primaryKey = 'identity';
    public $incrementing = false;
}
