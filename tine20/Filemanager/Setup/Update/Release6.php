<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Filemanager_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update from 6.0 to 6.1
     *
     * @return void
     */
    public function update_0()
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName('Filemanager');

        $filter = new Tinebase_Model_ContainerFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => $app->getId())
        ), 'AND');
        $results = Tinebase_Container::getInstance()->search($filter);

        foreach($results as $container) {
            $container->model = 'Filemanager_Model_Node';
            Tinebase_Container::getInstance()->update($container);
        }
        $this->setApplicationVersion('Filemanager', '6.1');
    }

    /**
     * update to 7.0
     * 
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Filemanager', '7.0');
    }
}
