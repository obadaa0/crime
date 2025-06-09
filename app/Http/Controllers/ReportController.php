<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\MediaHelper;
use App\Models\Report;
use App\Models\User;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ReportController extends Controller
{
    public function show(Report $report,Request $request)
    {
    $report = Report::with('User')->paginate(10);
    return $report;
    }
    // public function show(Request $request)
    // {
    //     $numberOfReport = $request->input('per_page',10);
    //     $user = User::whereHas('reports')->with('reports')->paginate($numberOfReport);
    //     return $user;
    // }

    public function create(Request $request)
    {
        try{
            $validData = $request->validate([
                'description' => 'required|string',
                'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e){
            return response()->json(['message' => $e->errors()]);
        }
        $user = AuthHelper::getUserFromToken($request);
        if(!$user){
            return response()->json(['message' => "unAuth"]);
        }
        // try{
        //     $file = $request->file('media');
        //     $checkFromAI = Http::timeout(100)->attach(
        //         'file',
        //         file_get_contents($file->getRealPath()),
        //          $file->getClientOriginalName()
        //     )->post('https://5c90-185-184-195-145.ngrok-free.app/classify');
        //     if($checkFromAI->successful())
        //     {
        //         $result = $checkFromAI->json();
        //         if($result['label'] == 'Normal')
        //         {
        //             return response()->json(['message' => 'no crime here '],400);
        //         }
        //         $validData['crime_type'] = $result['label'];
        //     }
        // }
        // catch(Exception $e){
        //     return response()->json(['error' => $e->getMessage()]);
        // }
        // catch(ConnectionException $e)
        // {
        //     return response()->json(['error' => $e->getMessage()]);
        // }
        // catch(RequestException $e){
        //     return response()->json(['error' => $e->getMessage()]);
        // };
        $validData['media'] = MediaHelper::StoreMedia('reports',$request);
        $validData['crime_type'] = "fighting";
       $report = $user->reports()->create($validData);
        return response()->json(['message'=>'report send successfully', 'data' =>$report],200);
    }
    public function setProgress(Report $report)
    {
         $report->progress();
        return $report;
        }
    public function setResolved(Report $report)
    {
        $report->resolved();
        return $report;
    }
}
