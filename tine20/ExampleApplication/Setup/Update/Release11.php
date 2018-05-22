<?php
/**
 * Tine 2.0
 *
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class ExampleApplication_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * example update script
     */
    public function update_0()
    {
        $this->setApplicationVersion('ExampleApplication', '12.0');
    }
}
