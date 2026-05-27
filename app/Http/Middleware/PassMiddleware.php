<?php

namespace App\Http\Middleware;

use App\Models\Action;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PassMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Obtener el usuario autenticado por Sanctum
        $user = $request->user();
        if (!$user) {
            return response()->json('Unauthorized', 403);
        }

        // 2. Obtener la estructura limpia de la ruta y el método HTTP
        // Ejemplo: Si entran a /api/usuarios/V-1234, esto da: "api/usuarios/{cedula}"
        $current_path = $request->route()->uri(); 
        $method = $request->method();

        // 3. Buscar la acción correspondiente en la base de datos
        $action = Action::where('route', $current_path)
                        ->where('method', $method)
                        ->first(['id_action', 'id_module']);

        // Si la ruta ejecutada no existe en tu catálogo de acciones del sistema, cerramos acceso
        if (!$action) {
            return response()->json('Unauthorized', 403);
        }

        // 4. Obtener las acciones que el usuario tiene permitidas (Rol + Extras)
        $full_actions = $user->getAllPermittedActionIds();

        // 5. Validar si el ID de la acción actual está permitido para el usuario
        if (in_array($action->id_action, $full_actions)) {
            return $next($request);
        }

        // Si no cumple, rebote inmediato
        return response()->json('Unauthorized', 403);
    }
}
