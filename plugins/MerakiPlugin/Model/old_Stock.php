<?php
namespace FacturaScripts\Plugins\MerakiPlugin\Model;

use FacturaScripts\Core\Model\Stock as ParentStock;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Producto as DinProducto; // Asegúrate de importar esta clase
use FacturaScripts\Dinamic\Model\Variante as DinVariante; // Asegúrate de importar esta clase también

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
    
        // Solo mostrar el mensaje de éxito
        if ($result) {
           // Tools::log()->notice('El stock se ha actualizado correctamente en WooCommerce para SKU: ' . $this->referencia);
            
            // Mensajes comentados para referencia futura
            // Tools::log()->notice('El stock se ha actualizado para el producto ID: ' . $this->idproducto .
            //     '. Nueva cantidad de variante: ' . $variantStock);
    
            // Lógica para el cURL
            // Tools::log()->notice('Enviando datos a WooCommerce: URL: ' . $url . ', Datos: ' . $data);
            
            // Verificar la respuesta del cURL
            // if ($httpCode === 200) {
            //     Tools::log()->notice('El stock se ha actualizado correctamente en WooCommerce para SKU: ' . $this->referencia);
            // } else {
            //     Tools::log()->error('Error al actualizar el stock en WooCommerce para SKU: ' . $this->referencia .
            //         '. Código de respuesta: ' . $httpCode . '. Respuesta: ' . $response . '. Error de cURL: ' . $curlError);
            // }
        }
    
        return $result;
    }
}