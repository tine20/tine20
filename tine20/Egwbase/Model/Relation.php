<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class Egwbase_Model_Relation
 */
class Egwbase_Model_Relation extends Egwbase_Record_Abstract 
{

	protected $_identifier = 'identifier';
	
	/**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Egwbase';
	
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
        'own_role'               => array('presence' => 'required', 'allowEmpty' => false, 'Alpha'),
	    'related_application'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
	    'related_identifier'     => array('presence' => 'required', 'allowEmpty' => false, 'Int')
	);
    

} // end of Egwbase_Model_Relation
?>
