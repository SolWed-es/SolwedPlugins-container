<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Lib;

use FacturaScripts\Dinamic\Model\Producto;

/**
 * Handles data transformation between FacturaScripts and WooCommerce
 */
class WooDataMapper
{
    /**
     * Convert FacturaScripts product to WooCommerce format for creation
     */
    public static function fsToWooCommerce(Producto $fsProduct): array
    {
        error_log("WooDataMapper::fsToWooCommerce - Converting FS product ID: {$fsProduct->idproducto}");

        try {
            $data = [
                'name' => $fsProduct->descripcion ?? 'Producto sin nombre',
                'type' => 'simple',
                'regular_price' => (string) ($fsProduct->precio ?? '0'),
                'description' => $fsProduct->observaciones ?? '',
                'short_description' => $fsProduct->descripcion ?? '',
                'sku' => $fsProduct->codbarras ?? '',
                'manage_stock' => true,
                'stock_quantity' => (int) ($fsProduct->stockfis ?? 0),
                'status' => 'draft', // Start as draft for safety
                'catalog_visibility' => 'visible',
                'tax_status' => 'taxable',
                'virtual' => false,
                'downloadable' => false,
                'featured' => false,
                'reviews_allowed' => false,
                'stock_status' => 'instock',
                'weight' => '',
                'dimensions' => [
                    'length' => '',
                    'width' => '',
                    'height' => ''
                ]
            ];

            error_log("WooDataMapper::fsToWooCommerce - Successfully mapped data for product: {$fsProduct->referencia}");
            return $data;
        } catch (\Exception $e) {
            error_log("WooDataMapper::fsToWooCommerce - Error mapping FS product {$fsProduct->idproducto}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert form data to WooCommerce update format
     */
    public static function formToWooCommerce(array $formData): array
    {
        error_log("WooDataMapper::formToWooCommerce - Processing form data");

        try {
            $data = [
                'name' => $formData['name'] ?? '',
                'description' => $formData['description'] ?? '',
                'short_description' => $formData['short_description'] ?? '',
                'sku' => $formData['sku'] ?? '',
                'regular_price' => $formData['regular_price'] ?? '',
                'sale_price' => $formData['sale_price'] ?? '',
                'manage_stock' => $formData['manage_stock'] ?? false,
                'stock_quantity' => (int) ($formData['stock_quantity'] ?? 0),
                'status' => $formData['status'] ?? 'draft',
                'catalog_visibility' => $formData['catalog_visibility'] ?? 'visible',
                'categories' => $formData['categories'] ?? [],
                'weight' => $formData['weight'] ?? '',
                'tax_status' => $formData['tax_status'] ?? 'taxable',
                'virtual' => $formData['virtual'] ?? false,
                'downloadable' => $formData['downloadable'] ?? false,
                'featured' => $formData['featured'] ?? false,
                'reviews_allowed' => $formData['reviews_allowed'] ?? true,
                'tags' => $formData['tags'] ?? []
            ];

            // Handle dimensions
            if (!empty($formData['length']) || !empty($formData['width']) || !empty($formData['height'])) {
                $data['dimensions'] = [
                    'length' => $formData['length'] ?? '',
                    'width' => $formData['width'] ?? '',
                    'height' => $formData['height'] ?? ''
                ];
            }

            // Handle stock status
            if ($data['manage_stock']) {
                $data['stock_status'] = $data['stock_quantity'] > 0 ? 'instock' : 'outofstock';
            }

            error_log("WooDataMapper::formToWooCommerce - Successfully mapped form data");
            return $data;
        } catch (\Exception $e) {
            error_log("WooDataMapper::formToWooCommerce - Error mapping form data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update FacturaScripts product with WooCommerce response data
     */
    public static function updateFsWithWooData(Producto $fsProduct, $wooProduct, string $userNick = 'system'): void
    {
        error_log("WooDataMapper::updateFsWithWooData - Updating FS product {$fsProduct->idproducto} with WooCommerce data");

        try {
            // Basic WooCommerce data
            $fsProduct->woo_id = $wooProduct->id ?? null;
            $fsProduct->woo_product_name = $wooProduct->name ?? '';
            $fsProduct->woo_price = $wooProduct->regular_price ?? '';
            $fsProduct->woo_sale_price = $wooProduct->sale_price ?? '';
            $fsProduct->woo_permalink = $wooProduct->permalink ?? '';
            $fsProduct->woo_sku = $wooProduct->sku ?? '';
            $fsProduct->woo_status = $wooProduct->status ?? '';
            $fsProduct->woo_catalog_visibility = $wooProduct->catalog_visibility ?? '';

            // Inventory & Shipping
            $fsProduct->woo_manage_stock = $wooProduct->manage_stock ?? false;
            $fsProduct->woo_stock_quantity = $wooProduct->stock_quantity ?? 0;
            $fsProduct->woo_stock_status = $wooProduct->stock_status ?? '';
            $fsProduct->woo_weight = $wooProduct->weight ?? '';

            // Content
            $fsProduct->woo_description = $wooProduct->description ?? '';
            $fsProduct->woo_short_description = $wooProduct->short_description ?? '';

            // Product Features
            $fsProduct->woo_featured = $wooProduct->featured ?? false;
            $fsProduct->woo_virtual = $wooProduct->virtual ?? false;
            $fsProduct->woo_downloadable = $wooProduct->downloadable ?? false;
            $fsProduct->woo_reviews_allowed = $wooProduct->reviews_allowed ?? false;
            $fsProduct->woo_tax_status = $wooProduct->tax_status ?? '';

            // Complex data as JSON
            if (!empty($wooProduct->categories)) {
                $fsProduct->woo_categories = json_encode($wooProduct->categories);
            }

            if (!empty($wooProduct->images)) {
                $fsProduct->woo_images = json_encode($wooProduct->images);
            }

            if (!empty($wooProduct->tags)) {
                $fsProduct->woo_tags = json_encode($wooProduct->tags);
            }

            if (!empty($wooProduct->dimensions)) {
                $fsProduct->woo_dimensions = json_encode($wooProduct->dimensions);
            }

            // Tracking data
            if (empty($fsProduct->woo_creation_date)) {
                $fsProduct->woo_creation_date = date('Y-m-d H:i:s');
                $fsProduct->woo_nick = $userNick;
            }

            $fsProduct->woo_last_nick = $userNick;
            $fsProduct->woo_last_update = date('Y-m-d H:i:s');

            error_log("WooDataMapper::updateFsWithWooData - Successfully updated FS product {$fsProduct->idproducto}");
        } catch (\Exception $e) {
            error_log("WooDataMapper::updateFsWithWooData - Error updating FS product {$fsProduct->idproducto}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update FacturaScripts product with form data (before sending to WooCommerce)
     */
    public static function updateFsWithFormData(Producto $fsProduct, array $formData, string $userNick = 'system'): void
    {
        error_log("WooDataMapper::updateFsWithFormData - Updating FS product {$fsProduct->idproducto} with form data");

        try {
            // Update relevant FS fields with form data
            $fsProduct->woo_product_name = $formData['name'] ?? '';
            $fsProduct->woo_price = $formData['regular_price'] ?? '';
            $fsProduct->woo_sale_price = $formData['sale_price'] ?? '';
            $fsProduct->woo_sku = $formData['sku'] ?? '';
            $fsProduct->woo_status = $formData['status'] ?? '';
            $fsProduct->woo_catalog_visibility = $formData['catalog_visibility'] ?? '';

            // Inventory & Shipping
            $fsProduct->woo_manage_stock = $formData['manage_stock'] ?? false;
            $fsProduct->woo_stock_quantity = (int) ($formData['stock_quantity'] ?? 0);
            $fsProduct->woo_weight = $formData['weight'] ?? '';

            // Content
            $fsProduct->woo_description = $formData['description'] ?? '';
            $fsProduct->woo_short_description = $formData['short_description'] ?? '';

            // Product Features
            $fsProduct->woo_featured = $formData['featured'] ?? false;
            $fsProduct->woo_virtual = $formData['virtual'] ?? false;
            $fsProduct->woo_downloadabl = $formData['downloadable'] ?? false;
            $fsProduct->woo_reviews_allowed = $formData['reviews_allowed'] ?? false;
            $fsProduct->woo_tax_status = $formData['tax_status'] ?? '';

            // Complex data as JSON
            if (!empty($formData['categories'])) {
                $fsProduct->woo_categories = json_encode($formData['categories']);
            }

            if (!empty($formData['tags'])) {
                $fsProduct->woo_tags = json_encode($formData['tags']);
            }

            // Handle dimensions
            if (!empty($formData['length']) || !empty($formData['width']) || !empty($formData['height'])) {
                $dimensions = [
                    'length' => $formData['length'] ?? '',
                    'width' => $formData['width'] ?? '',
                    'height' => $formData['height'] ?? ''
                ];
                $fsProduct->woo_dimensions = json_encode($dimensions);
            }

            // Update tracking
            $fsProduct->woo_last_nick = $userNick;
            $fsProduct->woo_last_update = date('Y-m-d H:i:s');

            error_log("WooDataMapper::updateFsWithFormData - Successfully updated FS product {$fsProduct->idproducto} with form data");
        } catch (\Exception $e) {
            error_log("WooDataMapper::updateFsWithFormData - Error updating FS product {$fsProduct->idproducto} with form data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get safe decoded JSON data
     */
    public static function getJsonData(?string $jsonString): array
    {
        if (empty($jsonString)) {
            return [];
        }

        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("WooDataMapper::getJsonData - JSON decode error: " . json_last_error_msg() . " for data: " . $jsonString);
            return [];
        }

        return $data;
    }
}
