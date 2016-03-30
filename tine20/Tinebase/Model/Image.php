<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class Tinebase_Model_Image
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Image extends Tinebase_Record_Abstract 
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
    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    protected $_validators = array(
        // image identifiers
        'id'          => array('presence' => 'required', 'allowEmpty' => false, /*'Alnum'*/ ),
        'application' => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'location'    => array('default' => '', 'allowEmpty' => true, 'Alnum', ),
        
        // image properties
        'width'       => array('allowEmpty' => true, 'Int' ),
        'height'      => array('allowEmpty' => true, 'Int' ),
        'bits'        => array('allowEmpty' => true, 'Int' ),
        'channels'    => array('allowEmpty' => true, 'Int' ),
        'mime'        => array('allowEmpty' => true, array('InArray', array('image/png', 'image/jpeg', 'image/gif'))),
    
        // binary data
        'blob'        => array('allowEmpty' => true)
    );
    
    /**
     * returns image from given path
     * 
     * @param   string $_path
     * @return  Tinebase_Model_Image
     * @throws  Tinebase_Exception_NotFound
     */
    public static function getImageFromPath($_path)
    {
        if (!file_exists($_path)) {
            throw new Tinebase_Exception_NotFound('Image file not found.');
        }
        $imgBlob = file_get_contents($_path);
        return self::getImageFromBlob($imgBlob);
    }
    
    /**
     * returns image from given blob
     *
     * @param  string $_blob
     * @return Tinebase_Model_Image
     */
    public static function getImageFromBlob($_blob)
    {
        return new Tinebase_Model_Image(Tinebase_ImageHelper::getImageInfoFromBlob($_blob), true);
    }
    
    /**
     * returns image from imageURL
     * 
     * @param  string imageURL
     * @return Tinebase_Model_Image
     */
    public static function getImageFromImageURL($_imageURL)
    {
        $params = self::parseImageURL($_imageURL);
        $image = Tinebase_Controller::getInstance()->getImage($params['application'], $params['id'], $params['location']);
        return $image;
    }
    
    /**
     * parses an imageURL
     * 
     * @param   string imageURL
     * @return  array array of image params
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function parseImageURL($_imageURL)
    {
        $params = array();
        parse_str(parse_url($_imageURL, PHP_URL_QUERY), $params);
        if (!empty($params['application']) && !empty($params['id'])) {
            return $params;
        } else {
            throw new Tinebase_Exception_InvalidArgument("$_imageURL is not a valid imageURL");
        }
    }
    
    /**
     * returns an image url
     * @param string     $appName    the name of the application
     * @param string     $id         the identifier
     * @param string     $location   location
     * @param integer    $width      width
     * @param integer    $height     height
     * @param integer    $ratiomode  ratiomode
     */
    public static function getImageUrl($appName, $id, $location = '', $width = 90, $height = 90, $ratiomode = 0)
    {
        return 'index.php?method=Tinebase.getImage&application='
            . $appName . '&location=' . $location . '&id='
            . $id . '&width=' . $width . '&height=' . $height . '&ratiomode='.$ratiomode;
    }
    
    /**
     * scales given image to given size
     * 
     * @param  int    $_width desitination width
     * @param  int    $_height destination height
     * @param  int    $_ratiomode
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function resize($_width, $_height, $_ratiomode)
    {
        $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
        file_put_contents($tmpPath, $this->blob);
        
        switch ($this->mime) {
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
                throw new Tinebase_Exception_InvalidArgument("Unsupported image type: " . $this->mime);
                break;
        }
        $src_ratio = $this->width/$this->height;
        $dst_ratio = $_width/$_height;
        switch ($_ratiomode) {
            case self::RATIOMODE_PRESERVANDCROP:
                $dst_width = $_width;
                $dst_height = $_height;
                if($src_ratio - $dst_ratio >= 0) {
                    // crop width
                    $dst_image = imagecreatetruecolor($dst_width, $dst_height);
                    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $this->height * $dst_ratio, $this->height);
                } else {
                    // crop heights
                    $dst_image = imagecreatetruecolor($dst_width, $dst_height);
                    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $this->width, $this->width / $dst_ratio);
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
                imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $this->width, $this->height);
                break;
            default: 
                throw new Tinebase_Exception_InvalidArgument('Ratiomode not supported.');
                break;
        }
        $imgDumpFunction($dst_image, $tmpPath);
        
        $this->width = $dst_width;
        $this->height = $dst_height;
        $this->blob = file_get_contents($tmpPath);
        unlink($tmpPath);
        return;
    }
    
    /**
     * returns binary string in given format
     *
     * @param string    $_mime
     * @param int       $_maxSize in bytes
     * @return string
     */
    public function getBlob($_mime='image/jpeg', $_maxSize=0)
    {
        if ($this->mime != $_mime) {
            $img = @imagecreatefromstring($this->blob);
            
            $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
            switch ($_mime) {
                case ('image/png'):
                    imagepng($img, $tmpPath, 0);
                    break;
                case ('image/jpeg'):
                    imagejpeg($img, $tmpPath, 100);
                    break;
                case ('image/gif'):
                    imagegif($img, $tmpPath);
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument("Unsupported image type: " . $_mime);
                    break;
            }
            
            $blob = file_get_contents($tmpPath);
            unlink($tmpPath);
        } else {
            $blob = $this->blob;
        }

        if ($_maxSize) {
            $originalSize = strlen($blob);
            if ($originalSize > $_maxSize) {

                $cacheId = Tinebase_Helper::convertCacheId(__METHOD__ . $this->id . $_mime . $_maxSize);
                if (Tinebase_Core::getCache()->test($cacheId)) {
                    $blob =  Tinebase_Core::getCache()->load($cacheId);
                    return $blob;
                }

                // NOTE: resampling 1:1 through GD changes images size so
                //       we always to through GD before furthor calculations
                $qS = $_maxSize / strlen($blob);
                $qD = $_mime != $this->mime ? sqrt($qS) : 1;
                $qF = 1;
                $i = 0;

                do {
                    // feedback fault
                    $qD = $qD * $qF;

                    $clone = clone $this;
                    $clone->resize(
                        floor($this->width * $qD),
                        floor($this->height * $qD),
                        self::RATIOMODE_PRESERVANDCROP
                    );
                    $blob = $clone->getBlob();
                    $size = strlen($blob);

                    // size factor achieved;
                    $qSA = $size/$originalSize;

                    // size fault factor
                    $qF = sqrt($qS/$qSA);

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " resized {$this->id}: qS: $qS qD: $qD qSA: $qSA sizeA: $size qF: $qF ");

                // feedback size fault factor if we are still to big or more than 1% to small per attempt
                } while ($qF > (1 + $i++*0.01) || $qF < 1);

                Tinebase_Core::getCache()->save($blob, $cacheId, array(), null);
            }
        }



        return $blob;
    }
    
    /**
     * returns image extension from mime type
     * 
     * @return string extension
     */
    public function getImageExtension()    
    {
        $extension = '';
        
        switch ( $this->mime ) {
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/jpeg':
                $extension = '.jpg';
                break;
            case 'image/gif':
                $extension = '.gif';
                break;
        }
        
        return $extension;
    }
} // end of Tinebase_Model_Image
