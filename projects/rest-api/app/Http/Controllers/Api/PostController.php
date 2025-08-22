<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostCollection;
use App\Http\Requests\PostRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Post::with(['user', 'category', 'tags']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->published();
            } elseif ($request->status === 'draft') {
                $query->draft();
            }
        } else {
            $query->published();
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Sort by
        $sortBy = $request->get('sort_by', 'published_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $posts = $query->paginate($request->get('per_page', 15));

        return PostCollection::make($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PostRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        // Set published_at if status is published
        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $post = Post::create($validated);

        // Attach tags if provided
        if (isset($validated['tags'])) {
            $post->tags()->attach($validated['tags']);
        }

        return response()->json([
            'message' => 'Post created successfully',
            'data' => new PostResource($post->load(['user', 'category', 'tags']))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post): JsonResponse
    {
        // Check if post is published or user has permission
        if ($post->status !== 'published' && !auth()->user()?->can('manage-posts')) {
            return response()->json([
                'message' => 'Post not found'
            ], 404);
        }

        // Increment view count
        $post->increment('views');

        return response()->json([
            'data' => new PostResource($post->load(['user', 'category', 'tags', 'comments.user']))
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validated();

        // Set published_at if status is published and not already set
        if ($validated['status'] === 'published' && empty($validated['published_at']) && !$post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        // Sync tags if provided
        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return response()->json([
            'message' => 'Post updated successfully',
            'data' => new PostResource($post->load(['user', 'category', 'tags']))
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully'
        ]);
    }

    /**
     * Get posts by user
     */
    public function userPosts(Request $request): AnonymousResourceCollection
    {
        $user = auth()->user();
        
        $posts = $user->posts()
            ->with(['category', 'tags'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return PostCollection::make($posts);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $post->update(['featured' => !$post->featured]);

        $status = $post->featured ? 'featured' : 'unfeatured';

        return response()->json([
            'message' => "Post {$status} successfully",
            'data' => new PostResource($post)
        ]);
    }

    /**
     * Get featured posts
     */
    public function featured(): AnonymousResourceCollection
    {
        $posts = Post::published()
            ->featured()
            ->with(['user', 'category', 'tags'])
            ->latest('published_at')
            ->paginate(10);

        return PostCollection::make($posts);
    }

    /**
     * Get posts by category
     */
    public function byCategory(Request $request, $categoryId): AnonymousResourceCollection
    {
        $posts = Post::published()
            ->byCategory($categoryId)
            ->with(['user', 'category', 'tags'])
            ->latest('published_at')
            ->paginate($request->get('per_page', 15));

        return PostCollection::make($posts);
    }

    /**
     * Search posts
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $posts = Post::published()
            ->search($request->q)
            ->with(['user', 'category', 'tags'])
            ->latest('published_at')
            ->paginate($request->get('per_page', 15));

        return PostCollection::make($posts);
    }
}
