<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
Loc::loadMessages(__FILE__);
use Bitrix\Main\Config\Option;
$module_id = "awz.bx24lead";

global $APPLICATION;
$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($POST_RIGHT == "D") return;

if(Loader::includeModule($module_id)){
    $aMenu[] = array(
        "parent_menu" => "global_menu_settings",
        "section" => str_replace('.','_',$module_id),
        "sort" => 100,
        "module_id" => $module_id,
        "text" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME'),
        "title" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME'),
        "items_id" => str_replace('.','_',$module_id),
        "items" => array(
            array(
                "text" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME_MENU1'),
                "url" => "awz_bx24lead_providers_list.php?lang=".LANGUAGE_ID,
                "more_url" => Array("awz_bx24lead_providers_edit.php?lang=".LANGUAGE_ID),
                "title" => Loc::getMessage('AWZ_BX24LEAD_MENU_NAME_MENU1'),
                "sort" => 100,
            ),
        ),
    );
    return $aMenu;
}