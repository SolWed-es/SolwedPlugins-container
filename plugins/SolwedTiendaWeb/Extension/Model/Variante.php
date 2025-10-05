<?php

namespace FacturaScripts\Plugins\SolwedTiendaWeb\Extension\Model;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Plugins\SolwedTiendaWeb\Lib\WooProductService;

/**
 * Para modificar el comportamiento de modelos de otro plugins (o del core)
 * podemos crear una extensión de ese modelo.
 *
 * https://facturascripts.com/publicaciones/extensiones-de-modelos
 */
class Variante
{
    /**
     * WooCommerce variation ID
     * @var int
     */
    public $woo_variation_id;

    /**
     * Variation description
     * @var string
     */
    public $woo_description;

    /**
     * WooCommerce-specific regular price for this variation
     * @var float
     */
    public $woo_regular_price;

    /**
     * WooCommerce-specific sale price for this variation
     * @var float
     */
    public $woo_sale_price;


    /**
     * Whether to manage stock for this variation in WooCommerce
     * @var bool
     */
    public $woo_manage_stock;

    /**
     * WooCommerce-specific stock quantity for this variation
     * @var int
     */
    public $woo_stock_quantity;

    /**
     * WooCommerce-specific weight for this variation
     * @var float
     */
    public $woo_weight;

    /**
     * JSON-encoded dimensions (length, width, height) for WooCommerce
     * @var string
     */
    public $woo_dimensions;

    /**
     * Variation status (draft, pending, private, publish)
     * @var string
     */
    public $woo_status;

    /**
     * Variation permalink
     * @var string
     */
    public $woo_permalink;


    /**
     * Last synchronization timestamp with WooCommerce
     * @var string
     */
    public $woo_last_update;





    // ***************************************
    // ** Métodos disponibles para extender **
    // ***************************************

    public function clear(): Closure
    {
        return function () {
            // Initialize WooCommerce fields
            $this->woo_variation_id = null;
            $this->woo_description = null;
            $this->woo_regular_price = null;
            $this->woo_sale_price = null;
            $this->woo_manage_stock = false;
            $this->woo_stock_quantity = null;
            $this->woo_weight = null;
            $this->woo_dimensions = null;
            $this->woo_status = null;
            $this->woo_permalink = null;
            $this->woo_last_update = null;
        };
    }

    public function delete(): Closure
    {
        return function () {};
    }

    public function deleteBefore(): Closure
    {
        return function () {
            // Check if the variation is synced with WooCommerce
            if (!empty($this->woo_variation_id)) {
                error_log("Variante::deleteBefore - Deleting synced variation. Woo Variation ID: " . $this->woo_variation_id);

                try {
                    // Load the parent product to get the woo_product_id
                    $fsProduct = new Producto();
                    if ($fsProduct->loadFromCode($this->idproducto) && !empty($fsProduct->woo_id)) {
                        error_log("Variante::deleteBefore - Parent product found with Woo ID: " . $fsProduct->woo_id);

                        // Call the service to delete the variation from WooCommerce
                        $productService = new WooProductService();
                        $result = $productService->deleteVariation(
                            $this->idvariante,
                            (int)$fsProduct->woo_id,
                            (int)$this->woo_variation_id,
                            true // Force delete
                        );

                        if (!$result['success']) {
                            error_log("Variante::deleteBefore - FAILED to delete variation from WooCommerce. Message: " . $result['message']);
                            // Optional: You could prevent deletion if the API call fails by returning false
                            // return false;
                        } else {
                            error_log("Variante::deleteBefore - SUCCESSFULLY deleted variation from WooCommerce.");
                        }
                    } else {
                        error_log("Variante::deleteBefore - Could not find parent product or parent is not synced. FS Product ID: " . $this->idproducto);
                    }
                } catch (\Exception $e) {
                    error_log("Variante::deleteBefore - EXCEPTION while deleting variation from WooCommerce: " . $e->getMessage());
                }
            }

            return true; // Allow the local deletion to proceed
        };
    }

    public function save(): Closure
    {
        return function () {
            // Handle WooCommerce integer fields before saving
            //$this->woo_variation_id = $this->idvariante;
            if ($this->woo_stock_quantity === '') {
                $this->woo_stock_quantity = null;
            }

            return true; // Continue with original save()
        };
    }

    public function saveBefore(): Closure
    {
        return function () {};
    }

    public function saveInsert(): Closure
    {
        return function () {};
    }

    public function saveInsertBefore(): Closure
    {
        return function () {};
    }

    public function saveUpdate(): Closure
    {
        return function () {};
    }

    public function saveUpdateBefore(): Closure
    {
        return function () {};
    }

    public function test(): Closure
    {
        return function () {

            // Convert empty strings to null for integer fields
            if ($this->woo_variation_id === '') {
                $this->woo_variation_id = null;
            }

            // Set woo_variation_id if empty (this runs before save)
            // if (empty($this->woo_variation_id) && !empty($this->idvariante)) {
            //     $this->woo_variation_id = $this->idvariante;
            //     error_log("Set woo_variation_id in test(): " . $this->woo_variation_id);
            // }

            if ($this->woo_stock_quantity === '') {
                $this->woo_stock_quantity = null;
            }


            return true; // Continue with original test()
        };
    }

    public function testBefore(): Closure
    {
        return function () {};
    }
}
