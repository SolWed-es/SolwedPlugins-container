<?php
namespace FacturaScripts\Plugins\HumanResourcesSolwed\Controller;

use FacturaScripts\Core\Controller\EditUser as ParentController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\RoleUser;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Cookie;

class EditUser extends EditController
{

    /**
 * Return a list of pages where user has access.
 *
 * @param User $user
 *
 * @return array
 */
protected function getUserPages(User $user): array
{
    $pageList = [];
    if ($user->admin) {
        $pageModel = new Page();
        foreach ($pageModel->all([], ['name' => 'ASC'], 0, 0) as $page) {
            if (false === $page->showonmenu) {
                continue;
            }

            $pageList[] = ['value' => $page->name, 'title' => $page->name];
        }

        return $pageList;
    }

    $roleUserModel = new RoleUser();
    foreach ($roleUserModel->all([new DataBaseWhere('nick', $user->nick)]) as $roleUser) {
        foreach ($roleUser->getRoleAccess() as $roleAccess) {
            $page = $roleAccess->getPage();
            if (false === $page->exists() || false === $page->showonmenu) {
                continue;
            }

            $pageList[$roleAccess->pagename] = ['value' => $roleAccess->pagename, 'title' => $roleAccess->pagename];
        }
    }

    return $pageList;
}
    public function getImageUrl(): string
    {
        $mvn = $this->getMainViewName();
        return $this->views[$mvn]->model->gravatar();
    }

    public function getModelClassName(): string
    {
        return 'User';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'user';
        $data['icon'] = 'fas fa-user-circle';
        return $data;
    }

    protected function createViews()
    {
        // Mensaje de prueba
        Tools::log()->notice("Iniciando la carga de vistas de usuarios.");

        // Obtener el rol del usuario desde la tabla roles_user
        $roleUserModel = new RoleUser();
        $userRoles = $roleUserModel->all([new DataBaseWhere('nick', $this->user->nick)]);

        // Determinar el grupo del usuario
        $userGroup = 'regular user'; // Valor por defecto
        if (!empty($userRoles)) {
            $userGroup = $userRoles[0]->codrole; 
        }

        // Log el grupo del usuario
        Tools::log()->notice("User group: " . $userGroup); // Log warning

        // Mensaje de prueba
        Tools::log()->notice("Finalizando la carga de vistas de usuarios.");

        parent::createViews();
        $this->setTabsPosition('top');

        // Disable company column if there is only one company
        $mvn = $this->getMainViewName();
        if ($this->empresa->count() < 2) {
            $this->views[$mvn]->disableColumn('company');
        }

        // Disable warehouse column if there is only one company
        $almacen = new Almacen();
        if ($almacen->count() < 2) {
            $this->views[$mvn]->disableColumn('warehouse');
        }

        // Disable options and print buttons
        $this->setSettings($mvn, 'btnOptions', false);
        $this->setSettings($mvn, 'btnPrint', false);

        // Add roles tab
        if ($this->user->admin || $this->user->rrhh) {
            $this->createViewsRole();
        }

        // Add page options tab
        $this->createViewsPageOptions();

        // Add emails tab
        $this->createViewsEmails();
    }

    private function allowUpdate(): bool
    {
        // Preload user data
        $code = $this->request->request->get('code', $this->request->query->get('code'));
        $user = new User();
        if (false === $user->loadFromCode($code)) {
            // User not found, maybe it is a new user, so only admin or rrhh can create it
            return $this->user->admin || $this->isUserInRole('rrhh');
        }
    
        // Check if the user being edited is the admin
        if ($user->nick === 'admin') { // Cambia 'admin' por el nick real del admin si es diferente
            return $this->user->admin; // Solo admin puede editar al admin
        }
    
        // Admin and rrhh can update all other users
        if ($this->user->admin || $this->isUserInRole('rrhh')) {
            return true;
        }
    
        // Non-admin users can only update their own data
        return $user->nick === $this->user->nick;
    }
    
    private function isUserInRole(string $role): bool
    {
        $roleUserModel = new RoleUser();
        $userRoles = $roleUserModel->all([new DataBaseWhere('nick', $this->user->nick)]);
    
        foreach ($userRoles as $userRole) {
            if ($userRole->codrole === $role) {
                return true;
            }
        }
        return false;
    }

    protected function insertAction(): bool
    {
        // Allow both admin and rrhh users to create users
        $this->permissions->allowUpdate = $this->user->admin || $this->user->rrhh;
        return parent::insertAction();
    }

