<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\MediaHelper;
use App\Mail\BlockUserMail;
use App\Mail\UnBlockUserMail;
use App\Models\Friend;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function create(Request $request)
    {
        try{
            $validate=$request->validate([
                'firstname' => 'string',
                'lastname' => 'string',
                'father_name' => 'required|string',
                'mother_name' => 'required|string',
                'live' => 'required|string',
                'education' => 'required|string',
                'work' => 'required|string',
                'email' => 'email|required',
                'birthday' => 'date|required',
                'gender' => 'required|in:male,female',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required',
                'phone' => 'required|digits:10',
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
        $userEx=User::where('email',$request['email'])->exists();
        if($userEx)
        {
            return response()->json(['message' => 'User has been exist'],400);
        }
        $user=User::create($validate);
        $token=$user->createToken('auth_token')->plainTextToken;
        return response()->json(['data' =>['token' => $token]],200);
    }
    public function login(Request $request)
    {
        try{
            $valide=$request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);
        }
        catch(\Illuminate\Validation\ValidationException $e)
        {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
        $user=User::
        where('email',$request['email'])
        ->first();
        if(!$user)
        {
            return response()->json(['data' =>'user not found'],404);
        }
        if($user->block){
           return response()->json([
            'message' => 'Your account has been blocked for violating our privacy policy.',
            'status' => 'blocked',
            'code' => 403
        ], 403);
        }
        if(!Hash::check($valide['password'],$user->password))
        {
            return response()->json(['message' =>'Not correct password'],401);
        }
        $token=$user->createToken('auth_token')->plainTextToken;
        return response()->json(['data' =>['token' => $token]],200);
    }
   public function editProfile(Request $request)
{
    try {
        $validData = $request->validate([
            'image' => 'nullable|file|mimes:jpeg,png,jpg',
            'bio' => 'nullable|string'
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['message' => $e->errors()], 422);
    }
    $user = AuthHelper::getUserFromToken($request);
    if (!$user) {
        return response()->json([
            'message' => 'User not found!'
        ], 404);
    }
    $updateData = [];
    if ($request->hasFile('image')) {
        $path = MediaHelper::StoreMedia('profileImage', $request, 'image');
        $updateData['profile_image'] = $path;
    }
    if ($request->has('bio')) {
        $updateData['bio'] = $request->input('bio');
    }
    if (!empty($updateData)) {
        User::find($user->id)->update($updateData);
        return response()->json(['message' => 'Profile updated successfully'], 200);
    }
    return response()->json(['message' => 'Nothing to update'], 400);
}
    public function show(Request $request)
    {
        $user =AuthHelper::getUserFromToken($request);
        if(!$user)
        {
            return response()->json(['message' => "unAuth"],401);
        }
        $friends = $user->friends()->count();
        $user['friends'] = $friends;
        $user->loadCount(['posts as posts']);
        return response()->json(['data' => $user]);
    }
    public function showpost(User $user, Request $request)
    {
    $user1 =AuthHelper::getUserFromToken($request);
    if (!$user1) {
        return response()->json(['message' => 'user not found'], 404);
    }
    $posts = $user->posts()->withCount([
        'reactions as likes',
        'comment as comments'
    ])->get();
    if ($posts->isEmpty()) {
        return response()->json(['message' => "can't find any post"], 204);
    }
    foreach ($posts as $post) {
        $isFriend = Friend::where([
            ['user_id', $user1->id],
            ['friend_id', $post->user_id],
        ])->orWhere([
            ['user_id', $post->user_id],
            ['friend_id', $user1->id],
        ])->exists();
        $hasLiked = $post->reactions()
            ->where('reaction_type', 'like')
            ->where('user_id', $user1->id)
            ->exists();
        $post->setAttribute('is_friend', $isFriend);
        $post->setAttribute('has_liked', $hasLiked);
        $post->setAttribute('user_name', $user->firstname . ' ' . $user->lastname);
        $post->setAttribute('profile_image', $user->profile_image);
        unset($post->user);
    }

    return response()->json([
        'message' => 'Successfully response',
        'data' => [
                'posts' => $posts,
        ]
    ], 200);
}
    public function showprofile(User $user,Request $request)
    {
        $userLog = AuthHelper::getUserFromToken($request);
        if(!$userLog)
        {
            return response()->json(['message' => 'UnAuth'],401);
        }

         $friendsCount = $user->friends()->count();
         $user['friends'] = $friendsCount;
        $isFriend = Friend::where(function ($query) use ($user, $userLog) {
    $query->where([
        ['user_id', $user->id],
        ['friend_id', $userLog->id],
        ['status', 'accepted']
    ])->orWhere([
        ['user_id', $userLog->id],
        ['friend_id', $user->id],
        ['status', 'accepted']
    ]);
})->exists();
        $user['is_friend'] = $isFriend;
        $user->loadCount(['posts as posts']);
        return response()->json(['data' => $user]);
    }
}
