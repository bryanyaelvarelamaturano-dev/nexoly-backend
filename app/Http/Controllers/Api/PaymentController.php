<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\PaymentTransaction;
use App\Models\StripeCustomerToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Inicializar Stripe con la llave secreta del .env
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    /**
     * Inicia el proceso de pago real con Stripe creando un PaymentIntent.
     */
    public function process(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'contract_id' => 'required|integer|exists:contracts,id',
            'method' => 'nullable|string' // Ej. 'stripe_card'
        ]);

        $contract = Contract::with('service')->find($data['contract_id']);
        
        if (!$contract) {
            return response()->json(['message' => 'Contrato no encontrado'], 404);
        }

        // Solo el usuario que creó el contrato puede pagarlo
        if ($contract->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para pagar este contrato'], 403);
        }

        // Evitar pagar un contrato que ya está liquidado
        if ($contract->payment_status === 'paid') {
            return response()->json(['message' => 'Este contrato ya ha sido pagado anteriormente.'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Calcular el monto en centavos para Stripe (Stripe no acepta decimales, ej: $100.00 MXN = 10000 centavos)
            $amountInCents = (int) round($contract->price * 100);

            // 2. Crear el PaymentIntent en los servidores de Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => 'mxn', // Forzado a Pesos Mexicanos para Nexoly
                'metadata' => [
                    'contract_id' => $contract->id,
                    'user_id' => $user->id,
                    'service_id' => $contract->service_id
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // 3. Registrar la transacción con estado 'pending' en tu BD de Render para auditoría
            $transaction = PaymentTransaction::create([
                'contract_id' => $contract->id,
                'user_id' => $user->id,
                'service_id' => $contract->service_id,
                'amount' => $contract->price,
                'currency' => 'MXN',
                'fee' => 0.00, // Se actualizará mediante el webhook cuando Stripe cobre su comisión
                'payment_provider' => 'stripe',
                'payment_intent_id' => $paymentIntent->id,
                'status' => 'pending'
            ]);

            // 4. Actualizar el método de pago en el contrato
            $contract->update([
                'payment_method' => $data['method'] ?? 'stripe_card'
            ]);

            DB::commit();

            // 5. Retornar el client_secret al frontend (Vue 3 lo necesita para renderizar el formulario seguro)
            return response()->json([
                'status' => 'success',
                'message' => 'Intento de pago inicializado.',
                'client_secret' => $paymentIntent->client_secret,
                'transaction_id' => $transaction->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al inicializar pago en Stripe: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo procesar la orden con Stripe.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook asíncrono que escucha los eventos enviados por Stripe de forma segura.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            // Verificar que la petición realmente venga de Stripe y no haya sido manipulada
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Payload inválido'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Firma de webhook inválida'], 400);
        }

        // Procesar el tipo de evento recibido
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->registerSuccessfulPayment($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->registerFailedPayment($paymentIntent);
                break;
        }

        return response()->json(['status' => 'webhook_handled'], 200);
    }

    /**
     * Lógica interna para liquidar el contrato cuando el cobro fue exitoso
     */
    private function registerSuccessfulPayment($paymentIntent)
    {
        $contractId = $paymentIntent->metadata->contract_id ?? null;

        if (!$contractId) return;

        DB::transaction(function () use ($contractId, $paymentIntent) {
            $contract = Contract::find($contractId);
            if ($contract) {
                // Liquidar estado del contrato
                $contract->update(['payment_status' => 'paid']);

                // Actualizar la transacción local en Render
                PaymentTransaction::where('payment_intent_id', $paymentIntent->id)
                    ->update([
                        'status' => 'success',
                        'transaction_id' => $paymentIntent->latest_charge ?? null,
                        'webhook_received' => true,
                        'webhook_timestamp' => now()
                    ]);
            }
        });
    }

    /**
     * Lógica interna para marcar el fallo de la transacción si la tarjeta no pasó
     */
    private function registerFailedPayment($paymentIntent)
    {
        $errorMessage = $paymentIntent->last_payment_error ? $paymentIntent->last_payment_error->message : 'Error desconocido';

        PaymentTransaction::where('payment_intent_id', $paymentIntent->id)
            ->update([
                'status' => 'failed',
                'failure_reason' => $errorMessage,
                'webhook_received' => true,
                'webhook_timestamp' => now()
            ]);
    }
}