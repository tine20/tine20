<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync contact
 *
 * @package     Syncroton
 * @subpackage  Model
 * @property    string    Alias
 * @property    DateTime  Anniversary
 * @property    string    AssistantName
 * @property    string    AssistantPhoneNumber
 * @property    DateTime  Birthday
 * @property    string    Business2PhoneNumber
 * @property    string    BusinessAddressCity
 * @property    Syncroton_Model_EmailBody  Body
 */

class Syncroton_Model_Contact extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'ApplicationData';
    
    protected $_properties = array(
        'AirSyncBase' => array(
            'body'                   => array('type' => 'container', 'class' => 'Syncroton_Model_EmailBody')
        ),
        'Contacts' => array(
            'alias'                  => array('type' => 'string', 'supportedSince' => '14.0'),
            'anniversary'            => array('type' => 'datetime'),
            'assistantName'          => array('type' => 'string'),
            'assistantPhoneNumber'   => array('type' => 'string'),
            'birthday'               => array('type' => 'datetime'),
            'business2PhoneNumber'   => array('type' => 'string'),
            'businessAddressCity'    => array('type' => 'string'),
            'businessAddressCountry' => array('type' => 'string'),
            'businessAddressPostalCode' => array('type' => 'string'),
            'businessAddressState'   => array('type' => 'string'),
            'businessAddressStreet'  => array('type' => 'string'),
            'businessFaxNumber'      => array('type' => 'string'),
            'businessPhoneNumber'    => array('type' => 'string'),
            'carPhoneNumber'         => array('type' => 'string'),
            'categories'             => array('type' => 'container', 'childElement' => 'category'),
            'children'               => array('type' => 'container', 'childElement' => 'child'),
            'companyName'            => array('type' => 'string'),
            'department'             => array('type' => 'string'),
            'email1Address'          => array('type' => 'string'),
            'email2Address'          => array('type' => 'string'),
            'email3Address'          => array('type' => 'string'),
            'fileAs'                 => array('type' => 'string'),
            'firstName'              => array('type' => 'string'),
            'home2PhoneNumber'       => array('type' => 'string'),
            'homeAddressCity'        => array('type' => 'string'),
            'homeAddressCountry'     => array('type' => 'string'),
            'homeAddressPostalCode'  => array('type' => 'string'),
            'homeAddressState'       => array('type' => 'string'),
            'homeAddressStreet'      => array('type' => 'string'),
            'homeFaxNumber'          => array('type' => 'string'),
            'homePhoneNumber'        => array('type' => 'string'),
            'jobTitle'               => array('type' => 'string'),
            'lastName'               => array('type' => 'string'),
            'middleName'             => array('type' => 'string'),
            'mobilePhoneNumber'      => array('type' => 'string'),
            'officeLocation'         => array('type' => 'string'),
            'otherAddressCity'       => array('type' => 'string'),
            'otherAddressCountry'    => array('type' => 'string'),
            'otherAddressPostalCode' => array('type' => 'string'),
            'otherAddressState'      => array('type' => 'string'),
            'otherAddressStreet'     => array('type' => 'string'),
            'pagerNumber'            => array('type' => 'string'),
            'picture'                => array('type' => 'string', 'encoding' => 'base64'),
            'padioPhoneNumber'       => array('type' => 'string'),
            'rtf'                    => array('type' => 'string'),
            'spouse'                 => array('type' => 'string'),
            'suffix'                 => array('type' => 'string'),
            'title'                  => array('type' => 'string'),
            'webPage'                => array('type' => 'string'),
            'weightedRank'           => array('type' => 'string', 'supportedSince' => '14.0'),
            'yomiCompanyName'        => array('type' => 'string'),
            'yomiFirstName'          => array('type' => 'string'),
            'yomiLastName'           => array('type' => 'string'),
        ),
        'Contacts2' => array(
            'accountName'            => array('type' => 'string'),
            'companyMainPhone'       => array('type' => 'string'),
            'customerId'             => array('type' => 'string'),
            'governmentId'           => array('type' => 'string'),
            'iMAddress'              => array('type' => 'string'),
            'iMAddress2'             => array('type' => 'string'),
            'iMAddress3'             => array('type' => 'string'),
            'managerName'            => array('type' => 'string'),
            'mMS'                    => array('type' => 'string'),
            'nickName'               => array('type' => 'string'),
        )
    );
}