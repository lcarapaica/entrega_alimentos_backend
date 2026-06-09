<?php

declare(strict_types=1);

namespace App\Service\Staff;

use App\Entity\Staff\User;
use App\Entity\Staff\RefreshToken;
use App\Repository\Staff\RefreshTokenRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RefreshTokenService
{
    private RefreshTokenRepository $refreshTokenRepository;
    private JWTTokenManagerInterface $jwtTokenManager;

    public function __construct(
        RefreshTokenRepository $refreshTokenRepository,
        JWTTokenManagerInterface $jwtTokenManager
    ) {
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->jwtTokenManager = $jwtTokenManager;
    }

    public function createRefreshToken(User $user): RefreshToken
    {
        $tokenString = bin2hex(random_bytes(64));

        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setToken($tokenString);
        // Expire in 30 days
        $refreshToken->setExpiresAt(new \DateTime('+30 days'));

        $this->refreshTokenRepository->add($refreshToken, true);

        return $refreshToken;
    }

    public function refresh(string $tokenString): array
    {
        /** @var RefreshToken|null $refreshToken */
        $refreshToken = $this->refreshTokenRepository->findOneBy(['token' => $tokenString]);

        if ($refreshToken === null || !$refreshToken->isValid()) {
            if ($refreshToken !== null) {
                $this->refreshTokenRepository->remove($refreshToken, true);
            }
            throw new AccessDeniedException("Invalid or expired refresh token.");
        }

        $user = $refreshToken->getUser();
        if (!$user->getIsActive()) {
            $this->refreshTokenRepository->remove($refreshToken, true);
            throw new AccessDeniedException("User account is inactive.");
        }

        // Generate new JWT
        $jwt = $this->jwtTokenManager->create($user);

        // Rotate Refresh Token: delete the old one and create a new one
        $this->refreshTokenRepository->remove($refreshToken, true);
        $newRefreshToken = $this->createRefreshToken($user);

        return [
            'token' => $jwt,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'mustChangePassword' => $user->getMustChangePassword(),
            ],
            'refresh_token' => $newRefreshToken->getToken(),
        ];
    }

    public function invalidateToken(string $tokenString): void
    {
        /** @var RefreshToken|null $refreshToken */
        $refreshToken = $this->refreshTokenRepository->findOneBy(['token' => $tokenString]);
        if ($refreshToken !== null) {
            $this->refreshTokenRepository->remove($refreshToken, true);
        }
    }
}
