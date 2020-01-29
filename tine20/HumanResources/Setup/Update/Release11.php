<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

class HumanResources_Setup_Update_Release11 extends Setup_Update_Abstract
{
    public function update_0()
    {
        $this->setApplicationVersion('HumanResources', '12.0');
    }
}
