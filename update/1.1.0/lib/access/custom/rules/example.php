<?php
namespace Awz\Bx24lead\Access\Custom\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Bx24lead\Access\Custom\PermissionDictionary;
use Awz\Bx24lead\Access\Custom\Helper;

class Example extends \Bitrix\Main\Access\Rule\AbstractRule
{

    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin() && !Helper::ADMIN_DECLINE)
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::MODULE_SETT_VIEW))
        {
            return true;
        }
        return false;
    }

}