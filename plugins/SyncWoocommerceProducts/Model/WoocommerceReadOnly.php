<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

class WoocommerceReadOnly extends ModelClass
{
    use ModelTrait;

    public $woo_id;
    public $woo_product_name;
    public $woo_price;
    public $woo_status;
    public $vinculado;
    public $woo_permalink;

    public $creation_date;

    public function clear()
    {
        parent::clear();
    }

    public static function primaryColumn(): string
    {
        return "id";
    }

    public static function tableName(): string
    {
        return "woocommerce_read_only";
    }

    public function test(): bool
    {

        return parent::test();

        $this->woo_id            = Tools::noHtml($this->woo_id);
        $this->woo_product_name  = Tools::noHtml($this->woo_product_name);
        $this->woo_price         = Tools::noHtml($this->woo_price);
        $this->woo_status        = Tools::noHtml($this->woo_status);
        $this->vinculado         = Tools::noHtml($this->vinculado);
        $this->woo_permalink     = Tools::noHtml($this->woo_permalink);

        $this->creation_date     = $this->creation_date ?? Tools::dateTime();
    }

    protected function saveUpdate(array $values = []): bool
    {
        return parent::saveUpdate($values);
    }
}
