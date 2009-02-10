<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Phone Filter Class
 * @package Voipmanager
 */
class Voipmanager_Model_Snom_PhoneFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Voipmanager_Model_Snom_PhoneFilter';
    
	/**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    //protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array(
                'fields' => array('macaddress', 'ipaddress', 'description')
            )
        ),
        //'account_id'    => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
    /*
    protected $_validators = array(
        'id'                    => array('allowEmpty' => true,  'Int'   ),

        'macaddress'            => array('allowEmpty' => true           ),
        'ipaddress'             => array('allowEmpty' => true           ),
        'description'           => array('allowEmpty' => true           ),
        'accountId'             => array('allowEmpty' => true           ),
        'query'                 => array('allowEmpty' => true           )
        //'showClosed'          => array('allowEmpty' => true, 'InArray' => array(true,false)),
    );
    */
}