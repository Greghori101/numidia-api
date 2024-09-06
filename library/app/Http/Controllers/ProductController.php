<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        $request->validate([
            'sortBy' => ['nullable', 'string'],
            'sortDirection' => ['nullable', 'string', 'in:asc,desc'],
            'perPage' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string'],
        ]);
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $perPage = $request->query('perPage', 10);
        $search = $request->query('search', '');

        $productsQuery = Product::query();

        $productsQuery->when($search, function ($query) use ($search) {
            return $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%$search%");
            });
        });
        $products = $productsQuery->with('pictures')->orderBy($sortBy, $sortDirection)

            ->paginate($perPage);

        return $products;
    }

    public function create_product(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'qte' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
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

            if ($request->hasFile('pictures')) {
                $pictures = $request->file('pictures');
                foreach ($pictures as $picture) {

                    $file = $picture;

                    $file_extension = $picture->extension();

                    $bytes = random_bytes(ceil(64 / 2));
                    $hex = bin2hex($bytes);
                    $file_name = substr($hex, 0, 64);

                    $file_url = '/products/' .  $file_name . '.' . $file_extension;
                    Storage::put($file_url, file_get_contents($file));
                    $product->pictures()->save(
                        new File([
                            'url' => $file_url,
                        ])
                    );
                }
            }

            return response()->json(['message' => 'Product added successfully'], 201);
        });
    }

    public function update_product(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'qte' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $id) {
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

            $product->pictures()->delete();

            $images =  $request->file('pictures');
            if ($images) {
                foreach ($images as $image) {
                    $file = $image;

                    $file_extension = $image->extension();

                    $bytes = random_bytes(ceil(64 / 2));
                    $hex = bin2hex($bytes);
                    $file_name = substr($hex, 0, 64);

                    $file_url = '/products/' .  $file_name . '.' . $file_extension;

                    Storage::put($file_url, file_get_contents($file));

                    $product->pictures()->create(['url' => $file_url]);
                }
            }


            $product->save();

            return response()->json(['message' => 'Product updated successfully'], 200);
        });
    }

    public function show_product($id)
    {
        $product = Product::with(['pictures'])->findOrFail($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product, 200);
    }

    public function delete_product($id)
    {
        $product = Product::with(['pictures'])->findOrFail($id);

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
