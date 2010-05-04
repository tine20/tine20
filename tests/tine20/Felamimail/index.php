<?php

error_reporting(E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_PARSE);
ini_set('display_errors', 1);

define('PATH_TO_TINE_LIBRARY', dirname(__FILE__). '/../../../tine20/library');

$paths = array(
    PATH_TO_TINE_LIBRARY,
    get_include_path()
);

set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);


$config = array('auth' => 'login',
                'username' => 'username',
                'password' => 'password');

$tr = new Zend_Mail_Transport_Smtp('example.smtp.server.com', $config);

$mail = new Zend_Mail();
$mail->addTo('g.ciyiltepe@metaways.de');
$mail->setFrom('g.ciyiltepe@metaways.de');
                                
$mail->createAttachment(file_get_contents('files/mail.eml'), 
                        'message/rfc822',
                        Zend_Mime::DISPOSITION_INLINE,
                        Zend_Mime::ENCODING_8BIT);
                         
$mail->setSubject('Test Mail');
$mail->setBodyText('Hallo Welt');
$mail->send($tr);
#EOF