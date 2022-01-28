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

class Tinebase_Record_Expander_PropertyClass_Grants extends Tinebase_Record_Expander_Sub
{
    protected $_propertiesToProcess;

    public function __construct($_model, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        /** @var Tinebase_Record_Abstract $_model */
        if (null === ($mc = $_model::getConfiguration())) {
            throw new Tinebase_Exception_InvalidArgument($_model . ' doesn\'t have a modelconfig');
        }

        parent::__construct($mc->grantsModel, $_expanderDefinition, $_rootExpander);
    }

    // TODO this should use the defered _setData / DataRequest scheme to improve drastically in performance
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        foreach ($_records as $record) {
            if (!$record->{Tinebase_Record_Abstract::FLD_GRANTS}) {
                try {
                    $record->{Tinebase_Record_Abstract::FLD_GRANTS} = Tinebase_Container::getInstance()
                        ->getGrantsOfContainer($record->{$record::getConfiguration()->getContainerProperty()});
                } catch (Tinebase_Exception_AccessDenied $tead) {
                    continue;
                }
            }
            foreach ($record->{Tinebase_Record_Abstract::FLD_GRANTS} as $grant) {
                switch ($grant->account_type) {
                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                        try {
                            /** @phpstan-ignore-next-line */
                            $grant->account_name = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $grant->account_id);
                        } catch (Tinebase_Exception_NotFound $e) {
                            $grant->account_name = Tinebase_User::getInstance()->getNonExistentUser();
                        }
                        break;
                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                        try {
                            $grant->account_name = Tinebase_Group::getInstance()->getGroupById($grant->account_id);
                        } catch (Tinebase_Exception_Record_NotDefined $e) {
                            $grant->account_name = Tinebase_Group::getInstance()->getNonExistentGroup();
                        }
                        break;
                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                        try {
                            $grant->account_name = Tinebase_Acl_Roles::getInstance()->getRoleById($grant->account_id);
                        } catch(Tinebase_Exception_NotFound $tenf) {
                            $grant->account_name = Tinebase_Acl_Roles::getInstance()->getNonExistentRole();
                        }
                        break;
                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                        $grant->account_name = new Tinebase_Model_FullUser(['accountDisplayName' => Tinebase_Translation::getTranslation('Tinebase')->_('Anyone')], true);
                        break;
                    default:
                        throw new Tinebase_Exception_InvalidArgument('Unsupported accountType.');
                }
            }
        }

        // TODO add sub expanding!
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {

    }
}
