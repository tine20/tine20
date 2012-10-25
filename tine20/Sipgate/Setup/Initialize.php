<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Initialize.php 2 2011-04-26 17:27:39Z alex $
 *
 */

/**
 * class for Sipgate initialization
 * @package     Setup
 */
class Sipgate_Setup_Initialize extends Setup_Initialize
{
    /**
     * init key fields
     */
    function _initializeKeyfields()
    {
        // create status config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sipgate')->getId();

        $connectionStatusConfig = array(
            'name'    => Sipgate_Config::CONNECTION_STATUS,
            'records' => array(
                array('id' => 'accepted', 'value' => 'accepted', 'icon' => "../../images/../Sipgate/res/call_accepted.png", 'system' => true),  //_('accepted')
                array('id' => 'outgoing', 'value' => 'outgoing', 'icon' => "../../images/../Sipgate/res/call_outgoing.png", 'system' => true),  //_('outgoing')
                array('id' => 'missed',   'value' => 'missed',   'icon' => "../../images/../Sipgate/res/call_missed.png", 'system' => true),  //_('missed')
            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sipgate_Config::CONNECTION_STATUS,
            'value'             => json_encode($connectionStatusConfig),
        )));

        // create tos config
        $connectionTos = array(
            'name'    => Sipgate_Config::CONNECTION_TOS,
            'records' => array(
                array('id' => 'voice', 'value' => 'Telephone Call',  'icon' => "../../images/oxygen/16x16/apps/kcall.png",   'system' => true),  //_('Telephone Call')
                array('id' => 'fax',   'value' => 'Facsimile',       'icon' => "../../images/../Sipgate/res/16x16/kfax.png", 'system' => true),  //_('Facsimile')

            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sipgate_Config::CONNECTION_TOS,
            'value'             => json_encode($connectionTos),
        )));
        
        $c = array(
            'name'    => Sipgate_Config::ACCOUNT_ACCOUNT_TYPE,
            'records' => array(
                array('id' => 'plus', 'value' => 'basic/plus', 'icon' => "../../images/oxygen/16x16/places/user-identity.png", 'system' => true),  //_('basic/plus')
                array('id' => 'team', 'value' => 'team',       'icon' => "../../images/oxygen/16x16/apps/system-users.png",    'system' => true),  //_('team')
            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sipgate_Config::ACCOUNT_ACCOUNT_TYPE,
            'value'             => json_encode($c),
        )));

        // create tos config
        $c = array(
            'name'    => Sipgate_Config::ACCOUNT_TYPE,
            'records' => array(
                array('id' => 'private', 'value' => 'private', 'icon' => "../../images/oxygen/16x16/places/user-identity.png", 'system' => true),  //_('private')
                array('id' => 'shared',     'value' => 'shared',   'icon' => "../../images/oxygen/16x16/apps/system-users.png",    'system' => true),  //_('shared')

            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sipgate_Config::ACCOUNT_TYPE,
            'value'             => json_encode($c),
        )));
        
    }
}