<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * updates for major release 12
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     *
     * @return void
     */
    public function update_0()
    {
        $release11 = new ActiveSync_Setup_Update_Release11($this->_backend);
        $release11->update_0();
        $this->setApplicationVersion('ActiveSync', '12.1');
    }
}
