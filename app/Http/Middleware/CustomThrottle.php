<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

class CustomThrottle extends ThrottleRequests
{
    protected function buildResponse($key, $maxAttempts)
    {
        $response = response()->json([
            'message' => 'لقد قمت بعدد كبير من الطلبات، الرجاء المحاولة لاحقًا'
        ], 200);
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
}
