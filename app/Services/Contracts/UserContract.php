<?php

namespace App\Services\Contracts;

interface UserContract
{
    /**
     * Get the corresponding model
     * 
     * @return User 
     */
    public function getModel();

    /**
     * Handle login
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleLogin(array $data);

    /**
     * Handle signup
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleSignup(array $data);

    /**
     * Fetch data by email
     * 
     * @param string $email 
     * @param array $select 
     * @return array 
     */
    public function getByEmail(string $email, array $select = ['*']);

    /**
     * Handle forget password
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleForgetPassword(array $data);

    /**
     * Handle reset password
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleResetPassword(array $data);

    /**
     * Get the authenticated User
     *
     * @return JsonResponse
     */
    public function me();

    /**
     * Refresh a token
     *
     * @return JsonResponse
     */
    public function refreshToken();

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
    );
}
