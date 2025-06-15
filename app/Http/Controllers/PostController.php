<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Helpers\AuthHelper;
use App\Helpers\MediaHelper;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PostController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'content' => 'required|string',
                'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi',
            ]);
            $path = MediaHelper::StoreMedia('posts', $request, 'media');
            $user = AuthHelper::getUserFromToken($request);
            if (!$user) {
                return response()->json([
                    'message' => 'قم بتسجيل الدخول اولا'
                ]);
            }
            try {
                $response = Http::timeout(100)->post('https://19f5-212-102-51-98.ngrok-free.app/predict', [
                    'text' => $validatedData['content']
                ]);
                if ($response->successful()) {
                    if ($response['prediction'] != "real") {
                        return response()->json(['message' => ' النص مزيف تاكد من الخبر اولا'], 400);
                    }
                }
            } catch (Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            } catch (ConnectionException $e) {
                return response()->json(['error' => $e->getMessage()]);
            } catch (RequestException $e) {
                return response()->json(['error' => $e->getMessage()]);
            };
            $post = Post::create([
                'user_id' => $user->id,
                'content' => $request['content'],
                'media' => $path
            ]);
            Cache::forget('post_');
            return response()->json([
                'success' => true,
                'message' => 'تمت اضافة المنشور بنجاح',
                'data' => $post
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل اضافة المنشور',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function delete(Request $request, Post $post)
    {
        $user = AuthHelper::getUserFromToken($request);

        if (!$user) {
            return response()->json([
                'message' => 'قم بتسجيل الدخول اولا'
            ]);
        }
        if (!$post) {
            return response()->json([
                'message' => 'لا يوجد منشور'
            ], 404);
        }
        $post = $post
            ->where('user_id', $user->id)
            ->where('id', $post->id);
        if (!$post) {
            return response()->json([
                'message' => 'لا يوجد منشور'
            ], 404);
        }
        if ($post->delete()) {
            Cache::forget('post_');
            return response()->json([
                'mesasge' => 'تم حذف المنشور'
            ], 200);
        }
        return response()->json([
            'message' => ' حصل خطا ما حاول مرى اخرى'
        ], 501);
    }
    public function getPosts(Request $request)
    {
        $user = AuthHelper::getUserFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 404);
        }
        $posts = $user->posts; // user's posts
        if (!$posts) {
            return response()->json(['message' => "لا يوجد اي منشور الى الان"], 200);
        }
        foreach ($posts as $post) {
            $hasLiked = $post->reactions()
                ->where('reaction_type', 'like')
                ->where('user_id', $user->id)
                ->exists();
            $post->has_liked = $hasLiked;
            $post->loadCount([
                'reactions as likes' => function ($q) {
                    $q->where('reaction_type', 'like');
                }
            ]);
            $post->loadCount([
                'comment as comments'
            ]);
            $post['user_name'] = $user->firstname . ' ' . $user->lastname;
            $post['profile_image'] = $user->profile_image;
            if ($post->media == null) {
                $post->media = "";
            }
        }
        return response()->json(['message' => 'تم عرض جميع المنشورات بنجاح', 'data' =>
        [
            'posts' => $posts
        ]], 200);
    }

    public function getAllPost(Request $request)
    {
        $user = AuthHelper::getUserFromToken($request);
        if (!$user) {
            return response()->json(['message' => 'user not found'], 404);
        }
        $posts = Post::where('isNews', '!=', true)->inRandomOrder()->get();
        $news = Post::where('isNews', true)->latest()->first();
        $userPost = [];
        $otherPost = [];
        foreach ($posts as $post) {
            $isFriend = Friend::where(function ($query) use ($user, $post) {
                $query->where('user_id', $user->id)
                    ->where('friend_id', $post->user_id);
            })->orWhere(function ($query) use ($user, $post) {
                $query->where('user_id', $post->user_id)
                    ->where('friend_id', $user->id);
            })->exists();
            $post['is_friend'] = $isFriend;
            $post->loadCount([
                'reactions as likes',
                'comment as comments'
            ]);
            $hasLiked = $post->reactions()
                ->where('reaction_type', 'like')
                ->where('user_id', $user->id)
                ->exists();
            $post->has_liked = $hasLiked;
            $post['user_name'] = $post->user->firstname . ' ' . $post->user->lastname;
            $post['profile_image'] = $post->user->profile_image;
            if ($post->user_id == $user->id && $post->created_at->isToday()) {
                $post['his_post'] = true;
                $userPost[] = $post;
            } else {
                $otherPost[] = $post;
            }
        }
        $filterPost = [];
        if ($news) {

            if (is_string($news->content)) {
                $decoded = json_decode($news->content, true);
                if (is_array($decoded)) {
                    $news->content = implode("\n", $decoded);
                }
            }
            $news['is_news'] = true;
            $news['is_friend'] = true;
            $news->loadCount([
                'reactions as likes',
                'comment as comments'
            ]);
            $hasLiked = $news->reactions()
                ->where('reaction_type', 'like')
                ->where('user_id', $user->id)
                ->exists();
            $news->has_liked = $hasLiked;
            $news['user_name'] = $news->user->firstname . ' ' . $news->user->lastname;
            $news['profile_image'] = $news->user->profile_image;
            $filterPost[] = $news;
        }
        $filterPost = array_merge($filterPost, $userPost, $otherPost);
        if (empty($filterPost)) {
            return response()->json(['message' => "can't find any post"], 204);
        }
        return response()->json([
            'message' => 'Successfully response',
            'data' => [
                'posts' => $filterPost
            ]
        ], 200);
    }
    public function showPost(Post $post)
    {
        $post->loadCount('reactions as likes')
            ->loadCount('comment as comments');
        $post['user_name'] = $post->User->firstname . " " . $post->User->lastname;
        $post->setRelation('user', null);
        return response()->json(['data' => [$post]]);
    }
}
