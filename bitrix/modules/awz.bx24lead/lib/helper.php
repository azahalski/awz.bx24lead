<?php

namespace Awz\Bx24Lead;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class Helper {

    const CACHE_DIR = "/awz/bx24lead";
    const CAHE_TIME = 36000000;

    CONST ENTITY_IBLOCK = 'IBLOCK';
    CONST ENTITY_ORDER = 'ORDER';

    public static $providers = null;

    public static function getProviders(): array
    {
        if(self::$providers) return self::$providers;
        $providers = [];
        if(!Loader::includeModule('iblock')){
            return $providers;
        }
        $r = \Bitrix\Iblock\PropertyTable::getList([
            'select'=>['IBLOCK_ID','IB_NAME'=>'IBLOCK.NAME'],
            'filter'=>['=CODE'=>'AWZ_HANDLED']
        ]);
        while($data = $r->fetch()){
            $providers['IBLOCK_'.$data['IBLOCK_ID']] = '[IBLOCK_'.$data['IBLOCK_ID'].'] - '.$data['IB_NAME'];
        }
        if(Loader::includeModule('sale')){
            $r = \Bitrix\Sale\Internals\PersonTypeTable::getList(['select'=>["ID","NAME"]]);
            while($data = $r->fetch()){
                $providers['ORDER_SALE_'.$data['ID']] = '[ORDER_SALE_'.$data['ID'].'] - '.Loc::getMessage('AWZ_BX24LEAD_HELPER_ORDER'). ' '.Loc::getMessage('AWZ_BX24LEAD_HELPER_ORDER_FOR').' '. $data['NAME'];
            }
        }
        self::$providers = $providers;
        return self::$providers;
    }

    public static function getExtFieldParams(string $url): array
    {
        $fieldsHook = [];

        $obCache = Cache::createInstance();
        if( $obCache->initCache(self::CAHE_TIME,md5($url),self::CACHE_DIR) ){
            return $obCache->getVars();
        }elseif( $obCache->startDataCache()){
            if(substr($url,-12) === 'crm.lead.add'){
                $httpClient = new HttpClient();
                $httpClient->disableSslVerification();
                $httpClient->setTimeout(5);
                $httpClient->setStreamTimeout(5);
                $res = $httpClient->get(substr($url,0,-3).'fields');
                try {
                    $resData = Json::decode($res);
                    if(!empty($resData['result'])){
                        foreach($resData['result'] as $code=>$field){
                            if($field['isReadOnly']) continue;
                            $field['CODE'] = $code;
                            if($field['CODE'] === 'PHONE'){
                                $title = $field['title'];
                                $field['CODE'] = 'PHONE_WORK';
                                $field['title'] = $title.' WORK';
                                $fieldsHook[$field['CODE']] = $field;
                                $field['CODE'] = 'PHONE_MOBILE';
                                $field['title'] = $title.' MOBILE';
                                $fieldsHook[$field['CODE']] = $field;
                                $field['CODE'] = 'PHONE_HOME';
                                $field['title'] = $title.' HOME';
                                $fieldsHook[$field['CODE']] = $field;
                            }elseif($field['CODE'] === 'EMAIL'){
                                $title = $field['title'];
                                $field['CODE'] = 'EMAIL_WORK';
                                $field['title'] = $title.' WORK';
                                $fieldsHook[$field['CODE']] = $field;
                                $field['CODE'] = 'EMAIL_HOME';
                                $field['title'] = $title.' HOME';
                                $fieldsHook[$field['CODE']] = $field;
                            }else{
                                $fieldsHook[$code] = $field;
                            }
                            if($fieldsHook[$code]['title'] == $fieldsHook[$code]['CODE']){
                                if($fieldsHook[$code]['formLabel']){
                                    $fieldsHook[$code]['title'] = $fieldsHook[$code]['formLabel'];
                                }
                            }
                            if($fieldsHook[$code]['title'] == $fieldsHook[$code]['CODE']){
                                if($fieldsHook[$code]['listLabel']){
                                    $fieldsHook[$code]['title'] = $fieldsHook[$code]['listLabel'];
                                }
                            }
                        }
                    }
                }catch (\Exception $e){

                }
            }
            $obCache->endDataCache($fieldsHook);
        }

        return $fieldsHook;
    }

    public static function executePhp($code, $arParams)
    {
        //print_r($code);
        //echo"\n";
        $result = eval('use \Bitrix\Main\Mail\EventMessageThemeCompiler; ob_start();?>' . $code . '<? return ob_get_clean();');
        return $result;
    }

}