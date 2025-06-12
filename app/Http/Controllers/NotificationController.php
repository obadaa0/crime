<?php

namespace App\Http\Controllers;
use App\Helpers\AuthHelper;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
class NotificationController extends Controller
{
    public function index(Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);
        if(!$user)
        {
            return response()->json(['message' => "قم بتسجيل الدخول اولا"],401);
        }
        $notifications = $user->notifications->sortByDesc('created_at')->values();
        $notifications = $this->addNotificationInfo($notifications);
        return response()->json(['data'=>$notifications]);
    }
    public function markAsRead(Request $request,Notification $notification)
    {
     $user = AuthHelper::getUserFromToken($request);

        if(!$user)
        {
            return response()->json(['message' => "قم بتسجيل الدخول اولا"],401);
        }
        $notification->markAsRead();
         return response()->json(['message' => 'تمت قراءة الاشعار']);
    }
    public function markAllAsRead(Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);

        if(!$user)
        {
            return response()->json(['message' => "قم بتسجيل الدخول اولا"],401);
        }
         $user->notifications()->where('is_read', false)->update(['is_read' => true]);
        return response()->json(['message' => 'تمت قراءة كل الاشعارات']);
    }

    public function numberOfNotification(Request $request)
    {
     $user = AuthHelper::getUserFromToken($request);

        if(!$user)
        {
            return response()->json(['message' => "قم بتسجيل الدخول اولا"],401);
        }
        $user->loadCount([
            'notifications as notifications'
        ]);
        return response()->json(['data'=>['notifications_number' => $user->notifications]]);
    }

    public function addNotificationInfo($notifications)
    {
        foreach($notifications as $notification){
            $userNameSender= $notification->sender->firstname . " " .$notification->sender->lastname;
            $profile_image_sender = $notification->profile_image;
            unset($notification['sender']);
            $notification['sender'] = $userNameSender;
            $notification['profile_image'] = $profile_image_sender;
            $dataInfo = json_decode($notification['data']);
            $notification['message'] = $dataInfo->message;
            if($notification->type == "friend_request" || $notification->type == 'accept_friend_request')
            {
                $notification['post_id'] = 0;
            }
            else{
                $notification['post_id'] = $dataInfo->post_id;
            }
            unset($notification['data']);
        }
        return $notifications;
    }

}
