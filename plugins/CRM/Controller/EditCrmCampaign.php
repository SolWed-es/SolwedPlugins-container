<?php
/**
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2025 SolWed <dev@solwed.es> — Maintained by SolWed
 */

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Plugins\CRM\CronJob\SendCampaign;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class EditCrmCampaign extends EditController
{
    use DocFilesTrait;

    public function getModelClassName(): string
    {
        return 'CrmCampaign';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'campaign';
        $data['icon'] = 'fa-solid fa-envelope-open-text';
        return $data;
    }

    protected function addContactsAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->error('permission-denied');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $campaign = $this->getModel();
        if (false === $campaign->loadFromCode($this->request->get('code'))) {
            Tools::log()->error('record-not-found');
            return true;
        }

        $outgoing_email = $this->request->request->get('outgoing-email', '');
        $id_list = (int)$this->request->request->get('import-list', 0);
        $campaign->addList($id_list, $outgoing_email);

        return true;
    }

    protected function createViews()
    {
        parent::createViews();

        $this->setTabsPosition('bottom');

        $this->createViewsEmail();
        $this->createViewDocFiles();
    }

    protected function createViewsEmail(string $viewName = 'ListCrmCampaignEmail'): void
    {
        $this->addListView($viewName, 'CrmCampaignEmail', 'contacts', 'fa-solid fa-users')
            ->addOrderBy(['sent'], 'sent', 2)
            ->addSearchFields(['email'])
            ->setSettings('btnNew', false)
            ->addFilterSelectWhere('status', [
                ['label' => Tools::trans('all'), 'where' => []],
                ['label' => '------', 'where' => []],
                [
                    'label' => Tools::trans('sent'),
                    'where' => [new DataBaseWhere('sent', null, 'IS NOT')]
                ],
                [
                    'label' => Tools::trans('not-sent'),
                    'where' => [new DataBaseWhere('sent', null, 'IS')]
                ],
            ]);
    }

    protected function execPreviousAction($action): bool
    {
        switch ($action) {
            case 'add-contacts':
                return $this->addContactsAction();

            case 'add-file':
                return $this->addFileAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'send-test':
                return $this->sendTestAction();

            case 'unlink-file':
                return $this->unlinkFileAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();
        $id = $this->getViewModelValue($mvn, 'id');

        switch ($viewName) {
            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $this->getModel()->primaryColumnValue());
                break;

            case 'ListCrmCampaignEmail':
                $where = [new DataBaseWhere('id_campaign', $id)];
                $view->loadData('', $where);
                break;

            case $mvn:
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    break;
                }

                $this->loadSelectOutgoingEmail($viewName);
                $this->addButton($viewName, [
                    'action' => 'send-test',
                    'color' => 'warning',
                    'icon' => 'fa-regular fa-envelope',
                    'label' => 'test',
                    'type' => 'modal',
                ]);
                $this->loadSelectOutgoingEmail('ListCrmCampaignEmail');
                $this->addButton('ListCrmCampaignEmail', [
                    'action' => 'add-contacts',
                    'color' => 'success',
                    'icon' => 'fa-solid fa-plus',
                    'label' => 'add-contacts',
                    'type' => 'modal',
                ]);
                break;
        }
    }

    protected function loadSelectOutgoingEmail(string $viewName): void
    {
        $column = $this->tab($viewName)->columnModalForName('outgoing-email');
        if (empty($column) || $column->widget->getType() !== 'select') {
            Tools::log()->warning('column-not-found', [
                'view' => $viewName,
                'column' => 'outgoing-email',
            ]);
            return;
        }

        $mail = new NewMail();
        $customValues = [];
        foreach ($mail->getAvailableMailboxes() as $mailbox) {
            $customValues[] = [
                'value' => $mailbox,
                'title' => $mailbox,
            ];
        }
        $column->widget->setValuesFromArray($customValues);
    }

    protected function sendTestAction(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->error('permission-denied');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $campaign = $this->getModel();
        if (false === $campaign->loadFromCode($this->request->get('code'))) {
            Tools::log()->error('record-not-found');
            return true;
        }

        $outgoing_email = $this->request->request->get('outgoing-email', '');
        $email = $this->request->request->get('test-email', '');
        if (empty($email)) {
            Tools::log()->error('email-empty');
            return true;
        } elseif (false === Validator::email($email)) {
            Tools::log()->error('email-invalid');
            return true;
        }

        // mandamos el email
        $sent = SendCampaign::sendMail($campaign, $outgoing_email, $email, $this->user->nick);
        if (false === $sent) {
            Tools::log()->warning('email-not-sent');
            return true;
        }

        Tools::log()->notice('email-sent');
        return true;
    }
}
