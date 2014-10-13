<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */
class Phone_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 9.0
     *
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Phone', '9.0');
    }
}
