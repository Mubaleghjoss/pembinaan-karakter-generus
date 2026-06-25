<?php

namespace App\Imports;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserImport
{
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $failedCount = 0;

    /**
     * Import users dari file Excel
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Skip header row
        $header = array_shift($rows);
        $headerLower = array_map('strtolower', array_map('trim', $header));

        // Get pamong role
        $pamongRole = Role::where('name', 'teacher')->first();
        if (!$pamongRole) {
            throw new \Exception('Role Pamong tidak ditemukan');
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $data = $this->mapRowToData($row, $headerLower);
                $this->validateAndCreate($data, $rowNumber, $pamongRole);
            } catch (\Exception $e) {
                $this->errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                $this->failedCount++;
            }
        }

        return [
            'success' => $this->successCount,
            'failed' => $this->failedCount,
            'errors' => $this->errors,
        ];
    }

    /**
     * Map row data to associative array
     */
    protected function mapRowToData(array $row, array $headers): array
    {
        $headerMapping = [
            'username' => 'username',
            'nama' => 'username',
            'name' => 'username',
            'email' => 'email',
            'password' => 'password',
            'status' => 'status',
        ];

        $data = [];
        foreach ($headers as $index => $header) {
            $normalizedHeader = strtolower(trim($header));
            $fieldName = $headerMapping[$normalizedHeader] ?? $normalizedHeader;
            $data[$fieldName] = isset($row[$index]) ? trim((string)$row[$index]) : null;
        }
        return $data;
    }

    /**
     * Validate and create user
     */
    protected function validateAndCreate(array $data, int $rowNumber, Role $role): void
    {
        // Set default password if not provided
        if (empty($data['password'])) {
            $data['password'] = 'pamong123';
        }

        // Set default status
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }

        $validator = Validator::make($data, [
            'username' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'password' => 'required|string|min:6',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            throw new \Exception(implode(', ', $validator->errors()->all()));
        }

        // Check if username already exists
        if (User::where('username', $data['username'])->exists()) {
            throw new \Exception("Username '{$data['username']}' sudah terdaftar");
        }

        // Check if email already exists
        if (User::where('email', $data['email'])->exists()) {
            throw new \Exception("Email '{$data['email']}' sudah terdaftar");
        }

        // Create user
        User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
            'status' => $data['status'],
            'email_verified_at' => now(),
        ]);

        $this->successCount++;
    }
}
