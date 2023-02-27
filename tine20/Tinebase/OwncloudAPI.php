<?php declare(strict_types=1);

class Tinebase_OwncloudAPI
{
    /**
     * Tinebase_Expressive_RoutHandler function
     *
     * @return \Laminas\Diactoros\Response
     */
    public static function getUser(): \Laminas\Diactoros\Response
    {
        /** @var \Laminas\Diactoros\ServerRequest $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $params = $request->getServerParams();

        if (! isset($params['PHP_AUTH_USER']) || ! isset($params['PHP_AUTH_PW']) ) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write('User and/or password missing.');
            return $response;
        }

        $authResult = Tinebase_Auth::getInstance()->authenticate($params['PHP_AUTH_USER'], $params['PHP_AUTH_PW']);
        if ($authResult->isValid()) {
            $response = new \Laminas\Diactoros\Response('php://memory', 200, [
                'Access-Control-Allow-Origin' => '*',
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
            $user = Tinebase_User::getInstance()->getFullUserByLoginName($authResult->getIdentity());
            $result = json_encode([
                'ocs' => [
                    'meta' => [
                        'status' => 'ok',
                        'statuscode' => 200,
                        'message' =>  'OK',
                        'totalitems'  => '',
                        'itemsperpage' => ''
                    ],
                    'data' => [
                        'id' => $user->accountLoginName,
                        'display-name' => $user->accountDisplayName,
                        'email' =>  $user->accountEmailAddress,
                        'language'  => 'en',
                    ]
                ]
            ]);
            $response->getBody()->write($result);
        } else {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($authResult->getMessages()));
        }
        
        return $response;
    }

    /**
     * Tinebase_Expressive_RoutHandler function
     *
     * @return \Laminas\Diactoros\Response
     */
    public static function getCapabilities(): \Laminas\Diactoros\Response
    {
        $response = new \Laminas\Diactoros\Response('php://memory', 200, [
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);

        $result = json_encode([
            'ocs'   => [
                "meta" => [
                    "status"=>"ok",
                    "statuscode"=>200,
                    "message"=>"OK",
                    "totalitems"=>"",
                    "itemsperpage"=>""
                ],
                "data"  =>  [
                    'capabilities' => [
                        "core"  =>  [
                            "poll_interval"=> 30000,
                            "webdav_root"=>   "remote.php/webdav",
                            "status"=> [
                                "installed"     =>  true,
                                "maintenance"   =>  false,
                                "needsDbUpgrade"=>  false,
                                "version"       =>  "10.11.0.6",
                                "versionstring" =>  "10.11.0.6",
                                "edition"       =>  "Community",
                                "productname"   =>  "ownCloud",
                                "product"       =>  "ownCloud",
                                "productversion"=>  "10.11.0",
                                "hostname"      =>  "ownCloud",
                            ],
                            "support_url_signing"=> true,
                        ],
                        "guests_v1"=>[
                            "enabled"=>true 
                        ],
                        "checksums"=> [
                            "supported_types"   =>       ["SHA1"],
                            "preferred_upload_type" => ["SHA1"],
                        ],
                        "files"=> [
                            "privateLinks" => true,
                            "privateLinksDetailsParam" => true,
                            "bigfilechunking" => true,
                            "blacklisted_files" => [".htaccess"],
                            "blacklisted_files_regex" => "\\\\.(part|filepart)$",
                            "favorites" => true,
                            "file_locking_support" => true,
                            "file_locking_enable_file_action" => false,
                            "undelete" => true,
                            "versioning" => true,
                        ],
                        "files_sharing" => [
                            "api_enabled" => true,
                            "public" => [
                                "enabled" => true,
                                "password" => [
                                    "enforced_for" => [
                                        "read_only" => false,
                                        "read_write" => false,
                                        "upload_only" => false,
                                        "read_write_delete" => false
                                    ],
                                    "enforced" => false
                                ],
                                "roles_api" => true,
                                "can_create_public_link" => true,
                                "expire_date" => [
                                    "enabled" => false
                                ],
                                "send_mail" => false,
                                "social_share" => true,
                                "upload" => true,
                                "multiple" => true,
                                "supports_upload_only" => true,
                                "defaultPublicLinkShareName" => "Public link"
                            ],
                            "user" => [
                                "send_mail" => false,
                                "profile_picture" => true,
                                "expire_date" => [
                                    "enabled" => false
                                ]
                            ],
                            "group" => [
                                "expire_date" => [
                                    "enabled" => false
                                ]
                            ],
                            "remote" => [
                                "expire_date" => [
                                    "enabled" => false
                                ]
                            ],
                            "resharing" => true,
                            "group_sharing" => true,
                            "auto_accept_share" => true,
                            "share_with_group_members_only" => true,
                            "share_with_membership_groups_only" => true,
                            "can_share" => true,
                            "user_enumeration" => [
                                "enabled" => true,
                                "group_members_only" => false
                            ],
                            "default_permissions" => 31,
                            "providers_capabilities" => [
                                "ocinternal" => [
                                    "user" => ["shareExpiration"],
                                    "group" => ["shareExpiration"],
                                    "link" => [
                                        "shareExpiration",
                                        "passwordProtected"
                                    ]
                                ],
                                "ocFederatedSharing" => [
                                    "remote" => ["shareExpiration"]
                                ]
                            ],
                            "federation" => [
                                "outgoing" => true,
                                "incoming" => true
                            ],
                            "search_min_length" => 2
                        ],
                        "notifications" => [
                            "ocs-endpoints" => ["list","get","delete"]
                        ],
                        "dav"=> [
                            "reports"   => ["search-files"],
                            "chunking"=>"1.0",
                            "propfind" => [
                                "depth_infinity" =>false,
                            ],
                            "trashbin"=>"1.0",
                        ],
                    ],
                    "version"   =>  [
                        "product"=>        "ownCloud",
                        "edition"=>        "Community",
                        "major"=>          10,
                        "minor"=>          11,
                        "micro"=>          0,
                        "string"=>         "10.11.0",
                        "productversion"=> "10.11.0",
                    ],
                ]
            ]
        ]);
        
        $response->getBody()->write($result);
        return $response;
    }

    protected static function badRequest(): \Laminas\Diactoros\Response
    {
        return new \Laminas\Diactoros\Response('php://memory', 400);
    }
}
