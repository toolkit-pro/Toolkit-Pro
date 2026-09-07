<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| Toolkit Pro Helper Functions
|--------------------------------------------------------------------------
|
| This file contains all the helper functions used throughout the
| Toolkit Pro application. These functions are autoloaded via Composer.
|
*/

if (!function_exists('format_date')) {
    /**
     * Format date according to application settings.
     *
     * @param mixed $date
     * @param string $format
     * @return string|null
     */
    function format_date(mixed $date, string $format = 'd/m/Y'): ?string
    {
        if (!$date) {
            return null;
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format($format);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format datetime according to application settings.
     *
     * @param mixed $datetime
     * @return string|null
     */
    function format_datetime(mixed $datetime): ?string
    {
        return format_date($datetime, 'd/m/Y H:i:s');
    }
}

if (!function_exists('format_money')) {
    /**
     * Format money with currency symbol.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    function format_money(float $amount, string $currency = 'USD'): string
    {
        $currencies = config('app.currencies', []);
        $symbol = $currencies[$currency]['symbol'] ?? '$';
        
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('format_number')) {
    /**
     * Format number with thousands separator.
     *
     * @param mixed $number
     * @param int $decimals
     * @return string
     */
    function format_number(mixed $number, int $decimals = 0): string
    {
        return number_format($number, $decimals, '.', ',');
    }
}

if (!function_exists('format_bytes')) {
    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('format_duration')) {
    /**
     * Format duration in seconds to human readable.
     *
     * @param int $seconds
     * @return string
     */
    function format_duration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        $parts = [];
        if ($hours > 0) $parts[] = $hours . 'h';
        if ($minutes > 0) $parts[] = $minutes . 'm';
        if ($secs > 0 || empty($parts)) $parts[] = $secs . 's';
        
        return implode(' ', $parts);
    }
}

if (!function_exists('generate_slug')) {
    /**
     * Generate unique slug.
     *
     * @param string $text
     * @param string $model
     * @param string $field
     * @return string
     */
    function generate_slug(string $text, string $model = null, string $field = 'slug'): string
    {
        $slug = Str::slug($text);
        $originalSlug = $slug;
        $counter = 1;
        
        if ($model && class_exists($model)) {
            while ($model::where($field, $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        }
        
        return $slug;
    }
}

if (!function_exists('generate_unique_id')) {
    /**
     * Generate unique ID with prefix.
     *
     * @param string $prefix
     * @param int $length
     * @return string
     */
    function generate_unique_id(string $prefix = '', int $length = 16): string
    {
        $uniqueId = Str::random($length);
        return $prefix ? $prefix . '_' . $uniqueId : $uniqueId;
    }
}

if (!function_exists('generate_otp')) {
    /**
     * Generate OTP code.
     *
     * @param int $length
     * @return string
     */
    function generate_otp(int $length = 6): string
    {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generate_token')) {
    /**
     * Generate secure token.
     *
     * @param int $length
     * @return string
     */
    function generate_token(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('cache_remember')) {
    /**
     * Remember cache with key prefix.
     *
     * @param string $key
     * @param mixed $callback
     * @param int $ttl
     * @return mixed
     */
    function cache_remember(string $key, mixed $callback, int $ttl = 3600): mixed
    {
        $prefix = config('cache.prefix', 'toolkit');
        return Cache::remember($prefix . ':' . $key, $ttl, $callback);
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Forget cache by pattern.
     *
     * @param string $pattern
     * @return void
     */
    function cache_forget(string $pattern): void
    {
        $prefix = config('cache.prefix', 'toolkit');
        $keys = Cache::get($prefix . ':keys', []);
        
        foreach ($keys as $key) {
            if (Str::is($pattern, $key)) {
                Cache::forget($key);
            }
        }
    }
}

if (!function_exists('get_setting')) {
    /**
     * Get application setting.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get_setting(string $key, mixed $default = null): mixed
    {
        return cache_remember('setting:' . $key, function () use ($key, $default) {
            $setting = \App\Models\Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        }, 3600);
    }
}

if (!function_exists('set_setting')) {
    /**
     * Set application setting.
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $group
     * @return void
     */
    function set_setting(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
            ]
        );
        
        cache_forget('setting:' . $key);
    }
}

if (!function_exists('get_user_ip')) {
    /**
     * Get user IP address.
     *
     * @return string
     */
    function get_user_ip(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
        
        return '0.0.0.0';
    }
}

if (!function_exists('get_user_agent')) {
    /**
     * Get user agent.
     *
     * @return string
     */
    function get_user_agent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}

if (!function_exists('is_ajax_request')) {
    /**
     * Check if request is AJAX.
     *
     * @return bool
     */
    function is_ajax_request(): bool
    {
        return request()->ajax() || request()->expectsJson() || request()->is('api/*');
    }
}

if (!function_exists('is_production')) {
    /**
     * Check if application is in production.
     *
     * @return bool
     */
    function is_production(): bool
    {
        return app()->environment('production');
    }
}

if (!function_exists('is_development')) {
    /**
     * Check if application is in development.
     *
     * @return bool
     */
    function is_development(): bool
    {
        return app()->environment('local', 'development');
    }
}

if (!function_exists('log_error')) {
    /**
     * Log error with context.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function log_error(string $message, array $context = []): void
    {
        Log::error($message, array_merge([
            'user_id' => auth()->id(),
            'ip' => get_user_ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $context));
    }
}

if (!function_exists('log_info')) {
    /**
     * Log info with context.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function log_info(string $message, array $context = []): void
    {
        Log::info($message, array_merge([
            'user_id' => auth()->id(),
            'ip' => get_user_ip(),
        ], $context));
    }
}

if (!function_exists('file_upload')) {
    /**
     * Upload file and return path.
     *
     * @param mixed $file
     * @param string $folder
     * @param string $disk
     * @return string|null
     */
    function file_upload(mixed $file, string $folder = 'uploads', string $disk = 'public'): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }
        
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, $disk);
        
        return $path;
    }
}

if (!function_exists('file_delete')) {
    /**
     * Delete file from storage.
     *
     * @param string|null $path
     * @param string $disk
     * @return bool
     */
    function file_delete(?string $path, string $disk = 'public'): bool
    {
        if (!$path) {
            return false;
        }
        
        return Storage::disk($disk)->delete($path);
    }
}

if (!function_exists('file_url')) {
    /**
     * Get file URL.
     *
     * @param string|null $path
     * @param string $disk
     * @return string|null
     */
    function file_url(?string $path, string $disk = 'public'): ?string
    {
        if (!$path) {
            return null;
        }
        
        return Storage::disk($disk)->url($path);
    }
}

if (!function_exists('truncate_text')) {
    /**
     * Truncate text to specified length.
     *
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    function truncate_text(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists('strip_html')) {
    /**
     * Strip HTML tags and clean text.
     *
     * @param string $html
     * @param int $length
     * @return string
     */
    function strip_html(string $html, int $length = 0): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text);
        $text = trim($text);
        
        if ($length > 0) {
            $text = truncate_text($text, $length);
        }
        
        return $text;
    }
}

