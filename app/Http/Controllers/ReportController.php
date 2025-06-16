<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\MediaHelper;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Predis\Command\Redis\HTTL;

class ReportController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validData = $request->validate([
                'description' => 'required|string',
                'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi',
                'location' => 'string',
                'lng' => '',
                'lat' => ''
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->errors()], 400);
        }
        $user = AuthHelper::getUserFromToken($request);
        if (!$user) {
            return response()->json(['message' => "unAuth"]);
        }
        try {
            $file = $request->file('media');
            $checkFromAI = Http::timeout(100)->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post('https://2287-169-150-218-29.ngrok-free.app/classify');
            if ($checkFromAI->successful()) {
                $result = $checkFromAI->json();
                if ($result['label'] == 'Normal') {
                    return response()->json(['message' => 'no crime here '], 400);
                }
                $validData['crime_type'] = $result['label'];
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (ConnectionException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (RequestException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        };
        try {
            $predict = Http::timeout(100)->post('https://c8e5-185-252-220-126.ngrok-free.app/predict', [
                'latitude' => $validData['lng'],
                'longitude' => $validData['lat']
            ]);
            if ($predict->successful()) {
                $predictionData = $predict->json();
                $validData['predicted'] = json_encode($predictionData);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        } catch (ConnectionException $e) {
            return response()->json(['error' => $e->getMessage()]);
        } catch (RequestException $e) {
            return response()->json(['error' => $e->getMessage()]);
        };
        // $validData['predicted'] = json_encode([$validData['lng'], $validData['lat']]);
        $validData['media'] = MediaHelper::StoreMedia('reports', $request);
        // $validData['crime_type'] = "fighting";
        $report = $user->reports()->create($validData);
        return response()->json(['message' => 'report send successfully', 'data' => $report], 200);
    }
}