    protected function deleteAction(): bool
    {
        // Allow both admin and rrhh users to delete users
        if ($this->user->admin || $this->isUserInRole('rrhh')) {
            // Prevent admin from deleting themselves
            if ($this->user->nick === $this->request->request->get('code')) {
                return false; // No se permite eliminar al admin
            }
            $this->permissions->allowDelete = true;
        } else {
            $this->permissions->allowDelete = false;
        }
        
        return parent::deleteAction();
    }

    protected function createViewsEmails(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fas fa-envelope')
            ->addOrderBy(['date'], 'date', 2)
            ->addSearchFields(['addressee', 'body', 'subject']);

        // Disable the recipient column
        $this->views[$viewName]->disableColumn('user');

        // Disable the new button
        $this->setSettings($viewName, 'btnNew', false);

        // Filters
        $this->listView($viewName)->addFilterPeriod('period', 'date', 'date', true);
    }

    protected function createViewsPageOptions(string $viewName = 'ListPageOption'): void
    {
        $this->addListView($viewName, 'PageOption', 'options', 'fas fa-wrench')
            ->addOrderBy(['name'], 'name', 1)
            ->addOrderBy(['last_update'], 'last-update')
            ->addSearchFields(['name']);

        // Disable the new button
        $this->setSettings($viewName, 'btnNew', false);
    }

    protected function createViewsRole(string $viewName = 'EditRoleUser'): void
    {
        $this->addEditListView($viewName, 'RoleUser', 'roles', 'fas fa-address-card');
        $this->views[$viewName]->setInLine('true');

        // Disable column
        $this->views[$viewName]->disableColumn('user', true);
    }

    protected function editAction(): bool
    {
        $this->permissions->allowUpdate = $this->allowUpdate();

        // Prevent some user changes
        if ($this->request->request->get('code', '') === $this->user->nick) {
            if ($this->user->admin != (bool)$this->request->request->get('admin')) {
                // Prevent user from becoming admin
                $this->permissions->allowUpdate = false;
            } elseif ($this->user->enabled != (bool)$this->request->request->get('enabled')) {
                // Prevent user from disabling himself
                $this->permissions->allowUpdate = false;
            }
        }
        $result = parent::editAction();

        // Are we changing user language?
        if ($result && $this->views['EditUser']->model->nick === $this->user->nick) {
            Tools::lang()->setLang($this->views['EditUser']->model->langcode);

            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(
                Cookie::create(
                    'fsLang',
                    $this->views['EditUser']->model->langcode,
                    $expire,
                    Tools::config('route', '/')
                )
            );
        }

        return $result;
    }

    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();
        $nick = $this->getViewModelValue($mvn, 'nick');

        switch ($viewName) {
            case 'EditRoleUser':
                $where = [new DataBaseWhere('nick', $nick)];
                $view->loadData('', $where, ['id' => 'DESC']);
                break;

            case 'EditUser':
                parent::loadData($viewName, $view);
                $this->loadHomepageValues();
                $this->loadLanguageValues();
                if (false === $this->allowUpdate()) {
                    $this->setTemplate('Error/AccessDenied');
                } elseif ($view->model->nick == $this->user->nick) {
                    // Prevent user self-destruction
                    $this->setSettings($viewName, 'btnDelete', false);
                }
                // If the user is admin, hide the EditRoleUser tab
                if ($view->model->admin && array_key_exists('EditRoleUser', $this->views)) {
                    $this->setSettings('EditRoleUser', 'active', false);
                }
                break;

            case 'ListEmailSent':
                $where = [new DataBaseWhere('nick', $nick)];
                $view->loadData('', $where);
                break;

            case 'ListPageOption':
                $where = [
                    new DataBaseWhere('nick', $nick),
                    new DataBaseWhere('nick', null, 'IS', 'OR'),
                ];
                $view->loadData('', $where);
                break;
        }
    }

    protected function loadHomepageValues(): void
    {
        if (false === $this->views['EditUser']->model->exists()) {
            $this->views['EditUser']->disableColumn('homepage');
            return;
        }

        $columnHomepage = $this->views['EditUser']->columnForName('homepage');
        if ($columnHomepage && $columnHomepage->widget->getType() === 'select') {
            $userPages = $this->getUserPages($this->views['EditUser']->model);
            $columnHomepage->widget->setValuesFromArray($userPages, false, true);
        }
    }

    protected function loadLanguageValues(): void
    {
        $columnLangCode = $this->views['EditUser']->columnForName('language');
        if ($columnLangCode && $columnLangCode->widget->getType() === 'select') {
            $langs = [];
            foreach (Tools::lang()->getAvailableLanguages() as $key => $value) {
                $langs[] = ['value' => $key, 'title' => $value];
            }

            $columnLangCode->widget->setValuesFromArray($langs, false);
        }
    }
}