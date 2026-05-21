<?php

namespace App\Http\Controllers;

use App\Services\PayPalBillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, PayPalBillingService $paypal): Response
    {
        if (! $paypal->isConfigured()) {
            return response('PayPal not configured', 503);
        }

        try {
            $paypal->handleWebhook(
                collect($request->headers->all())
                    ->mapWithKeys(fn (array $values, string $key) => [strtolower($key) => $values[0] ?? null])
                    ->all(),
                $request->getContent(),
            );
        } catch (\UnexpectedValueException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage());
        }

        return response('Webhook handled', 200);
    }
}
