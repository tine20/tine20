<?php declare(strict_types=1);

interface SSO_RPConfigInterface
{
    public function beforeCreateUpdateHook(): void;
}
