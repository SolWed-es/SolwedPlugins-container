<?php
namespace FacturaScripts\Plugins\MerakiPlugin\Model;

use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Tools;
use finfo;


use FacturaScripts\Core\Model\AttachedFile as ParentAttachedFile;



class AttachedFile extends ParentAttachedFile {

    protected function setFile(): bool
     {
         $this->filename = $this->fixFileName($this->path);
         $newFolder = 'MyFiles/' . date('Y/m', strtotime($this->date));
         $newFolderPath = FS_FOLDER . '/' . $newFolder;
     
         if (false === Tools::folderCheckOrCreate($newFolderPath)) {
             Tools::log()->critical('cant-create-folder', ['%folderName%' => $newFolder]);
             return false;
         }
     
         $currentPath = FS_FOLDER . '/MyFiles/' . $this->path;
         
         if ($this->getStorageLimit() > 0 &&
             filesize($currentPath) + static::getStorageUsed([$this->idfile]) > $this->getStorageLimit()) {
             Tools::log()->critical('storage-limit-reached');
             unlink($currentPath);
             return false;
         }
     
         if (empty($this->path) ||
             false === rename($currentPath, $newFolderPath . '/' . $this->idfile . '.' . $this->getExtension())) {
             return false;
         }
     
         $this->path = $newFolder . '/' . $this->idfile . '.' . $this->getExtension();
         $this->size = filesize($this->getFullPath());
         $info = new finfo();
         $this->mimetype = $info->file($this->getFullPath(), FILEINFO_MIME_TYPE);
         if (strlen($this->mimetype) > 100) {
             $this->mimetype = substr($this->mimetype, 0, 100);
         }
     
         // Guardar el token
         $this->pathtoken = '?myft=' . MyFilesToken::get($this->path ?? '', true);
         return true;
     }
}
