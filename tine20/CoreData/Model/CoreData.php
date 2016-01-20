<?php
/**
 * @package     CoreData
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of a core data record
 *
 * @package CoreData
 */
class CoreData_Model_CoreData extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the field which
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
    protected $_application = 'CoreData';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty' => false         ),
        'application_id'       => array('allowEmpty' => false         ),
        'model'                => array('allowEmpty' => true          ),
        'label'                => array('allowEmpty' => true          ),
    // not used yet
        'filter'               => array('allowEmpty' => true          ),
    );
}
