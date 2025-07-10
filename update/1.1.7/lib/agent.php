<?php
namespace Awz\Bx24Lead;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Application;
use Bitrix\Main\IO;
use Bitrix\Main\Config\Option;

class Agent {

    /**
     * фоновое задание на отправку addBackgroundJob
     *
     * @param int $id
     * @param int $iblockId
     * @return void
     */
    public static function iblockJob(int $id, int $iblockId){

        $providers = ProvidersTable::getList([
            'select'=>['ID'],
            'filter'=>[
                '=ACTIVE'=>'Y','=ENTITY_ID'=>'IBLOCK_'.$iblockId
            ],
            'cache'=>[
                'ttl' => Helper::CAHE_TIME,
                'cache_joins' => true
            ]
        ]);
        while($data = $providers->fetch()){
            $result = Agent::sendForm($id, $iblockId, Helper::ENTITY_IBLOCK,3, (int)$data['ID']);
            if($result){
                \CAgent::AddAgent($result, "awz.bx24lead", "N", 600);
            }
        }
    }

    /**
     * фоновое задание на отправку addBackgroundJob
     *
     * @param int $id
     * @param int $iblockId
     * @return void
     */
    public static function formJob(int $resultId, int $formId){

        $providers = ProvidersTable::getList([
            'select'=>['ID'],
            'filter'=>[
                '=ACTIVE'=>'Y','=ENTITY_ID'=>'WEBFORM_'.$formId
            ],
            'cache'=>[
                'ttl' => Helper::CAHE_TIME,
                'cache_joins' => true
            ]
        ]);
        while($data = $providers->fetch()){
            $result = Agent::sendForm($resultId, $formId, Helper::ENTITY_WEBFORM,3, (int)$data['ID']);
            if($result){
                \CAgent::AddAgent($result, "awz.bx24lead", "N", 600);
            }
        }
    }


    /**
     * фоновое задание на отправку addBackgroundJob
     *
     * @param int $id
     * @param int $personTypeId
     * @return void
     */
    public static function saleJob(int $id, int $personTypeId){

        $providers = ProvidersTable::getList([
            'select'=>['ID'],
            'filter'=>[
                '=ACTIVE'=>'Y','=ENTITY_ID'=>'ORDER_SALE_'.$personTypeId
            ],
            'cache'=>[
                'ttl' => Helper::CAHE_TIME,
                'cache_joins' => true
            ]
        ]);
        while($data = $providers->fetch()){
            $result = Agent::sendForm($id, $personTypeId, Helper::ENTITY_ORDER,3, (int)$data['ID']);
            if($result){
                \CAgent::AddAgent($result, "awz.bx24lead", "N", 600);
            }
        }
    }



