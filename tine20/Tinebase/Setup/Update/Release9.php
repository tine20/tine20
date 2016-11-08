<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * 
     * @see 0011178: allow to lock preferences for individual users
     */
    public function update_0()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_11();
        $this->setApplicationVersion('Tinebase', '9.1');
    }

    /**
     * update to 9.2
     *
     * adds index to relations
     */
    public function update_1()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_12();
        $this->setApplicationVersion('Tinebase', '9.2');
    }

    /**
     * update to 9.3
     *
     * adds ondelete cascade to some indices (tags + roles)
     */
    public function update_2()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_13();
        $this->setApplicationVersion('Tinebase', '9.3');
    }

    /**
     * update to 9.4
     *
     * move keyFieldConfig defaults to config files
     */
    public function update_3()
    {
        $update8 = new Tinebase_Setup_Update_Release8($this->_backend);
        $update8->update_14();
        $this->setApplicationVersion('Tinebase', '9.4');
    }

    /**
     * update to 9.9
     *
     * @see 0012300: add container owner column
     */
    public function update_4()
    {
        if ($this->getTableVersion('container') < 10) {
            $declaration = new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>owner_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>
            ');
            $this->_backend->addCol('container', $declaration);

            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>owner_id</name>
                    <field>
                        <name>owner_id</name>
                    </field>
                </index>
            ');

            $this->_backend->addIndex('container', $declaration);
            $this->setTableVersion('relations', '8');
        }
        $this->setTableVersion('container', '10');

        //Tinebase_Core::getCache()->clean();
        $this->_setContainerOwners();

        $this->setApplicationVersion('Tinebase', '9.5');
    }

    /**
     * write owner to all personal containers
     */
    protected function _setContainerOwners()
    {
        $filter = new Tinebase_Model_ContainerFilter(array(
            array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_PERSONAL),
            array('field' => 'owner_id', 'operator' => 'isnull', 'value' => ''),
        ));
        $count = 0;
        $paging = new Tinebase_Model_Pagination(array(
            'start' => 0,
            'limit' => 100,
        ));
        $containers = Tinebase_Container::getInstance()->search($filter, $paging);
        while (count($containers) > 0) {
            foreach ($containers as $container) {
                $ownerId = Tinebase_Container::getInstance()->getContainerOwner($container);
                if ($ownerId) {
                    $container->owner_id = $ownerId;
                    Tinebase_Container::getInstance()->update($container);
                    $count++;
                }
            }
            $paging->start += $paging->limit;
            $containers = Tinebase_Container::getInstance()->search($filter, $paging);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Set owner for ' . $count . ' containers.');
    }
}