if (!function_exists('to_array')) {
    /**
     * Convert value to array.
     *
     * @param mixed $value
     * @return array
     */
    function to_array(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value) && Str::isJson($value)) {
            return json_decode($value, true) ?? [];
        }
        
        return [$value];
    }
}

if (!function_exists('to_json')) {
    /**
     * Convert value to JSON.
     *
     * @param mixed $value
     * @param bool $pretty
     * @return string
     */
    function to_json(mixed $value, bool $pretty = false): string
    {
        return json_encode($value, $pretty ? JSON_PRETTY_PRINT : 0);
    }
}

if (!function_exists('sort_collection')) {
    /**
     * Sort collection by multiple fields.
     *
     * @param Collection $collection
     * @param array $sortFields
     * @return Collection
     */
    function sort_collection(Collection $collection, array $sortFields): Collection
    {
        return $collection->sortBy($sortFields)->values();
    }
}

if (!function_exists('paginate_collection')) {
    /**
     * Paginate collection manually.
     *
     * @param Collection $collection
     * @param int $perPage
     * @param int $page
     * @return array
     */
    function paginate_collection(Collection $collection, int $perPage = 15, int $page = 1): array
    {
        $total = $collection->count();
        $lastPage = max(1, ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        
        $items = $collection->slice(($page - 1) * $perPage, $perPage)->values();
        
        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ],
        ];
    }
}

if (!function_exists('get_domain')) {
    /**
     * Get domain from URL.
     *
     * @param string $url
     * @return string
     */
    function get_domain(string $url): string
    {
        return parse_url($url, PHP_URL_HOST) ?: $url;
    }
}

if (!function_exists('is_valid_url')) {
    /**
     * Check if URL is valid.
     *
     * @param string $url
     * @return bool
     */
    function is_valid_url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('is_valid_email')) {
    /**
     * Check if email is valid.
     *
     * @param string $email
     * @return bool
     */
    function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('is_valid_phone')) {
    /**
     * Check if phone number is valid.
     *
     * @param string $phone
     * @return bool
     */
    function is_valid_phone(string $phone): bool
    {
        return preg_match('/^[0-9+\-\s()]{10,20}$/', $phone) === 1;
    }
}

if (!function_exists('is_valid_uuid')) {
    /**
     * Check if string is valid UUID.
     *
     * @param string $uuid
     * @return bool
     */
    function is_valid_uuid(string $uuid): bool
    {
        return Str::isUuid($uuid);
    }
}

if (!function_exists('remove_special_chars')) {
    /**
     * Remove special characters from string.
     *
     * @param string $string
     * @param string $replace
     * @return string
     */
    function remove_special_chars(string $string, string $replace = ''): string
    {
        return preg_replace('/[^a-zA-Z0-9\s]/', $replace, $string);
    }
}

if (!function_exists('get_initials')) {
    /**
     * Get initials from name.
     *
     * @param string $name
     * @param int $length
     * @return string
     */
    function get_initials(string $name, int $length = 2): string
    {
        $words = explode(' ', $name);
        $initials = '';
        
        foreach ($words as $word) {
            if (strlen($initials) >= $length) {
                break;
            }
            $initials .= strtoupper(substr($word, 0, 1));
        }
        
        return $initials;
    }
}
