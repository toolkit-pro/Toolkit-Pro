<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ToolResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Basic Information
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->instructions,
            
            // Media
            'icon_url' => $this->icon_url,
            'thumbnail_url' => $this->thumbnail_url,
            'screenshot_url' => $this->screenshot_url,
            'video_url' => $this->video_url,
            
            // URLs
            'url' => $this->url,
            'api_url' => $this->api_url,
            'documentation_url' => $this->documentation_url,
            'support_url' => $this->support_url,
            
            // Version & Status
            'version' => $this->version,
            'status' => $this->status,
            
            // Flags
            'is_featured' => $this->is_featured,
            'is_premium' => $this->is_premium,
            'is_free' => $this->is_free,
            'is_verified' => $this->is_verified,
            'is_approved' => $this->is_approved,
            'is_popular' => $this->is_popular,
            'is_new' => $this->is_new,
            'is_trending' => $this->is_trending,
            
            // Pricing
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_cycle' => $this->billing_cycle,
            'discount_price' => $this->discount_price,
            'discount_ends_at' => $this->discount_ends_at?->toIso8601String(),
            
            // Statistics
            'views_count' => $this->views_count,
            'uses_count' => $this->uses_count,
            'downloads_count' => $this->downloads_count,
            'favorites_count' => $this->favorites_count,
            'shares_count' => $this->shares_count,
            'comments_count' => $this->comments_count,
            
            // Rating
            'rating' => $this->rating,
            'rating_count' => $this->rating_count,
            'average_rating' => $this->average_rating,
            
            // Performance
            'average_time_spent' => $this->average_time_spent,
            'success_rate' => $this->success_rate,
            'error_rate' => $this->error_rate,
            
            // Metadata
            'tags' => $this->tags,
            'requirements' => $this->requirements,
            'compatibility' => $this->compatibility,
            'languages' => $this->languages,
            
            // Relationships
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return new UserResource($this->creator);
            }),
            'categories' => $this->when($this->relationLoaded('categories'), function () {
                return CategoryResource::collection($this->categories);
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Additional Data (Conditional)
            'usage_stats' => $this->when($request->has('include_stats'), function () {
                return $this->getUsageStats();
            }),
            'related_tools' => $this->when($request->has('include_related'), function () {
                return ToolResource::collection($this->getRelatedTools());
            }),
            'reviews' => $this->when($request->has('include_reviews'), function () {
                return ToolReviewResource::collection($this->reviews);
            }),
            
            // Links
            'links' => [
                'self' => route('api.tools.show', $this->slug),
                'web' => route('tools.show', $this->slug),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  Request  $request
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  Request  $request
     * @param  \Illuminate\Http\JsonResponse  $response
     * @return void
     */
    public function withResponse(Request $request, $response): void
    {
        $response->header('X-Tool-ID', $this->id);
        $response->header('X-Tool-Version', $this->version);
    }
}
