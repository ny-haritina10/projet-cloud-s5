<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class SignupController extends Controller
{   
    protected $maxVerificationAttempts;

    public function __construct()
    {
        $this->maxVerificationAttempts = config('auth.max_verification_attempts', 3);
    }
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,user_email',
            'password' => 'required|min:6',
            'name' => 'required|string|max:255',
            'birthday' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }

        // generate code pin
        $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        try {
            // Create user with unverified status
            $user = User::create([
                'user_name' => $request->name,
                'user_email' => $request->email,
                'user_password' => Hash::make($request->password),
                'user_birthday' => $request->birthday,
                'email_verification_code' => $verificationCode,
                'email_verified_at' => null,

                // add one hour to match mada hour
                // the pin expiration is 3 minutes 
                'verification_code_expires_at' => Carbon::now()->addMinutes(60 + 3), 
            ]);

            $this->sendVerificationEmail($user->user_email, $verificationCode);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered. Check your email for verification code.',
                'user_id' => $user->id
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function sendVerificationEmail($email, $verificationCode)
    {
        Mail::send('emails.verification', ['verificationCode' => $verificationCode], function ($message) use ($email) {
            $message->to($email)
                    ->subject('Verify Your Email');
        });
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'verification_code' => 'required|string|size:4'
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
                'message' => 'User not found'
            ], 404);
        }

        // Check if code is expired
        if (Carbon::now()->addMinutes(60)->isAfter($user->verification_code_expires_at)) {
            $user->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'Verification code has expired'
            ], 400);
        }

        // Check if verification code matches
        if ($user->email_verification_code !== $request->verification_code) {
            // Increment verification attempts
            $user->verification_attempts += 1;
            $user->last_verification_attempt_at = now();
            $user->save();

            // Check if max attempts reached
            if ($user->verification_attempts >= $this->maxVerificationAttempts) {
                $this->sendResetVerificationAttemptsEmail($user);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid verification code',
                'attempts_left' => $this->maxVerificationAttempts - $user->verification_attempts
            ], 400);
        }

        // Mark email as verified
        $user->email_verified_at = Carbon::now()->addMinutes(60);
        $user->email_verification_code = null;
        $user->verification_code_expires_at = null;
        $user->verification_attempts = 0;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully'
        ], 200);
    }

    public function resetVerificationAttempts(Request $request)
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

        $user = User::where('user_email', $request->input('email'))
            ->where('reset_verification_attempts_token', $request->input('reset_token'))
            ->first();

        if (!$user || !$user->reset_verification_attempts_token_expires_at || 
            now()->isAfter($user->reset_verification_attempts_token_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired reset token'
            ], 400);
        }

        // Reset verification attempts
        $user->verification_attempts = 0;
        $user->reset_verification_attempts_token = null;
        $user->reset_verification_attempts_token_expires_at = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Verification attempts reset successfully'
        ]);
    }

    private function sendResetVerificationAttemptsEmail($user)
    {
        $resetToken = Str::random(40);
        $resetTokenExpiration = now()->addHours(1);

        $user->update([
            'reset_verification_attempts_token' => $resetToken,
            'reset_verification_attempts_token_expires_at' => $resetTokenExpiration
        ]);

        $resetLink = "http://127.0.0.1:8000/api/auth/reset-verification-attempts?email={$user->user_email}&reset_token={$resetToken}";

        Mail::send('emails.reset_verification_attempts', [
            'resetLink' => $resetLink,
            'userName' => $user->user_name
        ], function ($message) use ($user) {
            $message->to($user->user_email)
                    ->subject('Reset Verification Attempts');
        });
    }
}