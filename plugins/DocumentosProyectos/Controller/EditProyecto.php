<?php

namespace FacturaScripts\Plugins\DocumentosProyectos\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

class EditProyecto extends \FacturaScripts\Plugins\Proyectos\Controller\EditProyecto {
    use \FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;

    protected function createViews() {
        parent::createViews();
        $this->createViewsFiles();
    }

    private function createViewsFiles(string $viewName = 'files')
    {
        $this->addHtmlView($viewName, 'Tab/DocFiles', 'AttachedFileRelation', 'files','fas fa-paperclip');
    }

    protected function execPreviousAction($action)
    {
        switch($action) {
            case 'add-file':
                return $this->addFileAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'unlink-file':
                return $this->unlinkFileAction();
        }

        return parent::execPreviousAction($action);
    }

    

    protected function loadData($viewName, $view) {
        switch($viewName) {
            case 'files':
                $code = $this->request->get('code');
                $where = [
                    new DataBaseWhere('model', 'Proyecto'),
                    new DataBaseWhere('modelid', $code)
                ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}