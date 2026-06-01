<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ForcedPasswordResetMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(20);
        $trashedUsers = collect();
        $roles = collect();
        $allPermissions = collect();
        if(current_user()->can('assign_roles')){
            $roles = Role::with('permissions')->where('guard_name', 'web')->get();
            $allPermissions = Permission::where('guard_name', 'web')->get();
        }
        
        if(current_user()->hasRole('admin')){
            $trashedUsers = User::onlyTrashed()->with('roles')->get();
        }

        return view('admin.roles.index', compact('users', 'roles', 'allPermissions', 'trashedUsers'));
    }

    /**
     * Soft-delete a user (move to trash).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if (!current_user()->hasRole('admin') && $user->id === current_user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
        }

        $user->delete();

        \Log::channel('security')->info('User soft-deleted by admin', [
            'deletable_id'   => current_user()->id,
            'deletable_type' => get_class(current_user()),
            'user_id'    => $user->id,
            'user_email' => $user->email,
            'ip'         => $request->ip(),
            'timestamp'  => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$user->name} has been moved to trash.",
        ]);
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        \Log::channel('security')->info('Trashed user restored by admin', [
            'restorable_id'   => current_user()->id,
            'restorable_type' => get_class(current_user()),
            'user_id'    => $user->id,
            'user_email' => $user->email,
            'ip'         => $request->ip(),
            'timestamp'  => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$user->name} has been restored.",
        ]);
    }

    /**
     * Permanently delete a trashed user (no recovery).
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $name  = $user->name;
        $email = $user->email;

        $user->forceDelete();

        \Log::channel('security')->info('User permanently deleted by admin', [
            'deletable_id'   => current_user()->id,
            'deletable_type'   => get_class(current_user()),
            'user_id'    => $id,
            'user_email' => $email,
            'ip'         => $request->ip(),
            'timestamp'  => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$name} has been permanently deleted.",
        ]);
    }

    public function assign(Request $request, User $user)
    {
        $this->authorize('assign_roles');
        $request->validate(['roles' => 'array', 'roles.*' => 'exists:roles,name']);

        $old = $user->getRoleNames()->toArray();
        $user->syncRoles($request->roles ?? []);
        $new = $request->roles ?? [];

        \Log::channel('security')->info('Role assignment changed', [
            'assignable_id' => current_user()->id,
            'assignable_type' => get_class(current_user()),
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
            'assignable_id' => current_user()->id,
            'assignable_type' => get_class(current_user()),
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

    public function verifyUser(Request $request, User $user)
    {
        $user->markEmailAsVerified();

        \Log::channel('security')->info('User email manually verified by admin', [
            'verifible_id' => current_user()->id,
            'verifible_type' => get_class(current_user()),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been marked as verified.",
        ]);
    }

    public function unverifyUser(Request $request, User $user)
    {
        $user->forceFill([
            'email_verified_at' => null,
        ])->save();

        \Log::channel('security')->info('User email manually un-verified by admin', [
            'verifible_id' => current_user()->id,
            'verifible_type' => get_class(current_user()),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been un-verified.",
        ]);
    }

    public function forcePasswordReset(Request $request, User $user)
    {
        $user->forceFill([
            'force_password_reset' => true,
        ])->save();

        // Send warning/explanation email
        Mail::to($user->email)->send(new ForcedPasswordResetMail($user));

        \Log::channel('security')->info('Forced password reset triggered by admin', [
            'forcable_id' => current_user()->id,
            'forcable_type' => get_class(current_user()),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Forced password reset enabled for {$user->name}. An email has been sent.",
        ]);
    }
}
