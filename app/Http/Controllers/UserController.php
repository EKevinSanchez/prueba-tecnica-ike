<?php

namespace App\Http\Controllers;

use App\Models\AccountActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Function to update the user's account activity and information
     */
    public function updateUser(Request $request){
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'username' => 'required|string',
                'name' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string',
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
            $username = User::where('username', $request->username)->where('id', '!=', $request->user_id)->first();
            if ($username != null) {
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error updating user',
                        'errors' => [
                            'username' => 'The username is already in use.'
                        ],
                        'code' => '400'
                    ]
                ], 400);
            }
            $email = User::where('email', $request->email)->where('id', '!=', $request->user_id)->first();
            if ($email != null) {
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error updating user',
                        'errors' => [
                            'email' => 'The email is already in use.'
                        ],
                        'code' => '400'
                    ]
                ], 400);
            }
            $user = User::find($request->user_id);
            if ($user == null) {
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error updating user',
                        'errors' => [
                            'user_id' => 'The user does not exist.'
                        ],
                        'code' => '400'
                    ]
                ], 400);
            }
            $user->username = $request->username;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->save();

            //verificar el ultimo registro en la tabla account_activities del usuario que en la columna consent_id2 tenga algun valor
            $consent_id2 = AccountActivity::where('user_id', $request->user_id)->whereNotNull('consent_id_email')->orderBy('id', 'desc')->first();
            $consent_id3 = AccountActivity::where('user_id', $request->user_id)->whereNotNull('consent_id_phone')->orderBy('id', 'desc')->first();
            if ($consent_id2 == null && $request->consent_id2 == true) {
                AccountActivity::create([
                    'user_id' => $request->user_id,
                    'consent_id_email' => AccountActivity::generateId('consent_id2'),
                    'date_consent_id_email' => date('Y-m-d')
                ]);
            }elseif ($consent_id2 == 'Revoked' && $request->consent_id2 == true) {
                AccountActivity::create([
                    'user_id' => $request->user_id,
                    'consent_id_email' => AccountActivity::generateId('consent_id2'),
                    'date_consent_id_email' => date('Y-m-d')
                ]);
            }elseif ($consent_id2 != null && $request->consent_id2 == false) {
                AccountActivity::create([
                    'user_id' => $request->user_id,
                    'consent_id_email' => 'Revoked',
                    'date_consent_id_email' => date('Y-m-d')
                ]);
            }
            if ($consent_id3 == null && $request->consent_id3 == true) {
                AccountActivity::create([
                    'user_id' => $request->user_id,
                    'consent_id_phone' => AccountActivity::generateId('consent_id3'),
                    'date_consent_id_email' => date('Y-m-d')
                ]);
            }elseif ($consent_id3 == 'Revoked' && $request->consent_id3 == true) {
                AccountActivity::create([
                    'user_id' => $request->user_id,
                    'consent_id_phone' => AccountActivity::generateId('consent_id3'),
                    'date_consent_id_email' => date('Y-m-d')
                ]);
            }elseif ($consent_id3 != null && $request->consent_id3 == false) {
                AccountActivity::create([
                    'user_id' => $request->user_id,
                    'consent_id_phone' => 'Revoked',
                    'date_consent_id_email' => date('Y-m-d')
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => 'success',
                'information' => [
                    'message' => 'User updated successfully',
                    'code' => '200'
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => 'error',
                'information' => [
                    'message' => 'Error updating user',
                    'errors' => $e->getMessage(),
                ]
            ], 500);
        }
    }

    /**
     * Function to delete the user's account and personal access token
     */
    public function deleteUser($userId){
        DB::beginTransaction();
        try {
            $user = User::find($userId);
            if ($user == null) {
                return response()->json([
                    'status' => 'error',
                    'information' => [
                        'message' => 'Error deleting user',
                        'errors' => [
                            'user_id' => 'The user does not exist.'
                        ],
                        'code' => '400'
                    ]
                ], 400);
            }
            $user->delete();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'information' => [
                    'message' => 'User deleted successfully',
                    'code' => '200'
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => 'error',
                'information' => [
                    'message' => 'Error deleting user',
                    'errors' => $e->getMessage(),
                ]
            ], 500);
        }
    }
}
