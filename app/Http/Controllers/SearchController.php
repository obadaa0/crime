<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
public function search(Request $request)
{
    $user = AuthHelper::getUserFromToken($request);
    if(!$user){
        return response()->json(['message' => 'قم بتسجيل الدخول اولا'],401);
    }

    $search = $request->input('query');
    if (is_array($search)) {
        return response()->json([
            'message' => 'Search word must be a string, not an array',
            'users' => [],
            'posts' => []
        ], 400);
    }
    if (empty($search)) {
        return response()->json([
            'message' => 'Search word is required',
            'users' => [],
            'posts' => []
        ], 400);
    }
    $posts = Post::with('User')
                ->withCount('reactions')
                ->withCount('comment')->where('content', 'LIKE', '%'.$search.'%')
                ->limit(20)
                ->get();
    $users = User::where('firstname', 'LIKE', '%'.$search.'%')
                ->whereIn('role',['user','police'])
                ->orWhere('lastname', 'LIKE', '%'.$search.'%')
                ->limit(10)
                ->get();
    $users = $this->isYourself($users,$user->id);
    return response()->json([
        'message' => 'Search results',
        'users' => $users,
        'posts' => $posts
    ]);
}
    public function isYourself($users,$loggedInUserId){
        foreach($users as $user){
            if($user->id == $loggedInUserId){
                $user['isYou'] =true;
            }
            else{
                $user['isYou'] =false;
            }
        }
        return $users;
    }
}
