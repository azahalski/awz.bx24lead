<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

if(!Loader::includeModule('awz.admin')){

    global $APPLICATION, $adminPage, $USER, $adminMenu, $adminChain, $POST_RIGHT;

    if(
        (defined("TIMELIMIT_EDITION") && TIMELIMIT_EDITION == "Y") ||
        (defined("DEMO") && DEMO == "Y")
    )
    {
        global $SiteExpireDate;
    }

    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
    \CAdminMessage::ShowMessage([
        'TYPE'=>'ERROR',
        'MESSAGE'=>Loc::getMessage('AWZ_BXORM_ADMIN_CHECK_ERR1')
    ]);
    ?>
    <h2><?=Loc::getMessage('AWZ_BXORM_ADMIN_CHECK_ERR2')?></h2>
    <a target="_blank" href="https://marketplace.1c-bitrix.ru/solutions/awz.admin/">
        <?=Loc::getMessage('AWZ_BXORM_ADMIN_CHECK_ERR3')?>
    </a> |
    <a target="_blank" href="https://github.com/zahalski/awz.admin">
        GitHub
    </a>
    <?php
    require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
    return;
}