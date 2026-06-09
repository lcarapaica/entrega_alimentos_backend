<?php

declare(strict_types=1);

namespace App\DTO\Staff;

use Symfony\Component\Validator\Constraints as Assert;

class UserRegisterDTO
{
    /**
     * @Assert\NotBlank(message="Email is required.")
     * @Assert\Email(message="Invalid email address.")
     * @Assert\Length(max=180)
     */
    public string $email;

    /**
     * @Assert\NotBlank(message="Password is required.")
     * @Assert\Length(
     *     min=3,
     *     minMessage="Password must be at least 3 characters long.",
     *     max=255
     * )
     */
    public string $password;

    /**
     * @Assert\NotBlank(message="Roles are required.")
     * @Assert\Type(type="array", message="Roles must be an array.")
     */
    public array $roles;

    /**
     * @Assert\Type(type="string", message="National ID must be a string.")
     */
    public ?string $national_id = null;

    public function __construct(array $data = [])
    {
        $this->email = isset($data['email']) ? (string) $data['email'] : '';
        $this->password = isset($data['password']) ? (string) $data['password'] : '';
        $this->roles = isset($data['roles']) ? (array) $data['roles'] : [];
        $this->national_id = isset($data['national_id']) ? (string) $data['national_id'] : null;
    }
}
