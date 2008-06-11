<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_Model_Relation
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Relation extends Tinebase_Record_Abstract 
{

	protected $_identifier = 'id';
	
	/**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
	
    protected $_validators = array(
        'id'                     => array('allowEmpty' => true,  'Alnum'),
        'created_by'             => array('allowEmpty' => true,  'Int'  ),
        'creation_time'          => array('allowEmpty' => true          ),
        'last_modified_by'       => array('allowEmpty' => true,  'Int'  ),
        'last_modified_time'     => array('allowEmpty' => true          ),
        'is_deleted'             => array('allowEmpty' => true          ),
        'deleted_time'           => array('allowEmpty' => true          ),
        'deleted_by'             => array('allowEmpty' => true,  'Int'  ),
	    'own_application'        => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
	    'own_id'                 => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
	    'related_application'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
	    'related_id'             => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
        'related_role'           => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
        'related_record'         => array('allowEmpty' => true) // property to store 'resolved' relation record
	);
    

} // end of Tinebase_Model_Relation
?>
