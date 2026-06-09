<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Staff\User;
use App\Service\Staff\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTAuthenticationSubscriber implements EventSubscriberInterface
{
    private RefreshTokenService $refreshTokenService;

    public function __construct(RefreshTokenService $refreshTokenService)
    {
        $this->refreshTokenService = $refreshTokenService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Create refresh token
        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        $data = $event->getData();
        $data['user'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'mustChangePassword' => $user->getMustChangePassword(),
        ];
        $data['refresh_token'] = $refreshToken->getToken();

        $event->setData($data);
    }
}
