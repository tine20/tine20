<?php
/**
 * Syncroton
 *
 * @package     Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Backend
 */
 
class Syncroton_Backend_Policy extends Syncroton_Backend_ABackend #implements Syncroton_Backend_IDevice
{
    protected $_tableName = 'policy';
    
    protected $_modelClassName = 'Syncroton_Model_Policy';
    
    protected $_modelInterfaceName = 'Syncroton_Model_IPolicy';
    
    /**
     * convert iteratable object to array
     * 
     * @param  unknown   $model
     * @return array
     */
    protected function _convertModelToArray($model)
    {
        $policyValues = $model->getProperties('Provision');
        
        $policy = array();
        
        foreach ($policyValues as $policyName) {
            if ($model->$policyName !== NULL) { 
                $policy[$policyName] = $model->$policyName;
            }
            
            unset($model->$policyName);
        }

        $data = parent::_convertModelToArray($model);
        
        $data['json_policy'] = Zend_Json::encode($policy);
        
        return $data;
    }
    
    /**
     * convert array to object
     * 
     * @param  array  $data
     * @return object
     */
    protected function _getObject($data)
    {
        $policy = Zend_Json::decode($data['json_policy']);
        
        foreach ($policy as $policyKey => $policyValue) {
            $data[$policyKey] = $policyValue;
        }
        
        unset($data['json_policy']);
        
        return parent::_getObject($data);
    }
}
