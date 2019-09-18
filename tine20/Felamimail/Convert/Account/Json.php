<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     Felamimail
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Felamimail
 * @subpackage  Convert
 */
class Felamimail_Convert_Account_Json extends Tinebase_Convert_Json
{
    /**
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        $result = parent::fromTine20Model($_record);
        if (isset($result['grants'])) {
            $result['grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($result['grants']);
        }

        $this->_addDefaultSignature($result);

        return $result;
    }

    /**
     * add default signature
     *
     * @param array $record
     * 
     * TODO add to fromTine20RecordSet, too?
     */
    protected function _addDefaultSignature(&$record)
    {
        if (! isset($record['signatures']) || ! is_array($record['signatures']) || count($record['signatures']) === 0) {
            $record['signatures'] = [];
            return;
        }

        $default = array_filter($record['signatures'], function ($signature) {
            if ($signature['is_default']) {
                return true;
            }
        });
        if (count($default) > 0) {
            $defaultSignature = array_pop($default);
            $record['signature'] = $defaultSignature['signature'];
        } else {
            $record['signature'] = $record['signatures'][0]['signature'];
        }
    }
}
