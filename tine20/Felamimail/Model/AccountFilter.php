<?php

class Felamimail_Model_AccountFilter extends Tinebase_Model_Filter_GrantsFilterGroup
{
    protected $_configuredModel = Felamimail_Model_Account::class;

    /**
     * @var string acl table name
     */
    protected $_aclTableName = 'felamimail_account_acl';
}