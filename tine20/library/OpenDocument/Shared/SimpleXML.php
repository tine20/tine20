<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * helper class for simplexml
 *
 * @package    OpenDocument
 * @subpackage Shared_SimpleXML
 */
class OpenDocument_Shared_SimpleXML {
    
    /**
     * inserts a simpleaml element after another one
     * 
     * @param SimpleXMLElement $insert the element to insert
     * @param array $target
     * @param number $targetIndex
     * @return SimpleXMLElement
     */
    public static function simplexml_insert_after(SimpleXMLElement $insert, array $target, $targetIndex = 0)
    {
        $dt = dom_import_simplexml($target[$targetIndex]);
        $di = $dt->ownerDocument->importNode(dom_import_simplexml($insert), true);
        
        if ($dt->nextSibling) {
            return $target[$targetIndex] = simplexml_import_dom($dt->parentNode->insertBefore($di, $dt->nextSibling));
        } else {
            return $target[$targetIndex] = simplexml_import_dom($dt->parentNode->appendChild($di));
        }
    }
    
    /**
     * 
     * @param SimpleXMLElement $insert
     * @param array $target
     * @param number $targetIndex
     * @return SimpleXMLElement
     */
    public static function simplexml_insert_before(SimpleXMLElement $insert, array $target, $targetIndex = 0)
    {
        $dt = dom_import_simplexml($target[$targetIndex]);
        $di = $dt->ownerDocument->importNode(dom_import_simplexml($insert), true);
        
        if ($dt->previousSibling) {
            return $target[$targetIndex] = simplexml_import_dom($dt->parentNode->insertBefore($di, $dt->previousSibling->nextSibling));
        } else {
            return $target[$targetIndex] = simplexml_import_dom($dt->parentNode->insertBefore($di, $dt));
        }
    }
}