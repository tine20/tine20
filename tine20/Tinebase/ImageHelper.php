<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

class Tinebase_ImageHelper
{
    /**
     * preserves ratio and cropes image on the oversize side
     */
    const RATIOMODE_PRESERVANDCROP = 0;
    
    /**
     * scales given image to given size
     * 
     * @param  string $_file
     * @param  int    $_width desitination width
     * @param  int    $_height destination height
     * @param  int    $_ratiomode
     * @return imagecreatetruecolor
     */
    public static function resize($_file, $_width, $_height, $_ratiomode)
    {
        $imgInfo = getimagesize($_file);
        switch ($imgInfo['mime']) {
            case ('image/png'):
                $src_image = imagecreatefrompng($_file);
                break;
            case ('image/jpeg'):
                $src_image = imagecreatefromjpeg($_file);
                break;
            case ('image/gif'):
                $src_image = imagecreatefromgif($_file);
                break;
            default:
                throw new Exception("unsupported image type: " . $imgInfo['mime']);
                break;
        }
        
        $dst_image = imagecreatetruecolor($_width, $_height);
        
        $src_ratio = $imgInfo[0]/$imgInfo[1];
        $dst_ratio = $_width/$_height;
        switch ($_ratiomode) {
            case self::RATIOMODE_PRESERVANDCROP:
                if($src_ratio - $dst_ratio >= 0) {
                    // crop width
                    $scaleBy = $_height/$imgInfo[1];
                    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $_width, $_height, $imgInfo[0] / $src_ratio, $imgInfo[1]);
                    
                } else {
                    // crop heights
                    $scaleBy = $_width/$imgInfo[0];
                    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $_width, $_height, $imgInfo[0], $imgInfo[1] * $src_ratio);
                }
                break;
            default: 
                throw new Exception('ratiomode not supported');
                break;
        }
        return $dst_image;
    }
}