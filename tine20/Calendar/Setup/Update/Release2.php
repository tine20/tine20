<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Calendar_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * update to version 3.0
     */
    public function update_0()
    {
        $this->setApplicationVersion('Calendar', '3.0');
    }
}
