<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\UI\Extension;
use Awz\Bx24lead\Access\AccessController;

Loc::loadMessages(__FILE__);
global $APPLICATION;
$module_id = "awz.bx24lead";
if(!Loader::includeModule($module_id)) return;
Loader::includeModule('iblock');
Extension::load('ui.sidepanel-content');
$request = Application::getInstance()->getContext()->getRequest();
$APPLICATION->SetTitle(Loc::getMessage('AWZ_BX24LEAD_OPT_TITLE'));

if($request->get('IFRAME_TYPE')==='SIDE_SLIDER'){
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
require_once('lib/access/include/moduleright.php');
CMain::finalActions();
die();
}

if(!AccessController::isViewSettings())
$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($request->get('addiblock') && AccessController::isEditSettings()){
    $r = \Bitrix\Iblock\PropertyTable::add([
        'NAME'=>'AWZ_HANDLED',
        'CODE'=>'AWZ_HANDLED',
        'PROPERTY_TYPE'=>'S',
        'IBLOCK_ID'=>$request->get('addiblock')
    ]);
}

if ($request->getRequestMethod()==='POST' && AccessController::isEditSettings() && $request->get('Update'))
{

    //Option::set($module_id, "test", $request->get("test")=='Y' ? 'Y' : 'N', "");
}

$aTabs = array();

$aTabs[] = array(
"DIV" => "edit1",
"TAB" => Loc::getMessage('AWZ_BX24LEAD_OPT_SECT1'),
"ICON" => "vote_settings",
"TITLE" => Loc::getMessage('AWZ_BX24LEAD_OPT_SECT1')
);

$saveUrl = $APPLICATION->GetCurPage(false).'?mid='.htmlspecialcharsbx($module_id).'&lang='.LANGUAGE_ID.'&mid_menu=1';
if($request->get('addiblock')){
    LocalRedirect($saveUrl);
}
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
<style>.adm-workarea option:checked {background-color: rgb(206, 206, 206);}</style>
<form method="POST" action="<?=$saveUrl?>" id="FORMACTION">
    <?
    $tabControl->BeginNextTab();
    \Bitrix\Main\UI\Extension::load("ui.alerts");
    ?>
    <tr>
        <td colspan="2" class="heading">
            <?=Loc::getMessage('AWZ_BX24LEAD_OPT_PROVIDERS')?>
        </td>
    </tr>
    <?
    $providers = \Awz\Bx24Lead\Helper::getProviders();
    foreach($providers as $providerId=>$provider){?>
        <tr>
            <td colspan="2" style="text-align: center;padding:5px;">
                <?=$provider?>
            </td>
        </tr>
    <?}?>

    <?
    if(Loader::includeModule('iblock')){
        ?>
        <tr>
            <td colspan="2" class="heading">
                <?=Loc::getMessage('AWZ_BX24LEAD_OPT_IB_TITLE')?>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="ui-alert ui-alert-primary">
                    <span class="ui-alert-message" style="width:100%;text-align: center;">
                        <?=Loc::getMessage('AWZ_BX24LEAD_OPT_SHOW_DESC')?>.
                    </span>
                </div>
            </td>
        </tr>
        <?
        $properties = [];
        $r = \Bitrix\Iblock\PropertyTable::getList([
            'select'=>['*'],
            'filter'=>['=CODE'=>'AWZ_HANDLED'],
            'order'=>['SORT'=>'ASC']
        ]);
        while($data = $r->fetch()){
            $properties[$data['IBLOCK_ID']] = $data;
        }
        $r = \Bitrix\Iblock\IblockTable::getList();
        ?>
            <?while($data = $r->fetch()){
                if(isset($properties[$data['ID']])) continue;
                ?>
        <tr>
            <td>[<?=$data['ID']?>] [<?=$data['IBLOCK_TYPE_ID']?>] <?=$data['NAME']?></td>
            <td>
                <?if(isset($properties[$data['ID']])){?>
                    <b><?=Loc::getMessage('AWZ_BX24LEAD_OPT_IB_OK')?></b>
                <?}else{?>
                    <a href="<?=$saveUrl?>&addiblock=<?=$data['ID']?>">
                        <?=Loc::getMessage('AWZ_BX24LEAD_OPT_ADD_IB')?>
                    </a>
                <?}?>
            </td>
        </tr>
            <?}?>
        <?
    }
    ?>

    <?
    $tabControl->Buttons();
    ?>
    <input <?if (!AccessController::isEditSettings()) echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage('AWZ_BX24LEAD_OPT_L_BTN_SAVE')?>" />
    <input type="hidden" name="Update" value="Y" />
    <?if(AccessController::isViewRight()){?>
        <button class="adm-header-btn adm-security-btn" onclick="BX.SidePanel.Instance.open('<?=$saveUrl?>');return false;">
            <?=Loc::getMessage('AWZ_BX24LEAD_OPT_SECT2')?>
        </button>
    <?}?>
    <?$tabControl->End();?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");