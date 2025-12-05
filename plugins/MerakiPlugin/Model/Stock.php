<?php

namespace FacturaScripts\Plugins\MerakiPlugin\Model;

use FacturaScripts\Core\Model\Stock as ParentStock;
use FacturaScripts\Dinamic\Model\Producto as DinProducto; // Importar DinProducto
use FacturaScripts\Dinamic\Model\Variante as DinVariante; // Importar DinVariante
use FacturaScripts\Core\Tools;

class Stock extends ParentStock
{
    protected function updateProductStock(): bool
    {
        // Obtener la cantidad específica de la variante que se está actualizando
        $variantStock = $this->cantidad; // Asumiendo que $this->cantidad es la cantidad de la variante actual

        $sql = "UPDATE " . DinProducto::tableName() . " SET stockfis = " . self::$dataBase->var2str($variantStock)
            . ", actualizado = " . self::$dataBase->var2str(Tools::dateTime())
            . " WHERE idproducto = " . self::$dataBase->var2str($this->idproducto) . ';';

        $totalVariant = $this->totalFromProduct($this->idproducto, $this->referencia);
        $sql .= "UPDATE " . DinVariante::tableName() . " SET stockfis = " . self::$dataBase->var2str($variantStock)
            . " WHERE referencia = " . self::$dataBase->var2str($this->referencia) . ';';

        // Ejecutar las consultas SQL
        $result = self::$dataBase->exec($sql);

        // Agregar el aviso de actualización de stock
        if ($result) {
            Tools::log()->notice('El stock se ha actualizado para el producto ID: ' . $this->idproducto .
                '. Nueva cantidad de variante: ' . $variantStock);

            // Lógica para el cURL
            $url = 'https://interioresmeraki.com/wp-json/wc/v3/custom/product-variants?sku=' . $this->referencia;
            $data = json_encode(['stock_quantity' => $variantStock]);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    'Authorization: Basic ' . base64_encode('ck_83c072c8afed1c86dfd8e353f2b3f8221bff59e8:cs_f9dd8338e88646ab5d96edafbd07220c9c2db1c0')
                ),
            ));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //borrar en producción

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl); // Captura el error de cURL
            curl_close($curl);

            // Verificar la respuesta del cURL
            if ($httpCode === 200) {
                Tools::log()->notice('El stock se ha actualizado correctamente en WooCommerce para SKU: ' . $this->referencia);
            } /* else {
                Tools::log()->error('Error al actualizar el stock en WooCommerce para SKU: ' . $this->referencia .
                    '. Código de respuesta: ' . $httpCode . '. Respuesta: ' . $response . '. Error de cURL: ' . $curlError);
            } */
        }

        return $result;
    }
}