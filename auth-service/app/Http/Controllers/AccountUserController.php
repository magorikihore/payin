<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountUserController extends Controller
{
    /**
     * List all users in the current account.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $users = User::where('account_id', $user->account_id)
            ->select('id', 'firstname', 'lastname', 'name', 'email', 'role', 'permissions', 'created_at')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'users' => $users,
            'available_permissions' => User::PERMISSIONS,
        ]);
    }

    /**
     * Add a new user to the current account.
     */
    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|in:admin,viewer',
            'password' => 'required|string|min:8',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', User::PERMISSIONS),
        ]);

        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'name' => $request->firstname . ' ' . $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_id' => $authUser->account_id,
            'role' => $request->role,
            'permissions' => $request->permissions ?? [],
        ]);

        return response()->json([
            'message' => 'User added successfully.',
            'user' => $user->only('id', 'firstname', 'lastname', 'name', 'email', 'role', 'permissions', 'created_at'),
        ], 201);
    }

    /**
     * Update a user's role within the account.
     */
    public function updateRole(Request $request, $id): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->isOwner()) {
            return response()->json(['message' => 'Unauthorized. Only account owner can change roles.'], 403);
        }

        $request->validate([
            'role' => 'required|in:admin,viewer',
        ]);

        $user = User::where('account_id', $authUser->account_id)->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->id === $authUser->id) {
            return response()->json(['message' => 'Cannot change your own role.'], 422);
        }

        if ($user->role === 'owner') {
            return response()->json(['message' => 'Cannot change owner role.'], 422);
        }

        $user->update(['role' => $request->role]);

        return response()->json([
            'message' => 'User role updated.',
            'user' => $user->only('id', 'name', 'email', 'role', 'permissions', 'created_at'),
        ]);
    }

    /**
     * Remove a user from the account.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->isOwner()) {
            return response()->json(['message' => 'Unauthorized. Only account owner can remove users.'], 403);
        }

        $user = User::where('account_id', $authUser->account_id)->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->id === $authUser->id) {
            return response()->json(['message' => 'Cannot remove yourself.'], 422);
        }

        if ($user->role === 'owner') {
            return response()->json(['message' => 'Cannot remove the account owner.'], 422);
        }

        // Revoke all tokens
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User removed successfully.']);
    }

    /**
     * Update a user's permissions.
     */
    public function updatePermissions(Request $request, $id): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->isOwner()) {
            return response()->json(['message' => 'Unauthorized. Only account owner can change permissions.'], 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:' . implode(',', User::PERMISSIONS),
        ]);

        $user = User::where('account_id', $authUser->account_id)->where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->id === $authUser->id) {
            return response()->json(['message' => 'Cannot change your own permissions.'], 422);
        }

        if ($user->role === 'owner') {
            return response()->json(['message' => 'Cannot change owner permissions.'], 422);
        }

        $user->update(['permissions' => $request->permissions]);

        return response()->json([
            'message' => 'Permissions updated successfully.',
            'user' => $user->only('id', 'name', 'email', 'role', 'permissions', 'created_at'),
        ]);
    }
}
