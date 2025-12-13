<?php

/**
 * Product Controller using ORM and Views
 */

namespace App\Http\Controllers;

use NeoCore\System\Core\Controller;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;
use NeoCore\System\Core\ORMService;
use App\Entities\Product;
use App\Repositories\ProductRepository;

class ProductORMController extends Controller
{
    private ProductRepository $productRepository;

    public function __construct()
    {
        $this->productRepository = ORMService::getRepository(Product::class);
    }

    /**
     * List products (HTML view)
     */
    public function index(Request $request, Response $response): Response
    {
        $products = $this->productRepository->findInStock(50);
        
        return $this->view($response, 'products/index', [
            'products' => $products
        ]);
    }

    /**
     * List products (JSON API)
     */
    public function apiIndex(Request $request, Response $response): Response
    {
        $category = $request->query('category');
        
        if ($category) {
            $products = $this->productRepository->findByCategory($category, 50);
        } else {
            $products = $this->productRepository->findInStock(50);
        }

        $data = array_map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category,
                'price' => $product->price,
                'stock' => $product->stock,
                'status' => $product->status,
            ];
        }, $products);

        return $this->respondSuccess($response, $data);
    }

    /**
     * Create new product
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->all();

        // Validation
        $errors = $this->validate($data, [
            'name' => 'required|min:3',
            'slug' => 'required',
            'price' => 'required|numeric',
        ]);

        if (!empty($errors)) {
            return $this->respondValidationError($response, $errors);
        }

        // Check if slug exists
        if ($this->productRepository->findBySlug($data['slug'])) {
            return $this->respondError($response, 'Slug already exists', 422);
        }

        // Create product entity
        $product = new Product();
        $product->name = $data['name'];
        $product->slug = $data['slug'];
        $product->description = $data['description'] ?? null;
        $product->category = $data['category'] ?? null;
        $product->price = (float)$data['price'];
        $product->stock = (int)($data['stock'] ?? 0);

        // Save
        $entityManager = ORMService::getEntityManager();
        $entityManager->persist($product);
        $entityManager->run();

        return $this->respondSuccess($response, [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
        ], 'Product created successfully');
    }

    /**
     * Show single product by slug
     */
    public function show(Request $request, Response $response): Response
    {
        $slug = $request->param('slug');
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            return $this->respondNotFound($response, 'Product not found');
        }

        return $this->respondSuccess($response, [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'category' => $product->category,
            'price' => $product->price,
            'stock' => $product->stock,
            'status' => $product->status,
            'in_stock' => $product->isInStock(),
        ]);
    }

    /**
     * Update stock
     */
    public function updateStock(Request $request, Response $response): Response
    {
        $id = $request->param('id');
        $product = $this->productRepository->findByPK($id);

        if (!$product) {
            return $this->respondNotFound($response, 'Product not found');
        }

        $quantity = (int)$request->input('quantity', 0);

        try {
            if ($quantity > 0) {
                $product->increaseStock($quantity);
            } else {
                $product->decreaseStock(abs($quantity));
            }

            $product->updatedAt = new \DateTimeImmutable();

            $entityManager = ORMService::getEntityManager();
            $entityManager->persist($product);
            $entityManager->run();

            return $this->respondSuccess($response, [
                'stock' => $product->stock
            ], 'Stock updated');

        } catch (\RuntimeException $e) {
            return $this->respondError($response, $e->getMessage(), 400);
        }
    }
}
