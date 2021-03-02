<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\ProductContract;
use Auth;
use Constants;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

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

    /**
     * Get products with pagination
     * 
     * @param Request $request 
     * @return JsonResponse 
     * @throws BindingResolutionException 
     */
    public function products(Request $request)
    {
        $result = $this->product->getAllFieldsWithPaginate(Auth::id(), $request->all());
        return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
    }

    /**
     * View, store and delete product
     * 
     * @param Request $request 
     * @return JsonResponse|void 
     * @throws BindingResolutionException 
     */
    public function product(Request $request)
    {
        if ($request->isMethod('get')) {
            $result = $this->product->getById(Auth::id(), $request->id);
            return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
        } elseif ($request->isMethod('post')) {
            $result = $this->product->store(Auth::id(), $request->all());
            return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
        } elseif ($request->isMethod('delete')) {
            $result = $this->product->deleteByIds(Auth::id(), $request->ids);
            return response()->json($result, !empty($result['status']) ? $result['status'] : Constants::STATUS_CODE_SUCCESS);
        }
    }
}
