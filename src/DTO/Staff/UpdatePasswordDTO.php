<?php

declare(strict_types=1);

namespace App\DTO\Staff;

use Symfony\Component\Validator\Constraints as Assert;

class UpdatePasswordDTO
{
    /**
     * @Assert\NotBlank(message="Current password is required.")
     */
    public string $current_password;

    /**
     * @Assert\NotBlank(message="New password is required.")
     * @Assert\Length(
     *     min=3,
     *     minMessage="New password must be at least 3 characters long.",
     *     max=255
     * )
     */
    public string $new_password;

    public function __construct(array $data = [])
    {
        $this->current_password = (string) ($data['current_password'] ?? '');
        $this->new_password = (string) ($data['new_password'] ?? '');
    }
}
