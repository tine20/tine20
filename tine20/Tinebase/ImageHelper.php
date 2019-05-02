<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * max pixels allowed per edge for resize operations
     */
    const MAX_RESIZE_PX = 2000;
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
        $width = min($_width, self::MAX_RESIZE_PX);
        $height = min($_height, self::MAX_RESIZE_PX);

        $_image->resize($width, $height, $_ratiomode);
    }

    /**
     * returns image metadata
     *
     * @param   string $_blob
     * @return array
     * @throws Tinebase_Exception
     */
    public static function getImageInfoFromBlob($_blob)
    {
        $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
        
        if ($tmpPath === FALSE) {
            throw new Tinebase_Exception('Could not generate temporary file.');
        }
        
        file_put_contents($tmpPath, $_blob);
        
        $imgInfo = @getimagesize($tmpPath);
        unlink($tmpPath);
        if (! in_array($imgInfo['mime'], self::getSupportedImageMimeTypes())) {
            throw new Tinebase_Exception_UnexpectedValue('given blob does not contain valid image data.');
        }
        if (! (isset($imgInfo['channels']) || array_key_exists('channels', $imgInfo))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Image of type ' . $imgInfo['mime']
                    . ' had no channel information. Setting channels to 0.');
            $imgInfo['channels'] = 0;
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
        if (! $_file || ! file_exists($_file)) {
            return false;
        }
        try {
            $imgInfo = @getimagesize($_file);
            if ($imgInfo && isset($imgInfo['mime']) && in_array($imgInfo['mime'], self::getSupportedImageMimeTypes())) {
                return true;
            }
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }
        return false;
    }

    /**
     * returns supported image mime types
     *
     * @return array
     */
    public static function getSupportedImageMimeTypes()
    {
        return array('image/png', 'image/jpeg', 'image/gif');
    }

    /**
     * get mime of given file extension
     *
     * @param  string $fileExt
     * @return string
     */
    public static function getMime($fileExt)
    {
        $ext = strtolower(str_replace('/^\./', '', $fileExt));
        switch ($ext) {
            case 'png':
                return 'image/png';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'gif':
                return 'image/gif';
            default:
                return '';
        }
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
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
        $tempFile = Tinebase_TempFile::getInstance()->getTempFile($imageParams['id']);
        
        if (! Tinebase_ImageHelper::isImageFile($tempFile->path)) {
            throw new Tinebase_Exception_UnexpectedValue('Given file is not an image.');
        }
        
        return file_get_contents($tempFile->path);
    }

    /**
     * get data url from given image path
     *
     * @param string $imagePath
     * @return string
     * @throws Tinebase_Exception
     */
    public static function getDataUrl($imagePath)
    {
        if (substr($imagePath, 0, 5) === 'data:') {
            return $imagePath;
        }

        $cacheId = md5(self::class . 'getDataUrl' . $imagePath);
        $dataUrl = Tinebase_Core::getCache()->load($cacheId);

        if (! $dataUrl) {
            $blob = Tinebase_Helper::getFileOrUriContents($imagePath);
            $mime = '';

            if (substr($imagePath, -4) === '.ico') {
                $mime = 'image/x-icon';
            } elseif ($blob) {
                $info = self::getImageInfoFromBlob($blob);
                $mime = $info['mime'];
            }

            $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($blob);
            Tinebase_Core::getCache()->save($dataUrl, $cacheId);
        }

        return $dataUrl;
    }
}
