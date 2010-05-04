<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe g.ciyiltepe@metaways.de>
 * @version     $Id: Task.php 13654 2010-03-23 16:06:19Z g.ciyiltepe@metaways.de $
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Scheduler_Task extends Zend_Scheduler_Task 
{
    /**
     * static task getter
     * 
     * @param  array $options
     * @return Tinebase_Scheduler_Task
     */
    public static function getTask(array $options = array())
    {
        return new Tinebase_Scheduler_Task($options);
    }
    
    public function run()
    {
        foreach ($this->getRequests() as $request) {
            $controller = Tinebase_Controller_Abstract::getController($request->getControllerName());
            return call_user_func_array(array($controller, $request->getActionName()), $request->getUserParams());
        }
    }
}
