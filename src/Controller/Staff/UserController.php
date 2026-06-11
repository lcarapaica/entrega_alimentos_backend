<?php

declare(strict_types=1);

namespace App\Controller\Staff;

use App\Entity\Staff\User;
use App\Repository\Staff\UserRepository;
use App\Service\Staff\UserService;
use App\Service\Staff\RefreshTokenService;
use App\DTO\Staff\UserRegisterDTO;
use App\DTO\Staff\UserFilterDTO;
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
use OpenApi\Annotations as OA;

class UserController extends AbstractController
{
    private UserService $userService;
    private UserRepository $userRepository;
    private ValidatorInterface $validator;
    private RefreshTokenService $refreshTokenService;

    public function __construct(
        UserService $userService,
        UserRepository $userRepository,
        ValidatorInterface $validator,
        RefreshTokenService $refreshTokenService
    ) {
        $this->userService = $userService;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->refreshTokenService = $refreshTokenService;
    }

    /**
     * @Route("/api/login", name="api_login", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authenticate user to obtain JWT token",
     *     description="Authenticate user to obtain JWT token",
     *     tags={"Me"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@movilnet.com"),
     *             @OA\Property(property="password", type="string", format="password", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", description="Stateless JWT token to be included in the Authorization header."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", format="email", example="admin@movilnet.com"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_SUPER_ADMIN"}),
     *                 @OA\Property(property="mustChangePassword", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="refresh_token", type="string", description="Stateless refresh token to obtain a new JWT token.", example="4b74f1bbb1184bb6...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials."
     *     )
     * )
     */
    public function login(): void {}

    /**
     * @Route("/api/token/refresh", name="api_token_refresh", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/token/refresh",
     *     summary="Refresh JWT token using a refresh token",
     *     description="Obtains a new JWT token and a rotated refresh token using an existing valid refresh token.",
     *     tags={"Me"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="abc123xyz...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", description="New stateless JWT token."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", format="email", example="admin@movilnet.com"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_SUPER_ADMIN"}),
     *                 @OA\Property(property="mustChangePassword", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="refresh_token", type="string", description="New rotated refresh token.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Refresh token is required"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired refresh token"
     *     )
     * )
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $token = $data['refresh_token'] ?? null;

        if ($token === null || !is_string($token) || trim($token) === '') {
            return $this->json(['error' => 'Refresh token is required.'], 400);
        }

        try {
            $tokens = $this->refreshTokenService->refresh($token);
            return $this->json($tokens);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 401);
        }
    }

    /**
     * @Route("/api/auth/reauthenticate", name="api_reauthenticate", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/auth/reauthenticate",
     *     summary="Re-authenticate user",
     *     description="Validates if the provided password matches the logged-in user's password. Useful to reconfirm identity for sensitive operations.",
     *     tags={"Me"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"password"},
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful validation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="valid", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Re-authentication successful.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Password is required"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid password or unauthenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="valid", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Invalid password.")
     *         )
     *     )
     * )
     */
    public function reauthenticate(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new ReauthenticateDTO($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['valid' => false, 'error' => 'Password is required.'], 400);
        }

