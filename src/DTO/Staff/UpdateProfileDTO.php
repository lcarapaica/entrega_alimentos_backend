<?php

declare(strict_types=1);

namespace App\DTO\Staff;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProfileDTO
{
    /**
     * @Assert\Email(message="Invalid email address.")
     * @Assert\Length(max=180)
     */
    public ?string $email = null;

    /**
     * @Assert\Type(type="array", message="Roles must be an array.")
     */
    public ?array $roles = null;

    /**
     * @Assert\Type(type="string", message="National ID must be a string.")
     */
    public ?string $national_id = null;

    public function __construct(array $data = [])
    {
        $this->email = isset($data['email']) ? (string) $data['email'] : null;
        $this->roles = isset($data['roles']) ? (array) $data['roles'] : null;
        $this->national_id = isset($data['national_id']) ? (string) $data['national_id'] : null;
    }
}
