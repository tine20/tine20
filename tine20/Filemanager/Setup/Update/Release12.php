<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Filemanager_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.0
     *
     * @return void
     */
    public function update_0()
    {
        $limit = 1000;
        $start = 0;
        $fmc = Filemanager_Controller::getInstance();

        do {
            $users = Tinebase_User::getInstance()->getFullUsers(null, 'accountId', 'ASC', $start, $limit);
            foreach ($users as $user) {
                $fmc->createPersonalFileFolder($user, 'Filemanager');
            }
            $start += $limit;
        } while ($users->count() === $limit);

        $this->setApplicationVersion('Filemanager', '12.1');
    }
}