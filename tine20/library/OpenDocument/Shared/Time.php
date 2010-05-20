<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @subpackage  Shared
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

class OpenDocument_Shared_Time
{
    /**
     * converts time from iso representation to odf representation
     * 
     * @param  string $iso
     * @return string
     */
    public static function ISO2ODF($iso)
    {
        if (preg_match('/(\d{2}):(\d{2}):(\d{2})$/', $iso, $matches)) {
            return "PT{$matches[1]}H{$matches[2]}M{$matches[3]}S";
        }
        
        return "";
    }
}