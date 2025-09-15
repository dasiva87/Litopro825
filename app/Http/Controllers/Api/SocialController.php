<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Models\SocialPostComment;
use App\Models\SocialLike;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SocialController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    /**
     * Toggle like on a post
     */
    public function toggleLike(Request $request, SocialPost $post): JsonResponse
    {
        $user = auth()->user();

        $existingLike = SocialLike::where([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'reaction_type' => 'like'
        ])->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
        } else {
            SocialLike::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'post_id' => $post->id,
                'reaction_type' => 'like',
            ]);
            $liked = true;
        }

        $likesCount = $post->likes()->count();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likesCount
        ]);
    }

    /**
     * Add comment to a post
     */
    public function addComment(Request $request, SocialPost $post): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:1|max:500'
        ]);

        $user = auth()->user();

        $comment = SocialPostComment::create([
            'company_id' => $user->company_id,
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => trim($request->content),
            'is_private' => false
        ]);

        $comment->load('author');

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'author_name' => $comment->author->name,
                'created_at_human' => $comment->created_at->diffForHumans(),
            ],
            'comments_count' => $post->comments()->count()
        ]);
    }

    /**
     * Get comments for a post
     */
    public function getComments(SocialPost $post, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $comments = $post->comments()
            ->with('author')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'comments' => $comments->items(),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'total' => $comments->total()
            ]
        ]);
    }

    /**
     * Get feed posts with pagination
     */
    public function getFeedPosts(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $posts = SocialPost::with(['company', 'author'])
            ->withCount(['likes', 'comments'])
            ->where('is_public', true)
            ->whereNull('expires_at')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $postsData = $posts->getCollection()->map(function ($post) use ($user) {
            return [
                'id' => $post->id,
                'content' => $post->content ?? '',
                'post_type' => $post->post_type ?? 'news',
                'post_type_label' => $post->getPostTypeLabel(),
                'post_type_color' => $post->getPostTypeColor(),
                'company_name' => optional($post->company)->name ?? 'Empresa Desconocida',
                'author_name' => optional($post->author)->name ?? 'Usuario Desconocido',
                'created_at' => $post->created_at,
                'created_at_human' => $post->created_at->diffForHumans(),
                'likes_count' => $post->likes_count ?? 0,
                'comments_count' => $post->comments_count ?? 0,
                'user_liked' => $user ? $post->hasUserReacted($user->id, 'like') : false,
                'can_edit' => $user && $post->user_id === $user->id,
                'avatar_initials' => optional($post->company)->name ? strtoupper(substr($post->company->name, 0, 2)) : '??',
                'image_url' => $post->getImageUrl(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $postsData,
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
                'per_page' => $posts->perPage()
            ]
        ]);
    }

    /**
     * Create a new post
     */
    public function createPost(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:10|max:1000',
            'post_type' => 'required|in:offer,request,news,equipment,materials,collaboration',
            'title' => 'nullable|string|max:200',
            'image' => 'nullable|image|max:2048'
        ]);

        $user = auth()->user();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('social-posts', 'public');
        }

        $post = SocialPost::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'post_type' => $request->post_type,
            'title' => $request->title ?: null,
            'content' => trim($request->content),
            'image_path' => $imagePath,
            'is_public' => true,
        ]);

        $post->load(['company', 'author']);

        return response()->json([
            'success' => true,
            'message' => '¡Publicación creada exitosamente!',
            'post' => [
                'id' => $post->id,
                'content' => $post->content,
                'post_type_label' => $post->getPostTypeLabel(),
                'company_name' => $post->company->name,
                'author_name' => $post->author->name,
                'created_at_human' => $post->created_at->diffForHumans(),
            ]
        ]);
    }
}