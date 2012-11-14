<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Voipmanager updates for version 6.x
 *
 * @package     Voipmanager
 * @subpackage  Setup
 */
class Voipmanager_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
    * update to 7.0
    *
    * @return void
    */
    public function update_0()
    {
        $this->setApplicationVersion('Voipmanager', '7.0');
    }
}
