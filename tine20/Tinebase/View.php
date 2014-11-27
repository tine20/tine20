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
        
        $result = array(
            $favicon,
            '<link rel="stylesheet" type="text/css" href="library/ExtJS/resources/css/' . $extJS . '" />',
            '<link rel="stylesheet" type="text/css" href="themes/' . $themePath . '/resources/css/' . $themePath . '.css" />'
        );
        
        return $result;
    }
}
