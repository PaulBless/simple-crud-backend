<?php

namespace App\Services;

use Hash;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;
use App\Services\Contracts\UserContract;
use Auth;
use Constants;
use InvalidArgumentException;
use Log;
use Str;
use Validator;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Password;

class UserService implements UserContract
{
    /**
     * Eloquent instance
     * 
     * @var User
     */
    private $model;
    
    /**
     * Instantiate a new instance.
     * 
     * @param User $user 
     * @return void 
     */
    public function __construct(User $user)
    {
        $this->model = $user;
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \App\User|null
     */
    public function guard()
    {
        return Auth::guard();
    }

    /**
     * Handle login and returns the authenticated user
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleLogin(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Validation Error',
                    'payload' => $validate->errors(),
                    'status'  => Constants::STATUS_CODE_VALIDATION_ERROR
                ];
            }

            $credentials['email'] = $data['email'];
            $credentials['password'] = $data['password'];

            if ($token = $this->guard()->attempt($credentials)) {
                return [
                    'message' => 'Successfully logged in',
                    'payload' => [
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        'expires_in' => $this->guard()->factory()->getTTL() * 60
                    ],
                    'status'  => Constants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Incorrect credentials',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_UNAUTHORIZED_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong.',
                'payload' => $th->getMessage(),
                'status'  => Constants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Handle registration
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleRegistration(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'required|max:255|email|unique:users',
                'password' => 'required|min:5|confirmed',
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Validation Error',
                    'payload' => $validate->errors(),
                    'status'  => Constants::STATUS_CODE_VALIDATION_ERROR
                ];
            }

            $newData['name'] = $data['name'];
            $newData['email'] = $data['email'];
            $newData['password'] = Hash::make($data['password']);

            $result = $this->createUser($newData);

            if ($result['status'] !== Constants::STATUS_CODE_SUCCESS) {
                return $result;
            } else {
                $user = $result['payload'];
            }

            $token = $this->guard()->login($user);
            
            return [
                'message' => 'Registration successful',
                'payload' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => $this->guard()->factory()->getTTL() * 60
                ],
                'status'  => Constants::STATUS_CODE_SUCCESS
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong.',
                'payload' => $th->getMessage(),
                'status'  => Constants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Store a new user to database
     * 
     * @param array $data 
     * @return array 
     */
    private function createUser(array $data)
    {
        try {
            $user = $this->model->create($data);
            return [
                'message' => 'Successfully created.',
                'payload' => $user,
                'status'  => Constants::STATUS_CODE_SUCCESS
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong.',
                'payload' => $th->getMessage(),
                'status'  => Constants::STATUS_CODE_ERROR
            ];
        }
    }
}
