<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Extension\Controller;

use Closure;
use FacturaScripts\Plugins\PushWoocommerceProductos\Lib\WooProductService;
use FacturaScripts\Plugins\PushWoocommerceProductos\Lib\WooCategoryService;

/**
 * Extension for EditProducto controller to add WooCommerce functionality
 *
 * This controller extension handles HTTP requests and delegates business logic
 * to specialized services for better maintainability and organization.
 */
class EditProducto
{
    public function createViews(): Closure
    {
        return function () {
            error_log("EditProducto::createViews - Adding WooCommerce tab");
            $this->addHtmlView('myHtmlView', 'Tab/WooTiendaWeb', 'ProductoImagen', 'shop', 'fas fa-shop');
        };
    }

    public function execAfterAction(): Closure
    {
        return function ($action) {
            error_log("EditProducto::execAfterAction - Processing action: {$action}");

            switch ($action) {
                case 'create-wc-product':
                    $this->handleCreateWooCommerceProduct();
                    return false;

                case 'update-wc-product':
                    $this->handleUpdateWooCommerceProduct();
                    return false;

                case 'get-wc-categories':
                    $this->handleGetWooCommerceCategories();
                    return false;

                case 'create-wc-category':
                    $this->handleCreateWooCommerceCategory();
                    return false;

                case 'sync-from-wc':
                    $this->handleSyncFromWooCommerce();
                    return false;

                case 'delete-wc-product':
                    $this->handleDeleteWooCommerceProduct();
                    return false;
            }
        };
    }

