<?php
/**
 * Tine 2.0
 *
 * @package     SimpleFAQ
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class SimpleFAQ_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * update to 7.0
     */
    public function update_0()
    {
        $this->setApplicationVersion('SimpleFAQ', '7.0');
    }
}
