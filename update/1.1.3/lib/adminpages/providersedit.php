<?php

namespace Awz\Bx24Lead\AdminPages;

use Awz\Admin\Helper;
use Bitrix\Main\Localization\Loc;
use Awz\Admin\IForm;
use Awz\Admin\IParams;
use Bitrix\Main\Loader;
use Bitrix\Main\Data\Cache;
use Awz\Bx24lead\Access\AccessController;
use Awz\Bx24lead\Access\Custom\ActionDictionary;

Loc::loadMessages(__FILE__);

class ProvidersEdit extends IForm implements IParams {

    public function __construct($params){
        parent::__construct($params);
    }

    public static function disabled($param1, $param2 = []){
        return false;
    }

    public function trigerCheckActionAdd($func){
        if(AccessController::isEditSettings()) {
            return $func;
        }else{
            return ["\\Awz\\Bx24Lead\\AdminPages\\ProvidersEdit", "disabled"];
        }
    }

    public function trigerCheckActionUpdate($func){
        if(AccessController::isEditSettings()) {
            return $func;
        }else{
            return ["\\Awz\\Bx24Lead\\AdminPages\\ProvidersEdit", "disabled"];
        }
    }

    public function checkDelete($funcDel=null) {
        $entity = $this->getParam("ENTITY");
        if ($funcDel === null) {
            $funcDel = array($entity, 'delete');
        }
        if ($this->getParam("ID")!==false && ($_REQUEST['action']=='delete') && \check_bitrix_sessid()) {
            if(AccessController::isEditSettings()){
                call_user_func($funcDel, $this->getParam("ID"));
            }
            \LocalRedirect($this->getParam("LIST_URL").$this->getParam("MODIF").'lang='.LANG);
        }
    }

    public static function getTitle(): string
    {
        return Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_TITLE');
    }

    public static function getParams(): array
    {
        $providers = \Awz\Bx24Lead\Helper::getProviders();
        $arParams = array(
            "ENTITY" => "\\Awz\\Bx24Lead\\ProvidersTable",
            "BUTTON_CONTEXTS"=>array('btn_list'=>false),
            "LIST_URL"=>'/bitrix/admin/awz_bx24lead_providers_list.php',
            "DEFAULT_VALUES"=>[
                "FIELD_ACTIVE"=>"Y"
            ],
            "TABS"=>array(
                "edit1" => array(
                    "NAME"=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_EDIT1'),
                    "FIELDS" => array(
                        "ACTIVE",
                        "NAME",
                        "ENTITY_ID"=>[
                            "NAME"=>"ENTITY_ID",
                            "TYPE"=>"SELECT",
                            "VALUES"=>$providers
                        ],
                        "HOOK",
                        "PRM"=>[
                            "NAME"=>"PRM",
                            "TYPE"=>"CUSTOM",
                            "TITLE"=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_PRM'),
                            "FUNC_VIEW"=>"EntityPRM"
                        ]
                    )
                )
            )
        );
        return $arParams;
    }

    public function EntityPRMRow($arField, $values, $fieldsHook, $code, $fieldName){
        ?>
        <tr>
            <td style="border-bottom:1px dashed #000000;">
                <?if(substr($code,0,5)==='const'){?>

                <?}else{?>
                    #<?=$code?># -
                <?}?>
                <?=$fieldName?>
            </td>
            <td style="border-bottom:1px dashed #000000;">
                <?
                $rowCnt = 1;
                if(htmlspecialcharsEx($values['values'][$code]) && strlen(htmlspecialcharsEx($values['values'][$code]))){
                    $rowCnt = ceil(strlen(htmlspecialcharsEx($values['values'][$code]))/60);
                }
                ?>
                <textarea rows="<?=$rowCnt?>" cols="60" name="<?=$arField['NAME']?>[values][<?=$code?>]"><?=htmlspecialcharsEx($values['values'][$code])?></textarea>
            </td>
            <td style="border-bottom:1px dashed #000000;">
                <select name="<?=$arField['NAME']?>[<?=$code?>]">
                    <option value="">-</option>
                    <?foreach($fieldsHook as $field){?>
                        <option value="<?=$field['CODE']?>"<?=(isset($values[$code]) && ($field['CODE'] === $values[$code])) ? "selected=\"selected\"" : ""?>><?=$field['CODE']?><?if($field['isMultiple']){?>[]<?}?> - <?=$field['type']?> - <?=$field['title']?></option>
                    <?}?>
                </select>
            </td>
        </tr>
        <?php
    }

