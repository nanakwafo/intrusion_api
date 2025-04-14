<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationMail;
use App\Models\User;
use Illuminate\Support\Facades\Http;
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
            'phone_number' => 'required',
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

        }
        $token = $user->createToken($user->name);

        $phoneNumber = $user->phone_number; // Assuming this is passed in the request

        $data = [
            'expiry'   => 10,
            'length'   => 6,
            'medium'   => 'sms',
            'message'  => 'This is OTP from CTVET, %otp_code%',
            'number'   => $phoneNumber,
            'sender_id'=> 'CTVET',
            'type'     => 'numeric',
        ];
        $headers = [
            'api-key' => 'VUFpUWRNZE5IWW9FUW9qd3FRbUE', // Replace with your actual API key
        ];
        $response = Http::withHeaders($headers)->post('https://sms.arkesel.com/api/otp/generate', $data);
        if ($response->successful()) {
            $result = $response->json();
            if ($result['code'] !== '1000') {
                return response()->json($result, 500);
            }
            $ip = $request->ip();
            $time = Carbon::now()->format('g:iA');
            $loginRequest = LoginRequest::create(['user_id' => $user->id,'ip' => $ip, 'time' => $time, 'phone_number' => $phoneNumber]);


            $timeout = 60; // seconds
            $startTime = time();

            while (time() - $startTime < $timeout) {
                // Get the login request

                $loginRequest = LoginRequest::find($user->id) ->latest()
                    ->first();

                // If we found the request and it's not pending, return the result
                if ($loginRequest && $loginRequest->status !== 'pending') {
                    // Timeout if the status isn't updated within the time limit


                    return [
                        'user' => $user,
                        'token' => $token->plainTextToken,

                    ];

                }

                // Wait for 2 seconds before polling again
                sleep(2);
            }
            return response()->json(['status' => 'timeout'], 408);


        }
        return response()->json(['error' => 'Failed to send OTP.'], 500);


    }
    public function  logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return [
            'message' => 'You are logged out.'
        ];
    }
}
