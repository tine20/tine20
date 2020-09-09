<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

class HumanResources_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     * 
     *  - Extrafreetime days can be negative
     */
    public function update_0()
    {
        $release9 = new HumanResources_Setup_Update_Release9($this->_backend);
        $release9->update_0();
        $this->setApplicationVersion('HumanResources', '10.1');
    }

    /**
     * update to 10.2
     *
     * update report templates node id in config
     */
    public function update_1()
    {
        try {
            $container = Tinebase_Container::getInstance()->get(
                HumanResources_Config::getInstance()->{HumanResources_Config::REPORT_TEMPLATES_CONTAINER_ID},
                /* $_getDeleted */ true
            );
            $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath('HumanResources', Tinebase_FileSystem::FOLDER_TYPE_SHARED);
            $path .= '/' . $container->name;
            $node = Tinebase_FileSystem::getInstance()->stat($path);
            HumanResources_Config::getInstance()->set(HumanResources_Config::REPORT_TEMPLATES_CONTAINER_ID, $node->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
        $this->setApplicationVersion('HumanResources', '10.2');
    }

    public function update_2()
    {
        $this->_backend->alterCol('humanresources_contract', new Setup_Backend_Schema_Field_Xml('<field>
            <name>feast_calendar_id</name>
            <type>text</type>
            <length>40</length>
            <default>null</default>
            <notnull>false</notnull>
        </field>'));

        $this->setTableVersion('humanresources_contract', 3);

        $this->setApplicationVersion('HumanResources', '10.3');
    }

    public function update_3()
    {
        $this->setApplicationVersion('HumanResources', '11.0');
    }
}
