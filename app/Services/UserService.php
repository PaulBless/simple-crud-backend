<?php

namespace App\Services;

use Hash;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\Contracts\ProductContract;
use App\Services\Contracts\UserContract;
use Auth;
use Carbon\Carbon;
use Constants;
use DB;
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
     * Get the corresponding model
     * 
     * @return User 
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Handle login
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
                $tokenDetails = [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => $this->guard()->factory()->getTTL() * 60
                ];

                return [
                    'message' => 'Successfully logged in',
                    'payload' => [
                        'user' => $this->guard()->user(),
                        'token' => $tokenDetails
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
     * Handle signup
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleSignup(array $data)
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

            $tokenDetails = [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60
            ];
            
            return [
                'message' => 'Signup is successful',
                'payload' => [
                    'user' => $this->guard()->user(),
                    'token' => $tokenDetails
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
        //delete existing tokens
        $this->deletePasswordResetToken($email);

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
            $this->deletePasswordResetToken($data['email']);

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
    private function deletePasswordResetToken(string $email)
    {
        DB::table('password_resets')
                ->where('email', $email)
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

    /**
     * Get the authenticated User
     *
     * @return JsonResponse
     */
    public function me()
    {
        $me = $this->guard()->user();

        return [
            'message' => 'User found',
            'payload' => $me,
            'status'  => Constants::STATUS_CODE_SUCCESS
        ];
    }

    /**
     * Refresh a token
     *
     * @return JsonResponse
     */
    public function refreshToken()
    {
        $token = $this->guard()->refresh();

        $tokenDetails = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ];

        return [
            'message' => 'Token refreshed',
            'payload' => $tokenDetails,
            'status'  => Constants::STATUS_CODE_SUCCESS
        ];
    }

    /**
     * Get stats
     * 
     * @param int $userId
     * @param string $todayStartDate
     * @param string $todayEndDate
     * @param string $thisWeekStartDate
     * @param string $thisWeekEndDate
     * @param string $thisMonthStartDate
     * @param string $thisMonthEndDate
     * @param bool $getProductData
     * @return JsonResponse
     */
    public function stats(
        int $userId, 
        string $todayStartDate = null, 
        string $todayEndDate = null, 
        string $thisWeekStartDate = null, 
        string $thisWeekEndDate = null, 
        string $thisMonthStartDate = null,
        string $thisMonthEndDate = null,
        bool $getProductData = true
    )
    {
        try {
            if ($todayStartDate) {
                $todayStartDate = Carbon::parse($todayStartDate)->format('Y-m-d H:i:s');
            }
            if ($todayEndDate) {
                $todayEndDate = Carbon::parse($todayEndDate)->format('Y-m-d H:i:s');
            }
            if ($thisWeekStartDate) {
                $thisWeekStartDate = Carbon::parse($thisWeekStartDate)->format('Y-m-d H:i:s');
            }
            if ($thisWeekEndDate) {
                $thisWeekEndDate = Carbon::parse($thisWeekEndDate)->format('Y-m-d H:i:s');
            }
            if ($thisMonthStartDate) {
                $thisMonthStartDate = Carbon::parse($thisMonthStartDate)->format('Y-m-d H:i:s');
            }
            if ($thisMonthEndDate) {
                $thisMonthEndDate = Carbon::parse($thisMonthEndDate)->format('Y-m-d H:i:s');
            }

            $data = [];

            if ($getProductData) {
                $productModel = resolve(ProductContract::class)->getModel();
                $product = $productModel->where('user_id', $userId);

                $data['product']['total'] = $product->count();

                if ($todayStartDate && $todayEndDate) {
                    $data['product']['totalToday'] = $product
                                                            ->where('created_at', '>=', $todayStartDate)
                                                            ->where('created_at', '<=', $todayEndDate)
                                                            ->count();
                }

                if ($thisWeekStartDate && $thisWeekEndDate) {
                    $data['product']['totalThisWeek'] = $product
                                                            ->where('created_at', '>=', $thisWeekStartDate)
                                                            ->where('created_at', '<=', $thisWeekEndDate)
                                                            ->count();
                }

                if ($thisMonthStartDate && $thisMonthEndDate) {
                    $data['product']['totalThisMonth'] = $product
                                                            ->where('created_at', '>=', $thisMonthStartDate)
                                                            ->where('created_at', '<=', $thisMonthEndDate)
                                                            ->count();
                }
            }

            return [
                'message' => 'Stats are fetched Successfully',
                'payload' => $data,
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
