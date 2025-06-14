<?php
namespace Awz\Bx24lead\Access\Permission\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Bx24lead\Access\Custom\PermissionDictionary;

class RightEdit extends \Bitrix\Main\Access\Rule\AbstractRule
{
    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin())
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::MODULE_RIGHT_EDIT))
        {
            return true;
        }
        return false;
    }
}