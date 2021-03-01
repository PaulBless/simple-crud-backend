<?php

namespace App\Services\Contracts;

interface UserContract
{
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
}
