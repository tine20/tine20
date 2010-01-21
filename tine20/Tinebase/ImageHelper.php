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
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function resize(Tinebase_Model_Image $_image, $_width, $_height, $_ratiomode)
    {
        $_image->resize($_width, $_height, $_ratiomode);
    }
    
    /**
     * returns image metadata
     * 
     * @param   blob  $_blob
     * @return  array
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public static function getImageInfoFromBlob($_blob)
    {
        $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
        
        if ($tmpPath === FALSE) {
            throw new Tinebase_Exception('Could not generate temporary file.');
        }
        
        file_put_contents($tmpPath, $_blob);
        
        $imgInfo = getimagesize($tmpPath);
        unlink($tmpPath);
        if (! in_array($imgInfo['mime'], array('image/png', 'image/jpeg', 'image/gif'))) {
            throw new Tinebase_Exception_UnexpectedValue('given blob does not contain valid image data.');
        }
        if (! array_key_exists('channels', $imgInfo)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Uploaded ' . $imgInfo['mime'] . ' image had no channel information. Setting channels to 3.');
            $imgInfo['channels'] = 3;
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
    
    /**
     * parses an image link
     * 
     * @param  string $link
     * @return array
     */
    public static function parseImageLink($link)
    {
        $params = array();
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . parse_url($link, PHP_URL_QUERY));
        parse_str(parse_url($link, PHP_URL_QUERY), $params);
        $params['isNewImage'] = false;
        if (isset($params['application']) && $params['application'] == 'Tinebase') {
            $params['isNewImage'] = true;
        }
        return $params;
    }

    /**
     * returns binary image data from a image identified by a imagelink
     * 
     * @param   array  $imageParams
     * @return  string binary data
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public static function getImageData($imageParams)
    {
        $tempfileBackend = new Tinebase_TempFile();
        $tempFile = $tempfileBackend->getTempFile($imageParams['id']);
        
        if (! Tinebase_ImageHelper::isImageFile($tempFile->path)) {
            throw new Tinebase_Exception_UnexpectedValue('Given file is not an image.');
        }
        
        return file_get_contents($tempFile->path);
    }
    
}