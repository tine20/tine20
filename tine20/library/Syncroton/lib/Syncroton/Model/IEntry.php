<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync contact
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */

interface Syncroton_Model_IEntry
{
    /**
     * 
     * @param unknown_type $properties
     */
    public function __construct($properties = null);
    
    /**
     * return true if data have got changed after initial data got loaded via constructor
     */
    public function isDirty();
    
    /**
     * 
     * @param array $properties
     */
    public function setFromArray(array $properties);    
}