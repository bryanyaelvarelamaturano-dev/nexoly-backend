<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Contract; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// Importación correcta de la clase de Cloudinary
use Cloudinary\Cloudinary;

class ServiceController extends Controller
{
    /**
     * Listar servicios con filtros
     */
    public function index()
    {
        try {
            $query = Service::with('user')->withAvg('reviews', 'rating');

            $query->when(request('q'), function ($q, $v) {
                $q->where(function ($s) use ($v) {
                    $s->where('title', 'like', "%{$v}%")
                      ->orWhere('description', 'like', "%{$v}%");
                });
            });

            $query->when(request('category'), function ($q, $v) {
                $q->where('category', $v);
            });

            $query->when(request('modality'), function ($q, $v) {
                $q->where('modality', $v);
            });

            $query->when(request('minPrice'), function ($q, $v) {
                $q->where('price', '>=', (float) $v);
            });

            $query->when(request('maxPrice'), function ($q, $v) {
                $q->where('price', '<=', (float) $v);
            });

            $perPage = (int) request('per_page', 12);
            $services = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json($services);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en el servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo servicio con imagen
     */
    public function store(Request $request)
    {
        try {
            // 1. VERIFICACIÓN DE SEGURIDAD
            $user = auth('api')->user();

            if (!$user) {
                // Si el token falla, podrías usar el ID 1 temporalmente para pruebas, 
                // pero lo correcto es pedir autenticación.
                return response()->json([
                    'message' => 'Error de autenticación',
                    'error' => 'No se pudo identificar al usuario.'
                ], 401);
            }

            // 2. VALIDACIÓN
            $validated = $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
                'price'       => 'required|numeric|min:0',
                'category'    => 'required|string',
                'modality'    => 'required|in:online,onsite',
                'image'       => 'required|image|max:10240',
            ]);

            // 3. CLOUDINARY (Conexión Directa para evitar errores de config)
            $cl = new Cloudinary('cloudinary://221434432647777:88OjPz52kitHEJZNiPYGoGpthl8@dzdbewxmg');
            $uploadedFile = $request->file('image');
            $upload = $cl->uploadApi()->upload($uploadedFile->getRealPath(), ['folder' => 'services']);
            $imageUrl = $upload['secure_url'];

            // 4. CREACIÓN
            $service = Service::create([
                'user_id'     => $user->id, 
                'title'       => $validated['title'],
                'description' => $validated['description'],
                'price'       => $validated['price'],
                'category'    => $validated['category'],
                'modality'    => $validated['modality'],
                'image_url'   => $imageUrl,
            ]);

            return response()->json($service, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Faltan datos obligatorios',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al crear el servicio',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile()
            ], 500);
        }
    }

    /**
     * Actualizar servicio existente
     */
    public function update(Request $request, $id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        if ($service->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price'       => 'sometimes|numeric|min:0',
            'category'    => 'sometimes|string',
            'modality'    => 'sometimes|in:online,onsite',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        if ($request->hasFile('image')) {
            $cl = new Cloudinary('cloudinary://221434432647777:88OjPz52kitHEJZNiPYGoGpthl8@dzdbewxmg');
            $uploadedFileUrl = $cl->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'services'
            ])['secure_url'];
            
            $validated['image_url'] = $uploadedFileUrl;
        }

        $service->update($validated);

        return response()->json([
            'message' => 'Servicio actualizado con éxito',
            'service' => $service->load('user')
        ]);
    }

    /**
     * Mostrar detalle de un servicio
     */
    public function show($id)
    {
        $service = Service::with(['user', 'reviews.user'])->withAvg('reviews', 'rating')->find($id);

        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        return response()->json(['data' => $service]);
    }

    /**
     * Eliminar servicio
     */
    public function destroy(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) return response()->json(['message' => 'Servicio no encontrado'], 404);
        if ($service->user_id !== auth('api')->id()) return response()->json(['message' => 'No autorizado'], 403);

        $service->delete();
        return response()->json(['message' => 'Servicio eliminado con éxito']);
    }

    /**
     * Servicios del usuario autenticado (Proveedor)
     */
    public function userServices(Request $request)
    {
        return response()->json([
            'data' => auth('api')->user()->services()->latest()->get()
        ]);
    }

    /**
     * Contratar un servicio
     */
    public function createContract(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        if ($service->user_id === auth('api')->id()) {
            return response()->json(['message' => 'No puedes contratar tu propio servicio'], 400);
        }

        $contract = Contract::create([
            'user_id' => auth('api')->id(),
            'service_id' => $service->id,
            'price' => $service->price,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Servicio contratado con éxito',
            'contract' => $contract
        ], 201);
    }

    /**
     * Contratos realizados por el usuario (Cliente)
     */
    public function userContracts(Request $request)
    {
        $contracts = Contract::where('user_id', auth('api')->id())
            ->with(['service.user'])
            ->latest()
            ->get();

        return response()->json(['data' => $contracts]);
    }

    /**
     * Ventas del usuario (Proveedor)
     */
    public function userSales(Request $request)
    {
        $sales = Contract::whereHas('service', function ($query) {
            $query->where('user_id', auth('api')->id());
        })
        ->with(['service', 'user'])
        ->latest()
        ->get();

        return response()->json(['data' => $sales]);
    }

    /**
     * Listar categorías únicas
     */
    public function categories()
    {
        $categories = Service::distinct()->whereNotNull('category')->pluck('category');
        return response()->json($categories);
    }
}