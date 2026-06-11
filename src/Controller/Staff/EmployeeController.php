<?php

declare(strict_types=1);

namespace App\Controller\Staff;

use App\DTO\Staff\CreateEmployeeDTO;
use App\DTO\Staff\EmployeeFilterDTO;
use App\DTO\Staff\UpdateEmployeeDTO;
use App\Entity\Staff\Employee;
use App\Repository\Staff\EmployeeRepository;
use App\Repository\Structure\SiteRepository;
use App\Service\Staff\EmployeeImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;

/**
 * @Route("/api/employees")
 */
class EmployeeController extends AbstractController
{
    private EmployeeImportService   $importService;
    private EmployeeRepository      $employeeRepository;
    private SiteRepository          $siteRepository;
    private EntityManagerInterface  $em;
    private ValidatorInterface      $validator;

    public function __construct(
        EmployeeImportService  $importService,
        EmployeeRepository     $employeeRepository,
        SiteRepository         $siteRepository,
        EntityManagerInterface $em,
        ValidatorInterface     $validator
    ) {
        $this->importService      = $importService;
        $this->employeeRepository = $employeeRepository;
        $this->siteRepository     = $siteRepository;
        $this->em                 = $em;
        $this->validator          = $validator;
    }

    /**
     * @Route("", name="api_employees_list", methods={"GET"})
     *
     * @OA\Get(
     *     path="/api/employees",
     *     summary="List and filter employees",
     *     description="Returns a paginated list of employees. Supports search across full_name, national_id, and p00_code. Defaults to active employees only.",
     *     tags={"Employees"},
     *     @OA\Parameter(name="search",   in="query", required=false, description="Search term (matches full_name, national_id, p00_code)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page",     in="query", required=false, description="Page number",                                              @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="limit",    in="query", required=false, description="Items per page (max 100)",                                 @OA\Schema(type="integer", default=25)),
     *     @OA\Parameter(name="isActive", in="query", required=false, description="Filter by active status: true | false | all",             @OA\Schema(type="string",  default="true")),
     *     @OA\Parameter(name="sort",     in="query", required=false, description="Sort field: id | full_name | national_id",                @OA\Schema(type="string",  default="id")),
     *     @OA\Parameter(name="order",    in="query", required=false, description="Sort direction: ASC | DESC",                              @OA\Schema(type="string",  default="ASC")),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated employee list",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id",              type="integer", example=1),
     *                     @OA\Property(property="national_id",     type="string",  example="V-12345678"),
     *                     @OA\Property(property="p00_code",        type="string",  nullable=true, example="P001234"),
     *                     @OA\Property(property="full_name",       type="string",  example="John Doe"),
     *                     @OA\Property(property="job_title",       type="string",  nullable=true, example="Analyst"),
     *                     @OA\Property(property="vice_presidency", type="string",  nullable=true, example="VP Engineering"),
     *                     @OA\Property(property="department",      type="string",  nullable=true, example="Software"),
     *                     @OA\Property(property="is_active",       type="boolean", example=true),
     *                     @OA\Property(property="foto_path",       type="string",  nullable=true, example=null),
     *                     @OA\Property(
     *                         property="site",
     *                         type="object", nullable=true,
     *                         @OA\Property(property="id",     type="integer", example=3),
     *                         @OA\Property(property="name",   type="string",  example="Caracas HQ"),
     *                         @OA\Property(property="region", type="string",  example="Capital"),
     *                         @OA\Property(property="state",  type="string",  example="Miranda")
     *                     ),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object", nullable=true,
     *                         @OA\Property(property="id",    type="integer", example=5),
     *                         @OA\Property(property="email", type="string",  example="john.doe@movilnet.com")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total_items",  type="integer", example=120),
     *                 @OA\Property(property="total_pages",  type="integer", example=5),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="limit",        type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $hasDistributionAccess = $this->isGranted('ROLE_DISTRIBUTION_ADMIN');
        $filter = EmployeeFilterDTO::fromRequest($request, $hasDistributionAccess);

        return $this->json($this->employeeRepository->searchAndPaginate($filter->toArray()));
    }

    /**
     * @Route("/{national_id}", name="api_employees_show", methods={"GET"})
     *
     * @OA\Get(
     *     path="/api/employees/{national_id}",
     *     summary="Get employee by national ID",
     *     description="Returns the full profile of a single employee identified by their cédula (national_id). Inactive employees can only be viewed by a distribution administrator.",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="national_id", in="path", required=true,
     *         description="National ID (cédula) of the employee",
     *         @OA\Schema(type="string", example="V-12345678")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id",              type="integer", example=1),
     *             @OA\Property(property="national_id",     type="string",  example="V-12345678"),
     *             @OA\Property(property="p00_code",        type="string",  nullable=true, example="P001234"),
     *             @OA\Property(property="full_name",       type="string",  example="John Doe"),
     *             @OA\Property(property="job_title",       type="string",  nullable=true, example="Analyst"),
     *             @OA\Property(property="vice_presidency", type="string",  nullable=true, example="VP Engineering"),
     *             @OA\Property(property="department",      type="string",  nullable=true, example="Software"),
     *             @OA\Property(property="is_active",       type="boolean", example=true),
     *             @OA\Property(property="foto_path",       type="string",  nullable=true, example=null),
     *             @OA\Property(
     *                 property="site",
     *                 type="object", nullable=true,
     *                 @OA\Property(property="id",     type="integer", example=3),
     *                 @OA\Property(property="name",   type="string",  example="Caracas HQ"),
     *                 @OA\Property(property="region", type="string",  example="Capital"),
     *                 @OA\Property(property="state",  type="string",  example="Miranda")
     *             ),
     *             @OA\Property(
     *                 property="user",
     *                 type="object", nullable=true,
     *                 @OA\Property(property="id",    type="integer", example=5),
     *                 @OA\Property(property="email", type="string",  example="john.doe@movilnet.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function show(string $national_id): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }

        $employee = $this->employeeRepository->findByNationalId($national_id);
        if ($employee === null) {
            return $this->json(['error' => 'Employee not found.'], 404);
        }

        if (!$employee->getIsActive() && !$this->isGranted('ROLE_DISTRIBUTION_ADMIN')) {
            return $this->json(['error' => 'Employee not found.'], 404);
        }

        return $this->json($this->employeeRepository->serializeEmployee($employee));
    }

    /**
     * @Route("", name="api_employees_create", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/employees",
     *     summary="Create a new employee",
     *     description="Creates a new employee record. Requires Admin or Super Admin role.",
     *     tags={"Employees"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"national_id", "full_name"},
     *             @OA\Property(property="national_id",     type="string", example="V-98765432"),
     *             @OA\Property(property="full_name",       type="string", example="Jane Smith"),
     *             @OA\Property(property="p00_code",        type="string", nullable=true, example="P009876"),
     *             @OA\Property(property="job_title",       type="string", nullable=true, example="Engineer"),
     *             @OA\Property(property="vice_presidency", type="string", nullable=true, example="VP Operations"),
     *             @OA\Property(property="department",      type="string", nullable=true, example="Infrastructure"),
     *             @OA\Property(property="site_id",         type="integer", nullable=true, example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Employee created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id",              type="integer", example=1),
     *             @OA\Property(property="national_id",     type="string",  example="V-12345678"),
     *             @OA\Property(property="p00_code",        type="string",  nullable=true, example="P001234"),
     *             @OA\Property(property="full_name",       type="string",  example="John Doe"),
     *             @OA\Property(property="job_title",       type="string",  nullable=true, example="Analyst"),
     *             @OA\Property(property="vice_presidency", type="string",  nullable=true, example="VP Engineering"),
     *             @OA\Property(property="department",      type="string",  nullable=true, example="Software"),
     *             @OA\Property(property="is_active",       type="boolean", example=true),
     *             @OA\Property(property="foto_path",       type="string",  nullable=true, example=null),
     *             @OA\Property(
     *                 property="site",
     *                 type="object", nullable=true,
     *                 @OA\Property(property="id",     type="integer", example=3),
     *                 @OA\Property(property="name",   type="string",  example="Caracas HQ"),
     *                 @OA\Property(property="region", type="string",  example="Capital"),
     *                 @OA\Property(property="state",  type="string",  example="Miranda")
     *             ),
     *             @OA\Property(
     *                 property="user",
     *                 type="object", nullable=true,
     *                 @OA\Property(property="id",    type="integer", example=5),
     *                 @OA\Property(property="email", type="string",  example="john.doe@movilnet.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error or duplicate national_id / p00_code"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Insufficient permissions")
     * )
     */
    public function create(Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }
        if (!$this->isGranted('ROLE_DISTRIBUTION_ADMIN')) {
            return $this->json(['error' => 'Distribution Admin access required.'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto  = new CreateEmployeeDTO($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 400);
        }

        // Duplicate national_id check
        if ($this->employeeRepository->findByNationalId($dto->national_id) !== null) {
            return $this->json(['error' => sprintf('An employee with national_id "%s" already exists.', $dto->national_id)], 400);
        }

        // Duplicate p00_code check
        if ($dto->p00_code !== null && $this->employeeRepository->findOneBy(['p00_code' => $dto->p00_code]) !== null) {
            return $this->json(['error' => sprintf('An employee with p00_code "%s" already exists.', $dto->p00_code)], 400);
        }

        $employee = new Employee();
        $employee->setNationalId($dto->national_id);
        $employee->setFullName($dto->full_name);
        $employee->setP00Code($dto->p00_code);
        $employee->setJobTitle($dto->job_title);
        $employee->setVicePresidency($dto->vice_presidency);
        $employee->setDepartment($dto->department);

        if ($dto->site_id !== null) {
            $site = $this->siteRepository->find($dto->site_id);
            if ($site === null) {
                return $this->json(['error' => sprintf('Site with id %d not found.', $dto->site_id)], 400);
            }
            $employee->setSite($site);
        }

        $this->em->persist($employee);
        $this->em->flush();

        return $this->json($this->employeeRepository->serializeEmployee($employee), 201);
    }

    /**
     * @Route("/{national_id}", name="api_employees_update", methods={"PUT"})
     *
     * @OA\Put(
     *     path="/api/employees/{national_id}",
     *     summary="Update an existing employee",
     *     description="Updates editable fields of an employee identified by national_id. Only the fields included in the request body are changed (partial update). Requires Admin or Super Admin role.",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="national_id", in="path", required=true,
     *         description="National ID (cédula) of the employee to update",
     *         @OA\Schema(type="string", example="V-12345678")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="full_name",       type="string", nullable=true, example="John Doe Jr."),
     *             @OA\Property(property="p00_code",        type="string", nullable=true, example="P001235"),
     *             @OA\Property(property="job_title",       type="string", nullable=true, example="Senior Analyst"),
     *             @OA\Property(property="vice_presidency", type="string", nullable=true, example="VP Finance"),
     *             @OA\Property(property="department",      type="string", nullable=true, example="Accounting"),
     *             @OA\Property(property="site_id",         type="integer", nullable=true, example=4)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Employee updated successfully"),
     *     @OA\Response(response=400, description="Validation error or duplicate p00_code"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Insufficient permissions"),
     *     @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function update(string $national_id, Request $request): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }
        if (!$this->isGranted('ROLE_DISTRIBUTION_ADMIN')) {
            return $this->json(['error' => 'Distribution Admin access required.'], 403);
        }

        $employee = $this->employeeRepository->findByNationalId($national_id);
        if ($employee === null) {
            return $this->json(['error' => 'Employee not found.'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto  = new UpdateEmployeeDTO($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 400);
        }

        // Duplicate p00_code check (skip if unchanged)
        if (in_array('p00_code', $dto->provided, true) && $dto->p00_code !== null) {
            $existing = $this->employeeRepository->findOneBy(['p00_code' => $dto->p00_code]);
            if ($existing !== null && $existing->getId() !== $employee->getId()) {
                return $this->json(['error' => sprintf('An employee with p00_code "%s" already exists.', $dto->p00_code)], 400);
            }
        }

        // Apply only the provided fields
        if (in_array('full_name', $dto->provided, true) && $dto->full_name !== null) {
            $employee->setFullName($dto->full_name);
        }
        if (in_array('p00_code', $dto->provided, true)) {
            $employee->setP00Code($dto->p00_code);
        }
        if (in_array('job_title', $dto->provided, true)) {
            $employee->setJobTitle($dto->job_title);
        }
        if (in_array('vice_presidency', $dto->provided, true)) {
            $employee->setVicePresidency($dto->vice_presidency);
        }
        if (in_array('department', $dto->provided, true)) {
            $employee->setDepartment($dto->department);
        }
        if (in_array('site_id', $dto->provided, true)) {
            if ($dto->site_id === null) {
                $employee->setSite(null);
            } else {
                $site = $this->siteRepository->find($dto->site_id);
                if ($site === null) {
                    return $this->json(['error' => sprintf('Site with id %d not found.', $dto->site_id)], 400);
                }
                $employee->setSite($site);
            }
        }

        $this->em->flush();

        return $this->json($this->employeeRepository->serializeEmployee($employee));
    }

    /**
     * @Route("/{national_id}/toggle-active", name="api_employees_toggle_active", methods={"PATCH"})
     *
     * @OA\Patch(
     *     path="/api/employees/{national_id}/toggle-active",
     *     summary="Toggle employee active status",
     *     description="Flips the is_active flag of the employee manually (independent of the import pipeline). Requires Admin or Super Admin role.",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="national_id", in="path", required=true,
     *         description="National ID (cédula) of the employee",
     *         @OA\Schema(type="string", example="V-12345678")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Active status toggled",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="national_id", type="string",  example="V-12345678"),
     *             @OA\Property(property="is_active",   type="boolean", example=false),
     *             @OA\Property(property="message",     type="string",  example="Employee deactivated.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Insufficient permissions"),
     *     @OA\Response(response=404, description="Employee not found")
     * )
     */
    public function toggleActive(string $national_id): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['error' => 'Authentication required.'], 401);
        }
        if (!$this->isGranted('ROLE_DISTRIBUTION_ADMIN')) {
            return $this->json(['error' => 'Distribution Admin access required.'], 403);
        }

        $employee = $this->employeeRepository->findByNationalId($national_id);
        if ($employee === null) {
            return $this->json(['error' => 'Employee not found.'], 404);
        }

        $newStatus = !$employee->getIsActive();
        $employee->setIsActive($newStatus);
        $this->em->flush();

        return $this->json([
            'national_id' => $employee->getNationalId(),
            'is_active'   => $newStatus,
            'message'     => $newStatus ? 'Employee activated.' : 'Employee deactivated.',
        ]);
    }

    /**
     * @Route("/import", name="api_employees_import", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/employees/import",
     *     summary="Import employees from an Excel file",
     *     description="Accepts an .xls or .xlsx file. Upserts employees matched by cédula, updates job title / department / vice presidency, and deactivates anyone absent from the file (treated as terminated). Returns a summary of the operation.",
     *     tags={"Employees"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(property="file", type="string", format="binary", description="Excel file (.xls or .xlsx) exported from ZAP")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Import completed. Returns a summary of created, updated, deactivated, and skipped records.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="created",     type="integer", example=42),
     *             @OA\Property(property="updated",     type="integer", example=310),
     *             @OA\Property(property="deactivated", type="integer", example=5),
     *             @OA\Property(property="skipped",     type="integer", example=2),
     *             @OA\Property(property="errors",      type="array",   @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=400, description="No file uploaded or invalid file type"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Insufficient permissions")
     * )
     */
    public function import(Request $request): JsonResponse
    {
        // Only distribution admins may run an import — a bad import deactivates employees
        if (!$this->isGranted('ROLE_DISTRIBUTION_ADMIN')) {
            return $this->json(['error' => 'Distribution Admin access required to run an employee import.'], 403);
        }

        $file = $request->files->get('file');

        if ($file === null) {
            return $this->json(['error' => 'No file was uploaded. Send the file under the "file" field.'], 400);
        }

        // Restrict to Excel formats only — do not accept CSV or anything else
        $allowedExtensions = ['xls', 'xlsx'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions, true)) {
            return $this->json([
                'error' => sprintf(
                    'Invalid file type ".%s". Only Excel files (.xls, .xlsx) are accepted.',
                    $extension
                )
            ], 400);
        }

        // Move to a temp path — the service reads from disk, not a stream
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('emp_import_', true) . '.' . $extension;
        $file->move(sys_get_temp_dir(), basename($tempPath));

        try {
            $summary = $this->importService->importFromFile($tempPath);
        } finally {
            // Always clean up the temp file regardless of outcome
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }

        return $this->json($summary);
    }
}
