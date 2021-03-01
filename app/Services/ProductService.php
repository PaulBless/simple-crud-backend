<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\ProductContract;
use Constants;
use Log;
use Validator;

class ProductService implements ProductContract
{
    /**
     * Eloquent instance
     * 
     * @var Product
     */
    private $model;
    
    /**
     * Instantiate a new instance.
     * 
     * @param Product $product 
     * @return void 
     */
    public function __construct(Product $product)
    {
        $this->model = $product;
    }

    /**
     * Get all fields
     * 
     * @param array $select
     * @return array 
     */
    public function getAllFields(array $select = ['*'])
    {
        try {
            $result = $this->model->select($select)->get();

            if ($result) {
                return [
                    'message' => 'Data is fetched successfully',
                    'payload' => $result,
                    'status'  => Constants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'No result found',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_NOT_FOUND_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status'  => Constants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Store/update data
     * 
     * @param array $data 
     * @return array 
     */
    public function store(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'title' => 'required|string',
                'description' => 'required|string',
                'price' => 'required',
                'image' => 'required',
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Validation Error',
                    'payload' => $validate->errors(),
                    'status'  => Constants::STATUS_CODE_VALIDATION_ERROR
                ];
            }

            $newData['title'] = $data['title'];
            $newData['description'] = $data['description'];
            $newData['price'] = $data['price'];
            $newData['image'] = $data['image'];
            
            if (!empty($data['id'])) {
                $response = $this->getById($data['id'], ['id']);
                if ($response['status'] !== Constants::STATUS_CODE_SUCCESS) {
                    return $response;
                } else {
                    $existingData = $response['payload'];
                }
                $response = $existingData->update($newData);
            } else {
                $response = $this->model->create($newData);
            }

            if ($response) {
                return [
                    'message' => !empty($data['id']) ? 'Data is successfully updated' : 'Data is successfully saved',
                    'payload' => $response,
                    'status'  => Constants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Something went wrong',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status'  => Constants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Fetch data by id
     * 
     * @param int $id
     * @param array $select
     * @return array
     */
    public function getById(int $id, array $select = ['*'])
    {
        try {
            $data = $this->model->select($select)->where('id', $id)->first();
            
            if ($data) {
                return [
                    'message' => 'Data is fetched successfully',
                    'payload' => $data,
                    'status'  => Constants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'No result is found',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_NOT_FOUND_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status'  => Constants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Get all fields with paginate
     * 
     * @param array $data
     * @param array $select
     * @return array 
     */
    public function getAllFieldsWithPaginate(array $data, array $select = ['*'])
    {
        try {
            $params = !empty($data['params']) ? json_decode($data['params']) : null;

            $perPage  = ($params && !empty($params->pageSize)) ? $params->pageSize : 10;
            
            if (!empty($data['sorter']) && count(json_decode($data['sorter'], true))) {
                $sorter = json_decode($data['sorter'], true);
                foreach ($sorter as $key => $value) {
                    $sortBy = $key;
                    $sortType = ($value === 'ascend' ? 'asc' : 'desc');
                }
            } else {
                $sortBy = 'created_at';
                $sortType = 'desc';
            }
            
            $result = $this->model->select($select)->orderBy($sortBy, $sortType);

            if ($params && !empty($params->keyword) && $params->keyword !== '') {
                $searchQuery = $params->keyword;
                $columns = !empty($data['columns']) ? $data['columns'] : null;
                
                if ($columns) {
                    $result->where(function($query) use ($columns, $searchQuery) {
                        foreach ($columns as $key => $column) {
                            if (!empty(json_decode($column)->search) && json_decode($column)->search === true) {
                                $fieldName = json_decode($column)->dataIndex;
                                $query->orWhere($fieldName, 'like', '%' . $searchQuery . '%');
                            }
                        }
                    });
                    
                }
            }

            $result = $result->paginate($perPage); 
            
            if ($result) {
                return [
                    'message' => 'Data is fetched successfully',
                    'payload' => $result,
                    'status'  => Constants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'No result is found',
                    'payload' => null,
                    'status'  => Constants::STATUS_CODE_NOT_FOUND_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status'  => Constants::STATUS_CODE_ERROR
            ];
        }
    }
}
