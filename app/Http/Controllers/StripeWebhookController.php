<?php

namespace App\Http\Controllers;

use App\Services\StripeBillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeBillingService $stripe): Response
    {
        if (! $stripe->isConfigured()) {
            return response('Stripe not configured', 503);
        }

        try {
            $stripe->handleWebhookPayload(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage());
        }

        return response('Webhook handled', 200);
    }
}
