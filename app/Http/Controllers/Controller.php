<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Success response helper.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $meta
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response helper.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @param string $errorCode
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message = 'Error',
        int $statusCode = 400,
        mixed $errors = null,
        string $errorCode = 'ERROR'
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'error' => [
                'code' => $errorCode,
                'status_code' => $statusCode,
            ],
        ];

        if ($errors !== null) {
            $response['error']['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Paginated response helper.
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param array $meta
     * @return JsonResponse
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];

        if (!empty($paginator->nextPageUrl())) {
            $pagination['next_page_url'] = $paginator->nextPageUrl();
        }

        if (!empty($paginator->previousPageUrl())) {
            $pagination['previous_page_url'] = $paginator->previousPageUrl();
        }

        $response = [
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => $pagination,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }

    /**
     * Created response helper.
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * No content response helper.
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Validation error response helper.
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        mixed $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            422,
            $errors,
            'VALIDATION_ERROR'
        );
    }

    /**
     * Not found response helper.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            404,
            null,
            'NOT_FOUND'
        );
    }

    /**
     * Unauthorized response helper.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            401,
            null,
            'UNAUTHORIZED'
        );
    }

    /**
     * Forbidden response helper.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            403,
            null,
            'FORBIDDEN'
        );
    }

    /**
     * Server error response helper.
     *
     * @param string $message
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function serverErrorResponse(
        string $message = 'Internal server error',
        mixed $errors = null
    ): JsonResponse {
        return $this->errorResponse(
            $message,
            500,
            $errors,
            'INTERNAL_ERROR'
        );
    }

    /**
     * Validate request data.
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return array
     */
    protected function validateRequest(
        Request $request,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): array {
        $validator = Validator::make(
            $request->all(),
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException(
                $validator,
                $this->validationErrorResponse($validator->errors())
            );
        }

        return $validator->validated();
    }

    /**
     * Cache response data.
     *
     * @param string $key
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    protected function cachedResponse(
        string $key,
        callable $callback,
        int $ttl = 3600
    ): mixed {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Clear cache by pattern.
     *
     * @param string $pattern
     * @return void
     */
    protected function clearCache(string $pattern): void
    {
        $keys = Cache::get($pattern . '_keys', []);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        Cache::forget($pattern . '_keys');
    }

    /**
     * Store cache key for later clearing.
     *
     * @param string $pattern
     * @param string $key
     * @return void
     */
    protected function storeCacheKey(string $pattern, string $key): void
    {
        $keys = Cache::get($pattern . '_keys', []);
        $keys[] = $key;
        Cache::put($pattern . '_keys', array_unique($keys), 86400);
    }

    /**
     * Log controller action.
     *
     * @param string $action
     * @param mixed $data
     * @return void
     */
    protected function logAction(string $action, mixed $data = null): void
    {
        Log::info($action, [
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get authenticated user ID.
     *
     * @return int|null
     */
    protected function getUserId(): ?int
    {
        return auth()->id();
    }

    /**
     * Check if user has permission.
     *
     * @param string $permission
     * @return bool
     */
    protected function hasPermission(string $permission): bool
    {
        return auth()->user()?->hasPermissionTo($permission) ?? false;
    }

    /**
     * Check if user has role.
     *
     * @param string|array $roles
     * @return bool
     */
    protected function hasRole(string|array $roles): bool
    {
        return auth()->user()?->hasRole($roles) ?? false;
    }

    /**
     * Authorize action.
     *
     * @param string $ability
     * @param mixed $arguments
     * @return void
     */
    protected function authorizeAction(string $ability, mixed $arguments = []): void
    {
        $this->authorize($ability, $arguments);
    }

    /**
     * Get pagination parameters.
     *
     * @param Request $request
     * @return array
     */
    protected function getPaginationParams(Request $request): array
    {
        return [
            'page' => (int) $request->input('page', 1),
            'per_page' => min((int) $request->input('per_page', 15), 100),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];
    }

    /**
     * Get search parameters.
     *
     * @param Request $request
     * @return array
     */
    protected function getSearchParams(Request $request): array
    {
        return [
            'search' => $request->input('search', ''),
            'filters' => $request->input('filters', []),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
    }

    /**
     * Format date for response.
     *
     * @param mixed $date
     * @param string $format
     * @return string|null
     */
    protected function formatDate(mixed $date, string $format = 'Y-m-d H:i:s'): ?string
    {
        if (!$date) {
            return null;
        }

        return $date instanceof \DateTime
            ? $date->format($format)
            : $date;
    }

    /**
     * Format collection for response.
     *
     * @param Collection $collection
     * @param string $resourceClass
     * @return Collection
     */
    protected function formatCollection(Collection $collection, string $resourceClass): Collection
    {
        return $resourceClass::collection($collection);
    }

    /**
     * Format model for response.
     *
     * @param Model $model
     * @param string $resourceClass
     * @return mixed
     */
    protected function formatModel(Model $model, string $resourceClass): mixed
    {
        return new $resourceClass($model);
    }
}
