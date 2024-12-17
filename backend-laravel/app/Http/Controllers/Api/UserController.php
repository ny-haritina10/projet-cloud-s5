<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Update a user by ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'user_name' => 'sometimes|string|max:255',
            // 'user_email' => 'sometimes|email|max:255|unique:users,user_email,' . $id,
            'user_password' => 'sometimes|string|min:8',
            'user_birthday' => 'sometimes|date',
            // 'token_last_used_at' => 'sometimes|date',
            // 'token_expires_at' => 'sometimes|date',
            // 'token' => 'sometimes|string|max:255',
            // 'email_verification_code' => 'sometimes|string|max:255',
            // 'verification_code_expires_at' => 'sometimes|date',
            // 'email_verified_at' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch the user
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        // Update user fields
        $user->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully.',
            'data' => $user,
        ], 200);
    }
}