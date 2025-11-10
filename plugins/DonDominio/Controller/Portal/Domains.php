<?php

namespace FacturaScripts\Plugins\DonDominio\Controller\Portal;

use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Controller\PortalController;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\DonDominio\Lib\DonDominioDomainService;
use FacturaScripts\Plugins\DonDominio\Model\ClienteDonDominio;

class Domains extends PortalController
{
    private const AUTORENEW_CONTRACT_URL = 'https://filedn.eu/litOB0SUT8q5aLOM933djFm/Contrato%20domiciliaci%C3%B3n-Solwed.pdf';

    /** @var array */
    public $domains = [];

    /** @var ClienteDonDominio */
    public $customer;

    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'Mis Dominios';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fas fa-globe';
        $pageData['showonmenu'] = false;
        return $pageData;
    }

    protected function createViews()
    {
        parent::createViews();
        
        // Cargar el cliente
        $this->customer = new ClienteDonDominio();
        $contact = $this->contact;
        $baseCustomer = $contact->getCustomer(false);
        
        if ($baseCustomer instanceof Cliente && $baseCustomer->exists()) {
            $this->customer->load($baseCustomer->codcliente);
        }

        // Cargar dominios
        $this->domains = DonDominioDomainService::getStoredDomains($this->customer);
    }

    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'download-autorenew-contract':
                return $this->downloadAutoRenewContractAction();

            case 'get-domain-authcode':
                return $this->getDomainAuthcodeAction();

            case 'get-domain-transfer-lock':
                return $this->getDomainTransferLockAction();

            case 'update-domain-transfer-lock':
                return $this->updateDomainTransferLockAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function downloadAutoRenewContractAction(): bool
    {
        if (false === $this->validateFormToken()) {
            Tools::log()->warning('invalid-request');
            return true;
        }

        $domainId = (int)$this->request->request->get('domainId');
        $domain = $this->findDomainForContact($domainId);
        
        if (!$domain instanceof ClienteDonDominio) {
            Tools::log()->warning('record-not-found');
            return true;
        }

        if ($domain->autorenew) {
            Tools::log()->notice('dondominio-domain-autorenew-enabled', ['%domain%' => $domain->domain]);
            return true;
        }

        $this->redirect(self::AUTORENEW_CONTRACT_URL);
        return false;
    }

    protected function getDomainAuthcodeAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        if (null === $this->request->request->get('domainId')) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $domain = $this->findDomainForContact((int)$this->request->request->get('domainId'));
        if (!$domain instanceof ClienteDonDominio) {
            Tools::log()->warning('record-not-found');
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('record-not-found')]);
        }

        $lockedError = null;
        $locked = DonDominioDomainService::getTransferLock($domain, $lockedError);
        if ($locked === true) {
            $message = Tools::lang()->trans('dondominio-authcode-locked');
            Tools::log()->warning('dondominio-authcode-locked', ['%domain%' => $domain->domain]);
            return $this->jsonResponse(['success' => false, 'message' => $message]);
        }

        $error = null;
        $code = DonDominioDomainService::getAuthCode($domain, $error);
        if (null === $code) {
            $message = Tools::lang()->trans('dondominio-domain-authcode-error', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? 'unknown',
            ]);
            Tools::log()->error('dondominio-domain-authcode-error', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? 'unknown',
            ]);
            return $this->jsonResponse(['success' => false, 'message' => $message]);
        }

        Tools::log()->notice('dondominio-domain-authcode', [
            '%domain%' => $domain->domain,
            '%authcode%' => $code,
        ]);

        return $this->jsonResponse([
            'success' => true,
            'code' => $code,
            'domain' => $domain->domain,
        ]);
    }

    protected function getDomainTransferLockAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        if (null === $this->request->request->get('domainId')) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $domain = $this->findDomainForContact((int)$this->request->request->get('domainId'));
        if (!$domain instanceof ClienteDonDominio) {
            Tools::log()->warning('record-not-found');
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('record-not-found')]);
        }

        $error = null;
        $enabled = DonDominioDomainService::getTransferLock($domain, $error);
        if (null === $enabled) {
            $message = Tools::lang()->trans('dondominio-domain-transfer-lock-unknown', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
            ]);
            if (!empty($error)) {
                $message .= ' (' . $error . ')';
            }

            Tools::log()->warning('dondominio-domain-transfer-lock-unknown', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? '',
            ]);

            return $this->jsonResponse(['success' => false, 'message' => $message]);
        }

        return $this->jsonResponse([
            'success' => true,
            'enabled' => $enabled,
            'domain' => $domain->domain,
            'enabledMessage' => Tools::lang()->trans('dondominio-transfer-lock-enabled-label'),
            'disabledMessage' => Tools::lang()->trans('dondominio-transfer-lock-disabled-label'),
        ]);
    }

    protected function updateDomainTransferLockAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        if (null === $this->request->request->get('domainId') || null === $this->request->request->get('enabled')) {
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('invalid-request')]);
        }

        $domain = $this->findDomainForContact((int)$this->request->request->get('domainId'));
        if (!$domain instanceof ClienteDonDominio) {
            Tools::log()->warning('record-not-found');
            return $this->jsonResponse(['success' => false, 'message' => Tools::lang()->trans('record-not-found')]);
        }

        $enabled = (bool)$this->request->request->get('enabled');
        $error = null;
        $result = DonDominioDomainService::setTransferLock($domain, $enabled, $error);

        if (false === $result) {
            $message = Tools::lang()->trans('dondominio-domain-transfer-lock-error', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? 'unknown',
            ]);
            Tools::log()->error('dondominio-domain-transfer-lock-error', [
                '%domain%' => $domain->domain ?: (string)$domain->id,
                '%message%' => $error ?? 'unknown',
            ]);
            return $this->jsonResponse(['success' => false, 'message' => $message]);
        }

        $message = $enabled
            ? Tools::lang()->trans('dondominio-domain-transfer-lock-enabled', ['%domain%' => $domain->domain])
            : Tools::lang()->trans('dondominio-domain-transfer-lock-disabled', ['%domain%' => $domain->domain]);

        Tools::log()->notice($enabled ? 'dondominio-domain-transfer-lock-enabled' : 'dondominio-domain-transfer-lock-disabled', [
            '%domain%' => $domain->domain,
        ]);

        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
        ]);
    }

    private function findDomainForContact(int $domainId): ?ClienteDonDominio
    {
        // TODO: Implementar la lógica para buscar el dominio por ID
        // Esto es un placeholder - necesitarás adaptarlo a tu estructura de datos actual
        return null;

        // Verificar que el dominio pertenece al cliente actual
        if ($domain->codcliente !== $this->customer->codcliente) {
            return null;
        }

        return $domain;
    }
}
