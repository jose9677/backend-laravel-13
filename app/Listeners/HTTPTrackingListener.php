<?php

namespace App\Listeners;

use Illuminate\Routing\Events\RequestHandled;
use App\Models\Tracking;
use App\Models\User;
use Illuminate\Foundation\Http\Events\RequestHandled as EventsRequestHandled;

class HTTPTrackingListener
{
    /**
     * Se ejecuta automáticamente cuando Laravel dispara el evento RequestHandled.
     */
    public function handle(EventsRequestHandled $event): void
    {
        try {
            // Extraemos de forma nativa el request y el response del evento
            $request = $event->request;
            $response = $event->response;

            // Filtro de seguridad: Solo auditar si la petición es de la API
            if ($request->is('api/*')) {
                
                $log = new Tracking();
                $identity = null;

                // 1. Si Sanctum ya identificó al usuario, lo extraemos directo de memoria
                if ($request->user()) {
                    $identity = $request->user()->identity;
                } else {
                    // 2. Si es una ruta pública, usamos el esqueleto URI para verificar tu config/env.php
                    $current_uri = $request->route()?->uri();

                    switch (true) {
                        case ($current_uri == config('env.login')):
                        case ($current_uri == config('env.restartUser')):
                        case ($current_uri == config('env.validateUser')):
                        case ($current_uri == config('env.registeUser')):
                            $identity = $request->input('identity');
                            break;

                        case ($current_uri == config('env.changePassword')):
                            if ($request->has('api_token')) {
                                $identity = User::where('api_token', $request->api_token)->value('identity');
                            }
                            break;

                        case (str_starts_with($current_uri, config('env.refreshOTP'))):
                            $identity = $request->route('result'); 
                            break;

                        case ($current_uri == config('env.validateEmail')):
                            if ($request->has('otp')) {
                                $identity = User::where('otp', $request->otp)->value('identity');
                            }
                            break;
                            
                        default:
                            $identity = 'Anónimo/Público';
                            break;
                    }
                }

                $log->identity = $identity ?? 'Anónimo/Público';
                $log->route = $request->fullUrl();
                $log->method = $request->method();
                $log->ip = $request->ip();
                $log->code_response = $response->getStatusCode();
                
                // camnbios en la contraseña para que no quede en texto plano en la DB de logs
                $data_sent = $request->all();
                if (isset($data_sent['password'])) {
                    $data_sent['password'] = '********';
                }
                $log->data_sent = json_encode($data_sent, JSON_UNESCAPED_UNICODE);

                // Capturar el contenido de la respuesta de forma segura
                $content = $response->getContent();
                $log->json_response = is_string($content) ? $content : json_encode($content);

                $log->save();
            }
        } catch (\Throwable $th) {
            // Si algo falla, se reporta al laravel.log interno para no tumbar la respuesta de la API
            logger('Error en HTTPTrackingListener: ' . $th->getMessage());
            report($th);
        }
    }
}
