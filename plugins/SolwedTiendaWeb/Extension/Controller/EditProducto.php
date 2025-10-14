<?php

namespace FacturaScripts\Plugins\SolwedTiendaWeb\Extension\Controller;

use Closure;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\SolwedTiendaWeb\Lib\WooProductService;
use FacturaScripts\Plugins\SolwedTiendaWeb\Lib\WooCategoryService;

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

                case 'save-woo-categories':
                case 'save-wc-categories':
                    $this->handleSaveWooCommerceCategories();
                    return false;

                case 'get-wc-categories':
                    $this->handleGetWooCommerceCategories();
                    return false;

                case 'create-wc-category':
                    $this->handleCreateWooCommerceCategory();
                    return false;

                case 'delete-wc-category':
                    $this->handleDeleteWooCommerceCategory();
                    return false;

                case 'sync-from-wc':
                    $this->handleSyncFromWooCommerce();
                    return false;

                case 'delete-wc-product':
                    $this->handleDeleteWooCommerceProduct();
                    return false;

                case 'delete-product':
                    $this->handleDeleteWooCommerceProduct();
                    return false;

                case 'sync-all-variations':
                    $this->handleSyncAllVariations();
                    return false;

                case 'sync-variation':
                    $this->handleSyncVariation();
                    return false;

                case 'create-variation':
                    $this->handleCreateVariation();
                    return false;

                case 'delete-variation':
                    $this->handleDeleteVariation();
                    return false;

                case 'sync-product-images':
                    $this->handleSyncProductImages();
                    return false;

                case 'sync-familias-to-wc':
                    $this->handleSyncFamiliasToWooCommerce();
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

                // Load variations for the product
                $fsProduct = $this->getModel();
                if ($fsProduct && $fsProduct->idproducto) {
                    $varianteModel = new Variante();
                    $where = [new DataBaseWhere('idproducto', $fsProduct->idproducto)];
                    $variations = $varianteModel->all($where);
                    $view->variations = $variations;

                    // Load familia if product has one assigned
                    if (!empty($fsProduct->codfamilia)) {
                        $familiaModel = new Familia();
                        if ($familiaModel->loadFromCode($fsProduct->codfamilia)) {
                            $view->productFamilia = $familiaModel;
                            error_log("EditProducto::loadData - Loaded familia: {$familiaModel->descripcion} (wc_category_id: " . ($familiaModel->wc_category_id ?? 'null') . ")");
                        }
                    }

                    // Decode WooCommerce categories for easier access in Twig
                    if (!empty($fsProduct->woo_categories)) {
                        $view->wcCategories = json_decode($fsProduct->woo_categories, true);
                    }
                }
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
                $parentId = (int) $this->request->request->get('parent_id', 0);
                $description = $this->request->request->get('description', '');

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

                // Create category with parent and description support
                $category = $categoryService->createCategory($categoryName, $description, null, $parentId);

                if ($category) {
                    error_log("EditProducto::handleCreateWooCommerceCategory - Category created successfully with ID: {$category->id}");

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
     * Handle deleting WooCommerce category
     */
    protected function handleDeleteWooCommerceCategory()
    {
        return function () {
            error_log("EditProducto::handleDeleteWooCommerceCategory - Deleting category");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $categoryId = (int) $this->request->request->get('category_id');
                $force = $this->request->request->get('force', false) === 'true' || $this->request->request->get('force', false) === true;

                if (empty($categoryId)) {
                    error_log("EditProducto::handleDeleteWooCommerceCategory - Category ID is empty");

                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'ID de categoría requerido']]
                    ]);
                    return;
                }

                $categoryService = new WooCategoryService();

                // Delete category
                $result = $categoryService->deleteCategory($categoryId, $force);

                if ($result) {
                    error_log("EditProducto::handleDeleteWooCommerceCategory - Category deleted successfully: {$categoryId}");

                    echo json_encode([
                        'success' => true,
                        'messages' => [['message' => 'Categoría eliminada exitosamente']]
                    ]);
                } else {
                    error_log("EditProducto::handleDeleteWooCommerceCategory - Failed to delete category: {$categoryId}");

                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'messages' => [['message' => 'Error al eliminar la categoría']]
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleDeleteWooCommerceCategory - Exception: " . $e->getMessage());

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'messages' => [['message' => 'Error interno del servidor: ' . $e->getMessage()]]
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

                $force = $this->request->request->get('force', true);

                $productService = new WooProductService();
                $result = $productService->deleteProduct((int)$fsProductId, $force);

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

            // Get tags array
            // $tags = [];
            // $tagNames = $request->request->get('woo_tags', []);
            // if (!empty($tagNames) && is_array($tagNames)) {
            //     foreach ($tagNames as $tagName) {
            //         if (!empty(trim($tagName))) {
            //             $tags[] = ['name' => trim($tagName)];
            //         }
            //     }
            // }

            $formData = [
                'name' => $request->request->get('woo_product_name', ''),
                'description' => $request->request->get('woo_description', ''),
                'short_description' => $request->request->get('woo_short_description', ''),
                'sku' => $request->request->get('woo_sku', ''),
                'regular_price' => $request->request->get('woo_price', ''),
                'sale_price' => $request->request->get('woo_sale_price', ''),
                'manage_stock' => $request->request->get('woo_manage_stock') === 'yes',
                'stock_quantity' => (int)$request->request->get('woo_stock_quantity', null),
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
                //'categories' => $categories,
                //'tags' => $tags
            ];

            error_log("EditProducto::getWooCommerceFormData - Extracted form data for product: " . ($formData['name'] ?? 'Unknown'));

            return $formData;
        };
    }




    /**
     * Handle syncing all variations
     */
    protected function handleSyncAllVariations()
    {
        return function () {
            error_log("EditProducto::handleSyncAllVariations - Syncing all variations");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $fsProductId = $this->getFsProductIdFromRequest();
                if (!$fsProductId) {
                    return;
                }

                $productService = new WooProductService();
                $result = $productService->syncAllVariations((int)$fsProductId);

                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleSyncAllVariations - Exception: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor'
                ]);
            }
        };
    }

    /**
     * Handle syncing a single variation
     */
    protected function handleSyncVariation()
    {
        return function () {
            error_log("EditProducto::handleSyncVariation - Syncing variation");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $variantId = $this->request->request->get('variant_id');
                if (empty($variantId)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID de variante faltante'
                    ]);
                    return;
                }

                $productService = new WooProductService();
                $result = $productService->syncVariation((int)$variantId);

                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleSyncVariation - Exception: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor'
                ]);
            }
        };
    }

    /**
     * Handle creating a single variation
     */
    protected function handleCreateVariation()
    {
        return function () {
            error_log("EditProducto::handleCreateVariation - Creating variation");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                //$fsProductId = $this->getFsProductIdFromRequest();
                $variantId = $this->request->request->get('variant_id');

                if (empty($variantId)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID de variante faltante'
                    ]);
                    return;
                }

                // Load the variant
                $variant = new Variante();
                if (!$variant->loadFromCode($variantId)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Variante no encontrada'
                    ]);
                    return;
                }

                // Load the product to get WooCommerce ID
                $fsProduct = new Producto();
                if (!$fsProduct->loadFromCode($variant->idproducto)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Producto no encontrado'
                    ]);
                    return;
                }

                // Check if product is synced
                if (empty($fsProduct->woo_id)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'El producto principal no está sincronizado con WooCommerce'
                    ]);
                    return;
                }

                $productService = new WooProductService();
                $result = $productService->createVariation((int)$fsProduct->woo_id, $variant, $this->user->nick ?? 'system');

                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleCreateVariation - Exception: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor'
                ]);
            }
        };
    }

    /**
     * Handle deleting a single variation
     */
    protected function handleDeleteVariation()
    {
        return function () {
            error_log("EditProducto::handleDeleteVariation - Deleting variation");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $variantId = $this->request->request->get('variant_id');
                $wooProductId = $this->request->request->get('woo_product_id');
                $wooVariationId = $this->request->request->get('woo_variation_id');

                if (empty($wooProductId) || empty($wooVariationId) || empty($variantId)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Faltan los IDs de producto o variación de WooCommerce'
                    ]);
                    return;
                }

                $productService = new WooProductService();
                $result = $productService->deleteVariation((int)$variantId, (int)$wooProductId, (int)$wooVariationId, true);

                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleDeleteVariation - Exception: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor'
                ]);
            }
        };
    }

    /**
     * Handle syncing product images from FacturaScripts to WooCommerce
     */
    protected function handleSyncProductImages()
    {
        return function () {
            error_log("EditProducto::handleSyncProductImages - Syncing product images");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $fsProductId = $this->getFsProductIdFromRequest();
                if (!$fsProductId) {
                    return;
                }

                $productService = new WooProductService();
                $result = $productService->syncProductImages((int)$fsProductId);

                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message'],
                        'data' => $result['data'] ?? null
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleSyncProductImages - Exception: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor'
                ]);
            }
        };
    }
    /**
     * Handle saving WooCommerce categories directly via PUT request
     */
    protected function handleSaveWooCommerceCategories()
    {
        return function () {
            error_log("=== START handleSaveWooCommerceCategories ===");
            error_log("EditProducto::handleSaveWooCommerceCategories - Saving categories directly to WooCommerce");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                // Log all request data
                error_log("Request method: " . $this->request->getMethod());
                error_log("Request data: " . print_r($this->request->request->all(), true));

                // Get product ID from request
                $fsId = $this->request->request->get('fs_id');
                error_log("Received fs_id: " . var_export($fsId, true));

                if (empty($fsId)) {
                    error_log("ERROR: Missing product ID in request");
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID de producto faltante'
                    ]);
                    return;
                }
                $fsProductId = (int)$fsId;
                error_log("Parsed fsProductId: " . $fsProductId);

                // Get categories from request (sent as categories[] from FormData)
                $categoryIds = $this->request->request->get('categories', []);
                error_log("Received categories (raw): " . print_r($categoryIds, true));
                error_log("Categories type: " . gettype($categoryIds));
                error_log("Categories count: " . (is_array($categoryIds) ? count($categoryIds) : 'not an array'));

                if (!is_array($categoryIds)) {
                    error_log("ERROR: Categories is not an array");
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Formato de categorías inválido'
                    ]);
                    return;
                }

                // Convert to integers
                $categoryIds = array_map('intval', $categoryIds);
                error_log("Categories after intval conversion: " . print_r($categoryIds, true));

                // Load FacturaScripts product
                error_log("Loading FacturaScripts product with ID: " . $fsProductId);
                $productService = new WooProductService();
                $fsProduct = new Producto();

                if (!$fsProduct->loadFromCode($fsProductId)) {
                    error_log("ERROR: FacturaScripts product not found with ID: " . $fsProductId);
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Producto de FacturaScripts no encontrado.']);
                    return;
                }

                error_log("FacturaScripts product loaded successfully");
                error_log("Product name: " . $fsProduct->descripcion);
                error_log("Product woo_id: " . var_export($fsProduct->woo_id, true));

                if (empty($fsProduct->woo_id)) {
                    error_log("ERROR: Product does not have a WooCommerce ID");
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Producto de WooCommerce no encontrado para el ID de FacturaScripts proporcionado.']);
                    return;
                }

                error_log("Calling WooProductService::updateProductCategories with woo_id: " . $fsProduct->woo_id . " and categories: " . print_r($categoryIds, true));

                $result = $productService->updateProductCategories($fsProduct->woo_id, $categoryIds);

                error_log("WooProductService::updateProductCategories returned: " . var_export($result, true));
                error_log("Result type: " . gettype($result));

                if ($result) {
                    error_log("SUCCESS: Categories updated successfully");
                    echo json_encode(['success' => true, 'message' => 'Categorías actualizadas exitosamente en WooCommerce.']);
                } else {
                    error_log("ERROR: updateProductCategories returned false");
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar las categorías en WooCommerce.']);
                }
            } catch (\Exception $e) {
                error_log("EXCEPTION in handleSaveWooCommerceCategories: " . $e->getMessage());
                error_log("Exception trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor: ' . $e->getMessage()
                ]);
            }

            error_log("=== END handleSaveWooCommerceCategories ===");
        };
    }

    /**
     * Handle syncing all Familias from FacturaScripts to WooCommerce categories
     */
    protected function handleSyncFamiliasToWooCommerce()
    {
        return function () {
            error_log("EditProducto::handleSyncFamiliasToWooCommerce - Starting Familias sync to WooCommerce");

            $this->setTemplate(false);
            header('Content-Type: application/json');

            try {
                $categoryService = new WooCategoryService();
                $result = $categoryService->syncFamiliasToWooCommerce();

                if ($result['success']) {
                    error_log("EditProducto::handleSyncFamiliasToWooCommerce - Sync successful: " . $result['message']);

                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'synced' => $result['synced'],
                        'errors' => $result['errors']
                    ]);
                } else {
                    error_log("EditProducto::handleSyncFamiliasToWooCommerce - Sync failed: " . $result['message']);

                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => $result['message'],
                        'errors' => $result['errors']
                    ]);
                }
            } catch (\Exception $e) {
                error_log("EditProducto::handleSyncFamiliasToWooCommerce - Exception: " . $e->getMessage());

                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor: ' . $e->getMessage()
                ]);
            }
        };
    }
}
