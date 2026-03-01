<?php

namespace FacturaScripts\Plugins\InfoCostePresupuesto\Mod;

use FacturaScripts\Core\Base\Contract\SalesLineModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;

use FacturaScripts\Core\Model\LineaPresupuestoCliente;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Proveedor;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

class SalesLineMod implements SalesLineModInterface
{

    public function apply(SalesDocument &$model, array &$lines, array $formData)
    {
        // TODO: Implement apply() method.
    }

    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id)
    {
       $line->coste = floatval($formData['costepersonalizado_' . $id] ?? $line->coste);
    }

    public function assets(): void
    {
        // TODO: Implement assets() method.
    }

    public function getFastLine(SalesDocument $model, array $formData): ?SalesDocumentLine
    {
        return null;
    }

    public function map(array $lines, SalesDocument $model): array
    {
        return [];
    }

    public function newFields(): array
    {
        return ['Proveedor', 'Coste'];
    }

    public function newModalFields(): array
    {
        return [];
    }

    public function newTitles(): array
    {
        return ['Proveedor', 'Coste'];
    }

    public function renderField(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string
    {
        if ($field === 'Proveedor') {
            return $this->proveedorField($i18n, $idlinea, $line, $model);
        }
        if ($field === 'Coste') {
            return $this->costeField($i18n, $idlinea, $line, $model);
        }
        return null;
    }

    public function renderTitle(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($field === 'Proveedor') {
            return $this->proveedorTitle($i18n);
        }
        if ($field === 'Coste') {
            return $this->costeTitle($i18n);
        }
        return null;
    }

    protected function proveedorField($i18n, $idlinea, $line, $model): string
    {

        $productInfo = $this->getProductInfo($line);
        $proveedores = $productInfo['proveedores'] ?? [];


        $attributes = $model->editable ?
            'name="proveedorpersonalizado_' . $idlinea . '" onchange="updateCosteFromProveedor(this); "' :
            'disabled=""';

        //TODO: Debugging ATM, Replace with actual suppliers

        $options = ['<option data-coste=' . $productInfo['coste'] . '>------</option>'];

        foreach ($proveedores as $prov) {
            $options[] =
                '<option value="' . $prov['codproveedor'] . '" data-coste="' . $prov['netoeuros'] . '">'
                . $prov['nombre'] . ' - ' . $prov['netoeuros'] . '€'
                . '</option>';
        }

        return '<div class="col-sm col-lg-1 order-2">'
            . '<div class="d-lg-none mt-3 small">' . $i18n->trans('supplier') . '</div>'
            . '<select ' . $attributes . ' class="form-control form-control-sm border-0">' . implode('', $options) . '</select>'
            . '</div>';
    }

    protected function costeField($i18n, $idlinea, $line, $model): string
    {
        $attributes = $model->editable ?
            'name="costepersonalizado_' . $idlinea . '" min="0" max="100" step="1" onkeyup="return ' . 'salesFormActionWait' . '(\'recalculate-line\', \'0\', event);"' :
            'disabled=""';
        return '<div class="col-sm col-lg-1 order-2">'
            . '<div class="d-lg-none mt-3 small">' . $i18n->trans('cost') . '</div>'
            . '<input type="number" ' . $attributes . ' value="' . $line->coste . '" class="form-control form-control-sm border-0"/>'
            . '</div>';
    }


    protected function proveedorTitle($i18n): string
    {
        return '<div class="col-lg-1 order-2">' . $i18n->trans('supplier') . '</div>';
    }

    protected function costeTitle($i18n): string
    {
        return '<div class="col-lg-1 order-2">' . $i18n->trans('cost') . '</div>';
    }


    protected function getProductInfo($line): array
    {
        //Initiating ProductoProveedor, Proveedor model
        $productoProveedorModel = new ProductoProveedor();
        $productoProveedor = new Proveedor();


        $where = [new DataBaseWhere('referencia', $line->referencia)];
        $proveedoresList = $productoProveedorModel->all($where);
        $proveedores = [];

        foreach ($proveedoresList as $prov) {
            $proveedor = $productoProveedor->get($prov->codproveedor);
            if ($proveedor) {
                $proveedores[] = [
                    'nombre' => $proveedor->nombre,
                    'netoeuros' => $prov->netoeuros,
                    'codproveedor' => $prov->codproveedor,
                ];
            }

        }

        return [
            'referencia' => $line->referencia,
            'idproducto' => $line->idproducto,
            'coste' => $line->coste,
            'proveedores' => $proveedores,
        ];

    }


}