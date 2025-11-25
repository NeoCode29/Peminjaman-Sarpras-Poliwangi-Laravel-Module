<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'active_users' => User::where('status', 'active')->count(),
        ];

        return view('dashboard', compact('stats'));
    }
}
