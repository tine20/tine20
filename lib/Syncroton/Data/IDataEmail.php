<?php

/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */
interface Syncroton_Data_IDataEmail
{
    /**
     * send an email
     * 
     * @param  resource  $inputStream
     * @param  boolean   $saveInSent
     */
    public function sendEmail($inputStream, $saveInSent);
    
    /**
     * forward an email
     * 
     * @param  string  $collectionId
     * @param  string  $itemId
     * @param  string  $inputStream
     * @param  string  $saveInSent
     */
    public function forwardEmail($collectionId, $itemId, $inputStream, $saveInSent);

    /**
     * reply to an email
     * 
     * @param  string  $collectionId
     * @param  string  $itemId
     * @param  string  $inputStream
     * @param  string  $saveInSent
     */
    public function replyEmail($collectionId, $itemId, $inputStream, $saveInSent);
}