    public function EntityPRM($arField){
        if(!Loader::includeModule('iblock')) return;
        $ID = $this->getParam('ID');
        $HOOK = $this->getFieldValue('FIELD_HOOK');
        $values = $this->getFieldValue($arField['NAME']);
        $fieldsHook = \Awz\Bx24Lead\Helper::getExtFieldParams($HOOK);

        if(!$ID){
            ?><p style="color:red;"><?=Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_PRM_HELP')?></p><?php
        }else{
            $entityId = $this->getFieldValue('FIELD_ENTITY_ID');
            $properties = [];
            if(substr($entityId,0,7)==='IBLOCK_'){
                $r = \Bitrix\Iblock\PropertyTable::getList([
                    'select'=>['*'],
                    'filter'=>['=IBLOCK_ID'=>str_replace('IBLOCK_','',$entityId)],
                    'order'=>['SORT'=>'ASC']
                ]);
                while($data = $r->fetch()){
                    $properties[] = $data;
                }
            }
            ?>
            <table>


                <tr>
                    <td style="border-bottom:1px dashed #000000;">
                        <?=Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_OPTION_REGISTER_SONET_EVENT')?>
                    </td>
                    <td style="border-bottom:1px dashed #000000;">
                        <?
                        $valuesOpt = [
                            'Y'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_SEL_YES'),
                            'N'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_SEL_NO'),
                        ];
                        ?>
                        <select name="<?=$arField['NAME']?>[options][rsevent]">
                            <option value="">-</option>
                            <?foreach($valuesOpt as $v=>$title){?>
                                <option value="<?=$v?>"<?=(isset($values['options']['rsevent']) && ($v == $values['options']['rsevent'])) ? "selected=\"selected\"" : ""?>><?=$title?></option>
                            <?}?>
                        </select>
                    </td>
                    <td style="border-bottom:1px dashed #000000;">

                    </td>
                </tr>
                <tr>
                    <td style="border-bottom:1px dashed #000000;">
                        Создавать компанию
                    </td>
                    <td style="border-bottom:1px dashed #000000;">
                        <?
                        $valuesOpt = [
                            'Y'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_SEL_YES'),
                            'N'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_SEL_NO'),
                        ];
                        ?>
                        <select name="<?=$arField['NAME']?>[options][addcompany]" style="float:left;">
                            <option value="">-</option>
                            <?foreach($valuesOpt as $v=>$title){?>
                                <option value="<?=$v?>"<?=(isset($values['options']['addcompany']) && ($v == $values['options']['addcompany'])) ? "selected=\"selected\"" : ""?>><?=$title?></option>
                            <?}?>
                        </select>

                    </td>
                    <td style="border-bottom:1px dashed #000000;">
                        <select name="<?=$arField['NAME']?>[options][nalogid]" style="float:left;">
                            <option value="">Выберите код свойства с налоговым идентификатором</option>
                            <?foreach($fieldsHook as $code=>$field){
                                if(substr($code,0,9)!='[COMPANY]') continue;
                                $v = str_replace('[COMPANY]','', $code);
                                if($v === 'NALOG_ID') continue;
                                $title = '['.$v.'] - '.$field['title'];
                                ?>
                                <option value="<?=$v?>"<?=(isset($values['options']['nalogid']) && ($v == $values['options']['nalogid'])) ? "selected=\"selected\"" : ""?>><?=$title?></option>
                            <?}?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="border-bottom:1px dashed #000000;">
                        Создавать контакт
                    </td>
                    <td style="border-bottom:1px dashed #000000;">
                        <?
                        $valuesOpt = [
                            'Y'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_SEL_YES'),
                            'N'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_SEL_NO'),
                        ];
                        ?>
                        <select name="<?=$arField['NAME']?>[options][addcontact]">
                            <option value="">-</option>
                            <?foreach($valuesOpt as $v=>$title){?>
                                <option value="<?=$v?>"<?=(isset($values['options']['addcontact']) && ($v == $values['options']['addcontact'])) ? "selected=\"selected\"" : ""?>><?=$title?></option>
                            <?}?>
                        </select>
                    </td>
                    <td style="border-bottom:1px dashed #000000;">
                        <?
                        $valuesOpt = [
                            'PHONE'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_OPTION_CONTACT_PHONE'),
                            'EMAIL'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_OPTION_CONTACT_EMAIL'),
                            'PHONE,EMAIL'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_OPTION_CONTACT_PHONE').', '.Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_OPTION_CONTACT_EMAIL'),
                            'EMAIL,PHONE'=>Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_OPTION_CONTACT_EMAIL').', '.Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_OPTION_CONTACT_PHONE'),
                        ];
                        ?>
                        <select name="<?=$arField['NAME']?>[options][contact]">
                            <option value="">Укажите как искать контакт</option>
                            <?foreach($valuesOpt as $v=>$title){?>
                                <option value="<?=$v?>"<?=(isset($values['options']['contact']) && ($v == $values['options']['contact'])) ? "selected=\"selected\"" : ""?>><?=$title?></option>
                            <?}?>
                        </select>
                    </td>
                </tr>



