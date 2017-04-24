<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_Tree_Node_Json extends Tinebase_Convert_Json
{
    /**
     * resolves child records after converting the record set to an array
     *
     * @param array $result
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     *
     * @return array
     */
    protected function _resolveAfterToArray($result, $modelConfiguration, $multiple = false)
    {
        $result = parent::_resolveAfterToArray($result, $modelConfiguration, $multiple);
        $result = $this->_resolveGrants($result);
        return $result;
    }


    protected function _resolveGrants($result)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Resolving grants of nodes ....');

        if (isset($result['grants'])) {
            $result['grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($result['grants']);
        } else {
            foreach ($result as &$record) {
                if (isset($record['grants'])) {
                    $record['grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($record['grants']);
                }
            }
        }

        return $result;
    }
}
