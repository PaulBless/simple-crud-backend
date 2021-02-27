<?php

namespace App\Services\Contracts;

use Illuminate\Http\Request;

interface UserContract
{
    /**
     * Handle login and returns the authenticated user
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleLogin(array $data);

    /**
     * Handle registration
     * 
     * @param array $data 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public function handleRegistration(array $data);
}
