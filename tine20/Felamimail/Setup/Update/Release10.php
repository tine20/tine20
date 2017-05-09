<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * change signature to medium text
     */
    public function update_0()
    {
        $update9 = new Felamimail_Setup_Update_Release9($this->_backend);
        $update9->update_2();
        $this->setApplicationVersion('Felamimail', '10.1');
    }

    /**
     * update to 10.2
     *
     * @see 0002284: add reply-to setting to email account
     */
    public function update_1()
    {
        $update9 = new Felamimail_Setup_Update_Release9($this->_backend);
        $update9->update_3();
        $this->setApplicationVersion('Felamimail', '10.2');
    }

    /**
     * update to 10.3
     *
     * update vacation templates node id in config
     */
    public function update_2()
    {
        try {
            $container = Tinebase_Container::getInstance()->get(
                Felamimail_Config::getInstance()->{Felamimail_Config::VACATION_TEMPLATES_CONTAINER_ID},
                /* $_getDeleted */ true
            );
            $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Felamimail', Tinebase_FileSystem::FOLDER_TYPE_SHARED);
            $path .= '/' . $container->name;
            $node = Tinebase_FileSystem::getInstance()->stat($path);
            Felamimail_Config::getInstance()->set(Felamimail_Config::VACATION_TEMPLATES_CONTAINER_ID, $node->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
        $this->setApplicationVersion('Felamimail', '10.3');
    }
}
