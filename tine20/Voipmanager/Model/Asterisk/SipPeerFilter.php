<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add name/context/username filters to javascript?
 */

/**
 * Asterisk SipPeer Filter Class
 * @package Voipmanager
 */
class Voipmanager_Model_Asterisk_SipPeerFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Voipmanager_Model_Asterisk_SipPeer';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array(
                'fields' => array('name', 'callerid', 'ipaddr')
            )
        ),
        'context'      => array('filter' => 'Tinebase_Model_Filter_Text'),        
        'name'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'defaultuser'  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'type'         => array('filter' => 'Tinebase_Model_Filter_Text')
    );
}