<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordReset;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;
use App\Events\PasswordChanged;
use App\Mail\PasswordResetCodeMail;

class PasswordResetController extends Controller
{
    public function sendCode(Request $request)
    {
    try{
        $validData = $request->validate([
            'email' =>"required|email"
        ]);
    }catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['message' =>$e->errors() ]);
    }
        $user =User::where('email',$validData['email']);
        $code = rand(100000, 999999);
        PasswordReset::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);
        return response()->json(['message' => 'Verification code sent.']);
    }
    public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|confirmed|min:6',
    ]);
    try {
        $user = User::where('email', $request['email'])->firstOrFail();
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'User not found!'], 400);
    }
    $reset = PasswordReset::where('user_id', $user->id)
        ->where('used', false)
        ->where('expires_at', '>', now())
        ->first();
    if (!$reset) {
        return response()->json(['message' => 'Invalid or expired code.'], 400);
    }
    $user->update(['password' => bcrypt($request['password'])]);
    $reset->update(['used' => true]);
    try{
        event(new PasswordChanged($user));
    }catch(Exception $e)
    {
        return $e->getMessage();
    }
    return response()->json(['message' => 'Password has been reset successfully.']);
}
    public function checkEmail(Request $request)
    {
        try{

            $validData = $request->validate([
                'email' => 'required|email'
            ]);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json(['message'=> $e->errors()]);
        }
        $user = User::where('email',$validData['email'])->first();
        if(!$user){
            return response()->json(['message' => 'User Not Found !'],404);
        }
        try{

            $code = rand(100000, 999999);
            PasswordReset::create([
                'user_id' => $user->id,
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);
           Mail::to($user->email)->queue(new PasswordResetCodeMail($user, $code));
        }catch(Exception $e){
            return response()->json(['message' =>$e],400);
        }
        return response()->json(['message' => 'send email successfuly']);
    }
    public function checkCode(Request $request)
    {
        try{
            $validData = $request->validate([
                'email' => 'required|email',
                'code' => 'min:6|max:6'
            ]);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json(['message' => $e->errors()],403);
        }
        $user = User::where('email' ,$validData['email']);
        if(!$user){
            return response()->json(['message' => "User Not Found !"],404);
        }
        $reset = PasswordReset::where('user_id', $user->pluck('id'))
        ->where('used', false)
        ->where('code',$validData['code'])
        ->where('expires_at', '>', now())
        ->first();
    if (!$reset) {
        return response()->json(['message' => 'Invalid or expired code.'], 400);
    }
    return response(null,200);
    }

    public function editPasswordInProfile(Request $request)
    {
        try{

            $validData = $request->validate([
                'old_password' => 'required',
                'password' => 'required|confirmed|min:8',
            ]);
        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' =>$e->errors() ]);
        }
        $token = PersonalAccessToken::findToken($request->bearerToken());
        if(!$token)
        {
            return response()->json(['message' => 'unAuth'],401);
        }
        $user = $token->tokenable;
        if(!$user)
        {
            return response()->json(['message' => 'unAuth'],401);
        }
        if(!Hash::check($validData['old_password'],$user->password))
        {
            return response()->json(['message' => 'Not correct password'],401);
        }
        $user->update(['password' => bcrypt($request['password'])]);
        return response()->json(['message' => 'update password successfully']);
    }
}
