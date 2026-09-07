<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreToolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('create-tools');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('tools', 'name')->whereNull('deleted_at'),
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'instructions' => [
                'nullable',
                'string',
                'max:10000',
            ],
            
            // Media Files
            'icon' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,gif,svg,webp',
                'max:2048', // 2MB
            ],
            'thumbnail' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,gif,webp',
                'max:5120', // 5MB
            ],
            'screenshot' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,gif,webp',
                'max:10240', // 10MB
            ],
            'video_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            
            // URLs
            'url' => [
                'nullable',
                'url',
                'max:255',
            ],
            'api_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            'documentation_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            'support_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            
            // Version & Status
            'version' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\d+\.\d+\.\d+$/',
            ],
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive', 'pending']),
            ],
            
            // Flags
            'is_featured' => [
                'nullable',
                'boolean',
            ],
            'is_premium' => [
                'nullable',
                'boolean',
            ],
            'is_free' => [
                'nullable',
                'boolean',
            ],
            
            // Pricing
            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000000',
                'required_if:is_premium,true',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
                'uppercase',
                Rule::in(array_keys(config('app.currencies', ['USD' => []]))),
            ],
            'billing_cycle' => [
                'nullable',
                Rule::in(['one_time', 'monthly', 'yearly']),
            ],
            'discount_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lt:price',
            ],
            'discount_ends_at' => [
                'nullable',
                'date',
                'after:now',
            ],
            
            // Category Relationships
            'category_ids' => [
                'nullable',
                'array',
                'min:1',
                'max:10',
            ],
            'category_ids.*' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
            
            // Metadata
            'tags' => [
                'nullable',
                'array',
                'max:20',
            ],
            'tags.*' => [
                'required',
                'string',
                'max:50',
            ],
            'requirements' => [
                'nullable',
                'array',
            ],
            'compatibility' => [
                'nullable',
                'array',
            ],
            'languages' => [
                'nullable',
                'array',
                'max:20',
            ],
            'languages.*' => [
                'required',
                'string',
                'size:2',
            ],
            
            // SEO
            'meta_title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'meta_description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'meta_keywords' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'টুলের নাম আবশ্যক',
            'name.min' => 'টুলের নাম কমপক্ষে ৩ অক্ষরের হতে হবে',
            'name.unique' => 'এই নামে একটি টুল ইতিমধ্যে বিদ্যমান',
            'description.required' => 'টুলের বিবরণ আবশ্যক',
            'description.min' => 'টুলের বিবরণ কমপক্ষে ১০ অক্ষরের হতে হবে',
            'icon.image' => 'আইকন অবশ্যই একটি ছবি হতে হবে',
            'icon.max' => 'আইকন সর্বোচ্চ ২MB হতে পারবে',
            'thumbnail.image' => 'থাম্বনেইল অবশ্যই একটি ছবি হতে হবে',
            'thumbnail.max' => 'থাম্বনেইল সর্বোচ্চ ৫MB হতে পারবে',
            'screenshot.max' => 'স্ক্রিনশট সর্বোচ্চ ১০MB হতে পারবে',
            'price.required_if' => 'প্রিমিয়াম টুলের জন্য মূল্য আবশ্যক',
            'category_ids.*.exists' => 'নির্বাচিত ক্যাটাগরি বিদ্যমান নেই',
            'tags.*.max' => 'প্রতিটি ট্যাগ সর্বোচ্চ ৫০ অক্ষরের হতে পারবে',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'টুলের নাম',
            'description' => 'বিবরণ',
            'instructions' => 'নির্দেশাবলী',
            'icon' => 'আইকন',
            'thumbnail' => 'থাম্বনেইল',
            'screenshot' => 'স্ক্রিনশট',
            'url' => 'URL',
            'price' => 'মূল্য',
            'category_ids' => 'ক্যাটাগরি',
            'tags' => 'ট্যাগ',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // ট্যাগ ট্রিম
        if ($this->has('tags') && is_array($this->tags)) {
            $this->merge([
                'tags' => array_map('trim', $this->tags),
            ]);
        }

        // প্রাইস ক্যালকুলেশন
        if ($this->has('is_free') && $this->boolean('is_free')) {
            $this->merge([
                'is_premium' => false,
                'price' => 0,
            ]);
        }

        if ($this->has('is_premium') && $this->boolean('is_premium')) {
            $this->merge([
                'is_free' => false,
            ]);
        }

        // ডিফল্ট সেটিংস
        if (!$this->has('currency')) {
            $this->merge([
                'currency' => config('app.settings.default_currency', 'USD'),
            ]);
        }

        if (!$this->has('languages') && $this->has('description')) {
            $this->merge([
                'languages' => [app()->getLocale()],
            ]);
        }
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation(): void
    {
        // SEO মেটা ডেটা অটো-জেনারেট
        if (!$this->has('meta_title') && $this->has('name')) {
            $this->merge([
                'meta_title' => $this->name,
            ]);
        }

        if (!$this->has('meta_description') && $this->has('description')) {
            $this->merge([
                'meta_description' => substr(strip_tags($this->description), 0, 160),
            ]);
        }
    }
}
