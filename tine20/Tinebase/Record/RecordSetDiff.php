<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_Record_RecordSetDiff
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property string model
 * @property Tinebase_Record_RecordSet added
 * @property Tinebase_Record_RecordSet removed
 * @property Tinebase_Record_RecordSet modified
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
        'added'             => array('allowEmpty' => TRUE), // RecordSet of records _('added')
        'removed'           => array('allowEmpty' => TRUE), // RecordSet of records _('removed')
        'modified'          => array('allowEmpty' => TRUE), // RecordSet of Tinebase_Record_Diff  _('modified')
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

    /**
     * returns human readable diff text
     * 
     * @return string
     * 
     * @todo add translated model name?
     */
    public function getTranslatedDiffText()
    {
        $result = array();
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $model = $this->model;
        foreach (array('added', 'removed', 'modified') as $action) {
            if (count($this->{$action}) > 0) {
                $str = count($this->{$action}) . ' ' . $translate->_($action) . ': ';
                $first = true;
                foreach ($this->{$action} as $data) {
                    /** @var Tinebase_Record_Interface $record */
                    $record = new $model($data, true);
                    $str .= ($first ? '' : ', ') . $record->getNotesTranslatedText();
                    $first = false;
                }
                $result[] = $str;
            }
        }
        
        return implode(' - ', $result);
    }
}
