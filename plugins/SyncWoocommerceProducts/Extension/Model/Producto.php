<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Extension\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Producto as BaseProducto;
use Closure;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * Para modificar el comportamiento de modelos de otro plugins (o del core)
 * podemos crear una extensión de ese modelo.
 *
 * https://facturascripts.com/publicaciones/extensiones-de-modelos
 */
class Producto
{
    public $woo_id;
    public $woo_product_name;
    public $woo_price;
    public $woo_sale_price;
    public $woo_permalink;
    public $woo_sku;
    public $woo_status;
    public $woo_catalog_visibility;

    // Inventory & Shipping
    public $woo_manage_stock;
    public $woo_stock_quantity;
    public $woo_stock_status;
    public $woo_weight;

    // Content
    public $woo_description;
    public $woo_short_description;

    // Product Features
    public $woo_featured;
    public $woo_virtual;
    public $woo_downloadable;
    public $woo_reviews_allowed;
    public $woo_tax_status;

    // TODO: Existing fields not in the new list
    public $woo_categories;
    public $woo_images;
    public $woo_dimensions;

    public $woo_nick;
    public $woo_creation_date;
    public $woo_last_nick;
    public $woo_last_update;





    // ***************************************
    // ** Métodos disponibles para extender **
    // ***************************************

    public function clear(): Closure
    {
        return function () {
            //$this->price = 0.0;
            //$this->woo_id = 0;
        };
    }

    public function delete(): Closure
    {
        return function () {};
    }

    public function deleteBefore(): Closure
    {
        return function () {};
    }

    public function save(): Closure
    {
        return function () {
            // tu código aquí
            // save() se ejecuta una vez realizado el save() del modelo,
            // cuando ya se ha guardado el registro en la base de datos
        };
    }

    public function saveBefore(): Closure
    {
        return function () {

            // tu código aquí
            // saveBefore() se ejecuta antes de hacer el save() del modelo.
            // Si devolvemos false, impedimos el save().
        };
    }

    public function saveInsert(): Closure
    {
        return function () {};
    }

    public function saveInsertBefore(): Closure
    {
        return function () {
            // tu código aquí
            // saveInsertBefore() se ejecuta antes de hacer el saveInsert() del modelo.
            // Si devolvemos false, impedimos el saveInsert().
        };
    }

    public function saveUpdate(): Closure
    {
        return function () {
            // tu código aquí
            // saveUpdate() se ejecuta una vez realizado el saveUpdate() del modelo,
            // cuando ya se ha guardado el registro en la base de datos
            $this->last_nick = Session::user()->nick;
            $this->last_update = Tools::dateTime();
        };
    }

    public function saveUpdateBefore(): Closure
    {
        return function () {
            // tu código aquí
            // saveUpdateBefore() se ejecuta antes de hacer el saveUpdate() del modelo.
            // Si devolvemos false, impedimos el saveUpdate().
        };
    }

    public function test(): Closure
    {
        return function () {
            $this->creation_date = $this->creationdate ?? Tools::dateTime();
            $this->nick = $this->nick ?? Session::user()->nick;
        };
    }

    public function testBefore(): Closure
    {
        return function () {
            // tu código aquí
            // test se ejecuta justo antes del método test del modelo.
            // Si devolvemos false, impedimos el save() y el resto de test().
        };
    }
}
