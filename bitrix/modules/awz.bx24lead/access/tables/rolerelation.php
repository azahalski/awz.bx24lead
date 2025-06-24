<?php
namespace Awz\Bx24lead\Access\Tables;

use Bitrix\Main\Access\Role\AccessRoleRelationTable;

class RoleRelationTable extends AccessRoleRelationTable
{
    public static function getTableName()
    {
        return 'awz_bx24lead_role_relation';
    }

}