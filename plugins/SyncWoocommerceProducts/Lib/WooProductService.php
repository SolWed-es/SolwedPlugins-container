<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Lib;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Producto;
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
            error_log("WooProductService::__construct - Successfully initialized WooCommerce client");
        } catch (\Exception $e) {
            error_log("WooProductService::__construct - Error initializing WooCommerce client: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new WooCommerce product from FacturaScripts product
     *
     * @param int $fsProductId FacturaScripts product ID
     * @param string $userNick User who initiated the creation
     * @return array Result array with success status and data
     */
    public function createProduct(int $fsProductId, string $userNick = 'system'): array
    {
        error_log("WooProductService::createProduct - Starting product creation for FS ID: {$fsProductId}");

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

            error_log("WooProductService::createProduct - Loaded FS product: {$fsProduct->referencia}");

            // Validate product data
            $validation = $this->validateProductForWooCommerce($fsProduct);
            if (!$validation['valid']) {
                error_log("WooProductService::createProduct - Validation failed for product {$fsProductId}: " . implode(', ', $validation['errors']));
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
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

            // Convert FS product to WooCommerce format
            $wooData = WooDataMapper::fsToWooCommerce($fsProduct);

            // Add product images
            $images = $this->getProductImages($fsProduct->idproducto);
            if (!empty($images)) {
                $wooData['images'] = $images;
                error_log("WooProductService::createProduct - Added " . count($images) . " images to product data");
            }

            error_log("WooProductService::createProduct - Sending product data to WooCommerce API");

            // Create product in WooCommerce
            $wooProduct = $this->wooClient->post('products', $wooData);

            // Debug - remove comment me out later
            error_log("WooCommerce Product: " . var_export($wooProduct, true));

            if (!$wooProduct || !isset($wooProduct->id)) {
                error_log("WooProductService::createProduct - Invalid response from WooCommerce API");
                return [
                    'success' => false,
                    'message' => 'Error al crear producto en WooCommerce - Respuesta inválida de API',
                    'data' => null
                ];
            }

            error_log("WooProductService::createProduct - Successfully created WooCommerce product with ID: {$wooProduct->id}");

            // Update FS product with WooCommerce data
            WooDataMapper::updateFsWithWooData($fsProduct, $wooProduct, $userNick);

            if (!$fsProduct->save()) {
                error_log("WooProductService::createProduct - Error saving FS product {$fsProductId} after WooCommerce creation");
                return [
                    'success' => false,
                    'message' => 'Producto creado en WooCommerce pero error al actualizar FacturaScripts',
                    'data' => $wooProduct
                ];
            }

            error_log("WooProductService::createProduct - Successfully completed product creation process");

            return [
                'success' => true,
                'message' => "Producto creado exitosamente en WooCommerce con ID: {$wooProduct->id}",
                'data' => $wooProduct
            ];
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

            error_log("WooProductService::updateProduct - Updating WooCommerce product ID: {$fsProduct->woo_id}");

            // Convert form data to WooCommerce format
            $wooData = WooDataMapper::formToWooCommerce($formData);

            // Add product images
            $images = $this->getProductImages($fsProduct->idproducto);
            if (!empty($images)) {
                $wooData['images'] = $images;
                error_log("WooProductService::createProduct - Added " . count($images) . " images to product data");
            }

            error_log("WooProductService::updateProduct - Sending update data to WooCommerce API");

            // Update product in WooCommerce
            $wooProduct = $this->wooClient->put("products/{$fsProduct->woo_id}", $wooData);

            if (!$wooProduct) {
                error_log("WooProductService::updateProduct - Error updating WooCommerce product {$fsProduct->woo_id}");
                return [
                    'success' => false,
                    'message' => 'Error al actualizar producto en WooCommerce',
                    'data' => null
                ];
            }

            error_log("WooProductService::updateProduct - Successfully updated WooCommerce product {$fsProduct->woo_id}");

            // Update FS product with form data and WooCommerce response
            WooDataMapper::updateFsWithFormData($fsProduct, $formData, $userNick);
            WooDataMapper::updateFsWithWooData($fsProduct, $wooProduct, $userNick);

            if (!$fsProduct->save()) {
                error_log("WooProductService::updateProduct - Error saving FS product {$fsProductId} after WooCommerce update");
                return [
                    'success' => false,
                    'message' => 'Producto actualizado en WooCommerce pero error al actualizar FacturaScripts',
                    'data' => $wooProduct
                ];
            }

            error_log("WooProductService::updateProduct - Successfully completed product update process");

            return [
                'success' => true,
                'message' => 'Producto actualizado exitosamente en WooCommerce',
                'data' => $wooProduct
            ];
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
     * Delete a WooCommerce product
     *
     * @param int $fsProductId FacturaScripts product ID
     * @param bool $force Force delete (bypass trash)
     * @return array Result array
     */
    public function deleteProduct(int $fsProductId, bool $force = false): array
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

            error_log("WooProductService::deleteProduct - Deleting WooCommerce product ID: {$fsProduct->woo_id}");

            // Delete from WooCommerce
            // Use true whether to permanently delete the product, Default is false (stays in trash).
            $params = $force ? ['force' => true] : [];
            $result = $this->wooClient->delete("products/{$fsProduct->woo_id}", $params);

            if (!$result) {
                error_log("WooProductService::deleteProduct - Error deleting WooCommerce product {$fsProduct->woo_id}");
                return [
                    'success' => false,
                    'message' => 'Error al eliminar producto de WooCommerce',
                    'data' => null
                ];
            }

            error_log("WooProductService::deleteProduct - Successfully deleted WooCommerce product {$fsProduct->woo_id}");

            // Clear WooCommerce data from FS product
            $this->clearWooCommerceData($fsProduct);

            if (!$fsProduct->save()) {
                error_log("WooProductService::deleteProduct - Error clearing WooCommerce data from FS product {$fsProductId}");
            }

            return [
                'success' => true,
                'message' => 'Producto eliminado exitosamente de WooCommerce',
                'data' => $result
            ];
        } catch (\Exception $e) {
            error_log("WooProductService::deleteProduct - Exception during product deletion: " . $e->getMessage());

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
     * Validate FacturaScripts product before creating in WooCommerce
     *
     * @param Producto $fsProduct
     * @return array Validation result
     */
    private function validateProductForWooCommerce(Producto $fsProduct): array
    {
        error_log("WooProductService::validateProductForWooCommerce - Validating FS product ID: {$fsProduct->idproducto}");

        $errors = [];

        if (empty($fsProduct->descripcion)) {
            $errors[] = ['message' => 'El producto debe tener una descripción'];
        }

        if (empty($fsProduct->referencia)) {
            $errors[] = ['message' => 'El producto debe tener una referencia'];
        }

        //        if (!isset($fsProduct->precio) || $fsProduct->precio < 0) {
        //            $errors[] = ['message' => 'El producto debe tener un precio válido'];
        //        }

        $isValid = empty($errors);

        if ($isValid) {
            error_log("WooProductService::validateProductForWooCommerce - Product validation passed");
        } else {
            error_log("WooProductService::validateProductForWooCommerce - Product validation failed: " . json_encode($errors));
        }

        return [
            'valid' => $isValid,
            'errors' => $errors
        ];
    }

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
        $fsProduct->woo_sale_price = null;
        $fsProduct->woo_permalink = null;
        $fsProduct->woo_sku = null;
        $fsProduct->woo_status = null;
        $fsProduct->woo_catalog_visibility = null;
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
        $fsProduct->woo_categories = null;
        $fsProduct->woo_images = null;
        $fsProduct->woo_tags = null;
        $fsProduct->woo_dimensions = null;
        $fsProduct->woo_creation_date = null;
        $fsProduct->woo_nick = null;
        $fsProduct->woo_last_nick = null;
        $fsProduct->woo_last_update = null;

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
}
