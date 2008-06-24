<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */

/**
 * Template Filter Class
 * @package Voipmanager
 */
class Voipmanager_Model_SnomTemplateFilter extends Tinebase_Record_Abstract
{
	/**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Voipmanager';
    
    protected $_validators = array(
        'id'                    => array('allowEmpty' => true,  'Int'   ),

        'model'                 => array('allowEmpty' => true           ),
        'description'           => array('allowEmpty' => true           ),
        'query'                 => array('allowEmpty' => true           )
    );
}