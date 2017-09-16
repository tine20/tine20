<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_FullUser_Json extends Tinebase_Convert_Json
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
        /** @noinspection PhpUndefinedMethodInspection */
        $records->xprops();

        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);
    }

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
        $fileSystem = Tinebase_FileSystem::getInstance();
        $personalPath = $fileSystem->getApplicationBasePath(
            'Filemanager',
            Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
        ) . '/';
        foreach (false === $multiple ? array(&$result) : $result as &$val) {
            $val['effectiveAndLocalQuota'] = null;
            try {
                $val['effectiveAndLocalQuota'] = $fileSystem->getEffectiveAndLocalQuota(
                    $fileSystem->stat($personalPath . $val['accountId']));
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' did not find personal folder for account ' . $val['accountId']);
            }
        }

        return parent::_resolveAfterToArray($result, $modelConfiguration, $multiple);
    }
}