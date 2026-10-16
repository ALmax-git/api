<?php

namespace App\Imports;

use App\Enums\AccountType;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;

class StaffsImport implements ToModel
{
    protected $department_id;
    protected $errors = [];

    public function __construct($department_id)
    {
        $this->department_id = $department_id;
    }

    /**
     * Validate and import question data from an Excel row.
     *
     * @param array $row
     * @return Question|null
     */
    public function model(array $row)
    {


        // Validate question text
        if (empty(trim($row[0]))) {
            $this->logError("staff name cannot be empty", $row);
            return null;
        }

        // Validate question text
        if (empty(trim($row[1]))) {
            $this->logError("staff personal number cannot be empty", $row);
            return null;
        }

        if (_app($row[0]) == _app('Name') || _app($row[1]) == _app('Staff_Number')) {
            return null;
        }


        // Check if question already exists
        $staff_exist = UserClient::where('staff_number', $row[1])

            ->where('client_id', Auth::user()->client->id)
            ->first();

        if ($staff_exist) {
            $this->logError("Duplicate User: User already exists", $row);
            return null;
        }

        try {
            // Create question
            $email = make_valid_email($row[1]);
            $user = User::create([
                'name' => $row[0],
                'email' => $email,
                'password' => Hash::make('carbon12'),
                'account_type' => AccountType::STANDARD,
                'client_id' => Auth::user()->client->id,
            ]);

            // Associate with subject
            UserClient::create([
                'user_id' => $user->id,
                'is_staff' => true,
                'department_id' => $this->department_id,
                'client_id' => Auth::user()->client->id,
                'staff_number' => $row[1]
            ]);


            return $user;
        } catch (\Exception $e) {
            $this->logError("Database error: " . $e->getMessage(), $row);
            return null;
        }
    }

    /**
     * Log import errors.
     *
     * @param string $message
     * @param array $row
     */
    private function logError($message, $row)
    {
        $errorDetails = [
            'error' => $message,
            'row_data' => $row,
        ];

        // Log the error
        Log::error('staff Import Error', $errorDetails);

        // Store error message for later retrieval (optional)
        $this->errors[] = $errorDetails;
    }

    /**
     * Get import errors (useful for debugging).
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
