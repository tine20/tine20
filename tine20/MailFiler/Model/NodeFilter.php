<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo 0007376: Tinebase_FileSystem / Node model refactoring: move all container related functionality to Filemanager
 */

/**
 * tree node filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class MailFiler_Model_NodeFilter extends Tinebase_Model_Tree_Node_Filter
{
    /**
     * sets this filter group from filter data in array representation
     *
     * @param array $_data
     */
    public function setFromArray($_data)
    {
        foreach ($_data as $key => &$filterData) {
            if (isset($filterData['field']) && $filterData['field'] === 'foreignRecord' &&
                $filterData['value']['linkType'] === 'relation') {
                if (!isset($filterData['options']) || !is_array($filterData['options'])) {
                    $filterData['options'] = array();
                }
                $filterData['options']['own_model'] = 'MailFiler_Model_Node';
            }
        }

        parent::setFromArray($_data);
    }

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array('name'))
        ),
        'id'                   => array('filter' => 'Tinebase_Model_Filter_Id'),
        'path'                 => array('filter' => 'Tinebase_Model_Tree_Node_PathFilter'),
        'parent_id'            => array('filter' => 'Tinebase_Model_Filter_Text'),
        'name'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'object_id'            => array('filter' => 'Tinebase_Model_Filter_Text'),
    // tree_fileobjects table
        'last_modified_time'   => array(
            'filter' => 'Tinebase_Model_Filter_Date',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'deleted_time'         => array(
            'filter' => 'Tinebase_Model_Filter_DateTime',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'creation_time'        => array(
            'filter' => 'Tinebase_Model_Filter_Date',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'last_modified_by'     => array(
            'filter' => 'Tinebase_Model_Filter_User',
            'options' => array('tablename' => 'tree_fileobjects'
        )),
        'created_by'           => array(
            'filter' => 'Tinebase_Model_Filter_User',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'type'                 => array(
            'filter' => 'Tinebase_Model_Filter_Text', 
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'contenttype'          => array(
            'filter' => 'Tinebase_Model_Filter_Text',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'description'          => array(
            'filter' => 'Tinebase_Model_Filter_Text',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'tag'                  => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'tree_nodes.id',
            'applicationName' => 'Tinebase',
        )),
    // tree_filerevisions table
        'size'                 => array(
            'filter' => 'Tinebase_Model_Filter_Int',
            'options' => array('tablename' => 'tree_filerevisions')
        ),
    // recursive search
        'recursive' => array(
            'filter' => 'Tinebase_Model_Filter_Bool'
        ),
    // message filters
        'subject'       => array('custom' => true),
        'from_email'    => array('custom' => true),
        'from_name'     => array('custom' => true),
        'received'      => array('custom' => true),
        'messageuid'    => array('custom' => true),
        'to'            => array('custom' => true),
        'cc'            => array('custom' => true),
        'bcc'           => array('custom' => true),
        'flags'         => array('custom' => true),
    );

    /**
     * appends custom filters to a given select object
     *
     * @param  Zend_Db_Select                       $_select
     * @param  Felamimail_Backend_Cache_Sql_Message $_backend
     * @return void
     *
     * TODO add query value to message query filter?
     */
    public function appendFilterSql($_select, $_backend)
    {
        $messageFilterArray = array();
        foreach ($this->_customData as $customData) {
            $messageFilterArray[] = array('field' => $customData['field'], 'operator' => $customData['operator'], 'value' => $customData['value']);
        }
        if (count($messageFilterArray) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Message filter data: ' . print_r($messageFilterArray, true));

            $messageFilter = new MailFiler_Model_MessageFilter($messageFilterArray);
            $messageBackend = new MailFiler_Backend_Message();
            $messages = $messageBackend->search($messageFilter);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Found matching nodes: ' . print_r($messages->node_id, true));

            $idFilter = new Tinebase_Model_Filter_Id('id', 'in', $messages->node_id);
            // don't return this filter to the client
            $idFilter->setIsImplicit(true);

            $this->addFilter($idFilter);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . $_select->__toString());
    }
}
