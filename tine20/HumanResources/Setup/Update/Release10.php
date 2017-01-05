<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */
class HumanResources_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     * 
     *  - Extrafreetime days can be negative
     */
    public function update_0()
    {
        $release9 = new HumanResources_Setup_Update_Release9($this->_backend);
        $release9->update_0();
        $this->setApplicationVersion('HumanResources', '10.1');
    }
}
