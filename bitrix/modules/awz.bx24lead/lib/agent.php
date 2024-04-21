<?php
namespace Awz\Bx24Lead;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Application;
use Bitrix\Main\IO;

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

        if($nextTime && $nextTime>time()){
            return "\Awz\Bx24Lead\Agent::sendForm(".$id.",".$entityId.",'".$entityType."',".$maxRetr.",".$providerId.",".$nextTime.");";
        }

        $obEl = null;
        $order = null;
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

        $provider['MAIN_HOOK'] = str_replace('crm.lead.add','',$provider['HOOK']);
        $fieldsHook = \Awz\Bx24Lead\Helper::getExtFieldParams($provider['HOOK']);

        $extParams = Helper::getExtFieldParams($provider['HOOK']);
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
        }elseif($entityType == Helper::ENTITY_ORDER){
            $valuesList = $order->getFieldValues();
            foreach($valuesList as $code=>$value){
                $macrosList['#'.$code.'#'] = htmlspecialcharsBack($value);
            }
            $propertyCollection = $order->getPropertyCollection();
            foreach($propertyCollection as $prop){
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
                    if(is_string($v) && strpos($v,'<?')!==false){
                        $v = Helper::executePhp($v, $valuesList);
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
            //echo'<pre>';print_r($prepareData);echo'</pre>';
            if(isset($prepareData['PHONE_WORK'])){
                if(!isset($prepareData['PHONE'])) $prepareData['PHONE'] = [];
                $prepareData['PHONE'][] = ['VALUE'=>$prepareData['PHONE_WORK'], 'TYPE'=>"WORK"];
                unset($prepareData['PHONE_WORK']);
            }
            if(isset($prepareData['PHONE_MOBILE'])){
                if(!isset($prepareData['PHONE'])) $prepareData['PHONE'] = [];
                $prepareData['PHONE'][] = ['VALUE'=>$prepareData['PHONE_MOBILE'], 'TYPE'=>"MOBILE"];
                unset($prepareData['PHONE_MOBILE']);
            }
            if(isset($prepareData['PHONE_HOME'])){
                if(!isset($prepareData['PHONE'])) $prepareData['PHONE'] = [];
                $prepareData['PHONE'][] = ['VALUE'=>$prepareData['PHONE_HOME'], 'TYPE'=>"HOME"];
                unset($prepareData['PHONE_HOME']);
            }
            if(isset($prepareData['EMAIL_WORK'])){
                if(!isset($prepareData['EMAIL'])) $prepareData['EMAIL'] = [];
                $prepareData['EMAIL'][] = ['VALUE'=>$prepareData['EMAIL_WORK'], 'TYPE'=>"WORK"];
                unset($prepareData['EMAIL_WORK']);
            }
            if(isset($prepareData['EMAIL_HOME'])){
                if(!isset($prepareData['EMAIL'])) $prepareData['EMAIL'] = [];
                $prepareData['EMAIL'][] = ['VALUE'=>$prepareData['EMAIL_HOME'], 'TYPE'=>"HOME"];
                unset($prepareData['EMAIL_HOME']);
            }

            $CONTACT_ID = false;
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
                    }
                }
            }
            if($CONTACT_ID){
                $prepareData['CONTACT_ID'] = $CONTACT_ID;
            }

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
            }elseif($maxRetr){
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

            return false;
            //echo'<pre>';print_r($prepareData);echo'</pre>';
            //echo'<pre>';print_r($provider['PRM']);echo'</pre>';
            //echo'<pre>';print_r($extParams);echo'</pre>';
        }
    }

}