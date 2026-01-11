<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 9999)]
#[AsEventListener(event: KernelEvents::RESPONSE, priority: 9999)]
class CorsListener
{
    private array $allowedOrigins;

    public function __construct(ParameterBagInterface $params)
    {
        $originsString = $params->get('cors.allowed_origins');

        if ($originsString) {
            $this->allowedOrigins = array_filter(
                array_map('trim', explode(',', $originsString))
            );
        } else {
            $this->allowedOrigins = [
                'http://localhost:5173',
                'http://localhost:3000',
                'http://127.0.0.1:5173',
                'http://127.0.0.1:3000',
            ];
        }
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            $this->addCorsHeaders($response, $request);
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $this->addCorsHeaders($response, $request);
    }

    private function addCorsHeaders(Response $response, $request): void
    {
        $origin = $request->headers->get('Origin');

        if ($origin && in_array($origin, $this->allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
}
