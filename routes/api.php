<?php
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\reportPostController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
    //user
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
    Route::post('/password/check-email',[PasswordResetController::class,'checkEmail']);
    Route::post('/password/check-code',[PasswordResetController::class,'checkCode']);
    Route::get('/user/show',[UserController::class,'show']);
    Route::post('/user/create',[UserController::class,'create'])->middleware('customThrottle:1,0.083');
    Route::post('/user/login',[UserController::class,'login'])->middleware('customThrottle:1,0.083');
    Route::get('/user/show-profile/{user}',[UserController::class,'showprofile'])->middleware('customThrottle:1,0.083');
    Route::get('/user/show-post/{user}',[UserController::class,'showpost'])->middleware('customThrottle:1,0.083');
    Route::post('/user/profile-image',[UserController::class,'editProfile'])->middleware('customThrottle:1,0.083');
    Route::put('/password/reset-in-profile',[PasswordResetController::class,'editPasswordInProfile'])->middleware('customThrottle:1,0.083');
    //post
    Route::post('/post/create',[PostController::class,'create'])->middleware('customThrottle:1,0.083');
    Route::delete('/post/delete/{post}',[PostController::class,'delete'])->middleware('customThrottle:1,0.083');
    Route::get('/post/index',[PostController::class,'getPosts']);
    Route::get('/post/show/{post}',[PostController::class,'showPost']);
    Route::get('/post/show',[PostController::class,'getAllPost']);
    Route::post('/post/reaction',[ReactionController::class,'reactToPost']);
    Route::get('/post/{post}/like',[ReactionController::class,'getLikePost']);
    Route::get('/post/{post}/like-user',[ReactionController::class,'getLikedUser']);
    Route::post('/post/report',[reportPostController::class,'create'])->middleware('customThrottle:1,0.083');
    //comment
    Route::post('/comment/add/{post}',[CommentController::class,'addComment'])->middleware('customThrottle:1,0.083');
    Route::delete('/comment/delete/{comment}',[CommentController::class,'deleteComment'])->middleware('customThrottle:1,0.083');
    Route::get('/post/{post}/comment',[CommentController::class,'getAllCommentsPost']);
    //friend
    Route::post('/friends/{friend}/send-request', [FriendshipController::class, 'sendRequest'])->middleware('customThrottle:1,0.083');
    Route::post('/friends/{friend}/remove-request', [FriendshipController::class, 'removeRequest'])->middleware('customThrottle:1,0.083');
    Route::post('/friends/{friend}/accept', [FriendshipController::class, 'acceptRequest'])->middleware('customThrottle:1,0.083');
    Route::post('/friends/{friend}/reject', [FriendshipController::class, 'rejectRequest'])->middleware('customThrottle:1,0.083');
    Route::get('/friends/get-myfriend', [FriendshipController::class, 'getFriendList']);
    Route::get('/friends/get-friend-request', [FriendshipController::class, 'getPendingRequest']);
    Route::get('/friends/number-friend-request', [FriendshipController::class, 'getNumberOFPendingRequest']);
    Route::get('/friend/isfriend/{friend}',[FriendshipController::class,'isFriend']);
    Route::get('/friend/get-friend/{user}',[FriendshipController::class,'getFriend']);
    //notifications
    Route::get('/notification/index',[NotificationController::class,'index']);
    Route::post('/notification/mark-as-read/{notification}',[NotificationController::class,'markAsRead'])->middleware('customThrottle:1,0.083');
    Route::post('/notification/mark-all-as-read',[NotificationController::class,'markAllAsRead'])->middleware('customThrottle:1,0.083');
    Route::get('notification/number-of-nitif',[NotificationController::class,'numberOfNotification']);
    //report
    Route::post('report/create',[ReportController::class,'create'])->middleware('customThrottle:1,0.083');
    //search
    Route::post('/search',[SearchController::class,'search']);
