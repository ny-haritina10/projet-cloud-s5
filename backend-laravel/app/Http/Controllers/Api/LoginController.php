<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LoginAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    protected $loginAttemptService;
    protected $maxAttempts;

    public function __construct(LoginAttemptService $loginAttemptService)
    {
        $this->loginAttemptService = $loginAttemptService;
        $this->maxAttempts = config('auth.max_login_attempts', 3);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('user_email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if user is blocked due to too many attempts
        if ($this->loginAttemptService->isBlocked($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many login attempts. Please reset your attempts.'
            ], 403);
        }

        if (!Hash::check($request->password, $user->user_password)) {
            // Failed login attempt
            $this->loginAttemptService->recordFailedAttempt($user);

            if ($user->login_attempts >= $this->maxAttempts) {
                $this->sendResetAttemptsEmail($user);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
                'attempts_left' => $this->maxAttempts - $user->login_attempts
            ], 401);
        }

        // Successful login
        $this->loginAttemptService->resetLoginAttempts($user);

        // Generate token
        $token = Str::random(60);
        $tokenExpiration = now()->addHours(config('auth.token_expiration', 24));

        $user->update([
            'token' => $token,
            'token_expires_at' => $tokenExpiration,
            'token_last_used_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'expires_at' => $tokenExpiration
        ]);
    }

    public function resetLoginAttempts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'reset_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('user_email', $request->email)
            ->where('reset_attempts_token', $request->reset_token)
            ->first();

        if (!$user || now()->isAfter($user->reset_attempts_token_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired reset token'
            ], 400);
        }

        $this->loginAttemptService->resetLoginAttempts($user);
        $user->update([
            'reset_attempts_token' => null,
            'reset_attempts_token_expires_at' => null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login attempts reset successfully'
        ]);
    }

    private function sendResetAttemptsEmail($user)
    {
        $resetToken = Str::random(40);
        $resetTokenExpiration = now()->addHours(1);

        $user->update([
            'reset_attempts_token' => $resetToken,
            'reset_attempts_token_expires_at' => $resetTokenExpiration
        ]);

        $resetLink = "http://127.0.0.1:8000/api/auth" . "/reset-login-attempts?email={$user->user_email}&reset_token={$resetToken}";

        Mail::send('emails.reset_login_attempts', [
            'resetLink' => $resetLink,
            'userName' => $user->user_name
        ], function ($message) use ($user) {
            $message->to($user->user_email)
                    ->subject('Reset Login Attempts');
        });
    }
}