            <?
            $arFields = [];
            $orderProps = [];
            $formProps = [];
            //print_r($entityId);die();
            if(substr($entityId,0,7)==='IBLOCK_') {
                $arFields = \CIBlockParameters::GetFieldCode('-', '-');
            }elseif(substr($entityId,0,8)==='WEBFORM_') {
                $columns = [];
                $answers = [];
                $answers2 = [];
                \CForm::GetResultAnswerArray(str_replace('WEBFORM_','',$entityId), $columns, $answers, $answers2);
                foreach($columns as $columnData){
                    $formProps[] = [
                            'ID'=>$columnData['ID'],
                            'CODE'=>$columnData['SID'],
                            'NAME'=>$columnData['TITLE']
                    ];
                }
            }elseif(substr($entityId,0,11)==='ORDER_SALE_'){
                $personTypeId = (int) str_replace('ORDER_SALE_','',$entityId);
                $personTypeIdData = \Bitrix\Sale\Internals\PersonTypeTable::getById($personTypeId)->fetch();
                if($personTypeId && Loader::includeModule('sale')){
                    $order = \Bitrix\Sale\Order::create($personTypeIdData['LID']);
                    $order->setPersonTypeId($personTypeId);
                    $propertyCollection = $order->getPropertyCollection();
                    if($propertyCollection){
                        /** @var $prop \Bitrix\Sale\PropertyValue */
                        foreach($propertyCollection as $prop){
                            //echo'<pre>';print_r($prop->getFields());echo'</pre>';
                            $orderProps[] = [
                                'ID'=>$prop->getPropertyId(),
                                'CODE'=>$prop->getField('CODE'),
                                'NAME'=>$prop->getField('NAME')
                            ];
                        }
                    }
                }
            }
            //echo'<pre>';print_r($orderProps);echo'</pre>';
            //print_r($orderProps);
            //die();

            $fieldsHookSimple = [];
            $fieldsHookMultiple = [];
            $fieldsHookFileM = [];
            $fieldsHookFile = [];
            foreach($fieldsHook as $code=>$field){
                //echo'<pre>';print_r($field);echo'</pre>';
                if($field['type']=='file'){
                    if($field['isMultiple']){
                        $fieldsHookFileM[$code] = $field;
                    }else{
                        $fieldsHookFile[$code] = $field;
                    }
                }else{
                    if($field['isMultiple']){
                        $fieldsHookMultiple[$code] = $field;
                    }else{
                        $fieldsHookSimple[$code] = $field;
                    }
                }
            }


