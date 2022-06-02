<?php declare(strict_types=1);

class Tinebase_FileSystem_DefaultGrantsCfg
{
    protected $data;

    public function __construct(array $data = []) //, $parent = null, $parentKey = null, $struct = null, $appName = null)
    {
        if (!isset($data['[^/]+/folders/shared/[^/]+'])) {
            $data['[^/]+/folders/shared/[^/]+'] = [
                array_merge([
                    'account_id' => [
                        ['field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT],
                    ],
                    'account_type' => 'user',
                ], array_fill_keys(Tinebase_Model_Grants::getAllGrants(), true)), [
                    'account_id' => [
                        ['field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Group::DEFAULT_USER_GROUP],
                    ],
                    'account_type' => 'group',
                    Tinebase_Model_Grants::GRANT_READ   => true,
                    Tinebase_Model_Grants::GRANT_SYNC   => true,
                ], array_merge([
                    'account_id' => [
                        ['field' => 'id', 'operator' => 'equals', 'value' => Tinebase_Group::DEFAULT_ADMIN_GROUP],
                    ],
                    'account_type' => 'group',
                ], array_fill_keys(Tinebase_Model_Grants::getAllGrants(), true)),
            ];
        }

        if (!isset($data['[^/]+/folders/personal/([^/]+)/[^/]+'])) {
            $data['[^/]+/folders/personal/([^/]+)/[^/]+'] = [
                array_merge([
                    'account_id' => '$1',
                    'account_type' => 'user',
                ], array_fill_keys(Tinebase_Model_Grants::getAllGrants(), true)),
            ];
        }

        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
