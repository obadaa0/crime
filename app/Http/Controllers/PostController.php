<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Doctrine\Common\Lexer\Token;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\Cast\String_;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use App\Helpers\AuthHelper;
use App\Helpers\MediaHelper;
use App\Models\News;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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
            $path = MediaHelper::StoreMedia('posts',$request,'media');
            $user = AuthHelper::getUserFromToken($request);
            if(!$user){
                return response()->json([
                    'message' => 'قم بتسجيل الدخول اولا'
                ]);
            }
            try{
                $response = Http::timeout(100)->post('https://19f5-212-102-51-98.ngrok-free.app/predict',[
                    'text' => $validatedData['content']
                ]);
                if($response->successful())
                {
                    if($response['prediction'] != "real"){
                        return response()->json(['message' => ' النص مزيف تاكد من الخبر اولا'],400);
                    }
                }
            }
              catch(Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }
        catch(ConnectionException $e)
        {
            return response()->json(['error' => $e->getMessage()]);
        }
        catch(RequestException $e){
            return response()->json(['error' => $e->getMessage()]);
        };
            $post=Post::create([
                'user_id' =>$user->id,
                'content' => $request['content'],
                'media' => $path
            ]);
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
    public function delete(Request $request,Post $post)
    {
      $user = AuthHelper::getUserFromToken($request);

        if(!$user){
            return response()->json([
                'message' => 'قم بتسجيل الدخول اولا'
            ]);
        }
        if(!$post){
            return response()->json([
                'message' => 'لا يوجد منشور'
            ],404);
        }
        $post=$post
        ->where('user_id',$user->id)
        ->where('id',$post->id);
        if(!$post)
        {
            return response()->json([
                'message' => 'لا يوجد منشور'
            ],404);
        }
        if($post->delete()){
            return response()->json([
                'mesasge' => 'تم حذف المنشور'
            ],200);
        }
        return response()->json([
            'message' => ' حصل خطا ما حاول مرى اخرى'
        ],501);
    }
    public function getPosts(Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 404);
        }
        $posts=$user->posts;
        if(!$posts)
        {
            return response()->json(['message' => "لا يوجد اي منشور الى الان"],200);
        }
        foreach($posts as $post)
        {
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
            $post['user_name'] = $user->firstname.' '.$user->lastname;
            $post['profile_image'] = $user->profile_image;
            if($post->media == null)
            {
                $post->media = "";
            }
        }
        return response()->json(['message' => 'تم عرض جميع المنشورات بنجاح','data'=>
        [
        'posts' => $posts
        ] ],200);
    }

    public function getAllPost(Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 404);
        }
        $posts=Post::inRandomOrder()->get();
        if(!$posts)
        {
            return response()->json(['message' => "لا يوجد منشور"],204);
        }
        $userPost = [];
        $otherPost = [];
        $filterPost = [];
        foreach($posts as $post)
        {
        $isFriend = Friend::where([
        ['user_id', $user->id],
        ['friend_id', $post->user_id],
    ])->orWhere([
        ['user_id', $post->user_id],
        ['friend_id', $user->id],
    ])->exists();
    $post['is_friend'] = $isFriend;
            if($post->user_id == $user->id)
            {
                if($post->created_at->isToday())
                {
                    $post['his_post'] = true;
                    $hasLiked = $post->reactions()
                ->where('reaction_type', 'like')
                ->where('user_id', $user->id)
                ->exists();
                $post->has_liked = $hasLiked;
                $post->loadCount([
                    'reactions as likes'
            ]);
            $post->loadCount([
                'comment as comments'
            ]);
            $post['user_name'] = $user->firstname . ' ' . $user->lastname;
            $post['profile_image'] = $user->profile_image;
                    $userPost[] =$post;
                }
                continue;
            }
            $hasLiked = $post->reactions()
            ->where('reaction_type', 'like')
            ->where('user_id', $user->id)
            ->exists();
            $post->has_liked = $hasLiked;
            $post->loadCount([
                'reactions as likes'
            ]);
            $post->loadCount([
                'comment as comments'
            ]);
            $post['user_name'] = $post->User->firstname . ' ' . $post->User->lastname;
            $post['profile_image'] = $post->user->profile_image;
            $otherPost[] = $post;
        }
        $filterPost =array_merge($userPost,$otherPost);
        return response()->json(['message' => 'Successfully response','data'=>
        [
        'posts' => $filterPost
        ] ],200);
    }
public function showPost(Post $post) {
    $post->loadCount('reactions as likes')
        ->loadCount('comment as comments');
    $post['user_name'] = $post->User->firstname . " " . $post->User->lastname;
    $post->setRelation('user', null);
    return response()->json(['data' => [$post]]);
}

    public function summarizeNews(Request $request)
    {
        $user = AuthHelper::getUserFromToken($request);
        if(!$user){
            return response()->json(['message' => 'قم بتسجيل الدخول اولا'],401);
        }

        // Carbon::setWeekStartsAt(Carbon::SATURDAY);
        // Carbon::setWeekEndsAt(Carbon::FRIDAY);
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $posts = Post::whereBetween('created_at', [$startOfWeek, $endOfWeek])
    ->whereHas('user', function ($query) {
        $query->where('role', 'police');
    })
    ->pluck('content');

    $postArray = $posts->toArray();
    $newsText = '';
    foreach($postArray as $postt){
        $newsText .= $postt;
    }
            $news = News::create([
            'news' => $newsText,
            'user_id' => $user->id
        ]);
        // return $news;
$response = Http::post('https://19f5-212-102-51-98.ngrok-free.app/summarize', [
    'texts' => $postArray
]);
    if($response->successful())
    {
        // $news = News::create([
        //     'news' => array_values($posts->toArray()),
        //     'user_id' => $user->id
        // ]);
        return response()->json(['data' => $response['summaries']]);
    }
    return $response->json();
    }
}
