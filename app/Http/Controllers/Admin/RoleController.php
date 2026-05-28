<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(20);
        $roles = Role::with('permissions')->where('guard_name', 'web')->get();
        $allPermissions = Permission::where('guard_name', 'web')->get();

        return view('admin.roles.index', compact('users', 'roles', 'allPermissions'));
    }

    public function assign(Request $request, User $user)
    {
        $request->validate(['roles' => 'array', 'roles.*' => 'exists:roles,name']);

        $old = $user->getRoleNames()->toArray();
        $user->syncRoles($request->roles ?? []);
        $new = $request->roles ?? [];

        \Log::channel('security')->info('Role assignment changed', [
            'admin_id' => auth('admin')->id(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'old_roles' => $old,
            'new_roles' => $new,
            'added' => array_diff($new, $old),
            'removed' => array_diff($old, $new),
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Roles updated for {$user->name}",
        ]);
    }

    public function syncPermissions(Request $request, Role $role)
    {
        $request->validate(['permissions' => 'array', 'permissions.*' => 'exists:permissions,name']);

        $oldPerms = $role->permissions->pluck('name')->toArray();
        $newPerms = $request->permissions ?? [];

        $permIds = Permission::whereIn('name', $newPerms)
            ->where('guard_name', $role->guard_name)
            ->pluck('id');

        $role->permissions()->sync($permIds);

        \Log::channel('security')->info('Role permissions changed', [
            'admin_id' => auth('admin')->id(),
            'role' => $role->name,
            'old_perms' => $oldPerms,
            'new_perms' => $newPerms,
            'added' => array_diff($newPerms, $oldPerms),
            'removed' => array_diff($oldPerms, $newPerms),
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Permissions updated for {$role->name}",
        ]);
    }
}
