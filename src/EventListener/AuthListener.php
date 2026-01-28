<?php

namespace App\EventListener;

use App\Attribute\RequireAuth;
use App\Attribute\RequireRole;
use App\Service\AuthService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::CONTROLLER)]
class AuthListener
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $controllerObject = $controller[0];
        $method = $controller[1];

        $reflectionClass = new \ReflectionClass($controllerObject);
        $reflectionMethod = $reflectionClass->getMethod($method);

        $requireAuth = $reflectionMethod->getAttributes(RequireAuth::class);
        if (empty($requireAuth)) {
            $requireAuth = $reflectionClass->getAttributes(RequireAuth::class);
        }

        $requireRole = $reflectionMethod->getAttributes(RequireRole::class);
        if (empty($requireRole)) {
            $requireRole = $reflectionClass->getAttributes(RequireRole::class);
        }

        if (empty($requireAuth) && empty($requireRole)) {
            return;
        }

        $request = $event->getRequest();
        $authHeader = $request->headers->get('Authorization');
        $token = $this->authService->extractTokenFromHeader($authHeader);

        if (!$token) {
            $event->setController(function () {
                return new JsonResponse(['error' => 'Missing or invalid authorization token'], 401);
            });
            return;
        }

        $authEntity = $this->authService->getAuthenticatedEntity($token);

        if (!$authEntity) {
            $event->setController(function () {
                return new JsonResponse(['error' => 'Invalid or expired token'], 401);
            });
            return;
        }

        if (!empty($requireRole)) {
            if (!($authEntity instanceof \App\Entity\User)) {
                $event->setController(function () {
                    return new JsonResponse(['error' => 'Insufficient permissions'], 403);
                });
                return;
            }

            $roleAttribute = $requireRole[0]->newInstance();

            if ($authEntity->getRole() !== $roleAttribute->role) {
                $event->setController(function () {
                    return new JsonResponse(['error' => 'Insufficient permissions'], 403);
                });
                return;
            }
        }

        $request->attributes->set('auth_user', $authEntity);
    }
}
