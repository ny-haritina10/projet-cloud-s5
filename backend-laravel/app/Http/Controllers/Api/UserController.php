<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

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
        $validator = Validator::make($request->all(), [
            'user_name' => 'sometimes|string|max:255',
            'user_password' => 'sometimes|string|min:8',
            'user_birthday' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);    
        }

        $data = $request->except(['user_password']);
        $user->fill($data);

        if ($request->has('user_password')) {
            $user->user_password = Hash::make($request->user_password);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully.',
            'data' => $user,
        ], 200);
    }
}