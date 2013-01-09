<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Filemanager_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * 
     * - set container model once again
     * @see 0007400: Newly created directories disappear
     * 
     * @return void
     */
    public function update_0()
    {
        $update6 = new Filemanager_Setup_Update_Release6($this->_backend);
        $update6->update_0();
        
        $this->setApplicationVersion('Filemanager', '7.1');
    }
}
