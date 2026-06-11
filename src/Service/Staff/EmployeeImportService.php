<?php

declare(strict_types=1);

namespace App\Service\Staff;

use App\Entity\Staff\Employee;
use App\Repository\Staff\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeImportService
{
    /**
     * Column header names as they appear in the ZAP export file.
     */
    private const COLUMN_MAP = [
        'full_name'       => 'APELLIDO(S) Y NOMBRE(S)',
        'cedula'          => 'CÉDULA',
        'cargo'           => 'CARGO',
        'vice_presidency' => 'PRESIDENCIA / VP',
        'department'      => 'GERENCIA GENERAL',
    ];

    private EmployeeRepository $employeeRepository;
    private EntityManagerInterface $em;

    public function __construct(
        EmployeeRepository $employeeRepository,
        EntityManagerInterface $em
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->em                 = $em;
    }

    /**
     * Processes an uploaded Excel file (.xls or .xlsx) and synchronizes
     * the Employee table against the contents.
     *
     * @param string $filePath Absolute path to the uploaded temp file on disk
     * @return array{created: int, updated: int, deactivated: int, skipped: int, errors: string[], deactivation_skipped: bool}
     */
    public function importFromFile(string $filePath): array
    {
        $summary = [
            'created'              => 0,
            'updated'              => 0,
            'deactivated'          => 0,
            'skipped'              => 0,
            'errors'               => [],
            'deactivation_skipped' => false,
        ];

        // Load the spreadsheet — auto-detects .xls vs .xlsx
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $e) {
            $summary['errors'][] = 'Could not read file: ' . $e->getMessage();
            return $summary;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows  = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            $summary['errors'][] = 'The file is empty.';
            return $summary;
        }

        // Locate header row and build a column-index map
        $columnIndex = $this->resolveColumnIndex($rows[0] ?? []);
        if ($columnIndex === null) {
            $summary['errors'][] = sprintf(
                'Could not locate required columns. Expected at minimum: %s and %s',
                self::COLUMN_MAP['cedula'],
                self::COLUMN_MAP['full_name']
            );
            return $summary;
        }

        // Process data rows — cedula is the ONLY hard requirement per row

        // Preload all existing employees into memory in one query.
        // This avoids N+1 selects when processing large files (e.g. 2300 employees).
        $existingMap = $this->employeeRepository->findAllIndexedByNationalId();
        $processedNationalIds = [];

        foreach (array_slice($rows, 1) as $rowNumber => $row) {
            $rawCedula   = trim((string) ($row[$columnIndex['cedula']] ?? ''));
            $rawFullName = trim((string) ($row[$columnIndex['full_name']] ?? ''));

            // Skip fully empty rows silently
            if ($rawCedula === '' && $rawFullName === '') {
                $summary['skipped']++;
                continue;
            }

            // Cedula is the identity key — nothing we can do without it
            if ($rawCedula === '') {
                $summary['errors'][] = sprintf('Row %d: missing cédula, skipped.', $rowNumber + 2);
                $summary['skipped']++;
                continue;
            }

            try {
                // Read optional string fields directly — null when column absent or empty
                $cargo          = $this->nullableString($columnIndex, $row, 'cargo');
                $vicePresidency = $this->nullableString($columnIndex, $row, 'vice_presidency');
                $department     = $this->nullableString($columnIndex, $row, 'department');

                $existing = $existingMap[$rawCedula] ?? null;

                if ($existing !== null) {
                    // Always re-activate and update reference string fields
                    $existing->setJobTitle($cargo);
                    $existing->setVicePresidency($vicePresidency);
                    $existing->setDepartment($department);
                    $existing->setIsActive(true);

                    // Only overwrite names when the file actually provides them
                    if ($rawFullName !== '') {
                        $existing->setFullName($this->normalizeName($rawFullName));
                    }

                    $this->employeeRepository->add($existing);
                    $summary['updated']++;
                } else {
                    $fullName = $rawFullName !== ''
                        ? $this->normalizeName($rawFullName)
                        : $rawCedula;

                    $employee = new Employee();
                    $employee->setNationalId($rawCedula);
                    $employee->setFullName($fullName);
                    $employee->setJobTitle($cargo);
                    $employee->setVicePresidency($vicePresidency);
                    $employee->setDepartment($department);
                    $employee->setIsActive(true);
                    $this->employeeRepository->add($employee);
                    $summary['created']++;
                }

                $processedNationalIds[] = $rawCedula;
            } catch (\Throwable $e) {
                $summary['errors'][] = sprintf('Row %d (cédula %s): %s', $rowNumber + 2, $rawCedula, $e->getMessage());
                $summary['skipped']++;
            }
        }

        // Commit all pending inserts/updates inside a transaction.
        // If flush() throws, rolls back the entire batch.
        try {
            $this->em->beginTransaction();
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            $summary['errors'][] = 'Database error during save, all changes rolled back: ' . $e->getMessage();
            return $summary;
        }

        // Deactivate employees NOT present in the file — but ONLY when zero row-level errors occurred
        if (!empty($processedNationalIds) && empty($summary['errors'])) {
            $summary['deactivated'] = $this->employeeRepository->deactivateMissingEmployees($processedNationalIds);
        } elseif (!empty($summary['errors'])) {
            $summary['deactivation_skipped'] = true;
        }

        return $summary;
    }

    // HELPER METHODS BELOW

    /**
     * Reads the header row and returns a map of internal alias → zero-based column index.
     * Only 'cedula' and 'full_name' are required; all others default to -1 sentinel.
     *
     * @param array<int, mixed> $headerRow
     * @return array<string, int>|null
     */
    private function resolveColumnIndex(array $headerRow): ?array
    {
        $normalized = [];
        foreach ($headerRow as $idx => $cell) {
            $normalized[$idx] = mb_strtoupper(trim((string) $cell));
        }

        $index = [];
        foreach (self::COLUMN_MAP as $alias => $expectedHeader) {
            $expectedNorm = mb_strtoupper(trim($expectedHeader));
            $found = false;
            foreach ($normalized as $idx => $cellNorm) {
                if ($cellNorm === $expectedNorm || strpos($cellNorm, $expectedNorm) !== false) {
                    $index[$alias] = $idx;
                    $found = true;
                    break;
                }
            }

            if (!$found && in_array($alias, ['cedula', 'full_name'], true)) {
                return null; // required header missing
            }

            if (!$found) {
                $index[$alias] = -1; // optional column absent
            }
        }

        return $index;
    }

    /**
     * Reads an optional column from a row.
     * Returns null when the column was absent in the header (-1 sentinel) or the cell is empty.
     *
     * @param array<string, int> $columnIndex
     * @param array<int, mixed>  $row
     */
    private function nullableString(array $columnIndex, array $row, string $alias): ?string
    {
        $idx = $columnIndex[$alias] ?? -1;
        if ($idx === -1) {
            return null;
        }

        $value = $this->toTitleCase(trim((string) ($row[$idx] ?? '')));
        return $value !== '' ? $value : null;
    }

    /**
     * Normalizes a raw name string from ZAP into a clean title-cased full name.
     * Handles both "GARCIA, JOSE" and "GARCIA JOSE" by replacing commas with spaces.
     */
    private function normalizeName(string $raw): string
    {
        $cleaned = str_replace(',', ' ', $raw);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $cleaned;
        return $this->toTitleCase(trim($cleaned));
    }

    /**
     * Converts an ALL-CAPS string to Title Case.
     * Example: "JOSE MARTINEZ" → "Jose Martinez"
     */
    private function toTitleCase(string $str): string
    {
        return mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
    }
}
