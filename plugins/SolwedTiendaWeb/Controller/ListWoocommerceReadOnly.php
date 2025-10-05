<?php

namespace FacturaScripts\Plugins\SolwedTiendaWeb\Controller;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Plugins\SolwedTiendaWeb\Lib\WooHelper;
use FacturaScripts\Plugins\SolwedTiendaWeb\Lib\WooProductService;
use FacturaScripts\Plugins\SolwedTiendaWeb\Model\WoocommerceReadOnly;

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
    }

    protected function createViewsProductosTienda(string $viewName = "ListWoocommerceReadOnly"): void
    {
        $this->addView($viewName, "WoocommerceReadOnly", "Productos Tienda Web");
        $this->addSearchFields($viewName, ['name']);
        $this->addOrderBy($viewName, ['woo_product_name'], 'name')
            ->addOrderBy(['creation_date'], 'date')
            ->addOrderBy(['vinculado'], 'Vinculado');

        // Disable button 'Nuevo'
        $this->setSettings($viewName, 'btnNew', false);

        // Add sync button
        $this->addButton($viewName, [
            'action' => 'sync-woocommerce',
            'color' => 'info',
            'icon' => 'fas fa-sync',
            'label' => 'Sincronizar con WooCommerce',
            'type' => 'action'
        ]);

        // Add delete button
        // $this->addButton($viewName, [
        //     'action' => 'delete',
        //     'color' => 'danger',
        //     'icon' => 'fas fa-trash-alt',
        //     'label' => 'delete',
        //     'type' => 'action'
        // ]);
    }

    protected function execAfterAction($action)
    {
        if ($action === 'sync-woocommerce') {
            $this->pullWooCommerceProducts();
            $this->toolBox()->i18nLog()->info('Productos sincronizados correctamente desde WooCommerce');
            return true;
        }

        return parent::execAfterAction($action);
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
                $this->toolBox()->i18nLog()->warning('No se encontraron productos en WooCommerce');
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

            error_log("Successfully pulled " . count($wooProducts) . " products from WooCommerce");
        } catch (\Exception $e) {
            error_log("Error pulling WooCommerce products: " . $e->getMessage());
            $this->toolBox()->i18nLog()->error('Error al sincronizar productos: ' . $e->getMessage());
        }
    }

    protected function clearExistingWooProducts(): void
    {
        $wooProductModel = new WoocommerceReadOnly();
        $existingProducts = $wooProductModel->all();

        foreach ($existingProducts as $producto) {
            error_log("Deleting existing WooCommerce product: " . $producto->woo_product_name);
            $producto->delete();
        }
    }

    private function saveWooProduct($wooProduct): void
    {
        try {
            $productosERP = new Producto();
            $wooProductModel = new WoocommerceReadOnly();

            // Check if woo_id exists in ERP products
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

            $wooProductModel->woo_id = $wooProduct->id ?? 0;
            $wooProductModel->woo_product_name = $wooProduct->name ?? '';
            $wooProductModel->woo_price = (float) ($wooProduct->price ?? 0.0);
            $wooProductModel->woo_status = $statusTranslations[$wooProduct->status] ?? $wooProduct->status;
            $wooProductModel->woo_permalink = $wooProduct->permalink ?? '';

            // Format date properly
            $wooProductModel->creation_date = isset($wooProduct->date_created)
                ? date('Y-m-d H:i:s', strtotime($wooProduct->date_created))
                : null;

            $wooProductModel->save();
        } catch (\Exception $e) {
            error_log("Error saving WooCommerce product ID " . ($wooProduct->id ?? 'unknown') . ": " . $e->getMessage());
        }
    }

    protected function deleteAction(): bool
    {
        // First check if we have permission and confirmations
        if (!$this->permissions->allowDelete) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            return true;
        }

        $selectedCodes = $this->request->request->get('code', []);
        if (empty($selectedCodes)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return true;
        }

        $wooProductModel = new WoocommerceReadOnly();
        $productService = new WooProductService();
        $successCount = 0;
        $errorCount = 0;

        foreach ($selectedCodes as $code) {
            if ($wooProductModel->loadFromCode($code)) {
                try {
                    $result = [];

                    if ($wooProductModel->vinculado === 'Si') {
                        // Linked: delete via FS product
                        $fsProduct = new Producto();
                        $where = [new DataBaseWhere('woo_id', $wooProductModel->woo_id)];

                        if ($fsProduct->loadFromCode('', $where)) {
                            // Use force=true to permanently delete from WooCommerce
                            $result = $productService->deleteProduct($fsProduct->idproducto, true);
                        }
                    } else {
                        // Not linked: delete directly by WooCommerce ID
                        // Use force=true to permanently delete from WooCommerce
                        $result = $productService->deleteWooProductById($wooProductModel->woo_id, true);
                    }

                    if (!empty($result) && $result['success']) {
                        error_log("Successfully deleted WooCommerce product ID: {$wooProductModel->woo_id}");
                        $successCount++;
                    } else {
                        error_log("Failed to delete WooCommerce product ID: {$wooProductModel->woo_id} - " . ($result['message'] ?? 'Unknown error'));
                        $errorCount++;
                    }
                } catch (\Exception $e) {
                    error_log("Error deleting WooCommerce product: " . $e->getMessage());
                    $errorCount++;
                }
            }
        }

        // Show summary message
        if ($successCount > 0) {
            $this->toolBox()->i18nLog()->info("Eliminados {$successCount} productos de WooCommerce");
        }
        if ($errorCount > 0) {
            $this->toolBox()->i18nLog()->warning("Errores al eliminar {$errorCount} productos");
        }

        // Now call parent to delete from local database
        return parent::deleteAction();
    }
}
