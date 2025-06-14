<?php

namespace Awz\Bx24Lead\AdminPages;

use Bitrix\Main\Localization\Loc;
use Awz\Admin\IList;
use Awz\Admin\IParams;
use Awz\Admin\Helper;
use Awz\Bx24lead\Access\AccessController;
use Awz\Bx24lead\Access\Custom\ActionDictionary;

Loc::loadMessages(__FILE__);

class ProvidersList extends IList implements IParams {

    public function __construct($params){
        global $POST_RIGHT;
        if(AccessController::isEditSettings()){
            $POST_RIGHT = "W";
        }
        parent::__construct($params);
    }

    public function trigerGetRowListAdmin($row){
        Helper::viewListField($row, 'ID', ['type'=>'entity_link'], $this);
        Helper::editListField($row, 'ACTIVE', ['type'=>'checkbox'], $this);
        Helper::editListField($row, 'NAME', ['type'=>'string'], $this);
        $row->AddSelectField('ENTITY_ID', \Awz\Bx24Lead\Helper::getProviders());
    }

    public function trigerInitFilter(){
    }

    public function trigerGetRowListActions(array $actions): array
    {
        return $actions;
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_LIST_TITLE');
    }

    public static function getParams(): array
    {
        $arParams = [
            "ENTITY" => "\\Awz\\Bx24Lead\\ProvidersTable",
            "FILE_EDIT" => "awz_bx24lead_providers_edit.php",
            "BUTTON_CONTEXTS"=> ['btn_new'=> [
                'TEXT'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_LIST_ADD_BTN'),
                'ICON'	=> 'btn_new',
                'LINK'	=> 'awz_bx24lead_providers_edit.php?lang='.LANG
            ]],
            "ADD_GROUP_ACTIONS"=> ["edit","delete"],
            "ADD_LIST_ACTIONS"=> ["delete","edit"],
            "FIND"=> [],
            "FIND_FROM_ENTITY"=>['ID'=>[],'ACTIVE'=>[],'NAME'=>[]]
        ];
        return $arParams;
    }
}