        $isValid = $this->userService->verifyPassword($currentUser, $dto->password);
        if (!$isValid) {
            return $this->json(['valid' => false, 'error' => 'Invalid password.'], 401);
        }
        return $this->json(['valid' => true, 'message' => 'Valid password.']);
    }

    /**
     * @Route("/api/auth/logout", name="api_logout", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Log out user",
     *     description="Invalidates the JWT token on the client side and deletes the associated refresh token from the database if provided.",
     *     tags={"Me"},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="refresh_token", type="string", nullable=true, example="abc123xyz...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful"
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['refresh_token']) && is_string($data['refresh_token'])) {
            $this->refreshTokenService->invalidateToken($data['refresh_token']);
        }

        return $this->json(['message' => 'Logged out successfully. Ensure the client-side token is deleted.']);
    }

    /**
     * @Route("/api/auth/me", name="api_me", methods={"GET"})
     *
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Get authenticated user profile",
     *     description="Returns detailed profile data of the currently authenticated user (including associated employee data if linked).",
     *     tags={"Me"},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", format="email", example="user@movilnet.com"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_ADMIN"}),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="must_change_password", type="boolean", example=false),
     *             @OA\Property(property="registered_at", type="string", format="date-time", example="2026-06-11T19:30:00Z"),
     *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(
     *                 property="employee",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="national_id", type="string", example="V-12345678"),
     *                 @OA\Property(property="p00_code", type="string", nullable=true, example="P001234"),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="foto_path", type="string", nullable=true, example="/uploads/photos/xyz.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        return $this->json($this->serializeUser($currentUser));
    }

    /**
     * @Route("/api/auth/me/password", name="api_update_password", methods={"PUT"})
     *
     * @OA\Put(
     *     path="/api/auth/me/password",
     *     summary="Update password of the authenticated user",
     *     description="Changes the password of the currently logged-in user. Requires the current password to be valid and the new password to be different.",
     *     tags={"Me"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"current_password", "new_password"},
     *             @OA\Property(property="current_password", type="string", format="password", example="SecurePass123!"),
     *             @OA\Property(property="new_password", type="string", format="password", example="NewSecurePass123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data or password update error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new UpdatePasswordDTO($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        try {
            $this->userService->updatePassword($currentUser, $dto);
            return $this->json(['message' => 'Password updated successfully.']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/api/register", name="api_register", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     description="Registers a new user. Requires authenticated Admin or Super Admin credentials.",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="staff@movilnet.com"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!"),
     *             @OA\Property(property="national_id", type="string", nullable=true, example="V-12345678"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_USER"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", format="email", example="staff@movilnet.com"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_USER"}),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="must_change_password", type="boolean", example=true),
     *             @OA\Property(property="registered_at", type="string", format="date-time", example="2026-06-11T19:30:00Z"),
     *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(
     *                 property="employee",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="national_id", type="string", example="V-12345678"),
     *                 @OA\Property(property="p00_code", type="string", nullable=true, example="P001234"),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="foto_path", type="string", nullable=true, example="/uploads/photos/xyz.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data or registration error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Insufficient permissions"
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new UserRegisterDTO($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

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
     * @Route("/api/users", name="api_users_list", methods={"GET"})
     *
     * @OA\Get(
     *     path="/api/users",
     *     summary="List and filter users",
     *     description="Retrieves a paginated list of users. Non-admins can only see active accounts. Admins can filter by active/deleted accounts.",
     *     tags={"Users"},
     *     @OA\Parameter(name="search", in="query", required=false, description="Search term matching email", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", required=false, description="Page number", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="limit", in="query", required=false, description="Items per page", @OA\Schema(type="integer", default=25)),
     *     @OA\Parameter(name="role", in="query", required=false, description="Filter by user role", @OA\Schema(type="string")),
     *     @OA\Parameter(name="isActive", in="query", required=false, description="Filter by active status (true/false). Non-admins are forced to true.", @OA\Schema(type="boolean", default=true)),
     *     @OA\Parameter(name="sort", in="query", required=false, description="Sort field (id, email)", @OA\Schema(type="string", default="id")),
     *     @OA\Parameter(name="order", in="query", required=false, description="Sort direction (ASC, DESC)", @OA\Schema(type="string", default="DESC")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", format="email", example="user@movilnet.com"),
     *                     @OA\Property(property="full_name", type="string", nullable=true, example="John Doe"),
     *                     @OA\Property(property="role", type="string", example="ROLE_ADMIN"),
     *                     @OA\Property(property="isActive", type="boolean", example=true),
     *                     @OA\Property(property="mustChangePassword", type="boolean", example=false),
     *                     @OA\Property(property="registeredAt", type="string", example="2026-06-11 19:30:00"),
     *                     @OA\Property(property="deletedAt", type="string", nullable=true, example=null),
     *                     @OA\Property(
     *                         property="employee",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="national_id", type="string", example="V-12345678"),
     *                         @OA\Property(property="p00_code", type="string", nullable=true, example="P001234"),
     *                         @OA\Property(property="full_name", type="string", example="John Doe"),
     *                         @OA\Property(property="foto_path", type="string", nullable=true, example="/uploads/photos/xyz.jpg")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total_items", type="integer", example=10),
     *                 @OA\Property(property="total_pages", type="integer", example=1),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $hasAdminAccess = $this->isGranted('ROLE_ADMIN');

        $filterInput = UserFilterDTO::fromRequest($request, $hasAdminAccess);

        $result = $this->userRepository->searchAndPaginate($filterInput->toArray());

        return $this->json($result);
    }

    /**
     * @Route("/api/users/{id}", name="api_get_user", methods={"GET"})
     *
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get details of a specific user",
     *     description="Retrieves the profile and linked employee information of a user by their ID. Non-admins can only retrieve themselves, and cannot see soft-deleted users.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", format="email", example="user@movilnet.com"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_ADMIN"}),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="must_change_password", type="boolean", example=false),
     *             @OA\Property(property="registered_at", type="string", format="date-time", example="2026-06-11T19:30:00Z"),
     *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(
     *                 property="employee",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="national_id", type="string", example="V-12345678"),
     *                 @OA\Property(property="p00_code", type="string", nullable=true, example="P001234"),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="foto_path", type="string", nullable=true, example="/uploads/photos/xyz.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $hasAdminAccess = $this->isGranted('ROLE_ADMIN');

        if (!$hasAdminAccess && $currentUser->getId() !== $targetUser->getId()) {
            return $this->json(['error' => 'Access denied.'], 403);
        }

        if ($targetUser->getDeletedAt() !== null && !$hasAdminAccess) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        return $this->json($this->serializeUser($targetUser));
    }

    /**
     * @Route("/api/users/{id}/force-password-change", name="api_force_password", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/users/{id}/force-password-change",
     *     summary="Force password change of another user",
     *     description="Allows an Admin or Super Admin to change the password of another user, respecting the established hierarchy rules.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to modify",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"new_password"},
     *             @OA\Property(property="new_password", type="string", format="password", example="ForcedPass123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password forced successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data or password change error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Insufficient permissions or hierarchy violation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function forcePassword(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new ForcePasswordChangeDTO($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

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
     * @Route("/api/users/{id}", name="api_update_profile", methods={"PUT"})
     *
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update user profile",
     *     description="Updates email, roles, and the linked employee (national_id) of the specified user. Regular users can only modify themselves. Modifying roles, national_id, or other users requires Admin or Super Admin permissions according to role hierarchy.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="new_email@movilnet.com"),
     *             @OA\Property(property="national_id", type="string", nullable=true, example="V-12345678"),
     *             @OA\Property(property="roles", type="array", nullable=true, @OA\Items(type="string"), example={"ROLE_ADMIN"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", format="email", example="new_email@movilnet.com"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_ADMIN"}),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="must_change_password", type="boolean", example=false),
     *             @OA\Property(property="registered_at", type="string", format="date-time", example="2026-06-11T19:30:00Z"),
     *             @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(
     *                 property="employee",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="national_id", type="string", example="V-12345678"),
     *                 @OA\Property(property="p00_code", type="string", nullable=true, example="P001234"),
     *                 @OA\Property(property="full_name", type="string", example="John Doe"),
     *                 @OA\Property(property="foto_path", type="string", nullable=true, example="/uploads/photos/xyz.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data or validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Insufficient permissions or hierarchy violation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function updateProfile(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new UpdateProfileDTO($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        try {
            $this->userService->updateProfile($targetUser, $dto, $currentUser);
            return $this->json($this->serializeUser($targetUser));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * @Route("/api/users/{id}", name="api_delete_user", methods={"DELETE"})
     *
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Soft-delete (deactivate) a user",
     *     description="Deactivates the user (soft-delete), registering deleted_at and suffixing their email with a timestamp. Regular users can only delete themselves.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User soft-deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Insufficient permissions or hierarchy violation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function deleteUser(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        try {
            $this->userService->softDelete($targetUser, $currentUser);
            return $this->json(['message' => 'User soft-deleted successfully.']);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * @Route("/api/users/{id}/toggle-active", name="api_toggle_active", methods={"PATCH"})
     *
     * @OA\Patch(
     *     path="/api/users/{id}/toggle-active",
     *     summary="Toggle active state of another user",
     *     description="Deactivates (soft-delete) or reactivates another user, respecting the established hierarchy. Accessible to Admins or Super Admins.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the user to toggle active/inactive",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Active state toggled successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The account has been disabled.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error toggling active state"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Insufficient permissions or hierarchy violation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function toggleActive(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        try {
            $this->userService->toggleActive($targetUser, $currentUser);
            return $this->json([
                'message' => $targetUser->getIsActive() ? 'The account has been enabled.' : 'The account has been disabled.'
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Serializes User entity to array.
     */
    private function serializeUser(User $user): array
    {
        $employee = $user->getEmployee();
        $employeeData = null;
        if ($employee !== null) {
            $employeeData = [
                'id'          => $employee->getId(),
                'national_id' => $employee->getNationalId(),
                'p00_code'    => $employee->getP00Code(),
                'full_name'   => $employee->getFullName(),
                'foto_path'   => $employee->getFotoPath(),
            ];
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'is_active' => $user->getIsActive(),
            'must_change_password' => $user->getMustChangePassword(),
            'registered_at' => $user->getRegisteredAt() ? $user->getRegisteredAt()->format(\DateTimeInterface::ATOM) : null,
            'deleted_at' => $user->getDeletedAt() ? $user->getDeletedAt()->format(\DateTimeInterface::ATOM) : null,
            'employee' => $employeeData,
        ];
    }
}
