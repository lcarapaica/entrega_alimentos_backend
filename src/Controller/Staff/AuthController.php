<?php

declare(strict_types=1);

namespace App\Controller\Staff;

use App\Entity\Staff\User;
use App\Repository\Staff\UserRepository;
use App\Service\Staff\UserService;
use App\DTO\Staff\UserRegisterDTO;
use App\DTO\Staff\ReauthenticateDTO;
use App\DTO\Staff\UpdatePasswordDTO;
use App\DTO\Staff\ForcePasswordChangeDTO;
use App\DTO\Staff\UpdateProfileDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AuthController extends AbstractController
{
    private UserService $userService;
    private UserRepository $userRepository;
    private ValidatorInterface $validator;

    public function __construct(UserService $userService, UserRepository $userRepository, ValidatorInterface $validator)
    {
        $this->userService = $userService;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    /**
     * @Route("/api/register", name="api_register", methods={"POST"})
     */
    public function register(Request $request): JsonResponse
    {
        // Extract the request payload and bind it to the registration DTO
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new UserRegisterDTO($data);

        // Validate the DTO properties against defined constraints
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        // Register the new user and handle potential validation or authorization errors
        try {
            $user = $this->userService->registerUser($dto, $currentUser);
            return $this->json($this->serializeUser($user), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (AccessDeniedException $e) {
            $status = $currentUser ? 403 : 401;
            return $this->json(['error' => $e->getMessage()], $status);
        }
    }

    /**
     * @Route("/api/auth/logout", name="api_logout", methods={"POST"})
     */
    public function logout(): JsonResponse
    {
        return $this->json(['message' => 'Logged out successfully. Please delete the client-side token.']);
    }

    /**
     * @Route("/api/auth/me", name="api_me", methods={"GET"})
     */
    public function me(): JsonResponse
    {
        // Enforce that the request is authenticated
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        return $this->json($this->serializeUser($currentUser));
    }

    /**
     * @Route("/api/auth/reauthenticate", name="api_reauthenticate", methods={"POST"})
     */
    public function reauthenticate(Request $request): JsonResponse
    {
        // Enforce that the request is authenticated
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        // Bind request data to the re-authentication DTO
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new ReauthenticateDTO($data);

        // Validate the password input
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['valid' => false, 'error' => 'Password is required.'], 400);
        }

        // Verify the password and return validation results
        $isValid = $this->userService->verifyPassword($currentUser, $dto->password);
        return $this->json(['valid' => $isValid]);
    }

    /**
     * @Route("/api/auth/me/password", name="api_update_password", methods={"PUT"})
     */
    public function updatePassword(Request $request): JsonResponse
    {
        // Enforce that the request is authenticated
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        // Bind the payload to the password update DTO
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new UpdatePasswordDTO($data);

        // Validate password change rules
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        // Change the user's password and catch errors
        try {
            $this->userService->updatePassword($currentUser, $dto);
            return $this->json(['message' => 'Password updated successfully.']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/auth/me", name="api_update_profile", methods={"PUT"})
     */
    public function updateProfile(Request $request): JsonResponse
    {
        // Enforce that the request is authenticated
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        // Bind the payload to the profile update DTO
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new UpdateProfileDTO($data);

        // Validate payload properties
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        // Update profile details and capture errors
        try {
            $this->userService->updateProfile($currentUser, $dto, $currentUser);
            return $this->json($this->serializeUser($currentUser));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * @Route("/api/users/{id}/force-password", name="api_force_password", methods={"POST"})
     */
    public function forcePassword(int $id, Request $request): JsonResponse
    {
        // Enforce that the request is authenticated
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        // Bind request data to the force-password DTO
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new ForcePasswordChangeDTO($data);

        // Validate password input
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        // Change target user password directly
        try {
            $this->userService->forcePasswordChange($targetUser, $dto->new_password, $currentUser);
            return $this->json(['message' => 'Password changed successfully.']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * @Route("/api/users/{id}/toggle-active", name="api_toggle_active", methods={"POST"})
     */
    public function toggleActive(int $id): JsonResponse
    {
        // Enforce that the request is authenticated
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        // Find the target user to toggle
        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        // Toggle user active status using the service
        try {
            $this->userService->toggleActive($targetUser, $currentUser);
            return $this->json($this->serializeUser($targetUser));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * @Route("/api/users/{id}", name="api_delete_user", methods={"DELETE"})
     */
    public function deleteUser(int $id): JsonResponse
    {
        // Enforce that the request is authenticated
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        // Find the target user to delete
        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        // Soft delete the user record
        try {
            $this->userService->softDelete($targetUser, $currentUser);
            return $this->json(['message' => 'User soft-deleted successfully.']);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Serializes User entity to array.
     */
    private function serializeUser(User $user): array
    {
        // Extract associated employee data if present
        $employee = $user->getEmployee();
        $employeeData = null;
        if ($employee !== null) {
            $employeeData = [
                'id' => $employee->getId(),
                'national_id' => $employee->getNationalId(),
                'p00_code' => $employee->getP00Code(),
                'first_name' => $employee->getFirstName(),
                'last_name' => $employee->getLastName(),
                'foto_path' => $employee->getFotoPath(),
            ];
        }

        // Return a clean array representation of the User entity
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'is_active' => $user->getIsActive(),
            'registered_at' => $user->getRegisteredAt() ? $user->getRegisteredAt()->format(\DateTimeInterface::ATOM) : null,
            'deleted_at' => $user->getDeletedAt() ? $user->getDeletedAt()->format(\DateTimeInterface::ATOM) : null,
            'employee' => $employeeData,
        ];
    }
}
