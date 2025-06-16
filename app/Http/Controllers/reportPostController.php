<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Post;
use App\Models\report_post;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class reportPostController extends Controller
{
    public function create(Request $request)
    {
        try {

            $validateData = $request->validate([
                'post_id' => 'required',
                'reason' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->errors()]);
        }

        $user = AuthHelper::getUserFromToken($request);
        if (!$user) {
            return response()->json(['message' => 'قم بتسجيل الدخول اولا'], 401);
        }
        $post_id = $validateData['post_id'];
        $post = Post::find($post_id);
        if (!$post) {
            return response()->json(['message' => 'لا يزجد منشور للابلاغ عليه !'], 404);
        }
        $reported_person = $post->User->id;
        if ($user->id === $reported_person) {
            return response()->json(['message' => 'لا يمكنك البلاغ على احد منشوراتك'], 400);
        }
        $validateData['reported_person'] = $reported_person;
        $report = $user->reporterPost()->create(
            $validateData
        );
        return response()->json([
            'message' => 'report send successfuly ',
            'data' => $report
        ]);
    }
}
