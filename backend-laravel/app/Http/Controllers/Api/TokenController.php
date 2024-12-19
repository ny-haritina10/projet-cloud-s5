<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class TokenController extends Controller
{
    public function logout(Request $request)
    {
        // retrieve the user token
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'No token provided'
            ], 401);
        }

        $user = User::where('token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token'
            ], 401);
        }

        // Clear the token
        $user->update([
            'token' => null,
            'token_expires_at' => null,
            'token_last_used_at' => null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    public function checkTokenValidity(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'No token provided'
            ], 401);
        }

        $user = User::where('token', $token)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token'
            ], 401);
        }

        // Check if token is expired
        if (!$user->token_expires_at || Carbon::now()->isAfter($user->token_expires_at)) {
            // Token is expired, clear it
            $user->update([
                'token' => null,
                'token_expires_at' => null,
                'token_last_used_at' => null
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Token has expired'
            ], 401);
        }

        // Update last used time to extend potential session tracking
        $user->update([
            'token_last_used_at' => Carbon::now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Token is valid',
            'expires_at' => $user->token_expires_at
        ]);
    }

    // Optional: Automated token cleanup job
    public function cleanupExpiredTokens()
    {
        $expiredTokens = User::whereNotNull('token')
            ->where('token_expires_at', '<', Carbon::now())
            ->get();

        foreach ($expiredTokens as $user) {
            $user->update([
                'token' => null,
                'token_expires_at' => null,
                'token_last_used_at' => null
            ]);
        }

        return response()->json([
            'status' => 'success',
            'cleaned_tokens' => $expiredTokens->count()
        ]);
    }
}