<?php
/**
 * Tine 2.0
 *
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

class ExampleApplication_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * example update script
     */
    public function update_1()
    {
        // DO SOMETHING HERE
        $this->setApplicationVersion('ExampleApplication', '1.0');
    }
}
