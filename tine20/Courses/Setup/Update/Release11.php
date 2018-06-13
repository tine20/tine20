<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */
class Courses_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     */
    public function update_0()
    {
        $this->updateKeyFieldIcon(Courses_Config::getInstance(), Courses_Config::INTERNET_ACCESS);

        $this->setApplicationVersion('Courses', '11.1');
    }
}
