<?php

namespace App\Http\Controllers;

use App\Models\AccountActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthUserController extends Controller
{
    //

    /**
     * Function to register a new user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerUser(Request $request){
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|unique:users',
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|string',
                'consent_id1' => 'required|boolean',
                'consent_id2' => 'required|boolean',
                'consent_id3' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error registering user',
                        'errors' => $validator->errors(),
                        'code' => '400'
                    ]
                ], 400);    
            }
            if ($request->consent_id1 == false ) {
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error registering user',
                        'errors' => [
                            'consent_id1' => 'The consent to the terms and conditions is mandatory.'
                        ],
                        'code' => '400'
                    ]
                ], 400);
            }
            $user = User::create([
                'username' => $request->username,
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);
            if (!$user){
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error registering user',
                        'code' => '500'
                    ]
                ], 500);
            }
            $consentIds = $this->generateId($request->all());
            foreach ($consentIds as $key => $value) {
                $request[$key] = $value;
            }
            //obtain the acutal language
            $language = config('app.faker_locale');
            $language = str_replace('_', '/', $language);
            //obtain the country code
            $country = substr($language, -2);
            $cardId = $language.'-1234'.$user->id;
            $activities = AccountActivity::create([
                'user_id' => $user->id,
                'card_id' => Crypt::encrypt($cardId),
                'consent_id_card' => $request->consent_id_card != null ? $request->consent_id_card : null,
                'date_consent_id_card' => date('Y-m-d'),
                'consent_capture_country' => $country,
                'presented_languaje' => $language,
                'consent_id_email' => $request->consent_id_email != null ? $request->consent_id_email : null,
                'date_consent_id_email' => date('Y-m-d'),
                'consent_id_phone' => $request->consent_id_phone != null ? $request->consent_id_phone : null,
                'date_consent_id_phone' => date('Y-m-d'),
            ]);
            if (!$activities){
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error registering user',
                        'code' => '500'
                    ]
                ], 500);
            }
            DB::commit();
            return response()->json([
                'status' => 'success',
                'information' => [
                    'message' => 'User registered successfully',
                    'id' => $user->id,
                    'code' => '200'
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('error registering user: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'information' => [
                    'message' => 'Error registering user',
                    'code' => '500'
                ]
            ], 500);
        }
    }

    /**
     * Function to generate Auth Token for a user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAuthToken(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string',
                'password' => 'required|string'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error generating auth token',
                        'errors' => $validator->errors(),
                        'code' => '400'
                    ]
                ], 400);    
            }
            $user = User::where('username', $request->username)->first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'User not found',
                        'code' => '404'
                    ]
                ], 404);
            }
            if (!Auth::attempt($request->only('username', 'password'))){
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Credentials do not match',
                        'code' => '401'
                    ]
                ], 401);
            }
            $token = $user->createToken('auth_token');
            return response()->json([
                'status' => 'success',
                'information' => [
                    'token' => $token->plainTextToken,
                    'date_finish' => $token->accessToken->created_at->addMinutes(config('sanctum.expiration')),
                    'code' => '200'
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('error generating auth token: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'information' => [
                    'message' => 'Error generating auth token',
                    'code' => '500'
                ]
            ], 500);
        }
    }

    /**
     * Function for generating the id of the accepted consents
     * @param $request
     * @return array
     */
    public function generateId($request){
        $catalog = [
            'consent_id1' => 'consent_id_card',
            'consent_id2' => 'consent_id_email',
            'consent_id3' => 'consent_id_phone'
        ];
        $consentIds = [];
        foreach ($request as $key => $value) {
            if (strpos($key, 'consent_id') !== false && $value == true) {
                $consentIds[$catalog[$key]] = AccountActivity::generateId($key);
            }
        }
        return $consentIds;
    }
}
