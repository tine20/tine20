<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class MailFiler_Convert_Node_Json extends Tinebase_Convert_Tree_Node_Json
{
    /**
     * resolves child records before converting the record set to an array
     * 
     * @param Tinebase_Record_RecordSet $records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     */
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);

        $this->_resolveMessages($records, $multiple);
    }

    /**
     * @param $records
     * @param boolean $multiple single or multiple nodes
     */
    protected function _resolveMessages($records, $multiple)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Resolving mails of nodes ....');

        $filter = new MailFiler_Model_MessageFilter(array(array('field' => 'node_id', 'operator' => 'in', 'value' => $records->getArrayOfIds())));
        $messages = MailFiler_Controller_Message::getInstance()->search($filter);
        foreach ($messages as $message) {
            $idx = $records->getIndexById($message->node_id);
            if (isset($idx) && $idx !== FALSE) {
                if (! $multiple) {
                    // only fetch body & attachments in single mode
                    // TODO body2html?
                    // TODO add config/preference for format?
                    $message->body = MailFiler_Controller_Message::getInstance()->getMessageBodyFromNode($message, $records[$idx]);
                    $message->attachments = MailFiler_Controller_Message::getInstance()->getAttachments($message);
                }
                $records[$idx]->message = $message;
            }
        }
    }
}
