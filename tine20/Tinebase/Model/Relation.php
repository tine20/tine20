<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
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

	protected $_identifier = 'identifier';
	
	/**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
	
    protected $_validators = array(
        'identifier'             => array('presence' => 'required', 'allowEmpty' => true, 'Int' ),
        'created_by'             => array('allowEmpty' => true,  'Int' ),
        'creation_time'          => array('allowEmpty' => true         ),
        'last_modified_by'       => array('allowEmpty' => true         ),
        'last_modified_time'     => array('allowEmpty' => true         ),
        'is_deleted'             => array('allowEmpty' => true         ),
        'deleted_time'           => array('allowEmpty' => true         ),
        'deleted_by'             => array('allowEmpty' => true         ),
	    'own_application'        => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
	    'own_identifier'         => array('presence' => 'required', 'allowEmpty' => false, 'Int'),
	    'related_application'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
	    'related_identifier'     => array('presence' => 'required', 'allowEmpty' => false, 'Int'),
        'related_role'           => array('presence' => 'required', 'allowEmpty' => false, 'Alpha')
	);
    

} // end of Tinebase_Model_Relation
?>
