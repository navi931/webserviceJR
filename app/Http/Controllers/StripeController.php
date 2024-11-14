<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeController extends Controller
{
    public function pagoStripe(Request $request)
    {
        // Configurar la clave secreta de Stripe
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Validar los datos entrantes
        $validatedData = $request->validate([
            'amount' => 'required|integer',
            'currency' => 'required|string',
            'reservationNumber' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'phone' => 'required|string',
        ]);

        // Crear un PaymentIntent
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $validatedData['amount'],
                'currency' => $validatedData['currency'],
                'payment_method_types' => ['card'],
                'description' => $validatedData['reservationNumber'],
                'receipt_email' => $validatedData['email'],
                'metadata' => [
                    'reservationNumber' => $validatedData['reservationNumber'],
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'phone' => $validatedData['phone'],
                ],
            ]);

            // Devolver el client_secret al frontend
            return response()->json(['clientSecret' => $paymentIntent->client_secret]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Manejar el error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
