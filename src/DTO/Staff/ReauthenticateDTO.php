<?php

declare(strict_types=1);

namespace App\DTO\Staff;

use Symfony\Component\Validator\Constraints as Assert;

class ReauthenticateDTO
{
    /**
     * @Assert\NotBlank(message="Password is required.")
     */
    public string $password;

    public function __construct(array $data = [])
    {
        $this->password = (string) ($data['password'] ?? '');
    }
}
