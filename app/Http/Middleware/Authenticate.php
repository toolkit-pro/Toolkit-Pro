<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\AuthenticationException;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        // API রিকোয়েস্টের জন্য JSON রেসপন্স
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        return route('login');
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards): void
    {
        // রেট লিমিট চেক
        $key = 'auth_failed_' . $request->ip();
        $attempts = Cache::get($key, 0);

        if ($attempts > 10) {
            Log::warning('Multiple authentication failures detected', [
                'ip' => $request->ip(),
                'attempts' => $attempts,
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Cache::put($key, $attempts + 1, 300); // ৫ মিনিট

        // API রিকোয়েস্টের জন্য JSON রেসপন্স
        if ($request->expectsJson() || $request->is('api/*')) {
            throw new AuthenticationException(
                'Unauthenticated.',
                $guards,
                $this->redirectTo($request)
            );
        }

        parent::unauthenticated($request, $guards);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, \Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        $user = $request->user();

        // ইউজার স্ট্যাটাস চেক
        if ($user && !$this->isUserActive($user)) {
            Auth::logout();
            
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated.',
                    'error' => [
                        'code' => 'ACCOUNT_DEACTIVATED',
                        'status_code' => 403,
                    ],
                ], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // ইমেইল ভেরিফিকেশন চেক (যদি প্রয়োজন হয়)
        if ($user && config('auth.verify_email', true) && !$user->hasVerifiedEmail()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not verified.',
                    'error' => [
                        'code' => 'EMAIL_NOT_VERIFIED',
                        'status_code' => 403,
                    ],
                ], 403);
            }

            return redirect()->route('verification.notice');
        }

        // সেশন সিকিউরিটি চেক
        if ($user && $this->shouldCheckSession($request)) {
            $sessionValid = $this->validateSession($request, $user);
            
            if (!$sessionValid) {
                Auth::logout();
                
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Session expired. Please login again.',
                        'error' => [
                            'code' => 'SESSION_EXPIRED',
                            'status_code' => 401,
                        ],
                    ], 401);
                }

                return redirect()->route('login')
                    ->withErrors(['email' => 'Your session has expired. Please login again.']);
            }
        }

        // লাস্ট অ্যাক্টিভিটি আপডেট
        if ($user && $this->shouldUpdateLastActivity($request)) {
            $this->updateLastActivity($user);
        }

        return $next($request);
    }

    /**
     * Check if user is active.
     *
     * @param  mixed  $user
     * @return bool
     */
    protected function isUserActive($user): bool
    {
        return $user->status === 'active';
    }

    /**
     * Determine if session should be checked.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldCheckSession(Request $request): bool
    {
        // শুধুমাত্র web রুটে সেশন চেক
        return !$request->is('api/*') && !$request->expectsJson();
    }

    /**
     * Validate session security.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return bool
     */
    protected function validateSession(Request $request, $user): bool
    {
        $session = $request->session();
        
        // IP পরিবর্তন চেক
        $sessionIp = $session->get('auth_ip');
        $currentIp = $request->ip();
        
        if ($sessionIp && $sessionIp !== $currentIp) {
            // IP পরিবর্তন হলে সেশন ইনভ্যালিড
            if (config('auth.strict_ip_check', false)) {
                Log::warning('Session IP mismatch', [
                    'user_id' => $user->id,
                    'session_ip' => $sessionIp,
                    'current_ip' => $currentIp,
                ]);
                return false;
            }
        } else {
            $session->put('auth_ip', $currentIp);
        }

        // User Agent চেক
        $sessionAgent = $session->get('auth_user_agent');
        $currentAgent = $request->userAgent();
        
        if ($sessionAgent && $sessionAgent !== $currentAgent) {
            if (config('auth.strict_agent_check', false)) {
                Log::warning('Session user agent mismatch', [
                    'user_id' => $user->id,
                    'session_agent' => $sessionAgent,
                    'current_agent' => $currentAgent,
                ]);
                return false;
            }
        } else {
            $session->put('auth_user_agent', $currentAgent);
        }

        return true;
    }

    /**
     * Determine if last activity should be updated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldUpdateLastActivity(Request $request): bool
    {
        return !$request->is('api/*') && !$request->expectsJson();
    }

    /**
     * Update last activity timestamp.
     *
     * @param  mixed  $user
     * @return void
     */
    protected function updateLastActivity($user): void
    {
        $cacheKey = 'user_last_activity_' . $user->id;
        $lastUpdate = Cache::get($cacheKey);

        // প্রতি ৫ মিনিটে আপডেট
        if (!$lastUpdate || now()->diffInMinutes($lastUpdate) >= 5) {
            $user->updateLastLogin();
            Cache::put($cacheKey, now(), 300);
        }
    }
}
