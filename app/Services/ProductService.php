<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\ProductContract;
use Constants;
use Log;
use Str;
use Illuminate\Http\UploadedFile;
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
     * @param int $userId
     * @param array $select
     * @return array 
     */
    public function getAllFields(int $userId, array $select = ['*'])
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
     * @param int $userId
     * @param array $data 
     * @return array 
     */
    public function store(int $userId, array $data)
    {
        try {
            $validate = Validator::make($data, [
                'title' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|numeric',
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
            $newData['user_id'] = $userId;
            
            if (!empty($data['id'])) {
                $result = $this->getById($userId, $data['id'], ['*']);
                if ($result['status'] !== Constants::STATUS_CODE_SUCCESS) {
                    return $result;
                } else {
                    $existingData = $result['payload'];
                }
                //process image
                $processImage = $this->processImage($userId, $data['image'], $existingData);
                if ($processImage['status'] !== Constants::STATUS_CODE_SUCCESS) {
                    return $processImage;
                }
                
                $newData['image'] = $processImage['payload']['file'];

                $result = $existingData->update($newData);
            } else {
                //process image
                $processImage = $this->processImage($userId, $data['image']);
                if ($processImage['status'] !== Constants::STATUS_CODE_SUCCESS) {
                    return $processImage;
                }

                $newData['image'] = $processImage['payload']['file'];
                $result = $this->model->create($newData);
            }

            if ($result) {
                return [
                    'message' => !empty($data['id']) ? 'Product is successfully updated' : 'Product is successfully saved',
                    'payload' => $result,
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
     * Process the image
     * 
     * @param int $userId 
     * @param UploadedFile $file 
     * @param Product|null $product
     * @return array
     */
    private function processImage(int $userId, UploadedFile $file, $product = null)
    {
        if ($product) {
            //delete previous
            try {
                if (file_exists($product->image)) {
                    unlink($product->image);
                }
            } catch (\Throwable $th) {
                Log::error($th->getMessage());
            }
        }
        //new entry
        try {
            $fileName = time().'_'.Str::random(10).'.png';
            $pathName = 'assets/upload/'.$userId.'/';
            
            if (!file_exists($pathName)) {
                mkdir( $pathName, 0777, true);
            }

            if ($file->move($pathName, $fileName)) {
                return [
                    'message' => 'File is successfully saved',
                    'payload' => [
                        'file' => $pathName.$fileName
                    ],
                    'status' => Constants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'File could not be saved',
                    'payload' => null,
                    'status' => Constants::STATUS_CODE_ERROR
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
     * @param int $userId
     * @param int $id
     * @param array $select
     * @return array
     */
    public function getById(int $userId, int $id, array $select = ['*'])
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
     * @param int $userId
     * @param array $data
     * @param array $select
     * @return array 
     */
    public function getAllFieldsWithPaginate(int $userId, array $data, array $select = ['*'])
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

    /**
     * Delete data by id array
     * 
     * @param int $userId
     * @param array $ids
     * @return array
     */
    public function deleteByIds(int $userId, array $ids)
    {
        try {
            $entries = $this->model->where('user_id', $userId)->whereIn('id', $ids)->get();

            $deleted = 0;

            foreach ($entries as $key => $entry) {
                //delete image
                try {
                    if (file_exists($entry->image)) {
                        unlink($entry->image);
                    }
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }
                
                $entry->delete();
                $deleted++;
            }

            if ($deleted) {
                return [
                    'message' => ($deleted > 1 ? 'Products are' : 'Product is').' deleted successfully',
                    'payload' => [
                        'totalDeleted' => $deleted
                    ],
                    'status'  => Constants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Nothing to Delete',
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
