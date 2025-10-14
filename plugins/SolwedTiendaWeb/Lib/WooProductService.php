<?php

namespace FacturaScripts\Plugins\SolwedTiendaWeb\Lib;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Handles WooCommerce product operations
 */
class WooProductService
{
    private $wooClient;

    public function __construct()
    {
        error_log("WooProductService::__construct - Initializing product service");
        try {
            $this->wooClient = WooHelper::getClient();
        } catch (\Exception $e) {
            error_log("WooProductService::__construct - Error initializing WooCommerce client: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new WooCommerce variable product from FacturaScripts product
     *
     * @param int $fsProductId FacturaScripts product ID
     * @param string $userNick User who initiated the creation
     * @return array Result array with success status and data
     */
    public function createProduct(int $fsProductId, string $userNick = 'system'): array
    {
        error_log("WooProductService::createProduct - Starting variable product creation for FS ID: {$fsProductId}");

        try {
            // Load FacturaScripts product
            $fsProduct = new Producto();
            if (!$fsProduct->loadFromCode($fsProductId)) {
                error_log("WooProductService::createProduct - FS product not found: {$fsProductId}");
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado en FacturaScripts',
                    'data' => null
                ];
            }

            // Check if already synced
            if (!empty($fsProduct->woo_id)) {
                error_log("WooProductService::createProduct - Product {$fsProductId} already synced with WooCommerce ID: {$fsProduct->woo_id}");
                return [
                    'success' => false,
                    'message' => "Este producto ya está sincronizado con WooCommerce (ID: {$fsProduct->woo_id})",
                    'data' => null
                ];
            }

            // Validate product data
            // $validation = $this->validateProductForWooCommerce($fsProduct);
            // if (!$validation['valid']) {
            //     error_log("WooProductService::createProduct - Product validation failed for ID: {$fsProductId}");
            //     return [
            //         'success' => false,
            //         'message' => 'El producto no es válido para sincronizar',
            //         'errors' => $validation['errors']
            //     ];
            // }

            // Load variants
            $varianteModel = new Variante();
            $where = [new DataBaseWhere('idproducto', $fsProduct->idproducto)];
            $variants = $varianteModel->all($where);

            // Determine product type based on variants (simple/variable)
            $productType = $this->determineProductType($variants);
            error_log("WooProductService::createProduct - Determined product type: {$productType}");

            if ($productType === 'simple') {
                return $this->createSimpleProduct($fsProduct, $userNick);
            } else {
                return $this->createVariableProduct($fsProduct, $variants, $userNick);
            }
        } catch (\Exception $e) {
            error_log("WooProductService::createProduct - Exception during product creation: " . $e->getMessage());
            error_log("WooProductService::createProduct - Exception trace: " . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Update a WooCommerce product with form data
     *
     * @param int $fsProductId FacturaScripts product ID
     * @param array $formData Form data from user input
     * @param string $userNick User who initiated the update
     * @return array Result array with success status and data
     */
    public function updateProduct(int $fsProductId, array $formData, string $userNick = 'system'): array
    {
        error_log("WooProductService::updateProduct - Starting product update for FS ID: {$fsProductId}");

        try {
            // Load FacturaScripts product
            $fsProduct = new Producto();
            if (!$fsProduct->loadFromCode($fsProductId)) {
                error_log("WooProductService::updateProduct - FS product not found: {$fsProductId}");
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado en FacturaScripts',
                    'data' => null
                ];
            }

            // Check if product is synced
            if (empty($fsProduct->woo_id)) {
                error_log("WooProductService::updateProduct - Product {$fsProductId} is not synced with WooCommerce");
                return [
                    'success' => false,
                    'message' => 'Este producto no está sincronizado con WooCommerce',
                    'data' => null
                ];
            }

            // Load variants to determine product type
            $varianteModel = new Variante();
            $where = [new DataBaseWhere('idproducto', $fsProduct->idproducto)];
            $variants = $varianteModel->all($where);

            // Determine product type based on variants
            $productType = $this->determineProductType($variants);
            error_log("WooProductService::updateProduct - Determined product type for update: {$productType}");

            // Delegate to the appropriate update method
            if ($productType === 'simple') {
                return $this->updateSimpleProduct($fsProduct, $formData, $userNick);
            } else {
                return $this->updateVariableProduct($fsProduct, $variants, $formData, $userNick);
            }
        } catch (\Exception $e) {
            error_log("WooProductService::updateProduct - Exception during product update: " . $e->getMessage());
            error_log("WooProductService::updateProduct - Exception trace: " . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function updateProductCategories(int $wooProductId, array $categoryIds): bool
    {
        error_log("=== START WooProductService::updateProductCategories ===");
        error_log("WooProductService::updateProductCategories - Updating categories for WooCommerce product ID: {$wooProductId}");
        error_log("WooProductService::updateProductCategories - Category IDs received: " . print_r($categoryIds, true));
        error_log("WooProductService::updateProductCategories - Category IDs count: " . count($categoryIds));

        try {
            // Build categories array for WooCommerce API
            $categoriesData = array_map(function ($id) {
                return ['id' => $id];
            }, $categoryIds);

            error_log("WooProductService::updateProductCategories - Categories data formatted: " . print_r($categoriesData, true));

            $data = [
                'categories' => $categoriesData
            ];

            error_log("WooProductService::updateProductCategories - Full data payload: " . json_encode($data));
            error_log("WooProductService::updateProductCategories - Calling wooClient->put with endpoint: products/{$wooProductId}");

            $result = $this->wooClient->put("products/{$wooProductId}", $data);

            error_log("WooProductService::updateProductCategories - API response received: " . print_r($result, true));
            error_log("WooProductService::updateProductCategories - Response type: " . gettype($result));

            if (is_object($result)) {
                error_log("WooProductService::updateProductCategories - Response has ID: " . (isset($result->id) ? 'YES (' . $result->id . ')' : 'NO'));
                if (isset($result->categories)) {
                    error_log("WooProductService::updateProductCategories - Response categories: " . print_r($result->categories, true));
                }
            }

            if (isset($result->id)) {
                error_log("WooProductService::updateProductCategories - SUCCESS: Categories updated for product ID: {$wooProductId}");

                // Find and update the FacturaScripts product
                $fsProduct = new Producto();
                $where = [new DataBaseWhere('woo_id', $result->id)];
                if ($fsProduct->loadFromCode('', $where)) {
                    if (isset($result->categories)) {
                        $fsProduct->woo_categories = json_encode($result->categories);
                        if (!$fsProduct->save()) {
                            error_log("WooProductService::updateProductCategories - FAILED to save woo_categories to FS product ID: {$fsProduct->idproducto}");
                        } else {
                            error_log("WooProductService::updateProductCategories - SUCCESS: Saved categories to FS product ID: {$fsProduct->idproducto}");
                        }
                    }
                } else {
                    error_log("WooProductService::updateProductCategories - FAILED to find FS product with woo_id: {$wooProductId}");
                }
                error_log("=== END WooProductService::updateProductCategories (SUCCESS) ===");
                return true;
            }

            error_log("WooProductService::updateProductCategories - FAILED: No ID in response for product ID: {$wooProductId}");
            error_log("=== END WooProductService::updateProductCategories (FAILED) ===");
            return false;
        } catch (\Exception $e) {
            error_log("WooProductService::updateProductCategories - EXCEPTION: " . $e->getMessage());
            error_log("WooProductService::updateProductCategories - Exception class: " . get_class($e));
            error_log("WooProductService::updateProductCategories - Exception trace: " . $e->getTraceAsString());
            error_log("=== END WooProductService::updateProductCategories (EXCEPTION) ===");
            return false;
        }
    }

    /**
     * Get a WooCommerce product by ID
     *
     * @param int $wooProductId WooCommerce product ID
     * @return object|false
     */
    public function getProduct(int $wooProductId)
    {
        error_log("WooProductService::getProduct - Fetching WooCommerce product ID: {$wooProductId}");

        try {
            $product = $this->wooClient->get("products/{$wooProductId}");

            if (!$product) {
                error_log("WooProductService::getProduct - WooCommerce product {$wooProductId} not found");
                return false;
            }

            error_log("WooProductService::getProduct - Successfully fetched WooCommerce product: {$product->name}");

            return $product;
        } catch (\Exception $e) {
            error_log("WooProductService::getProduct - Error fetching WooCommerce product {$wooProductId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a product (detects type and calls appropriate method)
     *
     * @param int $fsProductId FacturaScripts product ID
     * @param bool $force Force delete (bypass trash)
     * @return array Result array
     */
    public function deleteProduct(int $fsProductId, bool $force = true): array
    {
        error_log("WooProductService::deleteProduct - Starting product deletion for FS ID: {$fsProductId}");

        try {
            // Load FacturaScripts product
            $fsProduct = new Producto();
            if (!$fsProduct->loadFromCode($fsProductId)) {
                error_log("WooProductService::deleteProduct - FS product not found: {$fsProductId}");
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado en FacturaScripts',
                    'data' => null
                ];
            }

            // Check if product is synced
            if (empty($fsProduct->woo_id)) {
                error_log("WooProductService::deleteProduct - Product {$fsProductId} is not synced with WooCommerce");
                return [
                    'success' => false,
                    'message' => 'Este producto no está sincronizado con WooCommerce',
                    'data' => null
                ];
            }

            // Load variants to determine product type
            $varianteModel = new Variante();
            $where = [new DataBaseWhere('idproducto', $fsProduct->idproducto)];
            $variants = $varianteModel->all($where);

            // Determine product type
            $productType = $this->determineProductType($variants);
            error_log("WooProductService::deleteProduct - Determined product type: {$productType}");

            // Call appropriate deletion method
            if ($productType === 'simple') {
                return $this->deleteSimpleProduct($fsProduct, $force);
            } else {
                return $this->deleteVariableProduct($fsProduct, $variants, $force);
            }
        } catch (\Exception $e) {
            error_log("WooProductService::deleteProduct - Exception during product deletion: " . $e->getMessage());
            error_log("WooProductService::deleteProduct - Exception trace: " . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Delete a simple product from WooCommerce
     *
     * @param Producto $fsProduct FacturaScripts product
     * @param bool $force Force delete (bypass trash)
     * @return array Result array
     */
    private function deleteSimpleProduct(Producto $fsProduct, bool $force = true): array
    {
        error_log("WooProductService::deleteSimpleProduct - Deleting simple product with WooCommerce ID: {$fsProduct->woo_id}");

        try {
            // Delete from WooCommerce
            $params = $force ? ['force' => true] : [];
            $result = $this->wooClient->delete("products/{$fsProduct->woo_id}", $params);

            if (!$result) {
                error_log("WooProductService::deleteSimpleProduct - Error deleting WooCommerce product {$fsProduct->woo_id}");
                return [
                    'success' => false,
                    'message' => 'Error al eliminar producto simple de WooCommerce',
                    'data' => null
                ];
            }

            error_log("WooProductService::deleteSimpleProduct - Successfully deleted WooCommerce product {$fsProduct->woo_id}");

            // Clear WooCommerce data from FS product
            $this->clearWooCommerceData($fsProduct);

            if (!$fsProduct->save()) {
                error_log("WooProductService::deleteSimpleProduct - Error clearing WooCommerce data from FS product {$fsProduct->idproducto}");
            }

            return [
                'success' => true,
                'message' => 'Producto simple eliminado exitosamente de WooCommerce',
                'data' => $result
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::deleteSimpleProduct - Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Delete a variable product and all its variations from WooCommerce
     *
     * @param Producto $fsProduct FacturaScripts product
     * @param array $variants Array of Variante objects
     * @param bool $force Force delete (bypass trash)
     * @return array Result array
     */
    private function deleteVariableProduct(Producto $fsProduct, array $variants, bool $force = true): array
    {
        error_log("WooProductService::deleteVariableProduct - Starting deletion for variable product with WooCommerce ID: {$fsProduct->woo_id}");

        try {
            $deletedVariations = 0;
            $failedVariations = 0;

            // Delete all variations first
            foreach ($variants as $variant) {
                if (!empty($variant->woo_variation_id)) {
                    $result = $this->deleteVariation(
                        $variant->idvariante,
                        $fsProduct->woo_id,
                        $variant->woo_variation_id,
                        $force
                    );

                    if ($result['success']) {
                        $deletedVariations++;
                        error_log("WooProductService::deleteVariableProduct - Deleted variation: {$variant->woo_variation_id}");
                    } else {
                        $failedVariations++;
                        error_log("WooProductService::deleteVariableProduct - Failed to delete variation: {$variant->woo_variation_id}");
                    }
                }
            }

            error_log("WooProductService::deleteVariableProduct - Deleted {$deletedVariations} variations, {$failedVariations} failed");

            // Now delete the parent product
            $params = $force ? ['force' => true] : [];
            $result = $this->wooClient->delete("products/{$fsProduct->woo_id}", $params);

            if (!$result) {
                error_log("WooProductService::deleteVariableProduct - Error deleting parent product {$fsProduct->woo_id}");
                return [
                    'success' => false,
                    'message' => "Eliminadas {$deletedVariations} variaciones pero error al eliminar producto padre",
                    'data' => [
                        'variations_deleted' => $deletedVariations,
                        'variations_failed' => $failedVariations
                    ]
                ];
            }

            error_log("WooProductService::deleteVariableProduct - Successfully deleted parent product {$fsProduct->woo_id}");

            // Clear WooCommerce data from FS product
            $this->clearWooCommerceData($fsProduct);

            if (!$fsProduct->save()) {
                error_log("WooProductService::deleteVariableProduct - Error saving FS product {$fsProduct->idproducto} after clearing WooCommerce data");
            }

            return [
                'success' => true,
                'message' => "Producto variable y {$deletedVariations} variaciones eliminados exitosamente de WooCommerce",
                'data' => [
                    'variations_deleted' => $deletedVariations,
                    'variations_failed' => $failedVariations,
                    'parent_deleted' => true
                ]
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::deleteVariableProduct - Exception: " . $e->getMessage());
            error_log("WooProductService::deleteVariableProduct - Exception trace: " . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Delete a WooCommerce product By Woocommerce Product ID
     *
     * @param int $wooProductId WooCommerce product ID
     * @param bool $force Force delete (bypass trash)
     * @return array Result array
     */
    public function deleteWooProductById(int $wooProductId, bool $force = false): array
    {
        error_log("WooProductService::deleteWooProductById - Starting WooCommerce product deletion for Woo ID: {$wooProductId}");

        try {
            // Check if WooCommerce product exists first (optional validation)
            $wooProduct = $this->wooClient->get("products/{$wooProductId}");

            if (!$wooProduct || (isset($wooProduct->id) && $wooProduct->id != $wooProductId)) {
                error_log("WooProductService::deleteWooProductById - WooCommerce product not found: {$wooProductId}");
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado en WooCommerce',
                    'data' => null
                ];
            }

            error_log("WooProductService::deleteWooProductById - Deleting WooCommerce product ID: {$wooProductId}");

            // Delete from WooCommerce
            // Use true to permanently delete the product, Default is false (stays in trash).
            $params = $force ? ['force' => true] : [];
            $result = $this->wooClient->delete("products/{$wooProductId}", $params);

            if (!$result) {
                error_log("WooProductService::deleteWooProductById - Error deleting WooCommerce product {$wooProductId}");
                return [
                    'success' => false,
                    'message' => 'Error al eliminar producto de WooCommerce',
                    'data' => null
                ];
            }

            error_log("WooProductService::deleteWooProductById - Successfully deleted WooCommerce product {$wooProductId}");

            return [
                'success' => true,
                'message' => 'Producto eliminado exitosamente de WooCommerce',
                'data' => $result
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::deleteWooProductById - Exception during product deletion: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get product images from FacturaScripts
     *
     * @param int $fsProductId FacturaScripts product ID
     * @return array Array of image data for WooCommerce
     */
    private function getProductImages(int $fsProductId): array
    {
        error_log("WooProductService::getProductImages - Getting images for FS product ID: {$fsProductId}");

        try {
            $images = [];

            $where = [
                new DataBaseWhere('idproducto', $fsProductId),
                new DataBaseWhere('referencia', null, 'IS')
            ];

            // Order by 'orden' field to get images in the correct order
            $orderBy = ['orden' => 'ASC'];
            $productImages = (new ProductoImagen())->all($where, $orderBy);

            foreach ($productImages as $img) {
                $siteUrl = Tools::siteUrl();
                $imageUrl = $siteUrl . '/' . $img->url('download-permanent');
                $images[] = [
                    'src' => $imageUrl,
                    'alt' => $img->observaciones ?? ''
                ];

                error_log("WooProductService::getProductImages - Added image (order: {$img->orden}): {$imageUrl}");
            }

            error_log("WooProductService::getProductImages - Found " . count($images) . " images for product {$fsProductId}");

            return $images;
        } catch (\Exception $e) {
            error_log("WooProductService::getProductImages - Error getting images for product {$fsProductId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get variant images from FacturaScripts
     *
     * @param int $fsProductId FacturaScripts product ID
     * @param string $variantReference Variant reference code
     * @return array Array of image data for WooCommerce
     */
    private function getVariantImages(int $fsProductId, string $variantReference): array
    {
        error_log("WooProductService::getVariantImages - Getting images for variant: {$variantReference} (Product ID: {$fsProductId})");

        try {
            $images = [];

            $where = [
                new DataBaseWhere('idproducto', $fsProductId),
                new DataBaseWhere('referencia', $variantReference)
            ];

            // Order by 'orden' field to get images in the correct order
            $orderBy = ['orden' => 'ASC'];
            $variantImages = (new ProductoImagen())->all($where, $orderBy);

            foreach ($variantImages as $img) {
                $siteUrl = Tools::siteUrl();
                $imageUrl = $siteUrl . '/' . $img->url('download-permanent');
                $images[] = [
                    'src' => $imageUrl,
                    'alt' => $img->observaciones ?? ''
                ];

                error_log("WooProductService::getVariantImages - Added image (order: {$img->orden}): {$imageUrl}");
            }

            error_log("WooProductService::getVariantImages - Found " . count($images) . " images for variant {$variantReference}");

            return $images;
        } catch (\Exception $e) {
            error_log("WooProductService::getVariantImages - Error getting images for variant {$variantReference}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate FacturaScripts product before creating in WooCommerce
     *
     * @param Producto $fsProduct
     * @return array Validation result
     */
    // private function validateProductForWooCommerce(Producto $fsProduct): array
    // {
    //     error_log("WooProductService::validateProductForWooCommerce - Validating FS product ID: {$fsProduct->idproducto}");

    //     $errors = [];

    //     if (empty($fsProduct->descripcion)) {
    //         $errors[] = ['message' => 'El producto debe tener una descripción'];
    //     }

    //     if (empty($fsProduct->referencia)) {
    //         $errors[] = ['message' => 'El producto debe tener una referencia'];
    //     }

    //     //        if (!isset($fsProduct->precio) || $fsProduct->precio < 0) {
    //     //            $errors[] = ['message' => 'El producto debe tener un precio válido'];
    //     //        }

    //     $isValid = empty($errors);

    //     if ($isValid) {
    //         error_log("WooProductService::validateProductForWooCommerce - Product validation passed");
    //     } else {
    //         error_log("WooProductService::validateProductForWooCommerce - Product validation failed: " . json_encode($errors));
    //     }

    //     return [
    //         'valid' => $isValid,
    //         'errors' => $errors
    //     ];
    // }

    /**
     * Clear WooCommerce data from FacturaScripts product
     *
     * @param Producto $fsProduct
     */
    private function clearWooCommerceData(Producto $fsProduct): void
    {
        error_log("WooProductService::clearWooCommerceData - Clearing WooCommerce data for FS product ID: {$fsProduct->idproducto}");

        $fsProduct->woo_id = null;
        $fsProduct->woo_product_name = null;
        $fsProduct->woo_price = null;
        $fsProduct->woo_permalink = null;
        $fsProduct->woo_sku = null;
        $fsProduct->woo_status = null;
        $fsProduct->woo_catalog_visibility = null;
        $fsProduct->woo_categories = null;
        $fsProduct->woo_images = null;
        $fsProduct->woo_nick = null;
        $fsProduct->woo_creation_date = null;
        $fsProduct->woo_last_nick = null;
        $fsProduct->woo_last_update = null;
        $fsProduct->woo_sale_price = null;
        $fsProduct->woo_manage_stock = null;
        $fsProduct->woo_stock_quantity = null;
        $fsProduct->woo_stock_status = null;
        $fsProduct->woo_weight = null;
        $fsProduct->woo_description = null;
        $fsProduct->woo_short_description = null;
        $fsProduct->woo_featured = null;
        $fsProduct->woo_virtual = null;
        $fsProduct->woo_downloadable = null;
        $fsProduct->woo_reviews_allowed = null;
        $fsProduct->woo_tax_status = null;
        $fsProduct->woo_dimensions = null;
        $fsProduct->woo_has_variations = null;
        $fsProduct->woo_product_type = null;

        error_log("WooProductService::clearWooCommerceData - Successfully cleared WooCommerce data");
    }

    /**
     * Sync product data from WooCommerce to FacturaScripts
     *
     * @param int $fsProductId FacturaScripts product ID
     * @return array Result array
     */
    public function syncFromWooCommerce(int $fsProductId): array
    {
        error_log("WooProductService::syncFromWooCommerce - Syncing data from WooCommerce for FS ID: {$fsProductId}");

        try {
            // Load FacturaScripts product
            $fsProduct = new Producto();
            if (!$fsProduct->loadFromCode($fsProductId)) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado en FacturaScripts',
                    'data' => null
                ];
            }

            // Check if product is synced
            if (empty($fsProduct->woo_id)) {
                return [
                    'success' => false,
                    'message' => 'Este producto no está sincronizado con WooCommerce',
                    'data' => null
                ];
            }

            // Get fresh data from WooCommerce
            $wooProduct = $this->getProduct($fsProduct->woo_id);
            if (!$wooProduct) {
                return [
                    'success' => false,
                    'message' => 'Error al obtener datos del producto desde WooCommerce',
                    'data' => null
                ];
            }

            // Update FS product with fresh WooCommerce data
            WooDataMapper::updateFsWithWooData($fsProduct, $wooProduct);

            if (!$fsProduct->save()) {
                return [
                    'success' => false,
                    'message' => 'Error al guardar datos actualizados en FacturaScripts',
                    'data' => null
                ];
            }

            error_log("WooProductService::syncFromWooCommerce - Successfully synced data from WooCommerce");

            return [
                'success' => true,
                'message' => 'Datos sincronizados exitosamente desde WooCommerce',
                'data' => $wooProduct
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::syncFromWooCommerce - Exception during sync: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }



    /**
     * Check if a product should be a variable product in WooCommerce
     */
    // private function isVariableProduct(Producto $fsProduct): bool
    // {
    //     $varianteModel = new Variante();
    //     $where = [new DataBaseWhere('idproducto', $fsProduct->idproducto)];
    //     $variants = $varianteModel->all($where);

    //     // If we have more than one variant, it's a variable product
    //     return count($variants) > 1;
    // }

    /**
     * Determine if product should be simple or variable
     */
    private function determineProductType(array $variants): string
    {
        // No variants = simple product
        if (empty($variants)) {
            return 'simple';
        }

        // Only one variant = simple product
        if (count($variants) === 1) {
            return 'simple';
        }

        // Multiple variants but no attributes = simple product
        $hasValidAttributes = false;
        foreach ($variants as $variant) {
            $attributes = WooDataMapper::extractVariantAttributes($variant);
            if (!empty($attributes)) {
                $hasValidAttributes = true;
                break;
            }
        }

        return $hasValidAttributes ? 'variable' : 'simple';
    }

    /**
     * Create a simple product in WooCommerce
     */
    private function createSimpleProduct(Producto $fsProduct, string $userNick): array
    {
        error_log("WooProductService::createSimpleProduct - Creating simple product for FS ID: {$fsProduct->idproducto}");

        try {
            // Create the main simple product
            $wooData = WooDataMapper::fsToWooCommerceSimple($fsProduct);

            // Automatically set category from Familia
            if (!empty($fsProduct->codfamilia)) {
                error_log("WooProductService::createSimpleProduct - Product has codfamilia: " . $fsProduct->codfamilia);
                $familia = new Familia();
                $loaded = $familia->loadFromCode($fsProduct->codfamilia);
                if ($loaded && !empty($familia->wc_category_id)) {
                    error_log("WooProductService::createSimpleProduct - Familia loaded successfully and has wc_category_id: " . $familia->wc_category_id);
                    $wooData['categories'] = [['id' => $familia->wc_category_id]];
                    error_log("WooProductService::createSimpleProduct - Assigning category from Familia: " . $familia->descripcion . " (ID: " . $familia->wc_category_id . ")");
                } else {
                    error_log("WooProductService::createSimpleProduct - Condition failed. Familia loaded: " . ($loaded ? 'true' : 'false') . ". Has wc_category_id: " . (!empty($familia->wc_category_id) ? 'true (' . $familia->wc_category_id . ')' : 'false'));
                }
            } else {
                error_log("WooProductService::createSimpleProduct - Product does not have a codfamilia. Skipping category assignment.");
            }

            // Add product images
            $images = $this->getProductImages($fsProduct->idproducto);
            if (!empty($images)) {
                $wooData['images'] = $images;
                error_log("WooProductService::createSimpleProduct - Added " . count($images) . " images to product data");
            }

            // Create product in WooCommerce
            $wooProduct = $this->wooClient->post('products', $wooData);

            if (!$wooProduct || !isset($wooProduct->id)) {
                error_log("WooProductService::createSimpleProduct - Invalid response from WooCommerce API");
                return [
                    'success' => false,
                    'message' => 'Error al crear producto simple en WooCommerce - Respuesta inválida de API',
                    'data' => null
                ];
            }

            error_log("WooProductService::createSimpleProduct - Successfully created simple product with ID: {$wooProduct->id}");

            // Update FS product with WooCommerce data
            WooDataMapper::updateFsWithWooData($fsProduct, $wooProduct, $userNick);

            if (!$fsProduct->save()) {
                error_log("WooProductService::createSimpleProduct - Error saving FS product {$fsProduct->idproducto} after WooCommerce creation");
                return [
                    'success' => false,
                    'message' => 'Producto simple creado en WooCommerce pero error al actualizar FacturaScripts',
                    'data' => $wooProduct
                ];
            }

            return [
                'success' => true,
                'message' => "Producto simple creado exitosamente en WooCommerce con ID: {$wooProduct->id}",
                'data' => $wooProduct
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::createSimpleProduct - Exception: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create a variable product in WooCommerce
     */
    private function createVariableProduct(Producto $fsProduct, array $variants, string $userNick): array
    {
        error_log("WooProductService::createVariableProduct - Creating variable product for FS ID: {$fsProduct->idproducto}");
        error_log("Number of variants: " . count($variants));

        // Final validation for variable products
        if (empty($variants)) {
            error_log("WooProductService::createVariableProduct - No variants found for variable product");
            return [
                'success' => false,
                'message' => 'Un producto variable debe tener variantes',
                'data' => null
            ];
        }

        // Validate variants have attributes
        $hasValidAttributes = false;
        foreach ($variants as $variant) {
            $attributes = WooDataMapper::extractVariantAttributes($variant);
            if (!empty($attributes)) {
                $hasValidAttributes = true;
                break;
            }
        }

        if (!$hasValidAttributes) {
            error_log("WooProductService::createVariableProduct - No valid attributes found in variants");
            return [
                'success' => false,
                'message' => 'Las variantes deben tener atributos definidos para crear un producto variable',
                'data' => null
            ];
        }

        try {
            // Create the main variable product
            $wooData = WooDataMapper::fsToWooCommerceVariable($fsProduct, $variants);

            // Automatically set category from Familia
            if (!empty($fsProduct->codfamilia)) {
                error_log("WooProductService::createVariableProduct - Product has codfamilia: " . $fsProduct->codfamilia);
                $familia = new Familia();
                $loaded = $familia->loadFromCode($fsProduct->codfamilia);
                if ($loaded && !empty($familia->wc_category_id)) {
                    error_log("WooProductService::createVariableProduct - Familia loaded successfully and has wc_category_id: " . $familia->wc_category_id);
                    $wooData['categories'] = [['id' => $familia->wc_category_id]];
                    error_log("WooProductService::createVariableProduct - Assigning category from Familia: " . $familia->descripcion . " (ID: " . $familia->wc_category_id . ")");
                } else {
                    error_log("WooProductService::createVariableProduct - Condition failed. Familia loaded: " . ($loaded ? 'true' : 'false') . ". Has wc_category_id: " . (!empty($familia->wc_category_id) ? 'true (' . $familia->wc_category_id . ')' : 'false'));
                }
            } else {
                error_log("WooProductService::createVariableProduct - Product does not have a codfamilia. Skipping category assignment.");
            }

            // Add product images
            $images = $this->getProductImages($fsProduct->idproducto);
            if (!empty($images)) {
                $wooData['images'] = $images;
            }

            //error_log("WooProductService::createVariableProduct - Sending variable product data to WooCommerce API");
            $wooProduct = $this->wooClient->post('products', $wooData);

            if (!$wooProduct || !isset($wooProduct->id)) {
                error_log("WooProductService::createVariableProduct - Invalid response from WooCommerce API");
                return [
                    'success' => false,
                    'message' => 'Error al crear producto variable en WooCommerce - Respuesta inválida de API',
                    'data' => null
                ];
            }

            //error_log("WooProductService::createVariableProduct - Successfully created variable product with ID: {$wooProduct->id}");

            // Create variations
            $variationResults = $this->createVariations($wooProduct->id, $variants, $userNick);

            error_log("Variation creation results: " . json_encode($variationResults));

            // Update FS product with WooCommerce data
            WooDataMapper::updateFsWithWooData($fsProduct, $wooProduct, $userNick);

            if (!$fsProduct->save()) {
                error_log("WooProductService::createVariableProduct - Error saving FS product {$fsProduct->idproducto} after WooCommerce creation");
                return [
                    'success' => false,
                    'message' => 'Producto variable creado en WooCommerce pero error al actualizar FacturaScripts',
                    'data' => $wooProduct
                ];
            }

            return [
                'success' => true,
                'message' => "Producto variable creado exitosamente en WooCommerce con ID: {$wooProduct->id}. " .
                    "Variaciones creadas: {$variationResults['created']}/{$variationResults['total']}",
                'data' => $wooProduct
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::createVariableProduct - Exception: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Create variations for a variable product with rollback support
     */
    private function createVariations(int $wooProductId, array $variants, string $userNick): array
    {
        $results = ['total' => count($variants), 'created' => 0, 'errors' => 0, 'woo_variation_ids' => []];

        foreach ($variants as $variant) {
            try {
                $variationData = WooDataMapper::variantToWooCommerce($variant);

                // Get variant-specific images
                $variantImages = $this->getVariantImages($variant->idproducto, $variant->referencia);
                if (!empty($variantImages)) {
                    $variationData['image'] = $variantImages[0]; // WooCommerce limits 1 image per variation
                }

                // Create variation in WooCommerce
                $wooVariation = $this->wooClient->post("products/{$wooProductId}/variations", $variationData);

                if ($wooVariation && isset($wooVariation->id)) {
                    $results['woo_variation_ids'][] = $wooVariation->id; // Track for potential rollback

                    // Update variant with WooCommerce data using proper mapping
                    WooDataMapper::updateVariantWithWooData($variant, $wooVariation, $userNick);

                    if ($variant->save()) {
                        $results['created']++;
                        error_log("WooProductService::createVariations - Created variation ID: {$wooVariation->id}");
                    } else {
                        $results['errors']++;
                        error_log("WooProductService::createVariations - Failed to save variant data for WC variation: {$wooVariation->id}");

                        // If we can't save the variant data, this is a critical error
                        // We should consider rolling back the WooCommerce variation
                        try {
                            $this->wooClient->delete("products/{$wooProductId}/variations/{$wooVariation->id}", ['force' => true]);
                            error_log("WooProductService::createVariations - Rolled back WC variation {$wooVariation->id} due to FS save failure");
                        } catch (\Exception $rollbackException) {
                            error_log("WooProductService::createVariations - Failed to rollback WC variation {$wooVariation->id}: " . $rollbackException->getMessage());
                        }
                    }
                } else {
                    $results['errors']++;
                    error_log("WooProductService::createVariations - Failed to create variation for FS variant: {$variant->referencia}");
                }
            } catch (\Exception $e) {
                $results['errors']++;
                error_log("WooProductService::createVariations - Exception for variant {$variant->referencia}: " . $e->getMessage());
            }
        }

        // If more than half the variations failed, this might indicate a serious problem
        if ($results['errors'] > $results['total'] / 2) {
            error_log("WooProductService::createVariations - High error rate detected: {$results['errors']}/{$results['total']} variations failed");
        }

        return $results;
    }

    /**
     * Sync all variations for a product
     */
    public function syncAllVariations(int $fsProductId): array
    {
        error_log("WooProductService::syncAllVariations - Syncing all variations for FS product ID: {$fsProductId}");

        try {
            // Load the product
            $fsProduct = new Producto();
            if (!$fsProduct->loadFromCode($fsProductId)) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }

            // Check if product is synced
            if (empty($fsProduct->woo_id)) {
                return [
                    'success' => false,
                    'message' => 'El producto principal no está sincronizado con WooCommerce'
                ];
            }

            // Load all variants
            $varianteModel = new Variante();
            $where = [new DataBaseWhere('idproducto', $fsProductId)];
            $variants = $varianteModel->all($where);

            // Update parent product attributes first
            if (!$this->updateParentProductAttributes($fsProduct->woo_id, $variants)) {
                error_log("WooProductService::syncAllVariations - Failed to update parent product attributes");
                // Continue anyway - individual variations might still be synced
            }

            $results = ['total' => count($variants), 'synced' => 0, 'errors' => 0];

            foreach ($variants as $variant) {
                if (!empty($variant->woo_variation_id)) {
                    // Variation exists, update it
                    $result = $this->updateVariation($fsProduct->woo_id, $variant);
                } else {
                    // Variation doesn't exist, create it
                    $result = $this->createVariation($fsProduct->woo_id, $variant, $this->user->nick ?? 'system');
                }

                if ($result) {
                    $results['synced']++;
                } else {
                    $results['errors']++;
                }
            }

            return [
                'success' => true,
                'message' => "Variaciones sincronizadas: {$results['synced']}/{$results['total']}",
                'data' => $results
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::syncAllVariations - Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a simple product in WooCommerce.
     */
    private function updateSimpleProduct(Producto $fsProduct, array $formData, string $userNick): array
    {
        error_log("WooProductService::updateSimpleProduct - Updating simple product for FS ID: {$fsProduct->idproducto}");

        try {
            // Convert form data to WooCommerce format
            $wooData = WooDataMapper::formToWooCommerce($formData);

            // Automatically set category from Familia
            if (!empty($fsProduct->codfamilia)) {
                error_log("WooProductService::updateSimpleProduct - Product has codfamilia: " . $fsProduct->codfamilia);
                $familia = new Familia();
                $loaded = $familia->loadFromCode($fsProduct->codfamilia);
                if ($loaded && !empty($familia->wc_category_id)) {
                    error_log("WooProductService::updateSimpleProduct - Familia loaded successfully and has wc_category_id: " . $familia->wc_category_id);
                    $wooData['categories'] = [['id' => $familia->wc_category_id]];
                    error_log("WooProductService::updateSimpleProduct - Updating category from Familia: " . $familia->descripcion . " (ID: " . $familia->wc_category_id . ")");
                } else {
                    error_log("WooProductService::updateSimpleProduct - Condition failed. Familia loaded: " . ($loaded ? 'true' : 'false') . ". Has wc_category_id: " . (!empty($familia->wc_category_id) ? 'true (' . $familia->wc_category_id . ')' : 'false'));
                }
            } else {
                error_log("WooProductService::updateSimpleProduct - Product does not have a codfamilia. Skipping category update.");
            }

            // Add product images
            $images = $this->getProductImages($fsProduct->idproducto);
            if (!empty($images)) {
                $wooData['images'] = $images;
            }

            // Update product in WooCommerce
            $wooProduct = $this->wooClient->put("products/{$fsProduct->woo_id}", $wooData);

            if (!$wooProduct) {
                return ['success' => false, 'message' => 'Error al actualizar producto simple en WooCommerce'];
            }

            // Update FS product with form data and WooCommerce response
            WooDataMapper::updateFsWithFormData($fsProduct, $formData, $userNick);
            WooDataMapper::updateFsWithWooData($fsProduct, $wooProduct, $userNick);

            if (!$fsProduct->save()) {
                return ['success' => false, 'message' => 'Producto actualizado en WooCommerce pero error al guardar en FacturaScripts'];
            }

            return ['success' => true, 'message' => 'Producto simple actualizado exitosamente', 'data' => $wooProduct];
        } catch (\Exception $e) {
            error_log("WooProductService::updateSimpleProduct - Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }

    /**
     * Update a variable product in WooCommerce.
     */
    private function updateVariableProduct(Producto $fsProduct, array $variants, array $formData, string $userNick): array
    {
        error_log("WooProductService::updateVariableProduct - Updating variable product for FS ID: {$fsProduct->idproducto}");

        try {
            // Convert form data to WooCommerce format
            $wooData = WooDataMapper::formToWooCommerce($formData);

            // For variable products, price and stock are managed at the variation level.
            // Unset them from the parent product data to avoid conflicts.
            unset($wooData['regular_price']);
            unset($wooData['sale_price']);
            unset($wooData['manage_stock']);
            unset($wooData['stock_quantity']);

            // Automatically set category from Familia
            if (!empty($fsProduct->codfamilia)) {
                error_log("WooProductService::updateVariableProduct - Product has codfamilia: " . $fsProduct->codfamilia);
                $familia = new \FacturaScripts\Dinamic\Model\Familia();
                $loaded = $familia->loadFromCode($fsProduct->codfamilia);
                if ($loaded && !empty($familia->wc_category_id)) {
                    error_log("WooProductService::updateVariableProduct - Familia loaded successfully and has wc_category_id: " . $familia->wc_category_id);
                    $wooData['categories'] = [['id' => $familia->wc_category_id]];
                    error_log("WooProductService::updateVariableProduct - Updating category from Familia: " . $familia->descripcion . " (ID: " . $familia->wc_category_id . ")");
                } else {
                    error_log("WooProductService::updateVariableProduct - Condition failed. Familia loaded: " . ($loaded ? 'true' : 'false') . ". Has wc_category_id: " . (!empty($familia->wc_category_id) ? 'true (' . $familia->wc_category_id . ')' : 'false'));
                }
            } else {
                error_log("WooProductService::updateVariableProduct - Product does not have a codfamilia. Skipping category update.");
            }

            // Add product images (for the parent product)
            $images = $this->getProductImages($fsProduct->idproducto);
            if (!empty($images)) {
                $wooData['images'] = $images;
            }

            // Ensure attributes are up-to-date
            $attributes = WooDataMapper::extractAttributesFromVariants($variants);
            if (!empty($attributes)) {
                $wooData['attributes'] = $attributes;
            }

            // Update product in WooCommerce
            $wooProduct = $this->wooClient->put("products/{$fsProduct->woo_id}", $wooData);

            if (!$wooProduct) {
                return ['success' => false, 'message' => 'Error al actualizar producto variable en WooCommerce'];
            }

            // Update FS product with form data and WooCommerce response
            WooDataMapper::updateFsWithFormData($fsProduct, $formData, $userNick);
            WooDataMapper::updateFsWithWooData($fsProduct, $wooProduct, $userNick);

            if (!$fsProduct->save()) {
                return ['success' => false, 'message' => 'Producto actualizado en WooCommerce pero error al guardar en FacturaScripts'];
            }

            return ['success' => true, 'message' => 'Producto variable actualizado exitosamente', 'data' => $wooProduct];
        } catch (\Exception $e) {
            error_log("WooProductService::updateVariableProduct - Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }

    /**
     * Sync a single variation
     */
    public function syncVariation(int $variantId): array
    {
        error_log("WooProductService::syncVariation - Syncing variation ID: {$variantId}");

        try {
            // Load the variant
            $variant = new Variante();
            if (!$variant->loadFromCode($variantId)) {
                return [
                    'success' => false,
                    'message' => 'Variante no encontrada'
                ];
            }

            // Load the product
            $fsProduct = new Producto();
            if (!$fsProduct->loadFromCode($variant->idproducto)) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ];
            }

            // Check if product is synced
            if (empty($fsProduct->woo_id)) {
                return [
                    'success' => false,
                    'message' => 'El producto principal no está sincronizado con WooCommerce'
                ];
            }

            if (!empty($variant->woo_variation_id)) {
                // Variation exists, update it
                $result = $this->updateVariation($fsProduct->woo_id, $variant);
                $message = $result ? 'Variación actualizada exitosamente' : 'Error al actualizar la variación';
            } else {
                // Variation doesn't exist, create it
                $result = $this->createVariation($fsProduct->woo_id, $variant, 'system');
                $message = $result ? 'Variación creada exitosamente' : 'Error al crear la variación';
            }

            return [
                'success' => $result,
                'message' => $message
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::syncVariation - Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a single variation
     */
    public function createVariation(int $wooProductId, Variante $variant, string $userNick): array
    {
        error_log("WooProductService::createVariation - Creating variation for WooCommerce product ID: {$wooProductId}");

        try {
            // Load the product to get all variants
            $fsProduct = new Producto();
            if (!$fsProduct->loadFromCode($variant->idproducto)) {
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado en FacturaScripts'
                ];
            }

            // Get all variants for the product
            $varianteModel = new Variante();
            $where = [new DataBaseWhere('idproducto', $fsProduct->idproducto)];
            $allVariants = $varianteModel->all($where);

            // Update parent product attributes first
            if (!$this->updateParentProductAttributes($wooProductId, $allVariants)) {
                error_log("WooProductService::createVariation - Failed to update parent product attributes");
                // Continue anyway - the variation might still be created
            }

            // Now create the variation
            $variationData = WooDataMapper::variantToWooCommerce($variant);
            $wooVariation = $this->wooClient->post("products/{$wooProductId}/variations", $variationData);

            if ($wooVariation && isset($wooVariation->id)) {
                // Update the variant with WooCommerce data using proper mapping
                WooDataMapper::updateVariantWithWooData($variant, $wooVariation, $userNick);

                if ($variant->save()) {
                    error_log("WooProductService::createVariation - Variation created with ID: {$wooVariation->id}");
                    return [
                        'success' => true,
                        'message' => "Variación creada exitosamente en WooCommerce con ID: {$wooVariation->id}",
                        'data' => $wooVariation
                    ];
                } else {
                    error_log("WooProductService::createVariation - Failed to save variant after creation");
                    return [
                        'success' => false,
                        'message' => 'Variación creada en WooCommerce pero error al guardar en FacturaScripts',
                        'data' => $wooVariation
                    ];
                }
            } else {
                error_log("WooProductService::createVariation - Failed to create variation for FS variant: {$variant->referencia}");
                return [
                    'success' => false,
                    'message' => 'Error al crear variación en WooCommerce'
                ];
            }
        } catch (\Exception $e) {
            error_log("WooProductService::createVariation - Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a single variation
     *
     * @param int $wooProductId WooCommerce Product ID
     * @param int $wooVariationId WooCommerce Variation ID
     * @param bool $force Force delete
     * @return array
     */
    public function deleteVariation(int $variantId, int $wooProductId, int $wooVariationId, bool $force = false): array
    {
        error_log("WooProductService::deleteVariation - Deleting variation ID: {$wooVariationId} from product ID: {$wooProductId}");

        try {
            $params = $force ? ['force' => true] : [];
            $result = $this->wooClient->delete("products/{$wooProductId}/variations/{$wooVariationId}", $params);

            if (!$result) {
                error_log("WooProductService::deleteVariation - Error deleting variation {$wooVariationId}");
                return [
                    'success' => false,
                    'message' => 'Error al eliminar la variación de WooCommerce',
                ];
            }

            error_log("WooProductService::deleteVariation - Successfully deleted variation {$wooVariationId}");

            // Clear WooCommerce data from the corresponding FS variant
            $variant = new Variante();
            if (!$variant->loadFromCode($variantId)) {
                return [
                    'success' => false,
                    'message' => 'Variante no encontrada'
                ];
            }

            if (!empty($variant->woo_variation_id)) {
                $variant->woo_variation_id = null;
                $variant->woo_regular_price     = null;
                $variant->woo_sale_price = null;
                $variant->woo_sku = null;
                $variant->woo_manage_stock = null;
                $variant->woo_stock_quantity = null;
                $variant->woo_weight = null;
                $variant->woo_dimensions = null;
                $variant->woo_images = null;
                $variant->woo_attributes = null;
                $variant->woo_last_update = null;
                $variant->woo_description = null;
                $variant->woo_status = null;
                $variant->woo_permalink = null;
                $variant->save();
            }

            return [
                'success' => true,
                'message' => 'Variación eliminada exitosamente de WooCommerce',
                'data' => $result
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::deleteVariation - Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a variation
     */
    public function updateVariation(int $wooProductId, Variante $variant): bool
    {
        error_log("WooProductService::updateVariation - Updating variation ID: {$variant->woo_variation_id}");

        try {
            $variationData = WooDataMapper::variantToWooCommerce($variant);

            // Update images
            $variantImages = $this->getVariantImages($variant->idproducto, $variant->referencia);
            if (!empty($variantImages)) {
                $variationData['image'] = $variantImages[0];
            }

            $wooVariation = $this->wooClient->put("products/{$wooProductId}/variations/{$variant->woo_variation_id}", $variationData);

            if ($wooVariation) {
                // Update the variant with last sync time
                $variant->woo_last_update = date('d-m-Y H:i:s');
                $variant->save();

                error_log("WooProductService::updateVariation - Variation updated successfully");
                return true;
            } else {
                error_log("WooProductService::updateVariation - Failed to update variation");
                return false;
            }
        } catch (\Exception $e) {
            error_log("WooProductService::updateVariation - Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update parent product attributes when new variants are added
     */
    private function updateParentProductAttributes(int $wooProductId, array $allVariants): bool
    {
        error_log("WooProductService::updateParentProductAttributes - Updating attributes for parent product: {$wooProductId}");

        try {
            // Get the current parent product
            $wooProduct = $this->getProduct($wooProductId);
            if (!$wooProduct) {
                error_log("WooProductService::updateParentProductAttributes - Parent product not found");
                return false;
            }

            // Extract attributes from all variants
            $newAttributes = WooDataMapper::extractAttributesFromVariants($allVariants);

            // Check if attributes have changed - handle object format from WooCommerce
            $currentAttributes = $wooProduct->attributes ?? [];

            // WooCommerce returns attributes as an array of objects, so we need to handle that
            if ($this->attributesEqual($currentAttributes, $newAttributes)) {
                error_log("WooProductService::updateParentProductAttributes - No attribute changes detected");
                return true;
            }

            // Update the parent product with new attributes
            $updateData = ['attributes' => $newAttributes];
            $updatedProduct = $this->wooClient->put("products/{$wooProductId}", $updateData);

            if (!$updatedProduct) {
                error_log("WooProductService::updateParentProductAttributes - Failed to update parent product");
                return false;
            }

            error_log("WooProductService::updateParentProductAttributes - Successfully updated parent product attributes");
            return true;
        } catch (\Exception $e) {
            error_log("WooProductService::updateParentProductAttributes - Exception: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Compare if two attribute arrays are equal
     */
    private function attributesEqual($wooAttributes, $newAttributes): bool
    {
        error_log("WooProductService::attributesEqual - Comparing attributes");

        // Convert WooCommerce attributes (which may be objects) to array format
        $normalizeWooAttributes = function ($attr) {
            if (is_object($attr)) {
                return [
                    'name' => $attr->name ?? '',
                    'options' => isset($attr->options) ? (array)$attr->options : [],
                    'visible' => (bool)($attr->visible ?? false),
                    'variation' => (bool)($attr->variation ?? false)
                ];
            } elseif (is_array($attr)) {
                return [
                    'name' => $attr['name'] ?? '',
                    'options' => $attr['options'] ?? [],
                    'visible' => (bool)($attr['visible'] ?? false),
                    'variation' => (bool)($attr['variation'] ?? false)
                ];
            } else {
                return [
                    'name' => '',
                    'options' => [],
                    'visible' => false,
                    'variation' => false
                ];
            }
        };

        // Normalize new attributes (should already be arrays)
        $normalizeNewAttributes = function ($attr) {
            return [
                'name' => $attr['name'] ?? '',
                'options' => $attr['options'] ?? [],
                'visible' => (bool)($attr['visible'] ?? true),
                'variation' => (bool)($attr['variation'] ?? true)
            ];
        };

        // Normalize both sets
        $wooAttrs = is_array($wooAttributes) ? $wooAttributes : [$wooAttributes];
        $newAttrs = is_array($newAttributes) ? $newAttributes : [$newAttributes];

        $normalizedWoo = array_map($normalizeWooAttributes, $wooAttrs);
        $normalizedNew = array_map($normalizeNewAttributes, $newAttrs);

        // Sort both arrays by name for consistent comparison
        $sortByName = function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        };

        usort($normalizedWoo, $sortByName);
        usort($normalizedNew, $sortByName);

        // Compare the normalized arrays
        $equal = json_encode($normalizedWoo) === json_encode($normalizedNew);

        error_log("WooProductService::attributesEqual - Comparison result: " . ($equal ? 'equal' : 'different'));
        if (!$equal) {
            error_log("WooProductService::attributesEqual - Woo attributes: " . json_encode($normalizedWoo));
            error_log("WooProductService::attributesEqual - New attributes: " . json_encode($normalizedNew));
        }

        return $equal;
    }

    /**
     * Sync product images from FacturaScripts to WooCommerce
     * This will overwrite all images in WooCommerce with current FacturaScripts images
     * Automatically detects product type and delegates to appropriate handler
     */
    public function syncProductImages(int $fsProductId): array
    {
        error_log("WooProductService::syncProductImages - Starting image sync for FS ID: {$fsProductId}");

        try {
            // Load FacturaScripts product
            $fsProduct = new Producto();
            if (!$fsProduct->loadFromCode($fsProductId)) {
                error_log("WooProductService::syncProductImages - FS product not found: {$fsProductId}");
                return [
                    'success' => false,
                    'message' => 'Producto no encontrado en FacturaScripts',
                    'data' => null
                ];
            }

            // Check if product is synced
            if (empty($fsProduct->woo_id)) {
                error_log("WooProductService::syncProductImages - Product {$fsProductId} is not synced with WooCommerce");
                return [
                    'success' => false,
                    'message' => 'Este producto no está sincronizado con WooCommerce',
                    'data' => null
                ];
            }

            // Load variants to determine product type
            $varianteModel = new Variante();
            $where = [new DataBaseWhere('idproducto', $fsProduct->idproducto)];
            $variants = $varianteModel->all($where);

            // Determine product type and delegate
            $productType = $this->determineProductType($variants);
            error_log("WooProductService::syncProductImages - Detected product type: {$productType}");

            if ($productType === 'simple') {
                return $this->syncSimpleProductImages($fsProduct);
            } else {
                return $this->syncVariableProductImages($fsProduct, $variants);
            }
        } catch (\Exception $e) {
            error_log("WooProductService::syncProductImages - Exception during image sync: " . $e->getMessage());
            error_log("WooProductService::syncProductImages - Exception trace: " . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Sync images for a simple product
     */
    private function syncSimpleProductImages(Producto $fsProduct): array
    {
        error_log("WooProductService::syncSimpleProductImages - Syncing simple product images for FS ID: {$fsProduct->idproducto}");

        try {
            // Get current images from FacturaScripts
            $images = $this->getProductImages($fsProduct->idproducto);
            error_log("WooProductService::syncSimpleProductImages - Found " . count($images) . " images in FacturaScripts");

            // Prepare update data with only images
            $updateData = [
                'images' => $images
            ];

            error_log("WooProductService::syncSimpleProductImages - Updating WooCommerce product {$fsProduct->woo_id} with images");

            // Update product in WooCommerce with new images
            $wooProduct = $this->wooClient->put("products/{$fsProduct->woo_id}", $updateData);

            if (!$wooProduct) {
                error_log("WooProductService::syncSimpleProductImages - Error updating WooCommerce product {$fsProduct->woo_id}");
                return [
                    'success' => false,
                    'message' => 'Error al sincronizar imágenes del producto simple con WooCommerce',
                    'data' => null
                ];
            }

            // Update FS product with latest WooCommerce image data
            if (!empty($wooProduct->images)) {
                $fsProduct->woo_images = json_encode($wooProduct->images);
                $fsProduct->woo_last_update = date('d-m-Y H:i:s');
                $fsProduct->save();
            }

            error_log("WooProductService::syncSimpleProductImages - Successfully synchronized " . count($images) . " images for simple product");

            return [
                'success' => true,
                'message' => count($images) > 0
                    ? "Imágenes del producto simple sincronizadas: " . count($images) . " imagen(es) actualizadas"
                    : "Sincronización completada: todas las imágenes eliminadas del producto simple",
                'data' => [
                    'product_type' => 'simple',
                    'images_count' => count($images),
                    'woo_product_id' => $fsProduct->woo_id
                ]
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::syncSimpleProductImages - Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno sincronizando producto simple: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Sync images for a variable product (parent + all variations)
     */
    private function syncVariableProductImages(Producto $fsProduct, array $variants): array
    {
        error_log("WooProductService::syncVariableProductImages - Syncing variable product images for FS ID: {$fsProduct->idproducto}");

        try {
            $results = [
                'parent_images' => 0,
                'variant_images' => 0,
                'variants_updated' => 0,
                'errors' => 0
            ];

            // Step 1: Sync parent product images
            $parentImages = $this->getProductImages($fsProduct->idproducto);
            error_log("WooProductService::syncVariableProductImages - Found " . count($parentImages) . " parent images");

            $parentUpdateData = ['images' => $parentImages];
            $wooProduct = $this->wooClient->put("products/{$fsProduct->woo_id}", $parentUpdateData);

            if ($wooProduct) {
                $results['parent_images'] = count($parentImages);

                // Update FS product with latest image data
                if (!empty($wooProduct->images)) {
                    $fsProduct->woo_images = json_encode($wooProduct->images);
                    $fsProduct->woo_last_update = date('d-m-Y H:i:s');
                    $fsProduct->save();
                }

                error_log("WooProductService::syncVariableProductImages - Parent product images updated successfully");
            } else {
                error_log("WooProductService::syncVariableProductImages - Failed to update parent product images");
                $results['errors']++;
            }

            // Step 2: Sync variation images
            foreach ($variants as $variant) {
                if (empty($variant->woo_variation_id)) {
                    error_log("WooProductService::syncVariableProductImages - Skipping variant {$variant->idvariante} - not synced to WooCommerce");
                    continue;
                }

                try {
                    // Get variant-specific images
                    $variantImages = $this->getVariantImages($fsProduct->idproducto, $variant->referencia);
                    error_log("WooProductService::syncVariableProductImages - Found " . count($variantImages) . " images for variant {$variant->referencia}");

                    $variationUpdateData = [];

                    // WooCommerce variations can only have one image
                    if (!empty($variantImages)) {
                        $variationUpdateData['image'] = $variantImages[0]; // First image only
                        $results['variant_images']++;
                    } else {
                        // Remove image if no variant images exist
                        $variationUpdateData['image'] = null;
                    }

                    // Update variation in WooCommerce
                    $wooVariation = $this->wooClient->put("products/{$fsProduct->woo_id}/variations/{$variant->woo_variation_id}", $variationUpdateData);

                    if ($wooVariation) {
                        $results['variants_updated']++;
                        error_log("WooProductService::syncVariableProductImages - Updated variation {$variant->woo_variation_id}");
                    } else {
                        $results['errors']++;
                        error_log("WooProductService::syncVariableProductImages - Failed to update variation {$variant->woo_variation_id}");
                    }
                } catch (\Exception $variantException) {
                    $results['errors']++;
                    error_log("WooProductService::syncVariableProductImages - Exception updating variant {$variant->idvariante}: " . $variantException->getMessage());
                }
            }

            // Generate success message
            $messageParts = [];
            if ($results['parent_images'] > 0) {
                $messageParts[] = "Producto padre: {$results['parent_images']} imagen(es)";
            }
            if ($results['variant_images'] > 0) {
                $messageParts[] = "Variaciones: {$results['variant_images']} imagen(es)";
            }
            if ($results['variants_updated'] > 0) {
                $messageParts[] = "{$results['variants_updated']} variación(es) actualizadas";
            }

            $successMessage = !empty($messageParts)
                ? "Producto variable sincronizado - " . implode(', ', $messageParts)
                : "Sincronización completada: todas las imágenes eliminadas del producto variable";

            if ($results['errors'] > 0) {
                $successMessage .= " ({$results['errors']} error(es))";
            }

            error_log("WooProductService::syncVariableProductImages - Variable product sync completed");

            return [
                'success' => true,
                'message' => $successMessage,
                'data' => array_merge($results, [
                    'product_type' => 'variable',
                    'woo_product_id' => $fsProduct->woo_id,
                    'total_variants' => count($variants)
                ])
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::syncVariableProductImages - Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno sincronizando producto variable: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
