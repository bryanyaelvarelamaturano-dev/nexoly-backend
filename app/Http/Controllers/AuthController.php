<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http; // <-- IMPORTACIÓN CRUCIAL PARA LAS PETICIONES A GOOGLE
use Illuminate\Support\Str;
use Google_Client; 
use Cloudinary\Cloudinary;

class AuthController extends Controller
{
    /**
     * Iniciar Sesión con Google (Actualizado para Access Token)
     */
    public function googleLogin(Request $request)
    {
        // 1. Capturamos el token que viene desde Vue
        $accessToken = $request->input('access_token') ?? $request->input('token');
        
        if (!$accessToken) {
            return response()->json(['message' => 'El token de acceso es requerido'], 400);
        }

        // 2. Consultamos al endpoint oficial de Google para validar el Access Token
        $googleResponse = Http::get("https://www.googleapis.com/oauth2/v3/tokeninfo", [
            'access_token' => $accessToken
        ]);
        
        if ($googleResponse->failed()) {
            return response()->json(['message' => 'Token de Google inválido o expirado'], 401);
        }

        $payload = $googleResponse->json();
        $email = $payload['email'];
        
        $name = $payload['name'] ?? explode('@', $email)[0];
        
        // Consultamos la información de usuario para obtener la foto de perfil
        $userinfoResponse = Http::get("https://www.googleapis.com/oauth2/v3/userinfo", [
            'access_token' => $accessToken
        ]);
        
        $picture = null;
        if ($userinfoResponse->successful()) {
            $userData = $userinfoResponse->json();
            $picture = $userData['picture'] ?? null;
        }

        // 3. Tu lógica original de base de datos intacta:
        $user = User::where('email', $email)->first();
        $isNewUser = false;

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(24)), 
                'profile_image' => $picture,
                'role_id' => 1, 
            ]);
            $isNewUser = true;
        } else {
            if (empty($user->city) || empty($user->country)) {
                $isNewUser = true;
            }
        }

        // 4. Tu inicio de sesión con tu JWT nativo habitual
        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => $user,
            'is_new_user' => $isNewUser
        ]);
    }

    /**
     * Iniciar Sesión Tradicional
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Acceso denegado: Verifica tus credenciales'
            ], 401);
        }

        $user = auth('api')->user();
        $isNewUser = (empty($user->city) || empty($user->country));

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => $user,
            'is_new_user' => $isNewUser
        ]);
    }

    /**
     * Registro de Usuario Manual
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email:rfc,dns|max:255|unique:users',
            'password' => 'required|string|min:6',
        ], [
            'email.email' => 'El formato del correo no es válido.',
            'email.unique' => 'Este correo ya está registrado.'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => 1,
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'is_new_user' => true
        ], 201);
    }
    
    /**
     * Completar Perfil
     */
    public function completeProfile(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Sesión expirada'], 401);
        }

        $request->validate([
            'role_id' => 'required|integer',
            'country' => 'required|string',
            'state'   => 'required|string',
            'city'    => 'required|string',
        ]);

        $user->update([
            'role_id'       => $request->role_id,
            'country'       => $request->country,
            'state'         => $request->state,
            'city'          => $request->city,
            'business_name' => $request->business_name,
        ]);

        $user->refresh();

        return response()->json([
            'message' => 'Perfil configurado con éxito',
            'user' => $user,
            'is_new_user' => false 
        ]);
    }

    /**
     * Actualizar Perfil (LA MAGIA DE LAS FOTOS)
     */
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $user->name = $request->input('name', $user->name);
        $user->email = $request->input('email', $user->email);

        if ($request->hasFile('profile_image')) {
            $cl = new Cloudinary('cloudinary://221434432647777:88OjPz52kitHEJZNiPYGoGpthl8@dzdbewxmg');
            
            $upload = $cl->uploadApi()->upload($request->file('profile_image')->getRealPath(), [
                'folder' => 'profiles',
                'transformation' => [
                    ['width' => 400, 'height' => 400, 'crop' => 'fill', 'gravity' => 'face']
                ]
            ]);

            $user->profile_image = $upload['secure_url'];
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado con éxito',
            'user' => $user
        ]);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        // Cierre seguro de JWT
        auth('api')->logout();
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
}