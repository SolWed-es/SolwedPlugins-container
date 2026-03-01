<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Plugins\Facturae\Model;

use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use josemmo\Facturae\Face\FaceClient;
use josemmo\Facturae\Facturae;
use josemmo\Facturae\FacturaeCentre;
use josemmo\Facturae\FacturaeFile;
use josemmo\Facturae\FacturaeItem;
use josemmo\Facturae\FacturaeParty;

class XMLfacturae extends ModelClass
{

    use ModelTrait;

    const XML_FOLDER = 'MyFiles/Facturae';

    /**
     * @var string
     */
    public $ciuoficina;

    /**
     * @var string
     */
    public $ciuorgano;

    /**
     * @var string
     */
    public $ciuorganop;

    /**
     * @var string
     */
    public $ciuunidad;

    /**
     * @var string
     */
    public $codoficina;

    /**
     * @var string
     */
    public $codorgano;

    /**
     * @var string
     */
    public $codorganop;

    /**
     * @var string
     */
    public $codunidad;

    /**
     * @var string
     */
    public $cpoficina;

    /**
     * @var string
     */
    public $cporgano;

    /**
     * @var string
     */
    public $cporganop;

    /**
     * @var string
     */
    public $cpunidad;

    /**
     * @var string
     */
    public $creationdate;

    /**
     * @var string
     */
    public $desorganop;

    /**
     * @var string
     */
    public $diroficina;

    /**
     * @var string
     */
    public $dirorgano;

    /**
     * @var string
     */
    public $dirorganop;

    /**
     * @var string
     */
    public $dirunidad;

    /**
     * @var string
     */
    public $filereference;

    /**
     * @var string
     */
    public $iban;

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $idfactura;

    /**
     * @var string
     */
    public $nomoficina;

    /**
     * @var string
     */
    public $nomorgano;

    /**
     * @var string
     */
    public $nomorganop;

    /**
     * @var string
     */
    public $nomunidad;

    /**
     * @var string
     */
    public $numeroregistro;

    /**
     * @var bool
     */
    public $observaciones;

    /**
     * @var string
     */
    public $proficina;

    /**
     * @var string
     */
    public $prorgano;

    /**
     * @var string
     */
    public $prorganop;

    /**
     * @var string
     */
    public $prunidad;

    /**
     * @var string
     */
    public $receivercontraref;

    /**
     * @var string
     */
    public $receivertransref;

    /**
     * @var array
     */
    public $validationErrors = [];

    /**
     * @var string
     */
    public $vencimiento;

    public function clear()
    {
        $this->creationdate = date(self::DATETIME_STYLE);
        $this->observaciones = false;
        $this->receivertransref = false;
        $this->vencimiento = date(self::DATE_STYLE, strtotime('+1 week'));
    }

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        if ($this->signed()) {
            unlink(self::XML_FOLDER . '/' . $this->id . '.xml');
            unlink(self::XML_FOLDER . '/' . $this->id . '.xsig');
        }

