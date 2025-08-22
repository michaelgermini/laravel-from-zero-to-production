<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'featured_image' => $this->featured_image ? asset('storage/' . $this->featured_image) : null,
            'status' => $this->status,
            'featured' => $this->featured,
            'views' => $this->views,
            'reading_time' => $this->reading_time,
            'published_at' => $this->published_at?->toISOString(),
            'formatted_published_date' => $this->formatted_published_date,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar' => $this->user->avatar_url,
            ],
            
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ];
            }),
            
            'comments_count' => $this->whenLoaded('comments', function () {
                return $this->comments->count();
            }),
            
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            
            // Links
            'links' => [
                'self' => route('api.posts.show', $this->id),
                'edit' => route('api.posts.update', $this->id),
                'delete' => route('api.posts.destroy', $this->id),
            ],
        ];
    }
}
