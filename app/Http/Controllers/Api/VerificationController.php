<?php

namespace App\Http\Controllers\Api; // <-- Agrégale el \Api al final

use App\Http\Controllers\Controller; // <-- Añade esta línea para que Laravel encuentre el controlador base
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function submitVerification(Request $request)
    {
        // 1. Validar que sí venga un archivo y que sea del formato correcto
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // Máx 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Asegurarnos de que el usuario está logueado (trae token)
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuario no autenticado.'
                ], 401);
            }

            // 2. Guardar el archivo en el storage de Laravel (carpeta: verification_docs)
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                // Esto lo guarda de forma privada para que nadie externo lo pueda husmear
                $path = $file->store('verification_docs', 'local');

                // 3. Actualizar el estado del usuario en la base de datos
                // (Asumiendo que tienes estos campos en tu tabla 'users' o similar para controlar el KYC)
                $user->update([
                    'verification_document' => $path,
                    'verification_status' => 'pending' // Queda en espera de que el Admin lo apruebe
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Documento recibido con éxito. Tu perfil está en revisión.'
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'No se cargó ningún archivo.'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}