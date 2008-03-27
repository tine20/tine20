<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Task Pagination Class
 * @package Tasks
 */
class Tasks_Model_Pagination extends Tinebase_Record_Abstract
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
    protected $_application = 'Tasks';
    
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,  'Int'   ),
        
        'start'                => array('allowEmpty' => true,  'Int'   ),
        'limit'                => array('allowEmpty' => true,  'Int'   ),
        'sort'                 => array('allowEmpty' => true,          ),
        'dir'                  => array('allowEmpty' => true,  'Alpha' ),
    );
    
    protected $_datetimeFields = array(
        'due',
    );
    
}