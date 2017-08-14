<?php
/**
 * Tine 2.0
 *
 * @package     SimpleFAQ
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class SimpleFAQ_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * @return void
     */
    public function update_0()
    {
        if ($this->getTableVersion('simple_faq') < 3) {
            $this->setTableVersion('simple_faq', 3);
        }
        $this->setApplicationVersion('SimpleFAQ', '10.1');
    }
}
