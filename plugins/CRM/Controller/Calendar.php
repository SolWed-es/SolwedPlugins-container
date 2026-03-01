<?php

namespace FacturaScripts\Plugins\CRM\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\CRM\Model\CrmNota;
use FacturaScripts\Core\Tools;

class Calendar extends Controller
{
    public $notes = array();

    private $logLevels = ['critical', 'error', 'info', 'notice', 'warning'];

    public function getPageData(): array{
        $data = parent::getPageData();
        $data['menu'] = 'crm';
        $data['title'] = 'calendar';
        $data['icon'] = 'fa-solid fa-calendar';
        return $data;
    }

    public function privateCore(&$response, $user, $permissions){
        parent::privateCore($response, $user, $permissions);
        $this->loadExtensions();

        $action = $this->request->request->get('action', $this->request->query->get('action', ''));
        if ($this->request->get('ajax', false)) {
            $this->setTemplate(false);
            switch ($action) {
                case 'saveEvent':
                    $data = $this->saveEventAction();
                    break;
                case 'updateEvent':
                    $data = $this->updateEventAction();
                    break;
            }

            $content = array_merge(
                ['messages' => Tools::log()->read('master', $this->logLevels)],
                $data ?? []
            );
            $this->response->setContent(json_encode($content));
        }

    }

    private function loadExtensions(){
        $noteModel = new CrmNota();
        $where = [
            new DataBaseWhere('startdate', NULL, 'IS NOT')
        ];
        $this->notes = $noteModel->all($where, [], 0, 0); //los parámetros son para quitar el límite de 50
    }

    private function saveEventAction(){
        $result = array('saveEvent' => false); //Es falso por defecto, a menos que salga bien y lo cambio
        $id = $this->request->get('id');
        $title = $this->request->get('title');
        $startDate = $this->request->get('startDate');
        $endDate = $this->request->get('endDate');
        $startTime = $this->request->get('startTime');
        $endTime = $this->request->get('endTime');

        $nota = new CrmNota();
        $nota->loadFromCode($id);
        $nota->observaciones = $title;
        $nota->startdate = date('d-m-Y', strtotime($startDate));
        $nota->enddate = date('d-m-Y', strtotime($endDate));
        $nota->starttime = $startTime;
        $nota->endtime = $endTime;
        $nota->nick = $this->user->nick;

        $result['saveEvent'] = $nota->save(); //¿se ha guardado?

        return $result;
    }

    private function updateEventAction(){
        $result = array('updateEvent' => false); //Es falso por defecto, a menos que salga bien y lo cambio

        $id = $this->request->get('id');
        $title = $this->request->get('title');
        $startDate = $this->request->get('startDate');
        $endDate = $this->request->get('endDate');
        $startTime = $this->request->get('startTime');
        $endTime = $this->request->get('endTime');

        $nota = new CrmNota();
        $nota->loadFromCode($id);
        $nota->observaciones = $title;
        $nota->startdate = date('d-m-Y', strtotime($startDate));
        $nota->enddate = date('d-m-Y', strtotime($endDate));
        $nota->starttime = $startTime;
        $nota->endtime = $endTime;

        $result['updateEvent'] = $nota->save(); //¿se ha guardado?

        return $result;
    }
}
