<?php

namespace App\Services;

use Hash;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\Contracts\UserContract;
use Auth;
use Carbon\Carbon;
use Constants;
use DB;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Log;
use Str;
use Validator;

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

    /**
     * Handle forget password
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleForgetPassword(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'email' => 'required|max:255|email',
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Validation Error',
                    'payload' => $validate->errors(),
                    'status'  => Constants::STATUS_CODE_VALIDATION_ERROR
                ];
            }

            $result = $this->getByEmail($data['email']);

            if ($result['status'] !== Constants::STATUS_CODE_SUCCESS) {
                return $result;
            } else {
                $user = $result['payload'];
            }

            return $this->sendPasswordResetEmail($user);
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
     * Fetch data by email
     * 
     * @param string $email 
     * @param array $select 
     * @return array 
     */
    public function getByEmail(string $email, array $select = ['*'])
    {
        try {
            $data = $this->model->select($select)->where('email', $email)->first();
            
            if ($data) {
                return [
                    'message' => 'User found.',
                    'payload' => $data,
                    'status'  => Constants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Found no user with that email address.',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_NOT_FOUND_ERROR
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
     * Send password reset mail by creating reset token
     * 
     * @param User $user 
     * @return array
     */
    private function sendPasswordResetEmail(User $user)
    {
        $result = $this->createPasswordResetToken($user->email);

        if ($result['status'] !== Constants::STATUS_CODE_SUCCESS) {
            return $result;
        } else {
            $token = $result['payload'];
        }

        $user->notify(new ResetPasswordNotification($token));

        return [
            'message' => 'We have emailed your password reset link!',
            'payload' => null,
            'status'  => Constants::STATUS_CODE_SUCCESS
        ];
    }

    /**
     * Return password reset token
     * 
     * @param string $email 
     * @return array 
     */
    private function createPasswordResetToken(string $email)
    {
        $result = DB::table('password_resets')->where('email', $email)->first();

        if ($result) {
            return [
                'message' => 'Token found',
                'payload' => $result->token,
                'status'  => Constants::STATUS_CODE_SUCCESS
            ];
        }

        $token = Str::random(80);;
        $result = $this->savePasswordResetToken($token, $email);
        return $result;
    }

    /**
     * Store password reset token
     * 
     * @param string $token 
     * @param string $email 
     * @return array
     */
    private function savePasswordResetToken(string $token, string $email)
    {
        $entry = DB::table('password_resets')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()            
        ]);

        return [
            'message' => 'Successfully created.',
            'payload' => $token,
            'status'  => Constants::STATUS_CODE_SUCCESS
        ];
    }

    /**
     * Handle reset password
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleResetPassword(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'email' => 'required|max:255|email',
                'token' => 'required',
                'password' => 'required|min:5|confirmed',
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Validation Error',
                    'payload' => $validate->errors(),
                    'status'  => Constants::STATUS_CODE_VALIDATION_ERROR
                ];
            }

            $result = $this->getByEmail($data['email']);

            if ($result['status'] !== Constants::STATUS_CODE_SUCCESS) {
                return $result;
            } else {
                $user = $result['payload'];
            }

            $result = $this->validatePasswordResetToken($data['token'], $data['email']);

            if ($result['status'] !== Constants::STATUS_CODE_SUCCESS) {
                return $result;
            } else {
                $token = $result['payload'];
            }
            
            //delete token
            $this->deletePasswordResetToken($data['token'], $data['email']);

            //reset password
            return $this->resetPassword($user, $data['password']);
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
     * Validate password reset token
     * 
     * @param string $token 
     * @param string $email 
     * @return array
     */
    private function validatePasswordResetToken(string $token, string $email)
    {
        $data = DB::table('password_resets')
                ->where('token', $token)
                ->where('email', $email)
                ->where('created_at', '>', Carbon::now()->subHours(1))
                ->first();

        if ($data) {
            return [
                'message' => 'Token found.',
                'payload' => $data,
                'status'  => Constants::STATUS_CODE_SUCCESS
            ];
        } else {
            return [
                'message' => 'Invalid password reset token. Please try again',
                'payload' => null,
                'status'  => Constants::STATUS_CODE_NOT_FOUND_ERROR
            ];
        }
    }

    /**
     * delete password reset token
     * 
     * @param string $token 
     * @param string $email 
     * @return array
     */
    private function deletePasswordResetToken(string $token, string $email)
    {
        DB::table('password_resets')
                ->where('token', $token)
                ->where('email', $email)
                ->where('created_at', '>', Carbon::now()->subHours(1))
                ->delete();

        return [
            'message' => 'Token is deleted successfully.',
            'payload' => null,
            'status'  => Constants::STATUS_CODE_SUCCESS
        ];
    }

    /**
     * Reset password for the user
     * 
     * @param User $user 
     * @param string $password 
     * @return array
     */
    private function resetPassword(User $user, string $password)
    {
        $user->password = Hash::make($password);
        $user->save();

         return [
            'message' => 'Your password has been reset!',
            'payload' => $user,
            'status'  => Constants::STATUS_CODE_SUCCESS
        ];
    }
}
