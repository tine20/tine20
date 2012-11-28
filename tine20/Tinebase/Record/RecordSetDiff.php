<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_Record_RecordSetDiff
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_RecordSetDiff extends Tinebase_Record_Abstract 
{
    /**
     * identifier field name
     *
     * @var string
     */
    protected $_identifier = 'model';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'model'             => array('allowEmpty' => TRUE),
        'added'             => array('allowEmpty' => TRUE), // RecordSet of records
        'removed'           => array('allowEmpty' => TRUE), // RecordSet of records
        'modified'          => array('allowEmpty' => TRUE), // RecordSet of Tinebase_Record_Diff
    );
    
    /**
     * is empty (no difference)
     * 
     * @return boolean
     */
    public function isEmpty()
    {
        return (count($this->added)    === 0 &&
                count($this->removed)  === 0 &&
                count($this->modified) === 0);
    }
}
