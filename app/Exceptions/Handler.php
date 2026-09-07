<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExceptionOccurred;
use Sentry\Laravel\Integration;
use App\Exceptions\CustomException;
use App\Exceptions\ValidationException as CustomValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        // Authentication errors
        AuthenticationException::class => 'warning',
        
        // Authorization errors
        AuthorizationException::class => 'warning',
        
        // Not found errors
        NotFoundHttpException::class => 'notice',
        ModelNotFoundException::class => 'notice',
        
        // Validation errors
        ValidationException::class => 'info',
        
        // Rate limiting
        TooManyRequestsHttpException::class => 'warning',
        
        // Server errors
        QueryException::class => 'error',
        HttpException::class => 'error',
        ServiceUnavailableHttpException::class => 'critical',
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        // Authentication
        AuthenticationException::class,
        
        // Authorization
        AuthorizationException::class,
        
        // Validation
        ValidationException::class,
        
        // Not found
        NotFoundHttpException::class,
        ModelNotFoundException::class,
        
        // Method not allowed
        MethodNotAllowedHttpException::class,
        
        // Rate limiting
        TooManyRequestsHttpException::class,
        
        // CSRF token mismatch
        TokenMismatchException::class,
        
        // Custom exceptions
        CustomException::class,
        CustomValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'social_security',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Sentry রিপোর্টিং
            if (app()->bound('sentry') && app()->environment('production')) {
                Integration::captureUnhandledException($e);
            }
        });

        $this->renderable(function (Throwable $e, Request $request) {
            // API রেসপন্স
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->handleApiException($e, $request);
            }

            // ওয়েব রেসপন্স
            return $this->handleWebException($e, $request);
        });
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $e): void
    {
        // কাস্টম লগিং
        $this->logException($e);

        // ইমেইল নোটিফিকেশন (গুরুতর ত্রুটির জন্য)
        if ($this->shouldSendEmail($e)) {
            $this->sendExceptionEmail($e);
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e): Response
    {
        // JSON রেসপন্স
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderJsonException($e);
        }

        // ওয়েব রেসপন্স
        return $this->renderWebException($e);
    }

    /**
     * Handle API exceptions.
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException(Throwable $e, Request $request): JsonResponse
    {
        $statusCode = $this->getStatusCode($e);
        $errorCode = $this->getErrorCode($e);
        $message = $this->getErrorMessage($e);
        $details = $this->getErrorDetails($e);

        $response = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'status_code' => $statusCode,
            ],
        ];

        // ডিবাগ মোডে অতিরিক্ত তথ্য
        if (config('app.debug') && !$e instanceof ValidationException) {
            $response['error']['file'] = $e->getFile();
            $response['error']['line'] = $e->getLine();
            $response['error']['trace'] = $e->getTraceAsString();
        }

        // ভ্যালিডেশন এরর
        if ($e instanceof ValidationException) {
            $response['error']['errors'] = $e->errors();
        }

        // অতিরিক্ত ডিটেইলস
        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        // রিকোয়েস্ট আইডি
        $response['error']['request_id'] = request()->header('X-Request-ID') ?? uniqid('req_');

        return response()->json($response, $statusCode);
    }

    /**
     * Handle web exceptions.
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function handleWebException(Throwable $e, Request $request): Response
    {
        $statusCode = $this->getStatusCode($e);

        // নির্দিষ্ট এরর পেজ
        $errorPages = [
            400 => 'errors.400',
            401 => 'errors.401',
            403 => 'errors.403',
            404 => 'errors.404',
            405 => 'errors.405',
            419 => 'errors.419',
            429 => 'errors.429',
            500 => 'errors.500',
            502 => 'errors.502',
            503 => 'errors.503',
            504 => 'errors.504',
        ];

        $view = $errorPages[$statusCode] ?? 'errors.default';

        if (view()->exists($view)) {
            return response()->view($view, [
                'exception' => $e,
                'message' => $this->getErrorMessage($e),
                'statusCode' => $statusCode,
            ], $statusCode);
        }

        // ফ্যালব্যাক ভিউ
        return response()->view('errors.default', [
            'exception' => $e,
            'message' => $this->getErrorMessage($e),
            'statusCode' => $statusCode,
        ], $statusCode);
    }

    /**
     * Render JSON exception.
     *
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function renderJsonException(Throwable $e): JsonResponse
    {
        return $this->handleApiException($e, request());
    }

    /**
     * Render web exception.
     *
     * @param  \Throwable  $e
     * @return \Illuminate\Http\Response
     */
    protected function renderWebException(Throwable $e): Response
    {
        return $this->handleWebException($e, request());
    }

    /**
     * Get status code for exception.
     *
     * @param  \Throwable  $e
     * @return int
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof ModelNotFoundException) {
            return 404;
        }

        if ($e instanceof NotFoundHttpException) {
            return 404;
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return 405;
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return 429;
        }

        if ($e instanceof TokenMismatchException) {
            return 419;
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof ServiceUnavailableHttpException) {
            return 503;
        }

        return 500;
    }

    /**
     * Get error code for exception.
     *
     * @param  \Throwable  $e
     * @return string
     */
    protected function getErrorCode(Throwable $e): string
    {
        $codes = [
            AuthenticationException::class => 'AUTH_ERROR',
            AuthorizationException::class => 'FORBIDDEN',
            ModelNotFoundException::class => 'NOT_FOUND',
            NotFoundHttpException::class => 'NOT_FOUND',
            MethodNotAllowedHttpException::class => 'METHOD_NOT_ALLOWED',
            TooManyRequestsHttpException::class => 'RATE_LIMIT_EXCEEDED',
            TokenMismatchException::class => 'CSRF_TOKEN_MISMATCH',
            ValidationException::class => 'VALIDATION_ERROR',
            QueryException::class => 'DATABASE_ERROR',
            ServiceUnavailableHttpException::class => 'SERVICE_UNAVAILABLE',
            CustomException::class => 'CUSTOM_ERROR',
            CustomValidationException::class => 'CUSTOM_VALIDATION_ERROR',
        ];

        return $codes[get_class($e)] ?? 'INTERNAL_ERROR';
    }

    /**
     * Get error message for exception.
     *
     * @param  \Throwable  $e
     * @return string
     */
    protected function getErrorMessage(Throwable $e): string
    {
        // ভ্যালিডেশন এরর
        if ($e instanceof ValidationException) {
            return 'The given data was invalid.';
        }

        // কাস্টম এরর
        if ($e instanceof CustomException) {
            return $e->getMessage();
        }

        // প্রোডাকশনে সাধারণ মেসেজ
        if (!config('app.debug') && !$e instanceof AuthenticationException) {
            return 'An unexpected error occurred. Please try again later.';
        }

        return $e->getMessage();
    }

    /**
     * Get error details for exception.
     *
     * @param  \Throwable  $e
     * @return array
     */
    protected function getErrorDetails(Throwable $e): array
    {
        $details = [];

        // ডাটাবেস এরর
        if ($e instanceof QueryException) {
            $details = [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ];
        }

        // কাস্টম এরর ডিটেইলস
        if (method_exists($e, 'getDetails')) {
            $details = array_merge($details, $e->getDetails());
        }

        return $details;
    }

    /**
     * Log exception with custom format.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function logException(Throwable $e): void
    {
        $context = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'request_data' => request()->except($this->dontFlash),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('daily')->error($e->getMessage(), $context);
    }

    /**
     * Send exception email notification.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function sendExceptionEmail(Throwable $e): void
    {
        try {
            $recipient = config('mail.admin_email', 'admin@toolkitpro.com');
            
            Mail::to($recipient)->queue(new ExceptionOccurred($e));
        } catch (Exception $mailException) {
            Log::error('Failed to send exception email: ' . $mailException->getMessage());
        }
    }

    /**
     * Determine if email should be sent for exception.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function shouldSendEmail(Throwable $e): bool
    {
        // শুধুমাত্র প্রোডাকশনে ইমেইল পাঠান
        if (!app()->environment('production')) {
            return false;
        }

        // গুরুতর ত্রুটির জন্য ইমেইল পাঠান
        $criticalExceptions = [
            QueryException::class,
            ServiceUnavailableHttpException::class,
        ];

        return in_array(get_class($e), $criticalExceptions);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication required.',
                    'status_code' => 401,
                ],
            ], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
                'status_code' => 422,
            ],
        ], $exception->status);
    }
}