    public function execPreviousAction(): Closure
    {
        return function ($action) {
            // Reserved for future pre-action logic
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'myHtmlView') {
                error_log("EditProducto::loadData - Loading data for WooCommerce view");
                // Add any view-specific data loading logic here
            }
        };
    }

    /**
     * Handle WooCommerce product creation
     */
    protected function handleCreateWooCommerceProduct()
    {
        return function () {
            error_log("EditProducto::handleCreateWooCommerceProduct - Starting product creation");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $fsProductId = $this->getFsProductIdFromRequest();
                if (!$fsProductId) {
                    return;
                }

                $userNick = $this->user->nick ?? 'system';

                $productService = new WooProductService();
                $result = $productService->createProduct($fsProductId, $userNick);

                if ($result['success']) {
                    error_log("EditProducto::handleCreateWooCommerceProduct - Product creation successful");

                    echo json_encode([
                        'success' => true,
                        'product_id' => $result['data']->id ?? null,
                        'product_name' => $result['data']->name ?? '',
                        'messages' => [['message' => $result['message']]]
                    ]);
                } else {
                    error_log("EditProducto::handleCreateWooCommerceProduct - Product creation failed: " . $result['message']);

                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => isset($result['errors']) ? $result['errors'] : [['message' => $result['message']]]
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleCreateWooCommerceProduct - Exception: " . $e->getMessage());

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno del servidor']]
                ]);
            }
        };
    }

    /**
     * Handle WooCommerce product update
     */
    protected function handleUpdateWooCommerceProduct()
    {
        return function () {
            error_log("EditProducto::handleUpdateWooCommerceProduct - Starting product update");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $fsProductId = $this->getFsProductIdFromRequest();
                if (!$fsProductId) {
                    return;
                }

                $formData = $this->getWooCommerceFormData();
                $userNick = $this->user->nick ?? 'system';

                $productService = new WooProductService();
                $result = $productService->updateProduct($fsProductId, $formData, $userNick);

                if ($result['success']) {
                    error_log("EditProducto::handleUpdateWooCommerceProduct - Product update successful");

                    echo json_encode([
                        'success' => true,
                        'messages' => [['message' => $result['message']]]
                    ]);
                } else {
                    error_log("EditProducto::handleUpdateWooCommerceProduct - Product update failed: " . $result['message']);

                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => $result['message']]]
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleUpdateWooCommerceProduct - Exception: " . $e->getMessage());

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno del servidor']]
                ]);
            }
        };
    }

    /**
     * Handle getting WooCommerce categories
     */
    protected function handleGetWooCommerceCategories()
    {
        return function () {
            error_log("EditProducto::handleGetWooCommerceCategories - Getting categories");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $categoryService = new WooCategoryService();
                $categories = $categoryService->getCategoriesForSelect();

                if ($categories !== false) {
                    echo json_encode([
                        'success' => true,
                        'categories' => $categories
                    ]);
                } else {
                    error_log("EditProducto::handleGetWooCommerceCategories - Failed to get categories");

                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Error al obtener categorías de WooCommerce']]
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleGetWooCommerceCategories - Exception: " . $e->getMessage());

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno del servidor']]
                ]);
            }
        };
    }

    /**
     * Handle creating WooCommerce category
     */
    protected function handleCreateWooCommerceCategory()
    {
        return function () {
            error_log("EditProducto::handleCreateWooCommerceCategory - Creating category");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $categoryName = $this->request->request->get('category_name');

                if (empty($categoryName)) {
                    error_log("EditProducto::handleCreateWooCommerceCategory - Category name is empty");

                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Nombre de categoría requerido']]
                    ]);
                    return;
                }

                $categoryService = new WooCategoryService();

                // Validate category data
                $validation = $categoryService->validateCategoryData(['name' => $categoryName]);
                if (!$validation['valid']) {
                    error_log("EditProducto::handleCreateWooCommerceCategory - Category validation failed");

                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => array_map(function ($error) {
                            return ['message' => $error];
                        }, $validation['errors'])
                    ]);
                    return;
                }

                $category = $categoryService->createCategory($categoryName);

                if ($category) {
                    error_log("EditProducto::handleCreateWooCommerceCategory - Category created successfully");

                    echo json_encode([
                        'success' => true,
                        'category' => $category,
                        'messages' => [['message' => 'Categoría creada exitosamente']]
                    ]);
                } else {
                    error_log("EditProducto::handleCreateWooCommerceCategory - Failed to create category");

                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Error al crear la categoría']]
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleCreateWooCommerceCategory - Exception: " . $e->getMessage());

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno del servidor']]
                ]);
            }
        };
    }

    /**
     * Handle syncing product data from WooCommerce
     */
    protected function handleSyncFromWooCommerce()
    {
        return function () {
            error_log("EditProducto::handleSyncFromWooCommerce - Syncing from WooCommerce");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $fsProductId = $this->getFsProductIdFromRequest();
                if (!$fsProductId) {
                    return;
                }

                $productService = new WooProductService();
                $result = $productService->syncFromWooCommerce($fsProductId);

                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'messages' => [['message' => $result['message']]]
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => $result['message']]]
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleSyncFromWooCommerce - Exception: " . $e->getMessage());

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno del servidor']]
                ]);
            }
        };
    }

    /**
     * Handle deleting WooCommerce product
     */
    protected function handleDeleteWooCommerceProduct()
    {
        return function () {
            error_log("EditProducto::handleDeleteWooCommerceProduct - Deleting product");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $fsProductId = $this->getFsProductIdFromRequest();
                if (!$fsProductId) {
                    return;
                }

                $force = $this->request->request->get('force', false);

                $productService = new WooProductService();
                $result = $productService->deleteProduct($fsProductId, $force);

                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'messages' => [['message' => $result['message']]]
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => $result['message']]]
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleDeleteWooCommerceProduct - Exception: " . $e->getMessage());

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno del servidor']]
                ]);
            }
        };
    }

    /**
     * Get FacturaScripts product ID from request
     *
     *
     */
    protected function getFsProductIdFromRequest()
    {
        return function () {
            $fsId = $this->request->request->get('fs_id');

            if (empty($fsId)) {
                error_log("EditProducto::getFsProductIdFromRequest - Missing product ID in request");

                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'ID de producto faltante']]
                ]);
                return null;
            }

            return (int)$fsId;
        };
    }

    /**
     * Extract and validate form data for WooCommerce update
     *
     *
     */
    protected function getWooCommerceFormData()
    {
        return function () {
            error_log("EditProducto::getWooCommerceFormData - Extracting form data");

            $request = $this->request;

            // Get categories array
            $categories = [];
            $categoryIds = $request->request->get('woo_categories', []);
            if (!empty($categoryIds) && is_array($categoryIds)) {
                foreach ($categoryIds as $catId) {
                    if (!empty($catId)) {
                        $categories[] = ['id' => (int)$catId];
                    }
                }
            }

            // Get tags array
            $tags = [];
            $tagNames = $request->request->get('woo_tags', []);
            if (!empty($tagNames) && is_array($tagNames)) {
                foreach ($tagNames as $tagName) {
                    if (!empty(trim($tagName))) {
                        $tags[] = ['name' => trim($tagName)];
                    }
                }
            }

            $formData = [
                'name' => $request->request->get('woo_product_name', ''),
                'description' => $request->request->get('woo_description', ''),
                'short_description' => $request->request->get('woo_short_description', ''),
                'sku' => $request->request->get('woo_sku', ''),
                'regular_price' => $request->request->get('woo_price', ''),
                'sale_price' => $request->request->get('woo_sale_price', ''),
                'manage_stock' => $request->request->get('woo_manage_stock') === 'yes',
                'stock_quantity' => (int)$request->request->get('woo_stock_quantity', 0),
                'status' => $request->request->get('woo_status', 'draft'),
                'catalog_visibility' => $request->request->get('woo_catalog_visibility', 'visible'),
                'tax_status' => $request->request->get('woo_tax_status', 'taxable'),
                'virtual' => $request->request->get('woo_virtual') === 'yes',
                'downloadable' => $request->request->get('woo_downloadable') === 'yes',
                'featured' => $request->request->get('woo_featured') === 'yes',
                'reviews_allowed' => $request->request->get('woo_reviews_allowed') === 'yes',
                'weight' => $request->request->get('woo_weight', ''),
                'length' => $request->request->get('woo_length', ''),
                'width' => $request->request->get('woo_width', ''),
                'height' => $request->request->get('woo_height', ''),
                'categories' => $categories,
                'tags' => $tags
            ];

            error_log("EditProducto::getWooCommerceFormData - Extracted form data for product: " . ($formData['name'] ?? 'Unknown'));

            return $formData;
        };
    }
}
