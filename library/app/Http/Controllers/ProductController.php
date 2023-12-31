<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('pictures')->get();
        return response()->json($products, 200);
    }

    public function create_product(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'qte' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'price' => $request->price,
            'qte' => $request->qte,
            'description' => $request->description,
            'purchase_date' => $request->purchase_date,
            'purchase_price' => $request->purchase_price,
            'tags' => $request->tags,
            'level' => $request->level
        ]);
        $pictures = $request->file('pictures');
        foreach ($pictures as $picture) {

            $name = $picture->getClientOriginalName();
            $content = file_get_contents($picture->getRealPath());
            $extension = $picture->getClientOriginalExtension();

            $product->pictures()->save(
                new File([
                    'name' => $name,
                    'content' => base64_encode($content),
                    'extension' => $extension,
                ])
            );
        }

        return response()->json(['message' => 'Product added successfully'], 201);
    }

    public function update_product(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'qte' => 'required|numeric|min:0',
        ]);

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->name = $request->name;
        $product->price = $request->price;
        $product->qte = $request->qte;
        $product->description = $request->description;
        $product->purchase_date = $request->purchase_date;
        $product->purchase_price = $request->purchase_price;
        $product->tags = $request->tags;
        $product->level = $request->level;
        $product->save();

        return response()->json(['message' => 'Product updated successfully'], 200);
    }

    public function show_product($id)
    {
        $product = Product::with(['pictures'])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product, 200);
    }

    public function delete_product($id)
    {
        $product = Product::with(['pictures'])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        foreach ($product->pictures as $picture) {
            $picture->delete();
        }

        $product->delete();

        return response()->json(['message' => 'Product and associated pictures deleted successfully'], 200);
    }
}
