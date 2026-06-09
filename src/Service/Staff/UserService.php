<?php

declare(strict_types=1);

namespace App\Service\Staff;

use App\Entity\Staff\User;
use App\Repository\Staff\UserRepository;
use App\Repository\Staff\EmployeeRepository;
use App\DTO\Staff\UserRegisterDTO;
use App\DTO\Staff\UpdatePasswordDTO;
use App\DTO\Staff\UpdateProfileDTO;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserService
{
    private UserRepository $userRepository;
    private EmployeeRepository $employeeRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        EmployeeRepository $employeeRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->employeeRepository = $employeeRepository;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Registers a new User.
     *
     * @param UserRegisterDTO $dto
     * @param User|null $creator Nullable for bootstrap (chicken-and-egg setup)
     * @return User
     */
    public function registerUser(UserRegisterDTO $dto, ?User $creator): User
    {
        // Check if this is the first user registration in bootstrap mode
        $isBootstrap = $this->userRepository->count([]) === 0;
        $roles = $dto->roles;

        if ($isBootstrap) {
            // FORCE the first user to be a Super Admin so you don't get locked out
            $roles = ['ROLE_SUPER_ADMIN', 'ROLE_USER'];
        } else {
            // Require an authenticated operator if bootstrap mode is finished
            if ($creator === null) {
                throw new AccessDeniedException("Authenticated operator required to create users.");
            }

            // Verify the creator has permissions to create accounts
            $creatorRoles = $creator->getRoles();
            if (!in_array('ROLE_ADMIN', $creatorRoles) && !in_array('ROLE_SUPER_ADMIN', $creatorRoles)) {
                throw new AccessDeniedException("Only Admins and Super Admins can register new users.");
            }

            // Block admins from registering superadmin accounts
            if (in_array('ROLE_SUPER_ADMIN', $roles) && !in_array('ROLE_SUPER_ADMIN', $creatorRoles)) {
                throw new AccessDeniedException("Only Super Admins can assign the Super Admin role.");
            }

            // Guarantee basic role compliance for normal registrations
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
            }
        }

        // Clean up roles array to avoid any duplicates
        $roles = array_values(array_unique($roles));

        // Verify the email address is not already registered
        $existing = $this->userRepository->findOneBy(['email' => $dto->email]);
        if ($existing !== null) {
            throw new \InvalidArgumentException("Email is already registered.");
        }

        // Resolve and validate employee linkage according to role rules
        $employee = null;
        if (in_array('ROLE_SUPER_ADMIN', $roles)) {
            if ($dto->national_id !== null) {
                $employee = $this->getAndValidateEmployee($dto->national_id);
            }
        } else {
            if ($dto->national_id === null || trim($dto->national_id) === '') {
                throw new \InvalidArgumentException("Regular users must be linked to a valid employee.");
            }
            $employee = $this->getAndValidateEmployee($dto->national_id);
        }

        // Populate and hash password for the new User entity
        $user = new User();
        $user->setEmail($dto->email);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setIsActive(true);
        $user->setRegisteredAt(new \DateTime());

        if ($employee !== null) {
            $user->setEmployee($employee);
        }

        // Persist the user to the database
        $this->userRepository->add($user, true);

        return $user;
    }

    public function verifyPassword(User $user, string $password): bool
    {
        // Perform standard password validity check
        return $this->passwordHasher->isPasswordValid($user, $password);
    }

    public function updatePassword(User $user, UpdatePasswordDTO $dto): void
    {
        // Verify current password is correct
        if (!$this->verifyPassword($user, $dto->current_password)) {
            throw new \InvalidArgumentException("Incorrect current password.");
        }

        // Block changing password to the exact same one
        if ($this->verifyPassword($user, $dto->new_password)) {
            throw new \InvalidArgumentException("New password cannot be the same as the old password.");
        }

        // Hash and save the new password
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->new_password));
        $this->userRepository->add($user, true);
    }

    public function forcePasswordChange(User $userToModify, string $newPassword, User $actor): void
    {
        // Enforce hierarchy restrictions on target user modifications
        $this->checkHierarchy($actor, $userToModify);

        // Block resetting the password to the current one
        if ($this->verifyPassword($userToModify, $newPassword)) {
            throw new \InvalidArgumentException("New password cannot be the same as the old password.");
        }

        // Hash and save the forced password update
        $userToModify->setPassword($this->passwordHasher->hashPassword($userToModify, $newPassword));
        $this->userRepository->add($userToModify, true);
    }

    public function updateProfile(User $userToModify, UpdateProfileDTO $dto, User $actor): void
    {
        // Enforce hierarchy restrictions on profile updates
        $this->checkHierarchy($actor, $userToModify);

        // Update email if requested and ensure uniqueness
        if ($dto->email !== null && $dto->email !== $userToModify->getEmail()) {
            $existing = $this->userRepository->findOneBy(['email' => $dto->email]);
            if ($existing !== null && $existing->getId() !== $userToModify->getId()) {
                throw new \InvalidArgumentException("Email is already in use.");
            }
            $userToModify->setEmail($dto->email);
        }

        // Update target roles ensuring basic rules and superadmin constraints
        $roles = $userToModify->getRoles();
        if ($dto->roles !== null) {
            $targetRoles = $dto->roles;
            if (!in_array('ROLE_USER', $targetRoles)) {
                $targetRoles[] = 'ROLE_USER';
            }

            $actorRoles = $actor->getRoles();
            if (in_array('ROLE_SUPER_ADMIN', $targetRoles) && !in_array('ROLE_SUPER_ADMIN', $actorRoles)) {
                throw new AccessDeniedException("Only Super Admins can assign the Super Admin role.");
            }

            if (!in_array('ROLE_SUPER_ADMIN', $targetRoles) && $userToModify->getEmployee() === null) {
                throw new \InvalidArgumentException("Regular users must be linked to a valid employee.");
            }

            $userToModify->setRoles($targetRoles);
        }

        // Update employee linkage if authorized and validate constraints
        $currentEmployee = $userToModify->getEmployee();
        $currentNationalId = $currentEmployee ? $currentEmployee->getNationalId() : null;

        if ($dto->national_id !== null || $currentNationalId !== null) {
            $normalizedNew = $dto->national_id !== null ? trim($dto->national_id) : '';
            $normalizedCurrent = $currentNationalId !== null ? trim($currentNationalId) : '';

            if ($normalizedNew !== $normalizedCurrent) {
                $actorRoles = $actor->getRoles();
                if (!in_array('ROLE_ADMIN', $actorRoles) && !in_array('ROLE_SUPER_ADMIN', $actorRoles)) {
                    throw new AccessDeniedException("Only Admins and Super Admins can change the linked employee record.");
                }

                $roles = $userToModify->getRoles();
                if (in_array('ROLE_SUPER_ADMIN', $roles)) {
                    if ($dto->national_id === null || trim($dto->national_id) === '') {
                        $userToModify->setEmployee(null);
                    } else {
                        $employee = $this->getAndValidateEmployee($dto->national_id, $userToModify);
                        $userToModify->setEmployee($employee);
                    }
                } else {
                    if ($dto->national_id === null || trim($dto->national_id) === '') {
                        throw new \InvalidArgumentException("Regular users must be linked to a valid employee.");
                    }
                    $employee = $this->getAndValidateEmployee($dto->national_id, $userToModify);
                    $userToModify->setEmployee($employee);
                }
            }
        }

        // Persist the updated User profile settings
        $this->userRepository->add($userToModify, true);
    }

    public function softDelete(User $userToDelete, User $actor): void
    {
        // Fetch the roles of the actor initiating the deactivation
        $actorRoles = $actor->getRoles();
        
        // Block regular operators from deleting other user accounts
        if (!in_array('ROLE_ADMIN', $actorRoles) && !in_array('ROLE_SUPER_ADMIN', $actorRoles)) {
            if ($actor->getId() !== $userToDelete->getId()) {
                throw new AccessDeniedException("Users cannot delete other users, only themselves.");
            }
        } else {
            // Apply hierarchy rules for operators modifying other users
            $this->checkHierarchy($actor, $userToDelete);
        }

        // Return early if the user is already soft-deleted
        if (!$userToDelete->getIsActive()) {
            return;
        }

        // Perform soft deletion: deactivate, record timestamp, suffix email address
        $deletedAt = new \DateTime();
        $userToDelete->setIsActive(false);
        $userToDelete->setDeletedAt($deletedAt);
        $userToDelete->setEmail($userToDelete->getEmail() . '.' . $deletedAt->getTimestamp());

        // Persist deactivation changes
        $this->userRepository->add($userToDelete, true);
    }

    public function toggleActive(User $userToToggle, User $actor): void
    {
        // Require administrative permission to toggle states
        $actorRoles = $actor->getRoles();
        if (!in_array('ROLE_ADMIN', $actorRoles) && !in_array('ROLE_SUPER_ADMIN', $actorRoles)) {
            throw new AccessDeniedException("Only Admins and Super Admins can toggle user active status.");
        }

        // Apply hierarchy rules for operators modifying other users
        $this->checkHierarchy($actor, $userToToggle);

        // Deactivate active users or reactivate inactive ones
        if ($userToToggle->getIsActive()) {
            $this->softDelete($userToToggle, $actor);
        } else {
            // Restore email format by stripping the timestamp suffix
            $email = $userToToggle->getEmail();
            $lastDotPos = strrpos($email, '.');
            if ($lastDotPos !== false) {
                $suffix = substr($email, $lastDotPos + 1);
                if (ctype_digit($suffix)) {
                    $email = substr($email, 0, $lastDotPos);
                }
            }

            // Ensure the restored email is not already taken
            $existing = $this->userRepository->findOneBy(['email' => $email]);
            if ($existing !== null && $existing->getId() !== $userToToggle->getId()) {
                throw new \InvalidArgumentException("Cannot reactivate: email '{$email}' is already taken.");
            }

            // Reactivate the user account state
            $userToToggle->setEmail($email);
            $userToToggle->setIsActive(true);
            $userToToggle->setDeletedAt(null);

            // Persist the reactivated user settings
            $this->userRepository->add($userToToggle, true);
        }
    }

    private function getAndValidateEmployee(string $nationalId, ?User $userToModify = null): \App\Entity\Staff\Employee
    {
        // Locate employee by national identifier
        $employee = $this->employeeRepository->findOneBy(['national_id' => $nationalId]);
        if ($employee === null) {
            throw new \InvalidArgumentException("Employee with national ID '{$nationalId}' not found.");
        }

        // Verify that the employee is active
        if (!$employee->getIsActive()) {
            throw new \InvalidArgumentException("Employee '{$nationalId}' is inactive.");
        }

        // Check if the employee is already linked to another user account
        $linkedUser = $employee->getUser();
        if ($linkedUser !== null) {
            if ($userToModify === null || $linkedUser->getId() !== $userToModify->getId()) {
                throw new \InvalidArgumentException("Employee '{$nationalId}' is already linked to another user.");
            }
        }

        return $employee;
    }

    private function checkHierarchy(User $actor, User $target): void
    {
        // Allow self modification
        if ($actor->getId() === $target->getId()) {
            return;
        }

        // Translate roles to comparable hierarchy level values
        $actorMax = $this->getMaxRoleValue($actor);
        $targetMax = $this->getMaxRoleValue($target);

        // Block regular users from modifying others
        if ($actorMax === 1) {
            throw new AccessDeniedException("Users can only modify themselves.");
        }

        // Prevent modification of superior or equal hierarchy accounts
        if ($actorMax <= $targetMax) {
            throw new AccessDeniedException("Access denied: you do not have permission to modify a user with equal or higher role.");
        }
    }

    private function getMaxRoleValue(User $user): int
    {
        // Translate roles to relative hierarchy values
        $roles = $user->getRoles();
        if (in_array('ROLE_SUPER_ADMIN', $roles)) {
            return 3;
        }
        if (in_array('ROLE_ADMIN', $roles)) {
            return 2;
        }
        return 1;
    }
}
