<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Sanctum\PersonalAccessToken;
use App\Mail\WelcomeEmail;
use App\Mail\PasswordResetEmail;
use App\Mail\TwoFactorCodeEmail;
use App\Notifications\EmailVerificationNotification;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // ভ্যালিডেশন
        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users'],
            'language' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'accept_terms' => ['required', 'accepted'],
        ], [
            'accept_terms.accepted' => 'You must accept the terms and conditions.',
        ]);

        try {
            // ইউজার তৈরি
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'language' => $validated['language'] ?? config('app.locale', 'bn'),
                'timezone' => $validated['timezone'] ?? config('app.timezone', 'Asia/Dhaka'),
                'country' => $validated['country'] ?? null,
                'status' => 'active',
            ]);

            // রোল অ্যাসাইন
            $user->assignRole('user');

            // টোকেন তৈরি
            $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

            // ইমেইল ভেরিফিকেশন পাঠান
            $user->sendEmailVerificationNotification();

            // ওয়েলকাম ইমেইল
            Mail::to($user->email)->queue(new WelcomeEmail($user));

            // অ্যাক্টিভিটি লগ
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('user_registered');

            return $this->createdResponse([
                'user' => $user,
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at,
            ], 'Registration successful');

        } catch (\Exception $e) {
            report($e);
            return $this->serverErrorResponse('Registration failed. Please try again.');
        }
    }

    /**
     * Login user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // ভ্যালিডেশন
        $validated = $this->validateRequest($request, [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember_me' => ['nullable', 'boolean'],
        ]);

        // রেট লিমিট চেক
        $key = 'login_' . $request->ip() . '_' . $validated['email'];
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return $this->errorResponse(
                "Too many login attempts. Please try again in {$seconds} seconds.",
                429,
                ['retry_after' => $seconds],
                'RATE_LIMIT_EXCEEDED'
            );
        }

        // অথেনটিকেশন চেষ্টা
        if (!Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ], $validated['remember_me'] ?? false)) {
            
            RateLimiter::hit($key, 300); // ৫ মিনিট
            
            return $this->errorResponse(
                'Invalid credentials',
                401,
                null,
                'INVALID_CREDENTIALS'
            );
        }

        // রেট লিমিট ক্লিয়ার
        RateLimiter::clear($key);

        $user = Auth::user();

        // ইউজার স্ট্যাটাস চেক
        if (!$user->isActive()) {
            Auth::logout();
            
            return $this->forbiddenResponse('Your account has been deactivated.');
        }

        // ইমেইল ভেরিফিকেশন চেক
        if (!$user->isVerified()) {
            return $this->errorResponse(
                'Email not verified',
                403,
                ['verified' => false],
                'EMAIL_NOT_VERIFIED'
            );
        }

        // 2FA চেক
        if ($user->two_factor_enabled) {
            // 2FA কোড পাঠান
            $code = $this->generateTwoFactorCode($user);
            
            Mail::to($user->email)->queue(new TwoFactorCodeEmail($user, $code));
            
            return $this->successResponse([
                'requires_2fa' => true,
                'user_id' => $user->id,
            ], 'Two-factor authentication required');
        }

        // টোকেন তৈরি
        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addDays($validated['remember_me'] ?? false ? 90 : 30)
        );

        // লাস্ট লগইন আপডেট
        $user->updateLastLogin();

        // অ্যাক্টিভিটি লগ
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('user_logged_in');

        return $this->successResponse([
            'user' => $user,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ], 'Login successful');
    }

    /**
     * Verify two-factor authentication.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyTwoFactor(Request $request)
    {
        $validated = $this->validateRequest($request, [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // কোড ভেরিফাই
        if (!$this->verifyTwoFactorCode($user, $validated['code'])) {
            return $this->errorResponse(
                'Invalid verification code',
                400,
                null,
                'INVALID_2FA_CODE'
            );
        }

        // টোকেন তৈরি
        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        // লাস্ট লগইন আপডেট
        $user->updateLastLogin();

        return $this->successResponse([
            'user' => $user,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ], 'Two-factor authentication successful');
    }

    /**
     * Logout user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // বর্তমান টোকেন ডিলিট
        $user->currentAccessToken()->delete();

        // অ্যাক্টিভিটি লগ
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('user_logged_out');

        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * Logout from all devices.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAllDevices(Request $request)
    {
        $user = $request->user();

        // সব টোকেন ডিলিট
        $user->tokens()->delete();

        // অ্যাক্টিভিটি লগ
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('user_logged_out_all_devices');

        return $this->successResponse(null, 'Logged out from all devices');
    }

    /**
     * Get authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        $user->load(['roles', 'permissions', 'badges', 'settings']);

        return $this->successResponse([
            'user' => $user,
            'stats' => $user->getToolUsageStats(),
            'points' => $user->points_balance,
        ], 'User retrieved successfully');
    }

    /**
     * Refresh token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();

        // বর্তমান টোকেন ডিলিট
        $user->currentAccessToken()->delete();

        // নতুন টোকেন তৈরি
        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ], 'Token refreshed successfully');
    }

    /**
     * Send password reset link.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $validated = $this->validateRequest($request, [
            'email' => ['required', 'string', 'email', 'exists:users'],
        ]);

        // রেট লিমিট
        $key = 'password_reset_' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return $this->errorResponse(
                'Too many requests. Please try again later.',
                429,
                null,
                'RATE_LIMIT_EXCEEDED'
            );
        }

        RateLimiter::hit($key, 3600);

        $status = Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return $this->successResponse(null, 'Password reset link sent to your email');
        }

        return $this->errorResponse(
            'Unable to send password reset link',
            400,
            null,
            'RESET_LINK_FAILED'
        );
    }

    /**
     * Reset password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $validated = $this->validateRequest($request, [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $status = Password::reset($validated, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            // সব টোকেন রিভোক
            $user->tokens()->delete();

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('password_reset');
        });

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(null, 'Password reset successfully');
        }

        return $this->errorResponse(
            'Invalid or expired token',
            400,
            null,
            'INVALID_TOKEN'
        );
    }

    /**
     * Verify email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->errorResponse(
                'Invalid verification link',
                400,
                null,
                'INVALID_VERIFICATION_LINK'
            );
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified');
        }

        $user->markEmailAsVerified();

        return $this->successResponse(null, 'Email verified successfully');
    }

    /**
     * Resend verification email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified');
        }

        $user->sendEmailVerificationNotification();

        return $this->successResponse(null, 'Verification email sent');
    }

    /**
     * Generate two-factor code.
     *
     * @param User $user
     * @return string
     */
    protected function generateTwoFactorCode(User $user): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        cache()->put(
            '2fa_' . $user->id,
            [
                'code' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
            ],
            600
        );
        
        return $code;
    }

    /**
     * Verify two-factor code.
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    protected function verifyTwoFactorCode(User $user, string $code): bool
    {
        $cacheKey = '2fa_' . $user->id;
        $twoFactorData = cache()->get($cacheKey);

        if (!$twoFactorData || !isset($twoFactorData['code'])) {
            return false;
        }

        if (now()->gt($twoFactorData['expires_at'])) {
            cache()->forget($cacheKey);
            return false;
        }

        if (!Hash::check($code, $twoFactorData['code'])) {
            return false;
        }

        cache()->forget($cacheKey);
        return true;
    }
}
