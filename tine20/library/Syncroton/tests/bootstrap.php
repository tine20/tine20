<?php

$paths = array(
    realpath(dirname(__FILE__)),
    realpath(dirname(__FILE__) . '/../lib'),
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

function getTestDatabase()
{
    if (file_exists('/tmp/Syncroton_test.sq3')) {
        unlink('/tmp/Syncroton_test.sq3');
    }
    
    $sql = file_get_contents(dirname(__FILE__) . '/../docs/syncroton.sql');

    $sql = explode(';', $sql);
    
    // create in memory database by default 
    $params = array (
        #'dbname' => '/tmp/Syncroton_test.sq3',
        'dbname' => ':memory:'
    );
    
    $db = Zend_Db::factory('PDO_SQLITE', $params);
    
    // enable foreign keys
    #$db->query('PRAGMA read_uncommitted = true');
    
    foreach ($sql as $sql_query) {
        if (strlen($sql_query) > 10) {
            // Convert mysql DDL to SQLite format
            $start = strpos($sql_query, '(');
            $end   = strrpos($sql_query, ')');
            $cols  = substr($sql_query, $start, $end - $start);
            $cols  = explode(',', $cols);

            foreach ($cols as $idx => $col) {
                if (preg_match('/^KEY /', ltrim($col))) {
                    unset($cols[$idx]);
                    continue;
                }

                if (preg_match('/^CONSTRAINT /', ltrim($col))) {
                    unset($cols[$idx]);
                    continue;
                }

                $col = preg_replace('/UNIQUE KEY `[^`]+`/', 'UNIQUE', $col);
                $col = preg_replace('/`\([0-9]+\)/', '`', $col);

                $cols[$idx] = $col;
            }

            $sql_query = substr($sql_query, 0, $start) . "\n" . implode($cols, ',') . ")";

            $db->query($sql_query);
        }
    }
    
    // create test folders
    $folders = array(
        array(
            'id'        => 'addressbookFolderId',
            'type'      => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT,
            'name'      => 'Default Contacts Folder',
            'owner_id'  => '1234',
            'parent_id' => '0'
        ),
        array(
            'id'        => 'anotherAddressbookFolderId',
            'type'      => Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED,
            'name'      => 'Another Contacts Folder',
            'owner_id'  => '1234',
            'parent_id' => '0'
        ),
        array(
            'id'        => 'calendarFolderId',
            'type'      => Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR,
            'name'      => 'Default Contacts Folder',
            'owner_id'  => '1234',
            'parent_id' => '0'
        ),
        array(
            'id'        => 'tasksFolderId',
            'type'      => Syncroton_Command_FolderSync::FOLDERTYPE_TASK,
            'name'      => 'Default Tasks Folder',
            'owner_id'  => '1234',
            'parent_id' => '0'
        ),
        array(
            'id'        => 'emailInboxFolderId',
            'type'      => Syncroton_Command_FolderSync::FOLDERTYPE_INBOX,
            'name'      => 'Inbox',
            'owner_id'  => '1234',
            'parent_id' => '0'
        ),
        array(
            'id'        => 'emailSentFolderId',
            'type'      => Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL,
            'name'      => 'Sent',
            'owner_id'  => '1234',
            'parent_id' => '0'
        )
    );
    
    foreach ($folders as $folder) {
        $db->insert('syncroton_data_folder', $folder);
    }
    
    $entries = array(
        array(
            'id'        => 'contact1',
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'addressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Lars', 
                'lastName'  => 'Kneschke'
            )))
        ),
        array(
            'id'        => sha1(mt_rand(). microtime()),
            'class'     => 'Syncroton_Model_Contact',
            'folder_id' => 'anotherAddressbookFolderId',
            'data'      => serialize(new Syncroton_Model_Contact(array(
                'firstName' => 'Cornelius', 
                'lastName'  => 'Weiß'
            )))
        ),
        array(
            'id'        => 'email1',
            'class'     => 'Syncroton_Model_Email',
            'folder_id' => 'emailInboxFolderId',
            'data'      => serialize(new Syncroton_Model_Email(array(
                'accountId'    => 'FooBar',
                'attachments'  => array(
                    new Syncroton_Model_EmailAttachment(array(
                        'fileReference' => '12345abcd',
                        'umAttOrder'    => 1
                    ))
                ),
                'categories'   => array('123', '456'),
                'cc'           => 'l.kneschke@metaways.de',
                'dateReceived' => new DateTime('2012-03-21 14:00:00', new DateTimeZone('UTC')), 
                'from'         => 'k.kneschke@metaways.de',
                'subject'      => 'Test Subject',
                'to'           => 'j.kneschke@metaways.de',
                'read'         => 1,
                'body'         => new Syncroton_Model_EmailBody(array(
                    'type'              => Syncroton_Model_EmailBody::TYPE_PLAINTEXT, 
                    'data'              => 'Hello!', 
                    'truncated'         => true, 
                    'estimatedDataSize' => 600
                ))
            )))
        )
    );
    
    foreach ($entries as $entry) {
        $db->insert('syncroton_data', $entry);
    }
    
    return $db;
}
