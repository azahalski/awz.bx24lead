<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Awz\Bx24lead\Access\AccessController;
use Awz\Bx24lead\Access\Custom\ActionDictionary;
Loc::loadMessages(__FILE__);
$module_id = "awz.bx24lead";
if(!Loader::includeModule($module_id)) return;

$items = [];
if(AccessController::isViewSettings() || AccessController::isViewRight()){
    $level2 = [];
    if(AccessController::isViewSettings()){
        $level2[] = [
            "text" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME_MENU1'),
            "url" => "awz_bx24lead_providers_list.php?lang=".LANGUAGE_ID,
            "more_url" => ["awz_bx24lead_providers_edit.php?lang=".LANGUAGE_ID],
            "title" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME_MENU1'),
            "sort" => 100,
        ];
        $level2[] = [
            "text" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME_SETT_1'),
            "url" => "settings.php?lang=".LANGUAGE_ID.'&mid='.$module_id.'&mid_menu=1'
        ];
    }
    if(AccessController::isViewRight()){
        $level2[] = [
            "text" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME_SETT_2'),
            "url" => "javascript:BX.SidePanel.Instance.open('/bitrix/admin/settings.php?mid=".$module_id."&lang=".LANGUAGE_ID."&mid_menu=1');"
        ];
    }
    $items[] = [
        "text" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME_SETT'),
        "items_id" => str_replace('.','_',$module_id).'_sett',
        "items"=>$level2
    ];
}
if(empty($items)) return;
$aMenu[] = array(
    "parent_menu" => "global_menu_settings",
    "section" => str_replace('.','_',$module_id),
    "sort" => 100,
    "module_id" => $module_id,
    "text" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME'),
    "title" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME'),
    "items_id" => str_replace('.','_',$module_id),
    "items" => $items,
);
return $aMenu;