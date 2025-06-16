<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\Post;
use App\Models\PostReaction;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Helpers\AuthHelper;

class ReactionController extends Controller
{
    private $NotificationService;
    public function __construct(NotificationService $NotificationService)
    {
        $this->NotificationService = $NotificationService;
    }

    public function reactToPost(Request $request)
    {
        try {
            $validated = $request->validate([
                'post_id' => 'required|exists:posts,id',
                'reaction_type' => 'required|in:like',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e], 422);
        }
        $user = AuthHelper::getUserFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 401);
        }
        $post = Post::findOrFail($validated['post_id']);
        $existingReaction = PostReaction::withTrashed()
            ->where('user_id', $user->id)
            ->where('post_id', $validated['post_id'])
            ->first();
        if ($existingReaction) {
            if ($existingReaction->trashed()) {
                $existingReaction->restore();
                return response()->json(['message' => 'لقد تمت اضافة اعجاب بنجاح']);
            }
            if ($existingReaction->reaction_type === $validated['reaction_type']) {
                $existingReaction->delete();
                return response()->json(['message' => 'تمت ازالة اعجاب بنجاح']);
            }
        } else {
            PostReaction::create([
                'user_id' => $user->id,
                'post_id' => $validated['post_id'],
                'reaction_type' => $validated['reaction_type'],
            ]);
            if ($user->id != $post->User->id) {
                $this->NotificationService->sendLikeNotification($user, $post->User, $post->id);
            }
            return response()->json(['message' => 'تمت اضافة اعجاب']);
        }
    }
    public function getLikePost(Post $post)
    {
        $post->loadCount([
            'reactions as like_count'
        ]);
        return response()->json([
            'data' => ['likeNumber' => $post->like_count]
        ]);
    }
    public function getLikedUser(Post $post, Request $request)
    {
        $user = AuthHelper::getUserFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 401);
        }

        $reactions = $post->reactions;

        $users = [];
        foreach ($reactions as $reaction) {
            $isFriend = Friend::where([
                ['user_id', $user->id],
                ['friend_id', $reaction->user->id],
            ])->orWhere([
                ['user_id', $reaction->user->id],
                ['friend_id', $user->id],
            ])->exists();
            $reaction->user['is_friend'] = $isFriend;
            if ($isFriend) {
                $reaction->user['status'] = Friend::where([
                    ['user_id', $user->id],
                    ['friend_id', $reaction->user->id],
                ])->orWhere([
                    ['user_id', $reaction->user->id],
                    ['friend_id', $user->id],
                ])->first()->status;
            } else {
                $reaction->user['status'] = "";
            }
            if ($reaction->user->profile_image == null) {
                $reaction->user->profile_image = "";
            }
            $users[] = $reaction->user;
        }
        usort($users, function ($a, $b) {
            return $a->id - $b->id;
        });
        return response()->json(['data' => $users]);
    }
}
