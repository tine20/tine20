<?php
/**
 * Tine 2.0 - https://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  View
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * View Class
 *
 * @package     Tinebase
 * @subpackage  View
 */
class Tinebase_View
{
    public static function getThemeConfig()
    {
        $extJS     = 'ext-all.css';
        $themePath = 'tine20';
        $favicon   = 'images/favicon.ico';
        $title     = 'Tine 2.0';
        
        $themeConfig = Tinebase_Core::getConfig()->theme;
        
        if ($themeConfig instanceof Tinebase_Config_Struct && $themeConfig->active) {
            if ($themeConfig->path) {
                $themePath = $themeConfig->path;
                
                //is useBlueAsBase set?
                if ($themeConfig->useBlueAsBase) {
                    $extJS = 'ext-all-notheme.css';
                }
                
                //is there a customized favicon?
                if (file_exists('themes/' . $themePath . '/resources/images/favicon.ico')) {
                    $favicon = 'themes/' . $themePath . '/resources/images/favicon.ico';
                }
            }
        }
        //Do we have a branding favicon?
        $favicon = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_FAVICON) ? Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_FAVICON) : $favicon;
        //Do we have a branding title?
        $title = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE) ? Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE) : $title;
        
        $result = array(
            'favicon'   => $favicon,
            'extJs'     => '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/' . $extJS . '" />',
            'themePath' => '<link rel="stylesheet" type="text/css" href="themes/' . $themePath . '/resources/css/' . $themePath . '.css" />',
            'title'     => $title
        );
        
        return $result;
    }
}
