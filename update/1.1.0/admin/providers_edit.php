<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Awz\Bx24lead\Access\AccessController;
use Awz\Bx24lead\Access\Custom\ActionDictionary;

global $APPLICATION;
$dirs = explode(DIRECTORY_SEPARATOR, dirname(__DIR__, 1));
$module_id = array_pop($dirs);
unset($dirs);
Loc::loadMessages(__FILE__);

if(!Loader::includeModule($module_id)) return;

if(!AccessController::isViewSettings())
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

if(file_exists('check_awz_admin.php')){
    require_once('check_awz_admin.php');
}elseif(!Loader::includeModule('awz.admin')){
    return;
}

/* "Awz\Bx24Lead\AdminPages\ProvidersEdit" replace generator */
use Awz\Bx24Lead\AdminPages\ProvidersEdit as PageItemEdit;

$APPLICATION->SetTitle(PageItemEdit::getTitle());
$arParams = PageItemEdit::getParams();

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/awz.admin/include/handler_el.php");
/* @var bool $customPrint */
if(!$customPrint) {
    $adminCustom = new PageItemEdit($arParams);
    $adminCustom->defaultInterface();
}