<?php

$oldConfig = Addressbook_Config::getInstance()->get('syncBackends');
Addressbook_Config::getInstance()->set('syncBackends', array(
        '0' => array(
                'class' => 'Addressbook_Backend_Sync_Ldap',
                'options' => array(
                        'attributesMap' => array(
                                'n_fn'                  => 'commonName',
                                'n_family'              => 'surname',
                        ),
                        'baseDN' => 'ou=ab,dc=example,dc=org',
                        'ldapConnection' => array(
                                'host'          => 'localhost',
                                'port'          => 389,
                                'username'      => 'cn=Manager,dc=example,dc=org',
                                'password'      => 'tine20',
                                'bindRequiresDn'=> true,
                                'baseDn'        => 'dc=example,dc=org'
                        )
                ),
        )
));

Addressbook_Config::getInstance()->set('syncBackends', array());