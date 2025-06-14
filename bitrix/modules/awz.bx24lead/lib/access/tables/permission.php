<?php
namespace Awz\Bx24lead\Access\Tables;

use Bitrix\Main\Access\Permission\AccessPermissionTable;

class PermissionTable extends AccessPermissionTable
{
    public static function getTableName()
    {
        return 'awz_bx24lead_permission';
    }
}