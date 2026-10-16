<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\DepartmentType;
use App\Models\User;
use App\Models\UserClient;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use Maatwebsite\Excel\Concerns\ToModel;

class DepartmentImport implements ToModel
{
    protected $department_type_id;
    protected $errors = [];

    /**
     * $department_type_id is required to create a department_type.
     * @param string|int $department_type_id
     * @return void 
     */
    public function __construct($department_type_id)
    {
        $this->department_type_id = $department_type_id;
    }

    /**
     * Validate and import question data from an Excel row.
     *
     * @param array $row
     * @return Model|Model[]|null
     */
    public function model(array $row)
    {

        if (!DepartmentType::find($this->department_type_id)) {
            $this->log_error("please select department type first or create one if no any", $row[0]);
            return null;
        }
        // Validate question text
        if (empty(trim($row[0]))) {
            $this->log_error("department title cannot be empty", $row);
            return null;
        }

        // Validate question text
        if (empty(trim($row[1]))) {
            $this->log_error("department HOD SP personal number cannot be empty", $row);
            return null;
        }
        // Validate question text
        if (empty(trim($row[1]))) {
            $this->log_error("department HOD name cannot be empty", $row);
            return null;
        }

        if (_app($row[0]) == _app('title') || _app($row[1]) == _app('HOD_SP_number' || _app($row[2] == _app('HOD_name')))) {
            return null;
        }


        // Check if question already exists
        $hod_exist = UserClient::where('staff_number', $row[1])

            ->where('client_id', Auth::user()->client->id)

            ->first();

        if ($hod_exist) {
            $this->log_error($hod_exist->user?->name . " User already exists", $row);
        }

        try {
            // Create question
            $email = make_valid_email($row[1]);
            if ($hod_exist) {
                $user = User::find($hod_exist->user_id);
                if ($hod_exist->is_staff == false) {
                    $hod_exist->is_staff = true;
                    $hod_exist->save();
                }
            } else {
                $user = new User();
            }
            $department = new Department();
            $department->title = $row[0];
            $department->client_id = Auth::user()->client->id;
            $department->type_id = $this->department_type_id;

            if ($hod_exist) {
                $department->user_id = $hod_exist->user_id;
                $department->save();
            } else {
                $user->name = $row[2];
                $user->password = Hash::make('carbon12');
                $user->email = $email;
                $user->save();
                $department->user_id = $user->id;
                $department->save();


                // Associate with subject
                UserClient::create([
                    'user_id' => $user->id,
                    'is_staff' => true,
                    'department_id' => $department->id,
                    'client_id' => Auth::user()->client->id,
                    'staff_number' => $row[1]
                ]);
            }


            return $department;
        } catch (\Exception $e) {
            $this->log_error("Database error: " . $e->getMessage(), $row);
            return null;
        }
    }

    /**
     * Log import errors.
     *
     * @param string $message
     * @param array $row
     */
    private function log_error($message, $row)
    {
        $errorDetails = [
            'error' => $message,
            'row_data' => $row,
        ];

        // Log the error
        Log::error('@Department Import Error', $errorDetails);

        // Store error message for later retrieval (optional)
        $this->errors[] = $errorDetails;
    }

    /**
     * Get import errors (useful for debugging).
     *
     * @return array
     */
    public function get_errors()
    {
        return $this->errors;
    }
}
