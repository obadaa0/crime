<?php

namespace App\Http\Controllers;
use App\Helpers\AuthHelper;
use App\Models\Friend;
use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use App\Services\NotificationService;
use Exception;

class FriendshipController extends Controller
{
    private $NotificationService;
    public function __construct(NotificationService $NotificationService)
    {
        $this->NotificationService = $NotificationService;
    }
    public function sendRequest(Request $request, User $friend)
{
    $user = AuthHelper::getUserFromToken($request);

    if (!$user) {
        return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 401);
    }
    if ($user->id === $friend->id) {
        return response()->json(['message' => 'لا يمكنك ارسال طلب صداقة لنفسك'], 422);
    }
    $existingRequest = Friend::where([
        ['user_id', $user->id],
        ['friend_id', $friend->id],
    ])->orWhere([
        ['user_id', $friend->id],
        ['friend_id', $user->id],
    ])->first();
    if ($existingRequest) {
        return response()->json(['message' => 'طلب الصداقة معلق'], 422);
    }
    $friendCreate = Friend::create([
        'user_id' => $user->id,
        'friend_id' => $friend->id,
        'status' => 'pending'
    ]);
    $this->NotificationService->sendFriendRequestNotification($user,$friend);
    return response()->json([
        'message' => 'تم ارسال طلب الصداقة بنجاح ',
        'data' => $friendCreate
    ], 201);
}
    public function acceptRequest(Request $request, Friend $friend)
    {
       try{
        if (!$friend) {
            return response()->json(['message' => 'لا يوجد طلب الصداقة'], 404);
        }
            $user = AuthHelper::getUserFromToken($request);
        if ($user->id !== $friend->friend_id) {
            return response()->json(['message' => 'غير مصرح ب قبول طلب الصداقة'], 403);
        }
        $friend->acceptRequest();
        $this->NotificationService->acceptFriendRequestNotification($user,$friend->sender);
        return response()->json([
            'message' => 'تم قبول طلب الصداقة بنجاح',
            'friendship' => $friend
        ]);}
        catch(Exception $e)
        {
            return $e->getMessage();
        }
    }
    public function rejectRequest(Request $request,Friend $friend)
{
    if (!$friend) {
        return response()->json(['message' => 'لا يوجد طلب صداقة'], 404);
    }
        $user = AuthHelper::getUserFromToken($request);
    if ($user->id !== $friend->friend_id) {
        return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 403);
    }
    if ($friend->status === 'accepted') {
        return response()->json(['message' => 'لا يمكن رفض طلب الصداقة'], 400);
    }
    $friend->rejectRequest();
    return response()->json([
        'message' => 'تم رفض طلب الصداقة بنجاح',
        'friendship' => $friend
    ]);
}
    public function getFriendList(Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);
        if (!$user) {
            return response()->json(['message' => 'لا يوجد مستخدم'], 404);
        }
        $friends = $user->friends();
        if($friends->isEmpty())
            {
                return response()->json(['message' => 'لا يوجد اصدقاء لديك الان']);
            }
        return response()->json(['data' => $friends]);
    }
    public function getPendingRequest(Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'لا يوجد مستخدم'], 404);
        }
        $requests = Friend::with('sender')
        ->where('friend_id',$user->id)
        ->where('status','pending')
        ->get();
        return response()->json(['data' => $requests]);
    }
    public function getNumberOFPendingRequest(Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);

        if(!$user)
        {
            return response()->json(['message' => "قم بتسجيل الدخول اولا"],401);
        }
        $user->loadCount(['pendingRequest as pendingRequest ']);
        return response()->json(['data' => $user->pendingRequest]);
    }
    public function isFriend(Friend $friend,Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);

    if (!$user) {
        return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 401);
    }
    $existingRequest = Friend::where([
        ['user_id', $user->id],
        ['friend_id', $friend->id],
    ])->orWhere([
        ['user_id', $friend->id],
        ['friend_id', $user->id],
    ])->first()->exists();

    if ($existingRequest) {
        $isFriend = $existingRequest;
        return response()->json(['data' =>$isFriend ]);
    }
    }
    public function removeRequest(Request $request, User $friend)
    {
      $user = AuthHelper::getUserFromToken($request);

    if (!$user) {
        return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 401);
    }
    if ($user->id === $friend->id) {
        return response()->json(['message' => 'لا يمكن قبول/رفض طلب الصداقة لنفسك'], 422);
    }
    $existingRequest = Friend::where([
        ['user_id', $user->id],
        ['friend_id', $friend->id],
    ])->orWhere([
        ['user_id', $friend->id],
        ['friend_id', $user->id],
    ])->where('status','pending')->first();
    if ($existingRequest) {
        $existingRequest->delete();
        return response()->json(['message' => 'تم حذف طلب الصداقة بنجاح']);
    }
    return response()->json(['message' => 'طلب الصداقة غير موجود'],404);
    }
    public function getFriend(User $user)
    {
        $friend = $user->friends();
        if($friend->isEmpty())
        {
                return response()->json(['message' => 'لا يوجد اصدقاء']);
        }
        return response()->json(['data' => $friend]);
    }
}
