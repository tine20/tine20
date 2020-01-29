<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

/**
 * updates for major release 10
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 11.0
     *
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('ActiveSync', '11.0');
    }
}
