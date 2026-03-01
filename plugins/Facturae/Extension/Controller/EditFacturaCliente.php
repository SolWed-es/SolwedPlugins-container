<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Plugins\Facturae\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Plugins\Facturae\Model\XMLfacturae;

/**
 * Description of EditFacturaCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditFacturaCliente
{
    protected function createViews(): Closure
    {
        return function () {
            $this->addHtmlView('facturae', 'Tab/Facturae', 'XMLfacturae', 'facturae', 'fas fa-qrcode');
        };
    }

    protected function deleteFacturaeAction(): Closure
    {
        return function () {
            $xmlfacturae = new XMLfacturae();
            $idfacturae = $this->request->request->get('idfacturae');
            if ($xmlfacturae->loadFromCode($idfacturae) && $xmlfacturae->delete()) {
                $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
                return;
            }

            $this->toolBox()->i18nLog()->warning('record-deleted-error');
        };
    }

    protected function downloadFacturaeAction(): Closure
    {
        return function () {
            $xmlfacturae = new XMLfacturae();
            $idfacturae = $this->request->request->get('idfacturae');
            if (false === $xmlfacturae->loadFromCode($idfacturae)) {
                $this->toolBox()->i18nLog()->warning('record-not-found');
                return;
            }

            $this->setTemplate(false);
            $this->response->headers->set('Content-Type', 'application/xml');
            $this->response->headers->set('Content-Disposition', 'attachment;filename=facturae_' . $xmlfacturae->idfactura . '.xsig');
            $this->response->setContent(file_get_contents($xmlfacturae->getFilePath()));
            return false;
        };
    }

    protected function editFacturaeAction(): Closure
    {
        return function () {
            $factura = new FacturaCliente();
            $idfactura = $this->request->query->get('code');
            if (false === $factura->loadFromCode($idfactura)) {
                return;
            }

            $xmlfacturae = new XMLfacturae();
            $idfacturae = $this->request->request->get('idfacturae');
            if (empty($idfacturae) || $xmlfacturae->loadFromCode($idfacturae)) {
                // new record
                $xmlfacturae->idfactura = $factura->idfactura;
            }

            $xmlfacturae->vencimiento = $this->request->request->get('vencimiento');
            $xmlfacturae->iban = $this->request->request->get('iban');
            $xmlfacturae->filereference = $this->request->request->get('filereference');
            $xmlfacturae->observaciones = (bool)$this->request->request->get('observaciones', '0');
            $xmlfacturae->receivertransref = $this->request->request->get('receivertransref', '');
            $xmlfacturae->receivercontraref = $this->request->request->get('receivercontraref', '');

            // oficina contable
            $formData = $this->request->request->all();
            $xmlfacturae->codoficina = $formData['codoficina'];
            if ($xmlfacturae->codoficina) {
                $xmlfacturae->nomoficina = empty($formData['nomoficina']) ? $factura->nombrecliente : $formData['nomoficina'];
                $xmlfacturae->diroficina = empty($formData['diroficina']) ? $factura->direccion : $formData['diroficina'];
                $xmlfacturae->cpoficina = empty($formData['cpoficina']) ? $factura->codpostal : $formData['cpoficina'];
                $xmlfacturae->ciuoficina = empty($formData['ciuoficina']) ? $factura->ciudad : $formData['ciuoficina'];
                $xmlfacturae->proficina = empty($formData['proficina']) ? $factura->provincia : $formData['proficina'];
            } else {
                $xmlfacturae->nomoficina = $xmlfacturae->diroficina = $xmlfacturae->cpoficina = '';
                $xmlfacturae->ciuoficina = $xmlfacturae->proficina = '';
            }

            // órgano gestor
            $xmlfacturae->codorgano = $formData['codorgano'];
            if ($xmlfacturae->codorgano) {
                $xmlfacturae->nomorgano = empty($formData['nomorgano']) ? $factura->nombrecliente : $formData['nomorgano'];
                $xmlfacturae->dirorgano = empty($formData['dirorgano']) ? $factura->direccion : $formData['dirorgano'];
                $xmlfacturae->cporgano = empty($formData['cporgano']) ? $factura->codpostal : $formData['cporgano'];
                $xmlfacturae->ciuorgano = empty($formData['ciuorgano']) ? $factura->ciudad : $formData['ciuorgano'];
                $xmlfacturae->prorgano = empty($formData['prorgano']) ? $factura->provincia : $formData['prorgano'];
            } else {
                $xmlfacturae->nomorgano = $xmlfacturae->dirorgano = $xmlfacturae->cporgano = '';
                $xmlfacturae->ciuorgano = $xmlfacturae->prorgano = '';
            }

            // unidad tramitadora
            $xmlfacturae->codunidad = $formData['codunidad'];
            if ($xmlfacturae->codunidad) {
                $xmlfacturae->nomunidad = empty($formData['nomunidad']) ? $factura->nombrecliente : $formData['nomunidad'];
                $xmlfacturae->dirunidad = empty($formData['dirunidad']) ? $factura->direccion : $formData['dirunidad'];
                $xmlfacturae->cpunidad = empty($formData['cpunidad']) ? $factura->codpostal : $formData['cpunidad'];
                $xmlfacturae->ciuunidad = empty($formData['ciuunidad']) ? $factura->ciudad : $formData['ciuunidad'];
                $xmlfacturae->prunidad = empty($formData['prunidad']) ? $factura->provincia : $formData['prunidad'];
            } else {
                $xmlfacturae->nomunidad = $xmlfacturae->dirunidad = $xmlfacturae->cpunidad = '';
                $xmlfacturae->ciuunidad = $xmlfacturae->prunidad = '';
            }

            // órgano proponente
            $xmlfacturae->desorganop = $formData['desorganop'];
            $xmlfacturae->codorganop = $formData['codorganop'];
            if ($xmlfacturae->codorganop) {
                $xmlfacturae->nomorganop = empty($formData['nomorganop']) ? $factura->nombrecliente : $formData['nomorganop'];
                $xmlfacturae->dirorganop = empty($formData['dirorganop']) ? $factura->direccion : $formData['dirorganop'];
                $xmlfacturae->cporganop = empty($formData['cporganop']) ? $factura->codpostal : $formData['cporganop'];
                $xmlfacturae->ciuorganop = empty($formData['ciuorganop']) ? $factura->ciudad : $formData['ciuorganop'];
                $xmlfacturae->prorganop = empty($formData['prorganop']) ? $factura->provincia : $formData['prorganop'];
            } else {
                $xmlfacturae->nomorganop = $xmlfacturae->dirorganop = $xmlfacturae->cporganop = '';
                $xmlfacturae->ciuorganop = $xmlfacturae->prorganop = '';
            }

            if (false === $xmlfacturae->save()) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                return;
            }

            if ($this->signFacturae($xmlfacturae)) {
                $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                return;
            }

            $xmlfacturae->delete();
        };
    }

    protected function execPreviousAction(): Closure
    {
        return function ($action) {
            switch ($action) {
                case 'delete-facturae':
                    $this->deleteFacturaeAction();
                    break;

                case 'download-facturae':
                    return $this->downloadFacturaeAction();

                case 'edit-facturae':
                    $this->editFacturaeAction();
                    break;

                case 'send-face':
                    $this->sendFaceAction();
                    break;
            }
        };
    }

    protected function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName !== 'facturae') {
                return;
            }

            $mvn = $this->getMainViewName();
            $where = [new DataBaseWhere('idfactura', $this->views[$mvn]->model->primaryColumnValue())];
            $view->loadData('', $where);
            if ($view->model->exists()) {
                if (false === $view->model->signed()) {
                    $view->model->delete();
                    $view->count = 0;
                }
                return;
            }

            // sets default data for the new facturae
            $view->model->iban = $this->views[$mvn]->model->getPaymentMethod()->getBankAccount()->iban;
            $view->model->idfactura = $this->views[$mvn]->model->idfactura;
            $this->setFacturaeValuesFromOldInvoices($view->model);
        };
    }

    protected function sendFaceAction(): Closure
    {
        return function () {
            $xmlfacturae = new XMLfacturae();
            $idfacturae = $this->request->request->get('idfacturae');
            if (false === $xmlfacturae->loadFromCode($idfacturae)) {
                return;
            }

            $fcert = $this->request->files->get('fcert');
            $password = $this->request->request->get('certpass');
            if (empty($fcert) || empty($password)) {
                return;
            }

            if ($xmlfacturae->sendFace($fcert->getPathname(), $password, $this->user->email)) {
                $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            }
        };
    }

    protected function setFacturaeValuesFromOldInvoices(): Closure
    {
        return function ($xmlfacturae) {
            $invoice = new FacturaCliente();
            $mvn = $this->getMainViewName();
            $where = [
                new DataBaseWhere('codcliente', $this->views[$mvn]->model->codcliente),
                new DataBaseWhere('idfactura', $this->views[$mvn]->model->idfactura, '!=')
            ];
            foreach ($invoice->all($where) as $oldInvoice) {
                $xmlFeModel = new XMLfacturae();
                $where2 = [new DataBaseWhere('idfactura', $oldInvoice->idfactura)];
                foreach ($xmlFeModel->all($where2) as $oldXML) {
                    $xmlfacturae->codoficina = $oldXML->codoficina;
                    $xmlfacturae->nomoficina = $oldXML->nomoficina;
                    $xmlfacturae->diroficina = $oldXML->diroficina;
                    $xmlfacturae->cpoficina = $oldXML->cpoficina;
                    $xmlfacturae->ciuoficina = $oldXML->ciuoficina;
                    $xmlfacturae->proficina = $oldXML->proficina;
                    $xmlfacturae->codorgano = $oldXML->codorgano;
                    $xmlfacturae->nomorgano = $oldXML->nomorgano;
                    $xmlfacturae->dirorgano = $oldXML->dirorgano;
                    $xmlfacturae->cporgano = $oldXML->cporgano;
                    $xmlfacturae->ciuorgano = $oldXML->ciuorgano;
                    $xmlfacturae->prorgano = $oldXML->prorgano;
                    $xmlfacturae->codunidad = $oldXML->codunidad;
                    $xmlfacturae->nomunidad = $oldXML->nomunidad;
                    $xmlfacturae->dirunidad = $oldXML->dirunidad;
                    $xmlfacturae->cpunidad = $oldXML->cpunidad;
                    $xmlfacturae->ciuunidad = $oldXML->ciuunidad;
                    $xmlfacturae->prunidad = $oldXML->prunidad;
                    $xmlfacturae->desorganop = $oldXML->desorganop;
                    $xmlfacturae->codorganop = $oldXML->codorganop;
                    $xmlfacturae->nomorganop = $oldXML->nomorganop;
                    $xmlfacturae->dirorganop = $oldXML->dirorganop;
                    $xmlfacturae->cporganop = $oldXML->cporganop;
                    $xmlfacturae->ciuorganop = $oldXML->ciuorganop;
                    $xmlfacturae->prorganop = $oldXML->prorganop;
                }
            }
        };
    }

    protected function signFacturae(): Closure
    {
        return function ($xmlfacturae) {
            $fcert = $this->request->files->get('fcert');
            $password = $this->request->request->get('certpass');
            if (empty($fcert) || empty($password)) {
                return false;
            }

            if (false === $xmlfacturae->sign($fcert->getPathname(), $password)) {
                return false;
            }

            $sendface = (bool)$this->request->request->get('sendface', '0');
            if ($sendface) {
                return $xmlfacturae->sendFace($fcert->getPathname(), $password, $this->user->email);
            }

            return true;
        };
    }
}
