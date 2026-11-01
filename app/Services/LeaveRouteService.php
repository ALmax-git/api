<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DepartmentType;
use App\Models\DepartmentUnit;
use App\Models\LeaveRoute;
use App\Models\User;
use App\Models\UserRole;

class LeaveRouteService
{
    /**
     * Build the full approval path for a user's leave request.
     *
     * @param  \App\Models\User  $user  The employee requesting leave.
     * @return array An ordered list of approvers (users or roles).
     */
    public function get_approval_route(User $user): array
    {
        $approvalPath = [];

        $userClient = $user->user_clients()
            ->where('client_id', $user->client_id)
            ->first();

        if (!$userClient) return [];

        $department = Department::find($userClient->department_id);
        $unit = DepartmentUnit::find($userClient->department_unit_id);
        $departmentType = $department ? DepartmentType::find($department->type_id) : null;

        $leaveRoutes = LeaveRoute::where('client_id', $user->client_id)
            ->where('department_type_id', $departmentType?->id)
            ->orderBy('order')
            ->get();
        $step_count = 0;
        foreach ($leaveRoutes as $route) {
            $nextUser = null;

            switch ($route->target) {
                case 'staff':
                    $nextUser = $user;
                    break;

                case 'head_of_unit':
                    if ($unit && $unit->user_id && $unit->user_id !== $user->id) {
                        $nextUser = User::find($unit->user_id);
                    }
                    break;

                case 'head_of_department':
                    if ($department->user_id == $user->id) {
                        $approvalPath = null;
                        $approvalPath = [];
                    }
                    if ($department && $department->user_id && $department->user_id !== $user->id) {
                        $nextUser = User::find($department->user_id);
                    }
                    break;

                case 'department_type':
                    if ($departmentType && $departmentType->user_id && $departmentType->user_id !== $user->id) {
                        $nextUser = User::find($departmentType->user_id);
                    }
                    if ($departmentType->user_id == $user->id) {
                        $approvalPath = null;
                        $approvalPath = [];
                    }
                    break;

                case 'role':
                    if ($route->role_id) {
                        $userIds = UserRole::where('role_id', $route->role_id)
                            ->where('client_id', $user->client_id)
                            ->pluck('user_id');
                        // dd($userIds, $route->role_id);
                        $roleUsers = User::whereIn('id', $userIds)->get();

                        foreach ($roleUsers as $roleUser) {
                            $approvalPath[] = [
                                'step' => 'Role',
                                'user' => $roleUser
                            ];
                        }

                        continue 2; // Skip single-user push below
                    }
                    break;
            }

            if ($nextUser) {
                $approvalPath[] = [
                    'step'              => ++$step_count,
                    'office_title'      => $route->office_title,
                    'target'            => $route->target,
                    'user'              => $nextUser,
                    'route'             => $route,
                ];
            }
        }

        return $approvalPath;
    }
}
