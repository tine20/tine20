<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class OnlyOfficeIntegrator_Model_Node extends Filemanager_Model_Node
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = OnlyOfficeIntegrator_Config::APP_NAME;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    // old Tinebase_Converter_Json code
    public static function resolveTBTreeNode(array $tbTreeNode)
    {
        $tokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->getUnresolvedTokensCached();
        if ($tokens->count() === 0 || ($tokens = $tokens->filter(function($token) {
                return !$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED} &&
                    !$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION};
                }))->count() === 0) {
            return $tbTreeNode;
        }

        // very bad, old Tinebase_Converter_Json code
        if (isset($tbTreeNode['id'])) {
            $tmp = [&$tbTreeNode];
        } else {
            $tmp = $tbTreeNode;
        }

        $recordsToWorkOn = [];
        $allUsers = [];
        foreach ($tmp as &$record) {
            $recTokens = $tokens->filter(function($token) use($record) {
                return $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID} === $record['id'] &&
                    $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} === $record['revision'];
            });
            if ($recTokens->count() > 0) {
                $userIds = $recTokens->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_USER_ID};
                $record[OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME] = $userIds;
                $allUsers = array_merge($allUsers, $userIds);
                $recordsToWorkOn[] = &$record;
            }
        }

        if (!empty($allUsers)) {
            $allUsers = Tinebase_User::getInstance()->getMultiple(array_unique($allUsers));
            $contacts = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Addressbook_Model_Contact::class, [
                    ['field' => 'id', 'operator' => 'in', 'value' => $allUsers->contact_id],
                ]
            ));

            $contactCache = [];
            foreach ($recordsToWorkOn as &$record) {
                foreach ($record[OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME] as &$val) {
                    if (false === ($user = $allUsers->getById($val))) continue;
                    if (!isset($contactCache[$user->contact_id])) {
                        if (false === ($contact = $contacts->getById($user->contact_id))) continue;
                        $contactCache[$user->contact_id] = [
                            'id' => $user->contact_id,
                            'n_fileas' => $contact->n_fileas,
                        ];
                    }
                    $val = $contactCache[$user->contact_id];
                }
            }
        }

        return isset($tbTreeNode['id']) ? $tmp[0] : $tmp;
    }
}
