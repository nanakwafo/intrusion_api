<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationMail;
use App\Models\User;
use App\Services\AkeselService;
use App\Models\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
class AuthController extends Controller
{
    protected $akeselService;

    public function __construct(AkeselService $akeselService)
    {
        $this->akeselService = $akeselService;
    }
    //
    public function  register(Request $request)
    {

        $fields = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);

        $user = User::create($fields);

        $token = $user->createToken($request->name);

        Mail::to($request->email)->send(new RegistrationMail());

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }
    public function  login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ];
            // return [
            //     'message' => 'The provided credentials are incorrect.'
            // ];
        }

        $token = $user->createToken($user->name);

        // Create login approval record
        $loginRequest = LoginRequest::create(['user_id' => $user->id]);

        // Simulate SMS (for Flutter app to intercept or for actual SMS)
        $ip = $request->ip();
        $time = Carbon::now()->format('g:iA');
        $message= "<#> Login attempt from $ip at $time. Approval code:$loginRequest->id\nMyApp: QoEc1CI5/ss";
        // You could use a real SMS service here (Twilio, AWS SNS, etc.)
        $to = 233593858412;
        $this->akeselService->sendSms($to, $message);




        $requestId = $loginRequest->id;
        $timeout = 60; // seconds
        $startTime = time();

        while (time() - $startTime < $timeout) {
            // Get the login request

            $loginRequest = LoginRequest::find($requestId);

            // If we found the request and it's not pending, return the result
            if ($loginRequest && $loginRequest->status !== 'pending') {
                // Timeout if the status isn't updated within the time limit
                return response()->json(['status' => 'timeout'], 408);

            }

            // Wait for 2 seconds before polling again
            sleep(2);
        }


        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'request_id' => $loginRequest->id,
            'notification'=>$message
        ];



    }
    public function  logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return [
            'message' => 'You are logged out.'
        ];
    }
}
