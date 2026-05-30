<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Simulate a payment processing for a contract
    public function process(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'contract_id' => 'required|integer|exists:contracts,id',
            'method' => 'nullable|string'
        ]);

        $contract = Contract::find($data['contract_id']);
        if (!$contract) {
            return response()->json(['message' => 'Contrato no encontrado'], 404);
        }

        // Solo el usuario que creó el contrato puede pagarlo
        if ($contract->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para pagar este contrato'], 403);
        }

        // Simular procesamiento
        sleep(2);

        $contract->payment_status = 'paid';
        $contract->payment_method = $data['method'] ?? 'mvp_simulated';
        $contract->save();

        return response()->json(['message' => 'Pago procesado', 'contract' => $contract]);
    }
}
