<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
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

    public function verifyOtp(Request $request){
        $phoneNumber = $request->input('phoneNumber');
        $code = $request->input('code');

        $data = [
            'api_key' => 'VUFpUWRNZE5IWW9FUW9qd3FRbUE', // Arkesel API key
            'code'    => $code,
            'number'  => $phoneNumber,
        ];

        $headers = [
            'api-key' => 'VUFpUWRNZE5IWW9FUW9qd3FRbUE',
        ];

        $response = Http::withHeaders($headers)
            ->post('https://sms.arkesel.com/api/otp/verify', $data);

        if ($response->successful()) {
            $result = $response->json();

            if ($result['code'] !== '1100') {
                return response()->json($result, 500); // OTP invalid or expired
            }
    //update
            $latestRequest = LoginRequest::where('phone_number', $phoneNumber)
                ->latest() // orders by created_at descending
                ->first();

            if ($latestRequest) {
                $latestRequest->status = 'approved'; // or 'denied'
                $latestRequest->save();
            }
            return response()->json($result, 200); // OTP verified successfully
        }

        return response()->json(['error' => 'OTP verification failed.'], 500);
     }
    public function handle()
    {
        $loginRequests = LoginRequest::select('ip', 'time', 'status')->get();
        return response()->json($loginRequests);
//        return response()->json([
//            ["ip" => "192.168.0.1", "time" => "2025-04-13 21:30:00","status"=> "approved"],
//            ["ip" => "192.168.0.1", "time" => "2025-04-13 21:30:00","status"=> "approved"],
//            ["ip" => "10.0.0.5", "time" => "2025-04-13 18:10:00","status"=> "denied"],
//            ["ip" => "10.0.0.5", "time" => "2025-04-13 18:10:00","status"=> "pending"]
//        ]);
    }

}
