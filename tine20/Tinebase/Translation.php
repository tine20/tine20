<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * primary class to handle translations
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Translation
{
    static function getTranslation($_applicationName)
    {
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . ucfirst($_applicationName) . DIRECTORY_SEPARATOR . 'translations';
        
        $translate = new Zend_Translate('gettext', $path, null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        try {
            $translate->setLocale(Zend_Registry::get('locale'));
        } catch (Zend_Translate_Exception $e) {
            // the locale of the user is not available
            // translate with locale en
            $translate->setLocale('en');
        }
        
        return $translate;
    }
}