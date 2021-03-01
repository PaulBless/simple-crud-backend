<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\UserContract;
use Constants;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @var UserContract
     */
    private $user;

    /**
     * Create a new instance.
     * 
     * @param UserContract $user 
     * @return void 
     */
    public function __construct(UserContract $user)
    {
        $this->user = $user;
    }

    /**
     * Handle login
     * 
     * @param Request $request 
     * @return JsonResponse 
     * @throws BindingResolutionException 
     */
    public function login(Request $request)
    {
        $result = $this->user->handleLogin($request->all());

        return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
    }

    /**
     * Handle signup
     * 
     * @param Request $request 
     * @return JsonResponse 
     * @throws BindingResolutionException 
     */
    public function signup(Request $request)
    {
        $result = $this->user->handleSignup($request->all());

        return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
    }

    /**
     * Handle forget password
     * 
     * @param Request $request 
     * @return JsonResponse 
     * @throws BindingResolutionException 
     */
    public function forgetPassword(Request $request)
    {
        $result = $this->user->handleForgetPassword($request->all());

        return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
    }

    /**
     * Handle reset password
     * 
     * @param Request $request 
     * @return JsonResponse 
     * @throws BindingResolutionException 
     */
    public function resetPassword(Request $request)
    {
        $result = $this->user->handleResetPassword($request->all());

        return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
    }

    /**
     * Get the authenticated User
     *
     * @return JsonResponse
     */
    public function me()
    {
        $result = $this->user->me();

        return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
    }

    /**
     * Refresh a token
     *
     * @return JsonResponse
     */
    public function refreshToken()
    {
        $result = $this->user->refreshToken();

        return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
    }
}
