<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Maneja una solicitud entrante.
     * Verifica si el usuario autenticado tiene el role_id de Administrador (3).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificamos si existe el usuario y si su role_id es 3 (Admin)
        if (!$user || (int) $user->role_id !== 3) {
            return response()->json([
                'error' => 'Acceso denegado',
                'message' => 'Se requieren permisos de administrador para realizar esta acción.'
            ], 403);
        }

        return $next($request);
    }
}