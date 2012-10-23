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
 * interface for extended email backend
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
     * @param  string|array  $source       is either a string(LongId) or an array with following properties collectionId, itemId and instanceId
     * @param  string        $inputStream
     * @param  string        $saveInSent
     */
    public function forwardEmail($source, $inputStream, $saveInSent, $replaceMime);

    /**
     * reply to an email
     * 
     * @param  string|array  $source       is either a string(LongId) or an array with following properties collectionId, itemId and instanceId
     * @param  string        $inputStream
     * @param  string        $saveInSent
     */
    public function replyEmail($source, $inputStream, $saveInSent, $replaceMime);
}

