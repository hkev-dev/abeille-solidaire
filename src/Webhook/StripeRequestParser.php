<?php

namespace App\Webhook;

use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class StripeRequestParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            // Add RequestMatchers to fit your needs
        ]);
    }

    /**
     * @throws JsonException
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Invalid payload');
        } catch (SignatureVerificationException $e) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Invalid signature');
        }

        return new RemoteEvent(
            $event->type,
            $event->id,
            $event->toArray(),
        );
    }
}