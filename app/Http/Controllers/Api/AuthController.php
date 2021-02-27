<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\UserContract;
use Illuminate\Http\Request;

class AuthController extends Controller
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

        return response()->json($result, !empty($result['status']) ? $result['status'] : 200);
    }

    /**
     * Handle registration
     * 
     * @param Request $request 
     * @return JsonResponse 
     * @throws BindingResolutionException 
     */
    public function registration(Request $request)
    {
        $result = $this->user->handleRegistration($request->all());

        return response()->json($result, !empty($result['status']) ? $result['status'] : 200);
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

        return response()->json($result, !empty($result['status']) ? $result['status'] : 200);
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

        return response()->json($result, !empty($result['status']) ? $result['status'] : 200);
    }
}
