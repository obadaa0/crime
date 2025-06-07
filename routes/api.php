<?php
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
    //user
    // Route::post('/password/send-code', [PasswordResetController::class, 'sendCode']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
    Route::post('/password/check-email',[PasswordResetController::class,'checkEmail']);
    Route::post('/password/check-code',[PasswordResetController::class,'checkCode']);
    Route::get('/user/show',[UserController::class,'show']);
    Route::post('/user/create',[UserController::class,'create']);
    Route::post('/user/login',[UserController::class,'login']);
    Route::get('/user/show-profile/{user}',[UserController::class,'showprofile']);
    Route::get('/user/show-post/{user}',[UserController::class,'showpost']);
    Route::post('/user/profile-image',[UserController::class,'editProfile']);
    Route::put('/password/reset-in-profile',[PasswordResetController::class,'editPasswordInProfile']);
    Route::get('/police/users',[UserController::class,'getUsers']);
    Route::get('/police/polices',[UserController::class,'getPolice']);
    Route::get('/user/block/{user}',[UserController::class,'blockUser']);
    Route::get('/user/unblock/{user}',[UserController::class,'UnblockUser']);
    //post
    Route::post('/post/create',[PostController::class,'create']);
    Route::delete('/post/delete/{post}',[PostController::class,'delete']);
    Route::get('/post/index',[PostController::class,'getPosts']);
    Route::get('/post/show/{post}',[PostController::class,'showPost']);
    Route::get('/post/show',[PostController::class,'getAllPost']);
    Route::post('/post/reaction',[ReactionController::class,'reactToPost']);
    Route::get('/post/{post}/like',[ReactionController::class,'getLikePost']);
    Route::get('/post/{post}/like-user',[ReactionController::class,'getLikedUser']);
    //comment
    Route::post('/comment/add/{post}',[CommentController::class,'addComment']);
    Route::delete('/comment/delete/{comment}',[CommentController::class,'deleteComment']);
    Route::get('/post/{post}/comment',[CommentController::class,'getAllCommentsPost']);
    //friend
    Route::post('/friends/{friend}/send-request', [FriendshipController::class, 'sendRequest']);
    Route::post('/friends/{friend}/remove-request', [FriendshipController::class, 'removeRequest']);
    Route::post('/friends/{friend}/accept', [FriendshipController::class, 'acceptRequest']);
    Route::post('/friends/{friend}/reject', [FriendshipController::class, 'rejectRequest']);
    Route::get('/friends/get-myfriend', [FriendshipController::class, 'getFriendList']);
    Route::get('/friends/get-friend-request', [FriendshipController::class, 'getPendingRequest']);
    Route::get('/friends/number-friend-request', [FriendshipController::class, 'getNumberOFPendingRequest']);
    Route::get('/friend/isfriend/{friend}',[FriendshipController::class,'isFriend']);
    //notifications
    Route::get('/notification/index',[NotificationController::class,'index']);
    Route::post('/notification/mark-as-read/{notification}',[NotificationController::class,'markAsRead']);
    Route::post('/notification/mark-all-as-read',[NotificationController::class,'markAllAsRead']);
    Route::get('notification/number-of-nitif',[NotificationController::class,'numberOfNotification']);
    //report
    Route::get('/report/show',[ReportController::class,'show']);//->middleware('isPolice')
    // Route::get('/report/show',[ReportController::class,'show']);//->middleware('isPolice')
    Route::post('/report/create',[ReportController::class,'create']);
    Route::get('report/progress/{report}',[ReportController::class,'setProgress']);
    Route::get('report/resolved/{report}',[ReportController::class,'setResolved']);
    //admin
    Route::post('/admin/police/create',[UserController::class,'createPolice']);
    Route::post('admin/police/update/{user}',[UserController::class,'updatePolice']);
    Route::delete('admin/police/delete/{user}',[UserController::class,'deletePolice']);
    //news
    Route::get('/news/show',[PostController::class,'summarizeNews']);
