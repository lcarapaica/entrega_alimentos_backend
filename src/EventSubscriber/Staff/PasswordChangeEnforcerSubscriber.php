<?php

declare(strict_types=1);

namespace App\EventSubscriber\Staff;

use App\Entity\Staff\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;

class PasswordChangeEnforcerSubscriber implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10], // Priority 10 runs right after routing
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only monitor main API routes
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return;
        }

        // Whitelist routes that users MUST be able to hit even with a pending password change
        $whitelistedPaths = [
            '/api/login',
            '/api/token/refresh',
            '/api/auth/me/password',
            '/api/auth/logout',
            '/api/doc', // Swagger documentation interface
        ];

        if (in_array($path, $whitelistedPaths, true)) {
            return;
        }

        // Check if user is logged in
        $user = $this->security->getUser();
        if ($user instanceof User && $user->getMustChangePassword()) {
            // Block request and return a structured API error instantly
            $response = new JsonResponse([
                'error' => 'Password change required.',
                'code' => 'PASSWORD_CHANGE_REQUIRED'
            ], 403);
            
            $event->setResponse($response);
        }
    }
}
