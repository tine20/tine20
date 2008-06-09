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

/**
 * Helper class for image operations
 *
 * @package     Tinebase
 */
class Tinebase_ImageHelper
{
    /**
     * preserves ratio and cropes image on the oversize side
     */
    const RATIOMODE_PRESERVANDCROP = 0;
    /**
     * preserves ratio and does not crop image. Resuling image dimension is less
     * than requested on one dimension as this dim is not filled  
     */
    const RATIOMODE_PRESERVNOFILL = 1;
    /**
     * scales given image to given size
     * 
     * @param  Tinebase_Model_Image $_image
     * @param  int    $_width desitination width
     * @param  int    $_height destination height
     * @param  int    $_ratiomode
     * @return void
     */
    public static function resize(Tinebase_Model_Image $_image, $_width, $_height, $_ratiomode)
    {
        $tmpPath = tempnam('/tmp', 'tine20_tmp_gd');
        file_put_contents($tmpPath, $_image->blob);
        
        switch ($_image['mime']) {
            case ('image/png'):
                $src_image = imagecreatefrompng($tmpPath);
                $imgDumpFunction = 'imagepng';
                break;
            case ('image/jpeg'):
                $src_image = imagecreatefromjpeg($tmpPath);
                $imgDumpFunction = 'imagejpeg';
                break;
            case ('image/gif'):
                $src_image = imagecreatefromgif($tmpPath);
                $imgDumpFunction = 'imagegif';
                break;
            default:
                throw new Exception("unsupported image type: " . $_image['mime']);
                break;
        }
        $src_ratio = $_image->width/$_image->height;
        $dst_ratio = $_width/$_height;
        switch ($_ratiomode) {
            case self::RATIOMODE_PRESERVANDCROP:
                $dst_width = $_width;
                $dst_height = $_height;
                if($src_ratio - $dst_ratio >= 0) {
                    // crop width
                    $dst_image = imagecreatetruecolor($dst_width, $dst_height);
                    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $_image->height * $dst_ratio, $_image->height);
                } else {
                    // crop heights
                    $dst_image = imagecreatetruecolor($dst_width, $dst_height);
                    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $_image->width, $_image->width / $dst_ratio);
                }
                break;
            case self::RATIOMODE_PRESERVNOFILL:
                if($src_ratio - $dst_ratio >= 0) {
                    // fit width
                    $dst_height = floor($_width / $src_ratio);
                    $dst_width = $_width;
                } else {
                    // fit height
                    $dst_height = $_height;
                    $dst_width = floor($_height * $src_ratio);
                }
                // recalculate dst_ratio
                $dst_ratio = $dst_width/$dst_height;
                $dst_image = imagecreatetruecolor($dst_width, $dst_height);
                imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $_image->width, $_image->height);
                break;
            default: 
                throw new Exception('ratiomode not supported');
                break;
        }
        $imgDumpFunction($dst_image, $tmpPath);
        
        $_image->width = $dst_width;
        $_image->height = $dst_height;
        $_image->blob = file_get_contents($tmpPath);
        unlink($tmpPath);
        return;
    }
    /**
     * returns image metadata
     * 
     * @param  blob  $_blob
     * @return array
     */
    public static function getImageInfoFromBlob($_blob)
    {
        $tmpPath = tempnam('/tmp', 'tine20_tmp_gd');
        file_put_contents($tmpPath, $_blob);
        
        $imgInfo = getimagesize($tmpPath);
        unlink($tmpPath);
        if (!in_array($imgInfo['mime'], array('image/png', 'image/jpeg', 'image/gif'))) {
            throw new Exception('gvien blob does not contain valid image data');
        }
        return array(
            'width'    => $imgInfo[0],
            'height'   => $imgInfo[1],
            'bits'     => $imgInfo['bits'],
            'channels' => $imgInfo['channels'],
            'mime'     => $imgInfo['mime'],
            'blob'     => $_blob
        );
        
    }
    /**
     * checks wether given file is an image or not
     * 
     * @param  string $_file image file
     * @return bool
     */
    public static function isImageFile($_file)
    {
        if(!$_file) {
            return false;
        }
        $imgInfo = getimagesize($_file);
        if (isset($imgInfo['mime']) && in_array($imgInfo['mime'], array('image/png', 'image/jpeg', 'image/gif'))) {
            return true;
        }
        return false;
    }
}