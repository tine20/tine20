<?php declare(strict_types=1);
/**
 * Quota controller
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Quota controller
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Quota extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
    
    }

    /**
     * save quotas
     * @param string $application
     * @param array $additionalData
     * @return false[]|mixed|Tinebase_Config_Struct|Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Backend_Database_LockTimeout
     * @throws Tinebase_Exception_NotFound
     */
    public function updateQuota(string $application, $recordData = null, array $additionalData = [])
    {
        // for totalQuota set config
        $translate = Tinebase_Translation::getTranslation('Admin');
        
        if ($application === 'Tinebase') {
            // check allow total quota management config first
            if (!Admin_Config::getInstance()->{Admin_Config::QUOTA_ALLOW_TOTALINMB_MANAGEMNET}) {
                throw new Tinebase_Exception_AccessDenied(
                    $translate->_('It is not allowed to manage total Quota.'));
            }

            $this->validateQuota($application, $recordData, $additionalData);
            
            $quotaConfig = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA};
            $quotaConfig->{Tinebase_Config::QUOTA_TOTALINMB} = $additionalData['totalInMB'] / 1024 / 1024;
            return [Tinebase_Config::QUOTA_TOTALINMB => $quotaConfig->{Tinebase_Config::QUOTA_TOTALINMB}];
        }
        
        if (!$recordData) {
            throw new Tinebase_Exception_UnexpectedValue($translate->_('Record data needs to be set!'));
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' quota node data: '
            . print_r($recordData, true));

        $isPersonalNode = $additionalData['isPersonalNode'] ?? false;

        if ($application === 'Felamimail') {
            if ($isPersonalNode) {
                try {
                    if (!$recordData['name']) {
                        throw new Tinebase_Exception_UnexpectedValue($translate->_('Account E-Mail needs to be set!'));
                    }

                    $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                        ['field' => 'email', 'operator' => 'equals', 'value' => $recordData['name']]
                    ]);

                    if ($account = Admin_Controller_EmailAccount::getInstance()->search($filter)->getFirstRecord()) {
                        $this->validateQuota($application, $account, $additionalData);
                        
                        $account->email_imap_user = [
                            'emailMailQuota'  => !empty($additionalData['emailMailQuota']) ? $additionalData['emailMailQuota'] : null,
                            'emailSieveQuota' => !empty($additionalData['emailSieveQuota']) ? $additionalData['emailSieveQuota'] : null,
                        ];

                        $account = Admin_Controller_EmailAccount::getInstance()->update($account);
                        return $account;
                    } else {
                        throw new Tinebase_Exception_UnexpectedValue($translate->_('E-Mail account not found.'));
                    }
                } catch (Admin_Exception $ae) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Error while saving email quota ' . $ae->getMessage());
                    return array('success' => FALSE);
                }
            } else {
                if (!Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_SHARED_EMAIL_QUOTAS)) {
                    throw new Tinebase_Exception_AccessDenied("You do not have admin share rights for $application");
                }
            }
        }

        if ($application === 'Filemanager') {
            if ($isPersonalNode) {
                $this->validateQuota($application, $recordData, $additionalData);
                
                $user = Admin_Controller_User::getInstance()->get($additionalData['accountId']);
                $user->xprops()[Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA] = $recordData['quota'];
                Admin_Controller_User::getInstance()->update($user);
                return Admin_Controller_User::getInstance()->get($additionalData['accountId']);
            } else {
                if (!Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_SHARED_FILESYSTEM_QUOTAS)) {
                    throw new Tinebase_Exception_AccessDenied("You do not have admin share rights for $application");
                }
            }
        }

        // for filesystem load node & save node with ignoreACL
        $node = Tinebase_FileSystem::getInstance()->get($recordData['id']);
        $node->quota = $recordData['quota'];
        Tinebase_FileSystem::getInstance()->update($node);

        return Tinebase_FileSystem::getInstance()->get($recordData['id']);
    }

    public function validateQuota(string $application, $recordData, array $additionalData)
    {
        $context = $this->getRequestContext();
        // for totalQuota set config
        if (array_key_exists('confirm', $context['clientData']) || array_key_exists('confirm', $context)) {
            return;
        }

        $event = new Admin_Event_UpdateQuota();
        $event->recordData = $recordData;
        $event->application = $application;
        $event->additionalData = $additionalData;

        Tinebase_Event::fireEvent($event);
    }
}
