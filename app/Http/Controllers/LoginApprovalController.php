<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoginRequest;

class LoginApprovalController extends Controller
{
    public function checkStatus(Request $request)
    {
        $requestId = $request->input('request_id');
        $timeout = 60; // seconds
        $startTime = time();

        while (time() - $startTime < $timeout) {
            // Get the login request
            $loginRequest = LoginRequest::find($requestId);

            // If we found the request and it's not pending, return the result
            if ($loginRequest && $loginRequest->status !== 'pending') {
                return response()->json(['status' => $loginRequest->status]);
            }

            // Wait for 2 seconds before polling again
            sleep(2);
        }

        // Timeout if the status isn't updated within the time limit
        return response()->json(['status' => 'timeout'], 408);
    }


    public function handle(Request $request)
    {
        $requestId = $request->input('request_id');
        $approved = filter_var($request->input('approved'), FILTER_VALIDATE_BOOLEAN);
        $loginRequest = LoginRequest::find($requestId);

        if (!$loginRequest) {
            return response()->json(['error' => 'Invalid request ID'], 404);
        }

        if ($loginRequest->status !== 'pending') {
            return response()->json(['message' => 'Already handled']);
        }

        $loginRequest->status = $approved ? 'approved' : 'denied';
        $loginRequest->save();

        return response()->json(['message' => 'Login request updated.']);
    }

}
