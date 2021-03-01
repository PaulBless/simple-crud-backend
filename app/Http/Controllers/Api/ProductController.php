<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\ProductContract;
use Constants;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @var ProductContract
     */
    private $product;

    /**
     * Create a new instance.
     * 
     * @param UserContract $user 
     * @return void 
     */
    public function __construct(ProductContract $product)
    {
        $this->product = $product;
    }

    public function products(Request $request)
    {
        $result = $this->product->getAllFieldsWithPaginate($request->all());
        return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
    }
}
