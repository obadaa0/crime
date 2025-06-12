<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Services\NotificationService;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class CommentController extends Controller
{
    private $NotificationService;
    public function __construct(NotificationService $NotificationService)
    {
        $this->NotificationService = $NotificationService;
    }
    public function addComment(Request $request,Post $post)
    {
    try
        {
            $validate = $request->validate([
                'comment' => 'required|string|max:1000'
                 ]);
    }
    catch(ValidationException $e)
        {
            return response()->json(['message' => $e],422);
        }
    $user = AuthHelper::getUserFromToken($request);
    if(!$user)
    {
        return response()->json(['message' => 'unAuth'],401);
    }
    $comment = $post->comment()->create([
        'user_id' => $user->id,
        'comment' => $request->comment
    ]);
        $comment['flag'] = true;
        $comment['username'] = $user->firstname . ' '. $user->lastname;
        $comment['profile_image'] = $user->profile_image;
    if($user->id != $post->User->id){
        $this->NotificationService->sendCommentNotification($user,$post->User,$post->id,$comment);
    }
    return response()->json([
        'message' => 'تمت اضافة التعليق بنجاح',
        'data' => $comment
    ],201);
}
    public function deleteComment(Comment $comment,Request $request)
    {
        $user = AuthHelper::getUserFromToken($request);
        if(!$user)
        {
            return response()->json(['message' => 'unAuth'],401);
        }
        if($comment->user_id != $user->id)
        {
            return response()->json([
                'message' => "لا يمكنك حذف هذا التعليق"
            ], 403);
        }
        $comment->delete();
        return response()->json([
            'message' => 'تم حذف التعليق بنجاح'
        ]);
    }
    public function getAllCommentsPost(Post $post,Request $request)
    {
    $user = AuthHelper::getUserFromToken($request);
        if(!$user)
        {
            return response()->json(['message' => 'unAuth'],401);
        }
    $comments = $comments = $post->comment()->with('user')->get();
    foreach($comments as $comment)
        {
            if($comment['user_id'] == $user->id)
            {
                $comment['flag'] = true;
            }else{
                $comment['flag'] = false;
            }
            $comment['username'] = $comment['user']['firstname'].' '.$comment['user']['lastname'];
            $comment['profile_image'] = $comment['user']['profile_image'];
            unset($comment['user']);
        }
        if($comments->isEmpty())
        {
            return response()->json(['message' => 'لا يوجد تعليقات حتى الان'],200);
        }
        return response()->json(['message' => 'كل التعليقات',
            'data' => $comments]);
    }
}
