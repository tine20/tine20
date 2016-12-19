<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Sipgate updates for version 0.x
 *
 * @package     Sipgate
 * @subpackage  Setup
 */
class Sipgate_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update 0.1 -> 1.0
     */    
    public function update_1()
    {
        $this->setApplicationVersion('Sipgate', '1.0');
    }
}
