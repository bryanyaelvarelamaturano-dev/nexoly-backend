<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    /**
     * Crear contrato y procesar pago (Simulado)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'price'      => 'required|numeric|min:0'
        ]);

        try {
            $service = Service::findOrFail($data['service_id']);

            // No permitir autocompra
            if ($service->user_id === $user->id) {
                return response()->json([
                    'message' => 'No puedes contratar tus propios servicios.'
                ], 422);
            }

            $contract = DB::transaction(function () use ($user, $service, $data) {
                return Contract::create([
                    'user_id'        => $user->id,
                    'service_id'     => $service->id,
                    'price'          => $data['price'],
                    'payment_status' => 'paid',
                    'status'         => 'pending'
                ]);
            });

            return response()->json([
                'message' => '¡Contratación exitosa!',
                'contract' => $contract->load('service')
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error en Contract@store: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar el pago'], 500);
        }
    }

    /**
     * Cancelar un servicio (Cliente o Vendedor)
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $contract = Contract::with('service')->findOrFail($id);

        // Verificar si el usuario es parte del contrato
        $isCustomer = $contract->user_id === $user->id;
        $isSeller   = $contract->service->user_id === $user->id;

        if (!$isCustomer && !$isSeller) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($contract->status === 'completed') {
            return response()->json(['message' => 'No se puede cancelar un servicio ya completado'], 422);
        }

        $contract->status = 'cancelled';
        $contract->save();

        return response()->json([
            'message' => 'Servicio cancelado correctamente',
            'contract' => $contract
        ]);
    }

    /**
     * Listado de compras del cliente
     */
    public function myContracts(Request $request)
    {
        return response()->json([
            'data' => Contract::with('service.user')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get()
        ]);
    }

    /**
     * Listado de ventas para el vendedor
     */
    public function sellerOrders(Request $request)
    {
        $userId = $request->user()->id;
        
        $orders = Contract::with(['service', 'user'])
            ->whereHas('service', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->latest()
            ->get();

        return response()->json(['data' => $orders]);
    }

    /**
     * Actualizar estado (Ej: de Pendiente a Completado)
     */
    public function updateStatus(Request $request, $id)
    {
        $contract = Contract::with('service')->findOrFail($id);

        // Solo el vendedor puede marcar como entregado/completado
        if ($contract->service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate(['status' => 'required|string']);
        
        // Evitar cambios si ya está cancelado
        if ($contract->status === 'cancelled') {
            return response()->json(['message' => 'No se puede modificar un servicio cancelado'], 422);
        }

        $contract->status = $validated['status'];
        $contract->save();

        return response()->json(['message' => 'Estado actualizado', 'contract' => $contract]);
    }
}