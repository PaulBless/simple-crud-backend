<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\ProductContract;
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
        return response()->json($this->product->getAllFieldsWithPaginate($request->all()));
    }
}
