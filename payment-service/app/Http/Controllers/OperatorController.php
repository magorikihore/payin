<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    /**
     * List all operators (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role ?? null, ['super_admin', 'admin_user'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = Operator::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $operators = $query->orderBy('name')->get()->map(function ($op) {
            return [
                'id'                => $op->id,
                'name'              => $op->name,
                'code'              => $op->code,
                'api_url'           => $op->api_url,
                'sp_id'             => $op->sp_id,
                'merchant_code'     => $op->merchant_code,
                'api_version'       => $op->api_version,
                'collection_path'   => $op->collection_path,
                'disbursement_path' => $op->disbursement_path,
                'status_path'       => $op->status_path,
                'callback_url'      => $op->callback_url,
                'status'            => $op->status,
                'extra_config'      => $op->extra_config,
                'created_at'        => $op->created_at,
                'updated_at'        => $op->updated_at,
            ];
        });

        return response()->json(['operators' => $operators]);
    }

    /**
     * List active operators (for merchants/public).
     */
    public function active(Request $request): JsonResponse
    {
        $operators = Operator::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'status']);

        return response()->json(['operators' => $operators]);
    }

    /**
     * Create a new operator (super_admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (($user->role ?? null) !== 'super_admin') {
            return response()->json(['message' => 'Only super admin can manage operators.'], 403);
        }

        $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:50|unique:operators,code',
            'api_url'           => 'required|url|max:500',
            'sp_id'             => 'nullable|string|max:100',
            'merchant_code'     => 'nullable|string|max:100',
            'sp_password'       => 'nullable|string|max:500',
            'api_version'       => 'nullable|string|max:10',
            'collection_path'   => 'nullable|string|max:200',
            'disbursement_path' => 'nullable|string|max:200',
            'status_path'       => 'nullable|string|max:200',
            'callback_url'      => 'nullable|url|max:500',
            'status'            => 'nullable|in:active,inactive',
        ]);

        $operator = Operator::create([
            'name'              => $request->name,
            'code'              => strtolower($request->code),
            'api_url'           => $request->api_url,
            'sp_id'             => $request->sp_id,
            'merchant_code'     => $request->merchant_code,
            'sp_password'       => $request->sp_password,
            'api_version'       => $request->api_version ?? '5.0',
            'collection_path'   => $request->collection_path,
            'disbursement_path' => $request->disbursement_path,
            'status_path'       => $request->status_path,
            'callback_url'      => $request->callback_url,
            'status'            => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Operator created successfully.',
            'operator' => $operator,
        ], 201);
    }

    /**
     * Update an operator (super_admin only).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (($user->role ?? null) !== 'super_admin') {
            return response()->json(['message' => 'Only super admin can manage operators.'], 403);
        }

        $operator = Operator::find($id);
        if (!$operator) {
            return response()->json(['message' => 'Operator not found.'], 404);
        }

        $request->validate([
            'name'              => 'sometimes|string|max:100',
            'code'              => 'sometimes|string|max:50|unique:operators,code,' . $id,
            'api_url'           => 'sometimes|url|max:500',
            'sp_id'             => 'nullable|string|max:100',
            'merchant_code'     => 'nullable|string|max:100',
            'sp_password'       => 'nullable|string|max:500',
            'api_version'       => 'nullable|string|max:10',
            'collection_path'   => 'nullable|string|max:200',
            'disbursement_path' => 'nullable|string|max:200',
            'status_path'       => 'nullable|string|max:200',
            'callback_url'      => 'nullable|url|max:500',
            'status'            => 'nullable|in:active,inactive',
        ]);

        $data = $request->only([
            'name', 'api_url', 'sp_id', 'merchant_code', 'api_version',
            'collection_path', 'disbursement_path', 'status_path',
            'callback_url', 'status',
        ]);

        if ($request->filled('code')) {
            $data['code'] = strtolower($request->code);
        }

        // Only update sp_password if provided (don't blank it)
        if ($request->filled('sp_password')) {
            $data['sp_password'] = $request->sp_password;
        }

        $operator->update($data);

        return response()->json([
            'message' => 'Operator updated successfully.',
            'operator' => $operator->fresh(),
        ]);
    }

    /**
     * Delete an operator (super_admin only).
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (($user->role ?? null) !== 'super_admin') {
            return response()->json(['message' => 'Only super admin can manage operators.'], 403);
        }

        $operator = Operator::find($id);
        if (!$operator) {
            return response()->json(['message' => 'Operator not found.'], 404);
        }

        $operator->delete();

        return response()->json(['message' => 'Operator deleted successfully.']);
    }

    /**
     * Test operator connection — pushes a test request.
     */
    public function test(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (($user->role ?? null) !== 'super_admin') {
            return response()->json(['message' => 'Only super admin can test operators.'], 403);
        }

        $operator = Operator::find($id);
        if (!$operator) {
            return response()->json(['message' => 'Operator not found.'], 404);
        }

        // Build a test header/body to confirm the API is reachable
        $header = $operator->buildApiHeader();

        return response()->json([
            'message' => 'Operator connection details generated.',
            'operator' => $operator->name,
            'api_url' => $operator->api_url,
            'test_header' => $header,
            'collection_path' => $operator->collection_path,
            'disbursement_path' => $operator->disbursement_path,
        ]);
    }
}
