<?php

namespace App\Services\Contracts;

interface ProductContract
{
    /**
     * Get all fields
     * 
     * @param array $select
     * @return array 
     */
    public function getAllFields(array $select = ['*']);

    /**
     * Store/update data
     * 
     * @param array $data 
     * @return array 
     */
    public function store(array $data);

    /**
     * Fetch data by id
     * 
     * @param int $id
     * @param array $select
     * @return array
     */
    public function getById(int $id, array $select = ['*']);

    /**
     * Get all fields with paginate
     * 
     * @param array $data
     * @param array $select
     * @return array 
     */
    public function getAllFieldsWithPaginate(array $data, array $select = ['*']);
}
