<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     *
     * check for container without a model
     */
    public function update_0()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_27();
        $this->setApplicationVersion('Tinebase', '12.1');
    }
}