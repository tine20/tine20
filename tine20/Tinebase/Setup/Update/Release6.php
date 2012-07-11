<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update to 6.1
     * - changepw config option has moved
     */
    public function update_0()
    {
        $changepwSetting = Tinebase_User::getBackendConfiguration('changepw', TRUE);
        if (! $changepwSetting) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::PASSWORD_CHANGE, FALSE);
        }
        
        $this->setApplicationVersion('Tinebase', '6.1');
    }
    
    /**
     * update to 6.2
     *  - apply 5.11 if nessesary
     */
    public function update_1()
    {
        if ($this->getTableVersion('alarm') < 3) {
            $update = new Tinebase_Setup_Update_Release5($this->_backend);
            $update->update_10();
        }
        
        $this->setApplicationVersion('Tinebase', '6.2');
    }
}
