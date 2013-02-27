<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * - add default grant for anyone to resources
     */
    public function update_0()
    {
        Calendar_Controller_Resource::getInstance()->doContainerACLChecks(FALSE);
        $resources = Calendar_Controller_Resource::getInstance()->getAll();
        foreach ($resources as $resource) {
            $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($resource->container_id, TRUE);
            if (count($grants) === 0) {
                $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
                    'account_type' => 'anyone',
                    'account_id' => 0,
                    'readGrant' => TRUE
                )));
                $result = Tinebase_Container::getInstance()->setGrants($resource->container_id, $grants, TRUE, FALSE);
                
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Added anyone grant (READ) for resource ' . $resource->name);
            }
        }
        
        $this->setApplicationVersion('Calendar', '7.1');
    }
}