            for($i=1;$i<=\Awz\Bx24Lead\Helper::MAX_CONSTANTS_ROW;$i++){
                $code = 'const'.$i;
                $fieldName = Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_FIELD_CONST').' '.$i;
                $this->EntityPRMRow($arField, $values, $fieldsHook, $code, $fieldName);
            }

            foreach($arFields['VALUES'] as $code=>$fieldName){
                if(in_array($code, ['DETAIL_PICTURE','PREVIEW_PICTURE'])){
                    $this->EntityPRMRow($arField, $values, $fieldsHookFile, $code, $fieldName);
                }else{
                    $this->EntityPRMRow($arField, $values, $fieldsHookSimple, $code, $fieldName);
                }
            }
            if(Loader::includeModule('sale') && substr($entityId,0,11)==='ORDER_SALE_'){
                /** @var $field \Bitrix\Main\ORM\Fields\Field */
                foreach(\Bitrix\Sale\Internals\OrderTable::getMap() as $field){
                    if($field instanceof \Bitrix\Main\ORM\Fields\Relations\Reference) continue;
                    $code = $field->getName();
                    $fieldName = $field->getTitle();
                    $this->EntityPRMRow($arField, $values, $fieldsHook, $code, $fieldName);
                }
            }
            foreach($orderProps as $prop){
                $code = 'PROPERTY_'.$prop['CODE'];
                $fieldName = 'ID:'.$prop['ID'].' - '.$prop['NAME'];
                $this->EntityPRMRow($arField, $values, $fieldsHook, $code, $fieldName);
            }
            foreach($formProps as $prop){
                $code = $prop['CODE'];
                $fieldName = 'ID:'.$prop['ID'].' - '.$prop['NAME'];
                $this->EntityPRMRow($arField, $values, $fieldsHook, $code, $fieldName);
            }
            foreach($properties as $prop){
                if($prop['CODE'] === 'AWZ_HANDLED') continue;
                $code = 'PROPERTY_'.$prop['CODE'];
                $fieldName = $prop['NAME'];
                //echo'<pre>';print_r($prop);echo'</pre>';
                if($prop['PROPERTY_TYPE']=='F'){
                    if($prop['MULTIPLE']=='Y'){
                        $this->EntityPRMRow($arField, $values, $fieldsHookFileM, $code, $fieldName);
                    }else{
                        $this->EntityPRMRow($arField, $values, array_merge($fieldsHookFileM,$fieldsHookFile), $code, $fieldName);
                    }
                }else{
                    if($prop['MULTIPLE']=='Y'){
                        $this->EntityPRMRow($arField, $values, $fieldsHookMultiple, $code, $fieldName);
                    }else{
                        $this->EntityPRMRow($arField, $values, $fieldsHook, $code, $fieldName);
                    }
                }

            }?>
            </table>
            <?
            $checkSpr = false;
            foreach($fieldsHook as $code=>$field){
                if(!isset($field['items'])) continue;
                if(empty($field['items'])) continue;
                $checkSpr = true;
                break;
            }
            ?>
            <?if($checkSpr){?>
            <h2><?=Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_EDIT_SPRAV')?></h2>
            <table>
                <?foreach($fieldsHook as $code=>$field){

                    ?>
                    <tr><th style="text-align:left;" colspan="2"><?=$field['CODE']?><?if($field['isMultiple']){?>[]<?}?> - <?=$field['type']?> - <?=$field['title']?></th></tr>
                    <?foreach($field['items'] as $item){?>
                        <tr>
                            <td><?=$item['ID']?></td>
                            <td><?=$item['VALUE']?></td>
                        </tr>
                    <?}?>
                <?}?>

            </table>
            <?}?>
            <?
        }
    }

}