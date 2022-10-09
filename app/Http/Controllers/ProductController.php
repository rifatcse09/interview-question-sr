<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Http\Requests\ProductRequest;
use Exception, DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $variants = Variant::with('productVariants')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'title' => $item->title,
                    'variants' => collect($item->productVariants)->pluck('variant')->unique(),
                ];
            });

        $query = Product::select('id', 'title', 'description', 'created_at')
            ->with(
                'productVariantPrices:id,product_variant_one,product_variant_two,product_variant_three,price,stock,product_id',
                'productVariantPrices.variantOne:id,variant',
                'productVariantPrices.variantTwo:id,variant',
                'productVariantPrices.variantThree:id,variant'
            )
            ->latest();

        if ($request->variant) {
            $query->whereHas('productVariants', function ($q) use ($request) {
                $q->where('variant', 'like', "%{$request->variant}%");
            });
        }

        if ($request->title) {
            $query->where('title', 'like', "%{$request->title}%");
        }

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->price_from || $request->price_to) {
            $query->whereHas('productVariantPrices', function ($q) use ($request) {
                if ($request->price_from) {
                    $q->where('price', '>=', $request->price_from);
                }
                if ($request->price_to) {
                    $q->where('price', '<=', $request->price_to);
                }
                return $q;
            });
        }

        $products = $query->paginate(10);

        return view('products.index', compact('products', 'variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ProductRequest $request)
    {

        try {
            DB::beginTransaction();

            //Separate product data
            $productFiled = $request->only('title', 'sku', 'description');

            $product = Product::create($productFiled);

            if (count($request->product_image) > 0) {
                $product->productImage()->createMany($request->product_image);
            }

            //create variants
            $variantData = CommonHelper::createVariant($request->product_variant);
            $product->productVariants()->createMany($variantData);

            //create variant price    
            $productVariants = ProductVariant::where('product_id', $product->id)->get();
            $variantPrice = ProductVariantPrice::createVariantPrice($request->product_variant_prices, $productVariants, $product->id);
            ProductVariantPrice::insert($variantPrice);

            DB::commit();
            return response()->json(["message" => "Product Addedd Successfully"], 200);
        } catch (Exception $ex) {
            DB::rollback();
            return response()->json(["message" => "Faild to add product!", 'error' => $ex->getmessage()], 404);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();

        $product = Product::with(
            'productImage',
            'productVariants',
            'productVariantPrices',
            'productVariantPrices.variantOne',
            'productVariantPrices.variantTwo',
            'productVariantPrices.variantThree'
        )->find($product->id);

        return view('products.edit', compact('variants', 'product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request, Product $product)
    {
        try {

            DB::beginTransaction();

            $productFiled = $request->only('title', 'sku', 'description');
            $product->update($productFiled);

            if ($request->product_image) {
                $images = $request->product_image;
                $product->productImage()->createMany($images);
            }

            //Create variants
            $variant = CommonHelper::createVariant($request->product_variant);
            $product->productVariants()->delete();
            $product->productVariants()->createMany($variant);

            //Create variant price
            $productVariants = ProductVariant::where('product_id', $product->id)->get();
            $variantPrice = ProductVariantPrice::createVariantPrice($request->product_variant_prices, $productVariants, $product->id);
            $product->productVariantPrices()->delete();
            ProductVariantPrice::insert($variantPrice);

            DB::commit();
            return response()->json(["message" => "Product Successfully Update!"], 200);
        } catch (Exception $ex) {
            DB::rollback();
            return response()->json(["message" => "Faild to update data!", 'error' => $ex->getmessage()], 404);
        }
    }

    /**
     * Upload the specified resource from storage.
     *
     * @param \App\Models\Product $request
     * @return \Illuminate\Http\Response
     */
    public function fileUpload(Request $request)
    {
        $file = $request->file;
        $location = '';
        if ($file) {
            $fileName = date('ymdhis') . '-' . $file->getClientOriginalName();
            $path = 'uploads/products/';
            $location = CommonHelper::fileUpload($fileName, $file, $path);
        }
        return response()->json(['file_path' => $location], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
