<?php declare(strict_types=1);

/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Record_Expander_PropertyClass_AccountGrants extends Tinebase_Record_Expander_Sub
{
    protected $parentMC;

    public function __construct($_model, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        /** @var Tinebase_Record_Abstract $_model */
        if (null === ($mc = $_model::getConfiguration())) {
            throw new Tinebase_Exception_InvalidArgument($_model . ' doesn\'t have a modelconfig');
        }
        $this->parentMC = $mc;

        parent::__construct($mc->grantsModel, $_expanderDefinition, $_rootExpander);
    }

    // TODO this should use the defered _setData / DataRequest scheme to improve drastically in performance
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $containerCache = [];
        $funcCache = [];
        $func = function (Tinebase_Record_Interface $record, callable $func) use(&$funcCache) {
            $mc = $record::getConfiguration();
            if ($mc->delegateAclField) {
                $delegateRecord = $record->{$mc->delegateAclField};
                if (!$delegateRecord instanceof Tinebase_Record_Interface) {
                    if (!isset($funcCache[$delegateRecord])) {
                        $funcCache[$delegateRecord] = $mc->fields[$mc->delegateAclField][Tinebase_Record_Abstract::CONFIG][Tinebase_Record_Abstract::CONTROLLER_CLASS_NAME]::getInstance()->get($delegateRecord);
                    }
                    $delegateRecord = $funcCache[$delegateRecord];
                } elseif (!isset($funcCache[$delegateRecord->getId()])) {
                    $funcCache[$delegateRecord->getId()] = $delegateRecord;
                }
                // see comment below
                $record->setAccountGrants($func($delegateRecord, $func));
            } else {
                // this is important, we allow the record to process the account grants here and then inherit that processed grants down the line
                // the model down the line do not need to know how to process the grants. HR: Division -> Employee (process grants) -> FreeTime (does not need to know!)
                $record->setAccountGrants(Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $record->{$mc->getContainerProperty()}));
            }
            return $record->{Tinebase_ModelConfiguration::FLD_ACCOUNT_GRANTS};
        };
        /** @var Tinebase_Record_Interface $record */
        foreach ($_records as $record) {
            if (!$record->{Tinebase_Record_Abstract::FLD_ACCOUNT_GRANTS}) {
                $containerId = $record->getIdFromProperty($this->parentMC->getContainerProperty());
                if (!isset($containerCache[$containerId])) {
                    $containerCache[$containerId] = $func($record, $func);
                }
                $record->setAccountGrants($containerCache[$containerId]);
            }
        }

        // TODO add sub expanding!
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {

    }
}
