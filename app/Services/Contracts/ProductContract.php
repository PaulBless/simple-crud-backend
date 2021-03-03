<?php

namespace App\Services\Contracts;

interface ProductContract
{
    /**
     * Get the corresponding model
     * 
     * @return Product 
     */
    public function getModel();

    /**
     * Get all
     * 
     * @param int $userId
     * @param array $select
     * @return array 
     */
    public function getAll(int $userId, array $select = ['*']);

    /**
     * Store/update data
     * 
     * @param int $userId
     * @param array $data 
     * @return array 
     */
    public function store(int $userId, array $data);

    /**
     * Fetch data by id
     * 
     * @param int $userId
     * @param int $id
     * @param array $select
     * @return array
     */
    public function getById(int $userId, int $id, array $select = ['*']);

    /**
     * Get all with paginate
     * 
     * @param int $userId
     * @param array $data
     * @param array $select
     * @return array 
     */
    public function getAllWithPaginate(int $userId, array $data, array $select = ['*']);

    /**
     * Delete data by id array
     * 
     * @param int $userId
     * @param array $ids
     * @return array
     */
    public function deleteByIds(int $userId, array $ids);
}
