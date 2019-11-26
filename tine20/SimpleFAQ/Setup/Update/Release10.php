<?php
/**
 * Tine 2.0
 *
 * @package     SimpleFAQ
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);

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

    /**
     * update to 11.0
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('SimpleFAQ', '11.0');
    }
}
