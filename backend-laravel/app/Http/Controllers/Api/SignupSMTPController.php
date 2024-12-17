<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Carbon\Carbon;

class SignupSMTPController extends Controller
{   
    public function signup(Request $request)
    {
        // Validate input
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

        // Generate verification code
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
                'verification_code_expires_at' => Carbon::now()->addMinutes(60 + 3), // add one hour to match mada hour 
            ]);

            // Send verification email
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

        $user = User::where('user_email', $request->email)
            ->where('email_verification_code', $request->verification_code)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid verification code'
            ], 400);
        }

        // Check if code is expired
        if (Carbon::now()->addMinutes(60)->isAfter($user->verification_code_expires_at)) {
            $user->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'Verification code has expired'
            ], 400);
        }

        // Mark email as verified
        $user->email_verified_at = Carbon::now()->addMinutes(60);
        $user->email_verification_code = null;
        $user->verification_code_expires_at = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully'
        ], 200);
    }

    private function sendVerificationEmail($email, $verificationCode)
    {
        Mail::send('emails.verification', ['verificationCode' => $verificationCode], function ($message) use ($email) {
            $message->to($email)
                    ->subject('Verify Your Email');
        });
    }
}