<?php

namespace FacturaScripts\Plugins\PushWoocommerceProductos\Controller;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Plugins\PushWoocommerceProductos\Lib\WooHelper;
use FacturaScripts\Plugins\PushWoocommerceProductos\Lib\WooProductService;
use FacturaScripts\Plugins\PushWoocommerceProductos\Model\WoocommerceReadOnly;

/**
 * Este controlador tiene como propósito obtener todos los productos existentes
 * de WooCommerce para visualizar cuáles están vinculados a un producto real en
 * FacturaScripts y cuáles no.
 *
 * También permite eliminar productos directamente de la base de datos de WooCommerce.
 *
 * Funcionamiento:
 * 1. Al hacer clic en el botón de sincronización, se eliminan todas las entradas del modelo.
 * 2. Se obtienen los datos desde WooCommerce.
 * 3. Los datos se guardan en la base de datos para disponer de un listado actualizado.
 */


class ListWoocommerceReadOnly extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData["title"] = "Tienda WEB";
        $pageData["menu"] = "warehouse";
        $pageData["icon"] = "fas fa-store";
        return $pageData;
    }

    protected function createViews(): void
    {
        $this->createViewsProductosTienda();
        $this->addPullButton();
    }

    protected function execAfterAction($action)
    {
        // Trigger pullWoocommerceData() function
        if ($action === 'pullData') {
            $this->pullWooCommerceProducts();
        }
    }

    protected function createViewsProductosTienda(string $viewName = "ListWoocommerceReadOnly"): void
    {
        $this->addView($viewName, "WoocommerceReadOnly", "Productos Tienda Web");
        $this->addSearchFields($viewName, ['name']);
        $this->addOrderBy($viewName, ['woo_product_name'], 'name')
            ->addOrderBy(['creation_date'], 'date')
            ->addOrderBy(['vinculado'], 'Vinculado');


        // disable button 'Nuevo'
        $this->setSettings($viewName, 'btnNew', false);
    }


    protected function addPullButton()
    {

        $this->addButton('ListWoocommerceReadOnly', [
            'type' => 'action',
            'action' => 'pullData',
            'color' => 'info',
            'icon' => 'fas fa-file-export',
            'label' => 'refresh',
        ]);
    }



    protected function pullWooCommerceProducts(): void
    {
        try {
            error_log("Starting WooCommerce products pull...");

            // Initialize WooCommerce client & Get products from WooCommerce API
            $wooClient = WooHelper::getClient();
            $wooProducts = $wooClient->get('products', ['per_page' => 100]);

            if (empty($wooProducts)) {
                error_log("No products found in WooCommerce");
                return;
            }

            if (is_object($wooProducts)) {
                $wooProducts = (array) $wooProducts;
            }

            // Clear existing WooCommerce products (simple full sync)
            $this->clearExistingWooProducts();

            // Insert new products
            foreach ($wooProducts as $wooProduct) {
                $this->saveWooProduct($wooProduct);
            }


            // Page refresh to correctly view the data
            $this->redirect($this->url());

            error_log("Successfully pulled " . count($wooProducts) . " products from WooCommerce");
        } catch (\Exception $e) {
            error_log("Error pulling WooCommerce products: " . $e->getMessage());
        }
    }

    protected function clearExistingWooProducts(): void
    {
        $wooProductModel = new WoocommerceReadOnly();
        $existingProducts = $wooProductModel->all();
        foreach ($existingProducts as $producto) {
            error_log("Deleting existing WooCommerce product ID: " . $producto->woo_product_name);
            $producto->delete();
        }
    }

    private function saveWooProduct($wooProduct): void
    {
        try {
            // this is to check if the woo_id exists in the ERP products;
            $productosERP = new Producto();

            $wooProductModel = new WoocommerceReadOnly();

            // Conditionally set 'vinculado' to Si or No based on wether woo_id exists in Productos() or not
            $where = [new DataBaseWhere('woo_id', $wooProduct->id)];
            if ($productosERP->loadFromCode('', $where)) {
                $wooProductModel->vinculado = 'Si';
            } else {
                $wooProductModel->vinculado = 'No';
            }

            // Save translated 'Status' string
            $statusTranslations = [
                'draft' => 'Borrador',
                'pending' => 'Pendiente',
                'publish' => 'Publicado',
                'private' => 'Privado',
            ];
            $wooProductModel->woo_status = $statusTranslations[$wooProduct->status] ?? $wooProduct->status;

            $wooProductModel->woo_id = $wooProduct->id ?? 0;
            $wooProductModel->woo_product_name = $wooProduct->name ?? '';
            $wooProductModel->woo_price = (float) $wooProduct->price ?? 0.0;
            //$wooProductModel->woo_status = $wooProduct->status ?? '';
            $wooProductModel->woo_permalink = $wooProduct->permalink ?? '';

            $wooProductModel->creation_date = $wooProduct->date_created ?? '';

            $wooProductModel->save();
        } catch (\Exception $e) {
            error_log("Error saving WooCommerce product ID " . ($wooProduct->id ?? 'unknown') . ": " . $e->getMessage());
        }
    }


    // we modify the behavior of Delete button. First we bring parent logic, then we add ours
    protected function deleteAction(): bool
    {
        // Get the selected codes before calling parent delete
        $selectedCodes = $this->request->request->get('code', []);

        if (empty($selectedCodes)) {
            return parent::deleteAction();
        }

        // Process each selected item for WooCommerce deletion
        $wooProductModel = new WoocommerceReadOnly();
        $productService = new WooProductService();

        foreach ($selectedCodes as $code) {
            // Load the Woocommerce product to get the woo_id
            if ($wooProductModel->loadFromCode($code)) {
                try {
                    // Delete from WooCommerce first
                    $result = $productService->deleteWooProductById($wooProductModel->woo_id, false);

                    if ($result['success']) {
                        error_log("Successfully deleted WooCommerce product ID: " . $wooProductModel->woo_id);
                    } else {
                        error_log("Failed to delete WooCommerce product ID: " . $wooProductModel->woo_id . " - " . $result['message']);
                    }
                } catch (\Exception $e) {
                    error_log("Error deleting WooCommerce product: " . $e->getMessage());
                }
            }
        }

        // Now call parent to delete from local database
        return parent::deleteAction();
    }
}
