<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Contract;
use App\Models\Service;

class ReviewController extends Controller
{
    public function index($serviceId)
    {
        $service = Service::find($serviceId);
        if (!$service) return response()->json(['message' => 'Servicio no encontrado'], 404);

        $reviews = $service->reviews()->with('user')->latest()->paginate(15);

        $canReview = false;
        $user = auth('sanctum')->user(); // Aseguramos el guard de API

        if ($user) {
            // Solo puede dejar reseña si tiene un contrato PAGADO y aún no ha reseñado (opcional)
            $canReview = Contract::where('service_id', $serviceId)
                ->where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->exists();
        }

        // Calculamos el promedio y también los atributos más votados
        $avg = $service->reviews()->avg('rating');
        
        return response()->json([
            'data' => $reviews,
            'avg' => round($avg, 1),
            'can_review' => $canReview,
            'total_reviews' => $service->reviews()->count()
        ]);
    }

    public function store(Request $request, $serviceId)
    {
        $user = $request->user();
        $service = Service::find($serviceId);
        
        if (!$service) return response()->json(['message' => 'Servicio no encontrado'], 404);

        // 1. Verificar compra
        $bought = Contract::where('service_id', $serviceId)
            ->where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->exists();

        if (!$bought) {
            return response()->json(['message' => 'Solo los compradores con pago confirmado pueden dejar reseñas'], 403);
        }

        // 2. Validación (Añadimos 'attributes' como array)
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
            'attributes' => 'nullable|array', // Aquí recibimos ['pago_seguro', 'soporte_247', etc]
        ]);

        // 3. Crear la reseña
        // Laravel convertirá automáticamente el array 'attributes' a JSON si lo defines en el modelo Review
        $review = Review::create([
            'user_id' => $user->id,
            'service_id' => $serviceId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'attributes' => $validated['attributes'] ?? []
        ]);

        return response()->json([
            'message' => '¡Gracias por tu feedback!',
            'review' => $review->load('user')
        ], 201);
    }
}