        return true;
    }

    public function getFilePath(): string
    {
        return self::XML_FOLDER . '/' . $this->id . '.xsig';
    }

    public static function primaryColumn(): string
    {
        return "id";
    }

    public function sendFace(string $certPath, string $password, string $email): bool
    {
        if (false === $this->signed()) {
            return false;
        }

        $face = new FaceClient($certPath, null, $password);
        $face->setProduction(true);

        $file = new FacturaeFile();
        $file->loadFile($this->getFilePath());

        $res = $face->sendInvoice($email, $file);
        if (isset($res->resultado->codigo) && !empty($res->resultado->codigo)) {
            $this->toolBox()->log()->warning($res->resultado->descripcion);
            return false;
        }

        if (isset($res->factura->numeroRegistro) && !empty($res->factura->numeroRegistro)) {
            $this->numeroregistro = $res->factura->numeroRegistro;
            return $this->save();
        }

        return false;
    }

    public function sign(string $certPath, string $password): bool
    {
        $factura = new FacturaCliente();
        if (false === $factura->loadFromCode($this->idfactura)) {
            return false;
        }

        $facturae32 = new Facturae();
        $facturae32->setPrecision(Facturae::PRECISION_INVOICE);
        $facturae32->setNumber($factura->codserie . $factura->codejercicio, $factura->numero);
        $facturae32->setIssueDate(date('Y-m-d', strtotime($factura->fecha)));
        $facturae32->setDueDate(date('Y-m-d', strtotime($this->vencimiento)));
        $facturae32->setSeller(new FacturaeParty($this->getEmpresaArray($factura->getCompany())));

        if (empty($this->codoficina)) {
            $facturae32->setBuyer(new FacturaeParty($this->getClienteArray($factura)));
        } else {
            $facturae32->setBuyer(new FacturaeParty($this->getCentroAdministrativo($factura)));
        }

        if (!empty($this->iban)) {
            $facturae32->setPaymentMethod(Facturae::PAYMENT_TRANSFER, $this->iban);
        }

        $lineas = $factura->getLines();
        foreach ($lineas as $num => $linea) {
            $observations = '';
            if (!empty($factura->observaciones) && $this->observaciones && $num == count($lineas) - 1) {
                $observations = $factura->observaciones;
            }

            $lineData = [
                'name' => $linea->descripcion,
                'description' => $observations,
                'quantity' => $linea->cantidad,
                'unitPriceWithoutTax' => $linea->pvpunitario * $linea->getEUDiscount() * $factura->getEUDiscount(),
                'taxes' => []
            ];
            // el IVA siempre debe estar, aunque sea cero
            $lineData['taxes'][Facturae::TAX_IVA] = $linea->iva;
            if ($linea->recargo) {
                $lineData['taxes'][Facturae::TAX_REIVA] = $linea->recargo;
            }
            if ($linea->irpf) {
                $lineData['taxes'][Facturae::TAX_IRPF] = $linea->irpf;
            }
            if ($this->receivertransref) {
                $lineData['receiverTransactionReference'] = $this->receivertransref;
            }
            if ($this->receivercontraref) {
                $lineData['receiverContractReference'] = $this->receivercontraref;
            }
            if ($this->filereference) {
                $lineData['fileReference'] = $this->filereference;
            }
            $facturae32->addItem(new FacturaeItem($lineData));
        }

        if (false === file_exists(self::XML_FOLDER)) {
            mkdir(self::XML_FOLDER, 0777, true);
        }

        $facturae32->export(self::XML_FOLDER . '/' . $this->id . '.xml');
        if (false === $facturae32->sign($certPath, null, $password)) {
            $this->toolBox()->i18nLog()->warning('error-when-signing');
            return false;
        }

        $facturae32->export(self::XML_FOLDER . '/' . $this->id . '.xsig');
        return true;
    }

    public function signed(): bool
    {
        return file_exists(self::XML_FOLDER . '/' . $this->id . '.xsig');
    }

    public static function tableName(): string
    {
        return "xmlfacturaes";
    }

    public function test(): bool
    {
        $exclude = ['id', 'idfactura', 'observaciones', 'receivertransref'];
        foreach (array_keys($this->getModelFields()) as $key) {
            if (!empty($this->{$key}) && false === in_array($key, $exclude)) {
                $this->{$key} = $this->toolBox()->utils()->noHtml($this->{$key});
            }
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        switch ($type) {
            case 'download':
                return $this->getFilePath() . '?myft=' . MyFilesToken::get($this->getFilePath(), false);

            case 'download-permanent':
                return $this->getFilePath() . '?myft=' . MyFilesToken::get($this->getFilePath(), true);
        }

        return parent::url($type, $list);
    }

    public function validate(): bool
    {
        $factura = new FacturaCliente();
        if (false === $factura->loadFromCode($this->idfactura)) {
            $this->validationErrors[] = 'Factura no encontrada.';
            return false;
        }

        $empresa = $factura->getCompany();
        if ($factura->coddivisa != 'EUR') {
            $this->validationErrors[] = 'La factura no está en euros.';
            return false;
        } elseif ($factura->cifnif == '') {
            $this->validationErrors[] = 'La factura no tiene cif/nif.';
            return false;
        } elseif ($factura->nombrecliente == '') {
            $this->validationErrors[] = 'La factura no tiene nombre de cliente.';
            return false;
        } elseif ($factura->direccion == '') {
            $this->validationErrors[] = 'La factura no tiene dirección.';
            return false;
        } elseif (strlen($factura->codpostal) != 5) {
            $this->validationErrors[] = 'La factura debe tener un código postal de 5 dígitos.';
            return false;
        } elseif ($factura->ciudad == '') {
            $this->validationErrors[] = 'La factura no tiene ciudad.';
            return false;
        } elseif ($factura->provincia == '') {
            $this->validationErrors[] = 'La factura no tiene provincia.';
            return false;
        } elseif ($empresa->direccion == '') {
            $this->validationErrors[] = 'La empresa no tiene dirección.';
            return false;
        } elseif (strlen($empresa->codpostal) != 5) {
            $this->validationErrors[] = 'La empresa debe tener un código postal de 5 dígitos.';
            return false;
        } elseif ($empresa->ciudad == '') {
            $this->validationErrors[] = 'La empresa no tiene ciudad.';
            return false;
        } elseif ($empresa->provincia == '') {
            $this->validationErrors[] = 'La empresa no tiene provincia.';
            return false;
        }

        foreach ($factura->getLines() as $linea) {
            if (empty($linea->cantidad)) {
                $this->validationErrors[] = 'La factura no puede tener líneas con cantidad 0.';
                return false;
            }
        }

        return true;
    }

    /**
     * @param FacturaCliente $factura
     *
     * @return array
     */
    private function getCentroAdministrativo($factura): array
    {
        $data = $this->getClienteArray($factura);
        $data["centres"] = [
            new FacturaeCentre([
                "role" => FacturaeCentre::ROLE_GESTOR,
                "code" => $this->codorgano,
                "name" => substr($this->nomorgano, 0, 40),
                "address" => $this->dirorgano,
                "postCode" => $this->cporgano,
                "town" => $this->ciuorgano,
                "province" => $this->prorgano
            ]),
            new FacturaeCentre([
                "role" => FacturaeCentre::ROLE_TRAMITADOR,
                "code" => $this->codunidad,
                "name" => substr($this->nomunidad, 0, 40),
                "address" => $this->dirunidad,
                "postCode" => $this->cpunidad,
                "town" => $this->ciuunidad,
                "province" => $this->prunidad
            ]),
            new FacturaeCentre([
                "role" => FacturaeCentre::ROLE_CONTABLE,
                "code" => $this->codoficina,
                "name" => substr($this->nomoficina, 0, 40),
                "address" => $this->diroficina,
                "postCode" => $this->cpoficina,
                "town" => $this->ciuoficina,
                "province" => $this->proficina
            ])
        ];

        if ($this->codorganop) {
            $data["centres"][] = new FacturaeCentre([
                "role" => FacturaeCentre::ROLE_PROPONENTE,
                "code" => $this->codorganop,
                "name" => substr($this->nomorganop, 0, 40),
                "address" => $this->dirorganop,
                "postCode" => $this->cporganop,
                "town" => $this->ciuorganop,
                "province" => $this->prorganop,
                "description" => $this->desorganop
            ]);
        }

        return $data;
    }

    /**
     * @param FacturaCliente $factura
     *
     * @return array
     */
    private function getClienteArray($factura): array
    {
        $data = [
            "isLegalEntity" => true,
            "taxNumber" => str_replace(' ', '', $factura->cifnif),
            "name" => substr($factura->nombrecliente, 0, 40),
            "address" => $factura->direccion,
            "postCode" => $factura->codpostal,
            "town" => $factura->ciudad,
            "province" => $factura->provincia
        ];

        $cliente = $factura->getSubject();
        if ($cliente->personafisica && $cliente->tipoidfiscal === 'CIF') {
            $cliente->personafisica = false;
            $cliente->save();
        }

        if ($cliente->personafisica) {
            $data["isLegalEntity"] = false;

            $nombre = explode(' ', $factura->nombrecliente);
            switch (count($nombre)) {
                case 1:
                    $data["name"] = $nombre[0];
                    break;

                case 2:
                    $data["name"] = $nombre[0];
                    $data["firstSurname"] = $nombre[1];
                    break;

                default:
                    $data["lastSurname"] = $this->getLastName($nombre);
                    $data["firstSurname"] = $this->getLastName($nombre);
                    $data["name"] = implode(' ', $nombre);
                    break;
            }
        }

        return $data;
    }

    /**
     * @param Empresa $empresa
     *
     * @return array
     */
    private function getEmpresaArray($empresa): array
    {
        $data = [
            "isLegalEntity" => true,
            "taxNumber" => str_replace(' ', '', $empresa->cifnif),
            "name" => $empresa->nombre,
            "address" => $empresa->direccion,
            "postCode" => $empresa->codpostal,
            "town" => $empresa->ciudad,
            "province" => $empresa->provincia
        ];

        if ($empresa->personafisica && $empresa->tipoidfiscal === 'CIF') {
            $empresa->personafisica = false;
            $empresa->save();
        }

        if ($empresa->personafisica) {
            $data["isLegalEntity"] = false;

            $nombre = explode(' ', $empresa->nombre);
            switch (count($nombre)) {
                case 1:
                    $data["name"] = $nombre[0];
                    break;

                case 2:
                    $data["name"] = $nombre[0];
                    $data["firstSurname"] = $nombre[1];
                    break;

                default:
                    $data["lastSurname"] = $this->getLastName($nombre);
                    $data["firstSurname"] = $this->getLastName($nombre);
                    $data["name"] = implode(' ', $nombre);
                    break;
            }
        }

        return $data;
    }

    private function getLastName(array &$name): string
    {
        $num = count($name) - 1;
        if ($num < 0) {
            return '';
        }

        $txt = $name[$num];
        unset($name[$num]);
        return $txt;
    }
}
