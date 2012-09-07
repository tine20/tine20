<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * updates for major release 0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * update to 3.0
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('ActiveSync', '3.0');
    }
}
