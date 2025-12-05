 <?php
 namespace FacturaScripts\Plugins\MerakiPlugin\Extension\Model;

use FacturaScripts\Core\Base\MyFilesToken;
use Closure;

class AttachedFile {

    protected function setFile() : Closure
    {  
        return function() {
            $this->pathtokenplugin = $this->path . '?myft=' . MyFilesToken::get($this->path ?? '', false);
        };


    } 
} 