    public static function sendForm(int $id, int $entityId=0, string $entityType=Helper::ENTITY_IBLOCK, int $maxRetr = 0, int $providerId = 0, int $nextTime=0){
        //print_r([$id,$entityId,$maxRetr,$providerId]);
        //die();

        if($nextTime && $nextTime>time()){
            return "\Awz\Bx24Lead\Agent::sendForm(".$id.",".$entityId.",'".$entityType."',".$maxRetr.",".$providerId.",".$nextTime.");";
        }

        $obEl = null;
        $order = null;
        $arForm = null;
        $arFormData = null;
        $arFormResult = null;
        if($entityType==Helper::ENTITY_IBLOCK) {
            if (!Loader::includeModule('iblock')) return false;

            if(!$entityId){
                $obEl = \CIBLockElement::GetById($id)->GetNextElement();
                if($obEl){
                    $fl = $obEl->GetFields();
                    $entityId = $fl['IBLOCK_ID'];
                }else{
                    return false;
                }
            }
        }

        if($entityType==Helper::ENTITY_ORDER){
            if (!Loader::includeModule('sale')) return false;
            $order = \Bitrix\Sale\Order::load($id);

            if(!$order) return;
            if(!$entityId){
                $entityId = $order->getPersonTypeId();
            }
        }

        if($entityType==Helper::ENTITY_WEBFORM){
            if (!Loader::includeModule('form')) return false;
            \CFormResult::GetDataByID($id, [], $arFormResult, $arForm);
            $arFormData = [];
            foreach($arForm as $keyField=>$keyValueAr){
                if(!isset($arFormData[$keyField]))
                    $arFormData[$keyField] = [];
                foreach($keyValueAr as $vAr){
                    if($vAr['FIELD_TYPE']=='file' && $vAr['USER_FILE_ID']){
                        $arFormData[$keyField][] = \CFile::getPath($vAr['USER_FILE_ID']);
                    }elseif($vAr['FIELD_TYPE']=='dropdown' && $vAr['VALUE']){
                        $arFormData[$keyField][] = $vAr['VALUE'];
                    }else{
                        $arFormData[$keyField][] = $vAr['USER_TEXT'];
                    }
                }
            }
            $arFormData['arForm'] = $arForm;
            //echo'<pre>';print_r($arFormData);echo'</pre>';
            //echo'<pre>';print_r($arForm);echo'</pre>';
            // die();
        }



        if($providerId){
            $provider = ProvidersTable::getList([
                'select'=>['*'],
                'filter'=>[
                    '=ACTIVE'=>'Y','ID'=>$providerId
                ],
                'cache'=>[
                    'ttl' => Helper::CAHE_TIME,
                    'cache_joins' => true
                ]
            ])->fetch();
        }else{
            $provider = ProvidersTable::getProvider($entityType.'_' . $entityId);
        }
        if(empty($provider)) return false;

        $urlData = explode('|', $provider['HOOK']);

        $provider['MAIN_HOOK'] = str_replace(['crm.lead.add','crm.deal.add'],['',''],$provider['HOOK']);
        $fieldsHook = \Awz\Bx24Lead\Helper::getExtFieldParams($provider['HOOK']);

        $extParams = Helper::getExtFieldParams($provider['HOOK']);
        //$extParamsData = $extParams->getData();
        $valuesList = [];
        $macrosList = [];

        if($entityType==Helper::ENTITY_IBLOCK){
            if(!$obEl) $obEl = \CIBLockElement::GetById($id)->GetNextElement();
            if($obEl) {
                $fl = $obEl->GetFields();
                $entityId = $fl['IBLOCK_ID'];
                $props = $obEl->GetProperties();
                foreach($fl as $k=>$v){
                    if(is_string($v)){
                        $macrosList['#'.$k.'#'] = htmlspecialcharsBack($v);
                        $valuesList[$k] = htmlspecialcharsBack($v);
                    }else{
                        $valuesList[$k] = $v;
                    }
                    if(in_array($k, ['PREIEW_PICTURE','DETAIL_PICTURE']) && $v){
                        $filePath = \CFile::getPath($v);
                        $file = new IO\File(Application::getDocumentRoot().$filePath);
                        if($file->isExists()){
                            $arFile = \CFile::getById($v)->fetch();
                            $valuesList[$k] = [[
                                'fileData'=>[
                                    $arFile['FILE_NAME'],
                                    base64_encode($file->getContents())
                                ]
                            ]];
                        }
                    }
                }
                foreach($props as $k=>$v){
                    if(is_string($v['VALUE'])){
                        $macrosList['#PROPERTY_'.$k.'#'] = htmlspecialcharsBack($v['VALUE']);
                        $valuesList['PROPERTY_'.$k] = htmlspecialcharsBack($v['VALUE']);
                    }else{
                        $valuesList['PROPERTY_'.$k] = $v['VALUE'];
                    }
                    if($v['PROPERTY_TYPE']=='F'){
                        $files = is_array($valuesList['PROPERTY_'.$k]) ? $valuesList['PROPERTY_'.$k] : [$valuesList['PROPERTY_'.$k]];
                        $filesData = [];
                        foreach($files as $fileId){
                            if($fileId){

                                $filePath = \CFile::getPath($fileId);
                                $file = new IO\File(Application::getDocumentRoot().$filePath);
                                if($file->isExists()){
                                    $arFile = \CFile::getById($fileId)->fetch();
                                    $filesData[] = [
                                        'fileData'=>[
                                            $arFile['FILE_NAME'],
                                            base64_encode($file->getContents())
                                        ]
                                    ];
                                }

                            }
                        }
                        $valuesList['PROPERTY_'.$k] = $filesData;
                    }
                }
            }
        }
        elseif($entityType == Helper::ENTITY_WEBFORM){
            foreach ($arFormData as $code=>$values){
                if(isset($values[0]) && is_string($values[0])){
                    $macrosList['#'.$code.'#'] = htmlspecialcharsBack($values[0]);
                }else{
                    $macrosList['#'.$code.'#'] = '';
                }
                $valuesList[$code] = $values;
            }
        }
        elseif($entityType == Helper::ENTITY_ORDER){
            $valuesList = $order->getFieldValues();
            foreach($valuesList as $code=>$value){
                $macrosList['#'.$code.'#'] = htmlspecialcharsBack($value);
            }
            $propertyCollection = $order->getPropertyCollection();
            $shipmentCollection = $order->getShipmentCollection();
            //$paymentCollection = $order->getShipmentCollection();
            foreach($propertyCollection as $prop){
                if(!$prop->getField('VALUE') && isset($macrosList['#PROPERTY_'.$prop->getField('CODE').'#'])) continue;
                $macrosList['#PROPERTY_'.$prop->getField('CODE').'#'] = htmlspecialcharsBack($prop->getField('VALUE'));
                $valuesList['PROPERTY_'.$prop->getField('CODE')] = $prop->getField('VALUE');
            }
            $valuesList['ORDER'] = $order;

            //$order = $arParams['ORDER'];

        }


        if(!empty($valuesList)) {

            $prepareData = [];
            foreach($provider['PRM'] as $code=>$bx24Code){
                if($code === 'values') continue;
                if($code === 'options') continue;
                if(substr($code, 0,5)==='const'){
                    if(isset($provider['PRM']['values'][$code]) && $provider['PRM']['values'][$code])
                        $prepareData[$bx24Code] = $provider['PRM']['values'][$code];
                }elseif(substr($code, 0,9)==='PROPERTY_'){
                    if(isset($provider['PRM']['values'][$code]) && $provider['PRM']['values'][$code])
                        $prepareData[$bx24Code] = $provider['PRM']['values'][$code];
                }else{
                    if(isset($provider['PRM']['values'][$code]) && $provider['PRM']['values'][$code])
                        $prepareData[$bx24Code] = $provider['PRM']['values'][$code];
                }
                if(!$prepareData[$bx24Code]) {
                    $prepareData[$bx24Code] = $valuesList[$code];
                }
                if(is_string($prepareData[$bx24Code]) && strpos($prepareData[$bx24Code], '#')!==false){
                    $prepareData[$bx24Code] = str_replace(array_keys($macrosList), array_values($macrosList), $prepareData[$bx24Code]);
                }
                if($fieldsHook[$bx24Code]['type'] == 'file'){
                    if(!$fieldsHook[$bx24Code]['isMultiple'] && !empty($prepareData[$bx24Code])){
                        $prepareData[$bx24Code] = $prepareData[$bx24Code][0];
                    }
                }
            }

            //echo'<pre>';print_r($prepareData);echo'</pre>';
            //die();

            foreach($prepareData as $k=>&$v){
                $tmpv = $v;
                try{
                    //print_r([$v, $valuesList]);
                    if(is_string($v) && strpos($v,'<?')!==false){
                        $v = Helper::executePhp($v, $valuesList, $provider);
                        $unserTest = unserialize($v, ['allowed_classes' => false]);
                        if($unserTest!==false){
                            $v = $unserTest;
                        }elseif($v === 'b:0;'){
                            $v = false;
                        }
                    }
                }catch (\Exception $e){
                    $v = $tmpv;
                }
            }
            unset($v);

            if($urlData[0]=='amo'){

                $CONTACT_ID = false;
                if(isset($provider['PRM']['options']['contact']) && $provider['PRM']['options']['contact']){
                    $findAr = explode(",",$provider['PRM']['options']['contact']);
                    foreach($findAr as $type){
                        if($CONTACT_ID) break;
                        if($type === 'PHONE' && !empty($prepareData['[CONTACT]PHONE'])){
                            if($prepareData['[CONTACT]PHONE']){
                                $url = str_replace('/api/v4/leads','/api/v4/contacts',$urlData[1]);
                                $client = new HttpClient();
                                $client->disableSslVerification();
                                $client->setTimeout(5);
                                $client->setStreamTimeout(5);
                                $client->setHeader('Authorization', 'Bearer '.$urlData[2]);
                                $r = $client->get($url.'?query='.$prepareData['[CONTACT]PHONE']);

                                if($r){
                                    $r = Json::decode($r);
                                    if(isset($r['_embedded']['contacts'][0]['id'])){
                                        $CONTACT_ID = (int)$r['_embedded']['contacts'][0]['id'];
                                    }
                                }
                            }
                        }elseif($type === 'EMAIL' && !empty($prepareData['[CONTACT]EMAIL'])){
                            if($prepareData['[CONTACT]EMAIL']){
                                $url = str_replace('/api/v4/leads','/api/v4/contacts',$urlData[1]);
                                $client = new HttpClient();
                                $client->disableSslVerification();
                                $client->setTimeout(5);
                                $client->setStreamTimeout(5);
                                $client->setHeader('Authorization', 'Bearer '.$urlData[2]);
                                $r = $client->get($url.'?query='.$prepareData['[CONTACT]EMAIL']);

                                if($r){
                                    $r = Json::decode($r);
                                    if(isset($r['_embedded']['contacts'][0]['id'])){
                                        $CONTACT_ID = (int)$r['_embedded']['contacts'][0]['id'];
                                    }
                                }
                            }
                        }
                    }
                }
                if(!$CONTACT_ID && isset($provider['PRM']['options']['addcontact']) && $provider['PRM']['options']['addcontact']=='Y'){
                    $bodyf = [
                        'custom_fields_values'=>[],
                    ];
                    if(!empty($prepareData['[CONTACT]NAME'])){
                        $bodyf['name'] = $prepareData['[CONTACT]NAME'];
                    }
                    if(!empty($prepareData['[CONTACT]PHONE'])){
                        $bodyf['custom_fields_values'][] = [
                            'field_code'=>'PHONE',
                            'values'=>[
                                ["value"=> $prepareData['[CONTACT]PHONE'], "enum_code"=> "WORK"]
                            ]
                        ];
                    }
                    if(!empty($prepareData['[CONTACT]EMAIL'])){
                        $bodyf['custom_fields_values'][] = [
                            'field_code'=>'EMAIL',
                            'values'=>[
                                ["value"=> $prepareData['[CONTACT]EMAIL'], "enum_code"=> "WORK"]
                            ]
                        ];
                    }

                    $url = str_replace('/api/v4/leads','/api/v4/contacts',$urlData[1]);
                    $client = new HttpClient();
                    $client->disableSslVerification();
                    $client->setTimeout(5);
                    $client->setStreamTimeout(5);
                    $client->setHeader('Authorization', 'Bearer '.$urlData[2]);
                    $r = $client->post($url,Json::encode([$bodyf]));
                    $rJson = Json::decode($r);
                    //echo '<pre>';print_r([$rJson,$bodyf,$prepareData]);echo'</pre>';
                    $CONTACT_ID = $rJson['_embedded']['contacts'][0]['id'];
                }

                $bodyf = [];
                if($CONTACT_ID){
                    $bodyf['_embedded']['contacts'] = [['id'=>$CONTACT_ID]];
                }
                foreach($prepareData as $fieldCode=>$fieldValue){
                    if(!isset($extParams[$fieldCode]['type'])) continue;
                    if(substr($fieldCode,0,9) == '[CONTACT]') continue;
                    if($extParams[$fieldCode]['type']=='int'){
                        $fieldValue = (int)$fieldValue;
                    }elseif($extParams[$fieldCode]['type']=='text'){
                        $fieldValue = (string)$fieldValue;
                    }
                    if(substr($fieldCode,0,24)=='embedded[custom_fields]['){
                        $extCode = substr($fieldCode,24,-1);
                        $bodyf['custom_fields_values'][] = [
                            'field_id'=>(int)$extCode,
                            'values'=>[['value'=>$fieldValue]]
                        ];
                    }else{
                        $bodyf[$fieldCode] = $fieldValue;
                    }
                }
                $url = $urlData[1];
                $client = new HttpClient();
                $client->disableSslVerification();
                $client->setTimeout(5);
                $client->setStreamTimeout(5);
                $client->setHeader('Authorization', 'Bearer '.$urlData[2]);

                $r = $client->post($url,Json::encode([$bodyf]));
                //echo'<pre>';print_r([$url,$bodyf,$r]);echo'</pre>';
                //die();
                if($r){
                    if($entityType==Helper::ENTITY_IBLOCK) {
                        \CIBlockElement::SetPropertyValueCode($id, 'AWZ_HANDLED', $r);
                    }else{
                        \CEventLog::Add([
                            'SEVERITY'=>'INFO',
                            'AUDIT_TYPE_ID'=>'REST',
                            'MODULE_ID'=>'awz.bx24lead',
                            'ITEM_ID'=>$id,
                            'DESCRIPTION'=>$r
                        ]);
                    }
                }
                elseif($maxRetr){
                    if($maxRetr == 3){
                        $nextTime = time() + 15*60; //через 15 минут
                    }elseif($maxRetr == 2){
                        $nextTime = time() + 2*60*60; //через 2 часа
                    }elseif($maxRetr == 1){
                        $nextTime = time() + 8*60*60; //через 8 часов
                    }
                    $maxRetr = $maxRetr - 1;
                    return "\Awz\Bx24Lead\Agent::sendForm(".$id.",".$entityId.",'".$entityType."',".$maxRetr.",".$providerId.",".$nextTime.");";
                }

            }
            else{
                if(isset($prepareData['PHONE_WORK'])){
                    if(!isset($prepareData['PHONE'])) $prepareData['PHONE'] = [];
                    $prepareData['PHONE'][] = ['VALUE'=>$prepareData['PHONE_WORK'], 'TYPE'=>"WORK"];
                    unset($prepareData['PHONE_WORK']);
                }
                if(isset($prepareData['[CONTACT]PHONE_WORK'])){
                    if(!isset($prepareData['[CONTACT]PHONE'])) $prepareData['[CONTACT]PHONE'] = [];
                    $prepareData['[CONTACT]PHONE'][] = ['VALUE'=>$prepareData['[CONTACT]PHONE_WORK'], 'TYPE'=>"WORK"];
                    unset($prepareData['[CONTACT]PHONE_WORK']);
                }
                if(isset($prepareData['[COMPANY]PHONE_WORK'])){
                    if(!isset($prepareData['[COMPANY]PHONE'])) $prepareData['[COMPANY]PHONE'] = [];
                    $prepareData['[COMPANY]PHONE'][] = ['VALUE'=>$prepareData['[COMPANY]PHONE_WORK'], 'TYPE'=>"WORK"];
                    unset($prepareData['[COMPANY]PHONE_WORK']);
                }
                if(isset($prepareData['PHONE_MOBILE'])){
                    if(!isset($prepareData['PHONE'])) $prepareData['PHONE'] = [];
                    $prepareData['PHONE'][] = ['VALUE'=>$prepareData['PHONE_MOBILE'], 'TYPE'=>"MOBILE"];
                    unset($prepareData['PHONE_MOBILE']);
                }
                if(isset($prepareData['[CONTACT]PHONE_MOBILE'])){
                    if(!isset($prepareData['[CONTACT]PHONE'])) $prepareData['[CONTACT]PHONE'] = [];
                    $prepareData['[CONTACT]PHONE'][] = ['VALUE'=>$prepareData['[CONTACT]PHONE_MOBILE'], 'TYPE'=>"MOBILE"];
                    unset($prepareData['[CONTACT]PHONE_MOBILE']);
                }
                if(isset($prepareData['[COMPANY]PHONE_MOBILE'])){
                    if(!isset($prepareData['[COMPANY]PHONE'])) $prepareData['[COMPANY]PHONE'] = [];
                    $prepareData['[COMPANY]PHONE'][] = ['VALUE'=>$prepareData['[COMPANY]PHONE_MOBILE'], 'TYPE'=>"MOBILE"];
                    unset($prepareData['[COMPANY]PHONE_MOBILE']);
                }
                if(isset($prepareData['PHONE_HOME'])){
                    if(!isset($prepareData['PHONE'])) $prepareData['PHONE'] = [];
                    $prepareData['PHONE'][] = ['VALUE'=>$prepareData['PHONE_HOME'], 'TYPE'=>"HOME"];
                    unset($prepareData['PHONE_HOME']);
                }
                if(isset($prepareData['[CONTACT]PHONE_HOME'])){
                    if(!isset($prepareData['[CONTACT]PHONE'])) $prepareData['[CONTACT]PHONE'] = [];
                    $prepareData['[CONTACT]PHONE'][] = ['VALUE'=>$prepareData['[CONTACT]PHONE_HOME'], 'TYPE'=>"HOME"];
                    unset($prepareData['[CONTACT]PHONE_HOME']);
                }
                if(isset($prepareData['[COMPANY]PHONE_HOME'])){
                    if(!isset($prepareData['[COMPANY]PHONE'])) $prepareData['[COMPANY]PHONE'] = [];
                    $prepareData['[COMPANY]PHONE'][] = ['VALUE'=>$prepareData['[COMPANY]PHONE_HOME'], 'TYPE'=>"HOME"];
                    unset($prepareData['[COMPANY]PHONE_HOME']);
                }
                if(isset($prepareData['EMAIL_WORK'])){
                    if(!isset($prepareData['EMAIL'])) $prepareData['EMAIL'] = [];
                    $prepareData['EMAIL'][] = ['VALUE'=>$prepareData['EMAIL_WORK'], 'TYPE'=>"WORK"];
                    unset($prepareData['EMAIL_WORK']);
                }
                if(isset($prepareData['[CONTACT]EMAIL_WORK'])){
                    if(!isset($prepareData['[CONTACT]EMAIL'])) $prepareData['[CONTACT]EMAIL'] = [];
                    $prepareData['[CONTACT]EMAIL'][] = ['VALUE'=>$prepareData['[CONTACT]EMAIL_WORK'], 'TYPE'=>"WORK"];
                    unset($prepareData['[CONTACT]EMAIL_WORK']);
                }
                if(isset($prepareData['[COMPANY]EMAIL_WORK'])){
                    if(!isset($prepareData['[COMPANY]EMAIL'])) $prepareData['[COMPANY]EMAIL'] = [];
                    $prepareData['[COMPANY]EMAIL'][] = ['VALUE'=>$prepareData['[COMPANY]EMAIL_WORK'], 'TYPE'=>"WORK"];
                    unset($prepareData['[COMPANY]EMAIL_WORK']);
                }
                if(isset($prepareData['EMAIL_HOME'])){
                    if(!isset($prepareData['EMAIL'])) $prepareData['EMAIL'] = [];
                    $prepareData['EMAIL'][] = ['VALUE'=>$prepareData['EMAIL_HOME'], 'TYPE'=>"HOME"];
                    unset($prepareData['EMAIL_HOME']);
                }
                if(isset($prepareData['[CONTACT]EMAIL_HOME'])){
                    if(!isset($prepareData['[CONTACT]EMAIL'])) $prepareData['[CONTACT]EMAIL'] = [];
                    $prepareData['[CONTACT]EMAIL'][] = ['VALUE'=>$prepareData['[CONTACT]EMAIL_HOME'], 'TYPE'=>"HOME"];
                    unset($prepareData['[CONTACT]EMAIL_HOME']);
                }
                if(isset($prepareData['[COMPANY]EMAIL_HOME'])){
                    if(!isset($prepareData['[COMPANY]EMAIL'])) $prepareData['[COMPANY]EMAIL'] = [];
                    $prepareData['[COMPANY]EMAIL'][] = ['VALUE'=>$prepareData['[COMPANY]EMAIL_HOME'], 'TYPE'=>"HOME"];
                    unset($prepareData['[COMPANY]EMAIL_HOME']);
                }

                $CONTACT_ID = false;
                $COMPANY_ID = false;
                if(isset($provider['PRM']['options']['contact']) && $provider['PRM']['options']['contact']){
                    $findAr = explode(",",$provider['PRM']['options']['contact']);
                    foreach($findAr as $type){
                        if($CONTACT_ID) break;
                        if($type === 'PHONE' && !empty($prepareData['PHONE'])){
                            foreach($prepareData['PHONE'] as $phone){
                                if($CONTACT_ID) break;
                                if($phone['VALUE']){
                                    $body = [
                                        'filter' => [
                                            'PHONE'=>$phone['VALUE']
                                        ],
                                        'select'=>[
                                            "ID"
                                        ]
                                    ];
                                    $url = $provider['MAIN_HOOK'].'crm.contact.list';
                                    $client = new HttpClient();
                                    $client->disableSslVerification();
                                    $client->setTimeout(5);
                                    $client->setStreamTimeout(5);
                                    $r = $client->post($url,$body);

                                    if($r){
                                        $r = Json::decode($r);
                                        if(isset($r['result'][0]['ID']) && $r['result'][0]['ID']) {
                                            $CONTACT_ID = $r['result'][0]['ID'];
                                        }
                                    }
                                }
                            }
                        }elseif($type === 'PHONE' && !empty($prepareData['[CONTACT]PHONE'])){
                            foreach($prepareData['[CONTACT]PHONE'] as $phone){
                                if($CONTACT_ID) break;
                                if($phone['VALUE']){
                                    $body = [
                                        'filter' => [
                                            'PHONE'=>$phone['VALUE']
                                        ],
                                        'select'=>[
                                            "ID"
                                        ]
                                    ];
                                    $url = $provider['MAIN_HOOK'].'crm.contact.list';
                                    $client = new HttpClient();
                                    $client->disableSslVerification();
                                    $client->setTimeout(5);
                                    $client->setStreamTimeout(5);
                                    $r = $client->post($url,$body);

                                    if($r){
                                        $r = Json::decode($r);
                                        if(isset($r['result'][0]['ID']) && $r['result'][0]['ID']) {
                                            $CONTACT_ID = $r['result'][0]['ID'];
                                        }
                                    }
                                }
                            }
                        }elseif($type === 'EMAIL' && !empty($prepareData['EMAIL'])){
                            foreach($prepareData['EMAIL'] as $email){
                                if($CONTACT_ID) break;
                                if($email['VALUE']){
                                    $body = [
                                        'filter' => [
                                            'EMAIL'=>$email['VALUE']
                                        ],
                                        'select'=>[
                                            "ID"
                                        ]
                                    ];
                                    $url = $provider['MAIN_HOOK'].'crm.contact.list';
                                    $client = new HttpClient();
                                    $client->disableSslVerification();
                                    $client->setTimeout(5);
                                    $client->setStreamTimeout(5);
                                    $r = $client->post($url,$body);
                                    if($r){
                                        $r = Json::decode($r);
                                        if(isset($r['result'][0]['ID']) && $r['result'][0]['ID']) {
                                            $CONTACT_ID = $r['result'][0]['ID'];
                                        }
                                    }
                                }
                            }
                        }elseif($type === 'EMAIL' && !empty($prepareData['[CONTACT]EMAIL'])){
                            foreach($prepareData['[CONTACT]EMAIL'] as $email){
                                if($CONTACT_ID) break;
                                if($email['VALUE']){
                                    $body = [
                                        'filter' => [
                                            'EMAIL'=>$email['VALUE']
                                        ],
                                        'select'=>[
                                            "ID"
                                        ]
                                    ];
                                    $url = $provider['MAIN_HOOK'].'crm.contact.list';
                                    $client = new HttpClient();
                                    $client->disableSslVerification();
                                    $client->setTimeout(5);
                                    $client->setStreamTimeout(5);
                                    $r = $client->post($url,$body);
                                    if($r){
                                        $r = Json::decode($r);
                                        if(isset($r['result'][0]['ID']) && $r['result'][0]['ID']) {
                                            $CONTACT_ID = $r['result'][0]['ID'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if(
                    isset($provider['PRM']['options']['nalogid']) && $provider['PRM']['options']['nalogid'] &&
                    isset($prepareData['[COMPANY]NALOG_ID']) && $prepareData['[COMPANY]NALOG_ID']
                ){

                    $url = $provider['MAIN_HOOK'].'crm.requisite.list';
                    $client = new HttpClient();
                    $client->disableSslVerification();
                    $client->setTimeout(5);
                    $client->setStreamTimeout(5);
                    $body = [
                        'filter' => [
                            'RQ_INN'=>$prepareData['[COMPANY]NALOG_ID'],
                            'ENTITY_TYPE_ID'=>4
                        ],
                        'select'=>[
                            "ENTITY_ID"
                        ]
                    ];
                    $r = $client->post($url,$body);
                    $r = Json::decode($r);
                    if(isset($r['result'][0]['ENTITY_ID']) && $r['result'][0]['ID']) {
                        $COMPANY_ID = $r['result'][0]['ENTITY_ID'];
                    }
                    if(!$COMPANY_ID){
                        $url = $provider['MAIN_HOOK'].'crm.company.list';
                        $client = new HttpClient();
                        $client->disableSslVerification();
                        $client->setTimeout(5);
                        $client->setStreamTimeout(5);
                        $body = [
                            'filter' => [
                                $provider['PRM']['options']['nalogid']=>$prepareData['[COMPANY]NALOG_ID']
                            ],
                            'select'=>[
                                "ID"
                            ]
                        ];
                        $r = $client->post($url,$body);

                        if($r){
                            $r = Json::decode($r);
                            if(isset($r['result'][0]['ID']) && $r['result'][0]['ID']) {
                                $COMPANY_ID = $r['result'][0]['ID'];
                            }
                        }
                    }
                }

                if(!$COMPANY_ID &&
                    isset($provider['PRM']['options']['addcompany']) && $provider['PRM']['options']['addcompany']=='Y' &&
                    isset($prepareData['[COMPANY]NALOG_ID']) && $prepareData['[COMPANY]NALOG_ID']
                ){
                    $prepareBeforeSendCompany = [];
                    foreach($prepareData as $f_code=>$f_val){
                        if($f_code === '[COMPANY]NALOG_ID') continue;
                        if(mb_substr($f_code,0,9)=='[COMPANY]') {
                            $prepareBeforeSendCompany[mb_substr($f_code,9)] = $f_val;
                        }
                    }
                    $bodyf = [
                        'fields' => $prepareBeforeSendCompany,
                        'params' => [
                            "REGISTER_SONET_EVENT" =>
                                (isset($provider['PRM']['options']['rsevent']) && $provider['PRM']['options']['rsevent'] == 'Y') ? "Y" : "N"
                        ]
                    ];
                    $url = str_replace(
                        ['crm.deal.add','crm.lead.add'],
                        ['crm.company.add','crm.company.add'],
                        $provider['HOOK']
                    );
                    $client = new HttpClient();
                    $client->disableSslVerification();
                    $client->setTimeout(5);
                    $client->setStreamTimeout(5);
                    $r = $client->post($url,$bodyf);
                    if($r){
                        \CEventLog::Add([
                            'SEVERITY'=>'INFO',
                            'AUDIT_TYPE_ID'=>'REST',
                            'MODULE_ID'=>'awz.bx24lead',
                            'ITEM_ID'=>$id,
                            'DESCRIPTION'=>$r
                        ]);
                        $r = Json::decode($r);
                        $COMPANY_ID = (int) $r['result'];
                    }
                }
                if(!$CONTACT_ID && isset($provider['PRM']['options']['addcontact']) && $provider['PRM']['options']['addcontact']=='Y'){
                    $prepareBeforeSendContact = [];
                    foreach($prepareData as $f_code=>$f_val){
                        if(mb_substr($f_code,0,9)=='[CONTACT]') {
                            $prepareBeforeSendContact[mb_substr($f_code,9)] = $f_val;
                        }
                    }
                    if($COMPANY_ID && !$prepareBeforeSendContact['COMPANY_ID']){
                        $prepareBeforeSendContact['COMPANY_ID'] = $COMPANY_ID;
                    }
                    $bodyf = [
                        'fields' => $prepareBeforeSendContact,
                        'params' => [
                            "REGISTER_SONET_EVENT" =>
                                (isset($provider['PRM']['options']['rsevent']) && $provider['PRM']['options']['rsevent'] == 'Y') ? "Y" : "N"
                        ]
                    ];
                    $url = str_replace(
                        ['crm.deal.add','crm.lead.add'],
                        ['crm.contact.add','crm.contact.add'],
                        $provider['HOOK']
                    );
                    $client = new HttpClient();
                    $client->disableSslVerification();
                    $client->setTimeout(5);
                    $client->setStreamTimeout(5);
                    $r = $client->post($url,$bodyf);
                    if($r){
                        \CEventLog::Add([
                            'SEVERITY'=>'INFO',
                            'AUDIT_TYPE_ID'=>'REST',
                            'MODULE_ID'=>'awz.bx24lead',
                            'ITEM_ID'=>$id,
                            'DESCRIPTION'=>$r
                        ]);
                        $r = Json::decode($r);
                        $CONTACT_ID = (int) $r['result'];
                    }
                }

                if($CONTACT_ID){
                    $prepareData['CONTACT_ID'] = $CONTACT_ID;
                }
                if($COMPANY_ID){
                    $prepareData['COMPANY_ID'] = $COMPANY_ID;
                }

                $prepareBeforeSend = [];
                foreach($prepareData as $f_code=>$f_val){
                    if(mb_substr($f_code,0,1)=='[') continue;
                    $prepareBeforeSend[$f_code] = $f_val;
                }
                $prepareData = $prepareBeforeSend;
                $products = [];
                if(isset($prepareData['PRODUCTS'])){
                    $products = $prepareData['PRODUCTS'];
                    unset($prepareData['PRODUCTS']);
                }

                //addProductsBx24

                $bodyf = [
                    'fields' => $prepareData,
                    'params' => [
                        "REGISTER_SONET_EVENT" =>
                            (isset($provider['PRM']['options']['rsevent']) && $provider['PRM']['options']['rsevent'] == 'Y') ? "Y" : "N"
                    ]
                ];
                $url = $provider['HOOK'];
                $client = new HttpClient();
                $client->disableSslVerification();
                $client->setTimeout(5);
                $client->setStreamTimeout(5);
                $r = $client->post($url,$bodyf);
                if($r){
                    //print_r($products);
                    if(!empty($products)){
                        try{
                            $data = Json::decode($r);
                            if($data['result']){
                                Helper::addProductsBx24((int) $data['result'], $products, $provider['HOOK']);
                            }
                        }catch (\Exception $e){

                        }
                    }
                    if($entityType==Helper::ENTITY_IBLOCK) {
                        \CIBlockElement::SetPropertyValueCode($id, 'AWZ_HANDLED', $r);
                    }else{
                        \CEventLog::Add([
                            'SEVERITY'=>'INFO',
                            'AUDIT_TYPE_ID'=>'REST',
                            'MODULE_ID'=>'awz.bx24lead',
                            'ITEM_ID'=>$id,
                            'DESCRIPTION'=>$r
                        ]);
                    }
                }
                elseif($maxRetr){
                    if($maxRetr == 3){
                        $nextTime = time() + 15*60; //через 15 минут
                    }elseif($maxRetr == 2){
                        $nextTime = time() + 2*60*60; //через 2 часа
                    }elseif($maxRetr == 1){
                        $nextTime = time() + 8*60*60; //через 8 часов
                    }
                    $maxRetr = $maxRetr - 1;
                    return "\Awz\Bx24Lead\Agent::sendForm(".$id.",".$entityId.",'".$entityType."',".$maxRetr.",".$providerId.",".$nextTime.");";
                }
            }




            return false;
            //echo'<pre>';print_r($prepareData);echo'</pre>';
            //echo'<pre>';print_r($provider['PRM']);echo'</pre>';
            //echo'<pre>';print_r($extParams);echo'</pre>';
        }
    }

}