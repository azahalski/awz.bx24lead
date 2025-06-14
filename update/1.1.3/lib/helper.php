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
    CONST ENTITY_WEBFORM = 'WEBFORM';

    const MAX_CONSTANTS_ROW = 20;

    public static $providers = null;

    public static $app = null;

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

        if(Loader::includeModule('form')){
            $rsForms = \CForm::GetList($by="s_id", $order="desc");
            while ($arForm = $rsForms->Fetch())
            {
                $providers['WEBFORM_'.$arForm['ID']] = '[WEBFORM_'.$arForm['ID'].'] - '.Loc::getMessage('AWZ_BX24LEAD_HELPER_WEBFORM'). ' '. $arForm['NAME'];
            }
        }

        self::$providers = $providers;
        return self::$providers;
    }

    public static function getExtFieldParams(string $url): array
    {
        $fieldsHook = [];
        $urlData = explode("|",$url);

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
                            //echo'<pre>';print_r($field);echo'</pre>';
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
                        $fieldsHook['PRODUCTS'] = [
                            'CODE'=>'PRODUCTS',
                            'type'=>'products',
                            'title'=>'Товары',
                            'required'=>0
                        ];
                        $fieldsHook['TRACE'] = [
                            'CODE'=>'TRACE',
                            'type'=>'string',
                            'title'=>'Сквозная аналитика',
                            'required'=>0
                        ];
                    }
                }catch (\Exception $e){

                }
            }
            elseif(substr($url,-12) === 'crm.deal.add'){
                $httpClient = new HttpClient();
                $httpClient->disableSslVerification();
                $httpClient->setTimeout(5);
                $httpClient->setStreamTimeout(5);
                $res = $httpClient->get(substr($url,0,-3).'fields');
                try {
                    $resData = Json::decode($res);

                    if(!empty($resData['result'])){
                        foreach($resData['result'] as $code=>$field){
                            //echo'<pre>';print_r($field);echo'</pre>';
                            if($field['isReadOnly']) continue;
                            $field['CODE'] = $code;

                            $fieldsHook[$code] = $field;

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
                        $fieldsHook['PRODUCTS'] = [
                            'CODE'=>'PRODUCTS',
                            'type'=>'products',
                            'title'=>'Товары',
                            'required'=>0
                        ];
                        $fieldsHook['TRACE'] = [
                            'CODE'=>'TRACE',
                            'type'=>'string',
                            'title'=>'Сквозная аналитика',
                            'required'=>0
                        ];
                    }

                }catch (\Exception $e){

                }
            }
            elseif($urlData[0]=='amo'){
                $httpClient = new HttpClient();
                $httpClient->disableSslVerification();
                $httpClient->setTimeout(5);
                $httpClient->setStreamTimeout(5);
                $httpClient->setHeader('Authorization', 'Bearer '.$urlData[2]);
                $res = $httpClient->get($urlData[1].'/custom_fields?limit=250');
                try {
                    $resData = Json::decode($res);

                    $fieldsHook['name'] = [
                        'CODE'=>'name',
                        'type'=>'string',
                        'title'=>'Название сделки',
                        'required'=>0
                    ];
                    $fieldsHook['CONTACT_NAME'] = [
                        'CODE'=>'[CONTACT]NAME',
                        'type'=>'string',
                        'title'=>'Имя контакта',
                        'required'=>0
                    ];
                    $fieldsHook['CONTACT_PHONE'] = [
                        'CODE'=>'[CONTACT]PHONE',
                        'type'=>'string',
                        'title'=>'Телефон контакта',
                        'required'=>0
                    ];
                    $fieldsHook['CONTACT_EMAIL'] = [
                        'CODE'=>'[CONTACT]EMAIL',
                        'type'=>'string',
                        'title'=>'Email контакта',
                        'required'=>0
                    ];
                    $fieldsHook['price'] = [
                        'CODE'=>'price',
                        'type'=>'int',
                        'title'=>'Бюджет сделки',
                        'required'=>0
                    ];
                    $fieldsHook['status_id'] = [
                        'CODE'=>'status_id',
                        'type'=>'int',
                        'title'=>'ID статуса',
                        'required'=>0
                    ];
                    $fieldsHook['pipeline_id'] = [
                        'CODE'=>'pipeline_id',
                        'type'=>'int',
                        'title'=>'ID воронки',
                        'required'=>0
                    ];
                    $fieldsHook['created_by'] = [
                        'CODE'=>'created_by',
                        'type'=>'int',
                        'title'=>'ID пользователя, создающий сделку',
                        'required'=>0
                    ];
                    $fieldsHook['updated_by'] = [
                        'CODE'=>'updated_by',
                        'type'=>'int',
                        'title'=>'ID пользователя, изменяющий сделку',
                        'required'=>0
                    ];
                    $fieldsHook['closed_at'] = [
                        'CODE'=>'closed_at',
                        'type'=>'int',
                        'title'=>'Дата закрытия сделки в Unix Timestamp',
                        'required'=>0
                    ];
                    $fieldsHook['created_at'] = [
                        'CODE'=>'created_at',
                        'type'=>'int',
                        'title'=>'Дата создания сделки в Unix Timestamp',
                        'required'=>0
                    ];
                    $fieldsHook['updated_at'] = [
                        'CODE'=>'updated_at',
                        'type'=>'int',
                        'title'=>'Дата изменения сделки в Unix Timestamp',
                        'required'=>0
                    ];
                    $fieldsHook['loss_reason_id'] = [
                        'CODE'=>'loss_reason_id',
                        'type'=>'int',
                        'title'=>'ID причины отказа',
                        'required'=>0
                    ];
                    $fieldsHook['responsible_user_id'] = [
                        'CODE'=>'responsible_user_id',
                        'type'=>'int',
                        'title'=>'ID пользователя, ответственного за сделку',
                        'required'=>0
                    ];
                    $fieldsHook['embedded[source][external_id]'] = [
                        'CODE'=>'embedded[source][external_id]',
                        'type'=>'int',
                        'title'=>'Внешний ID источника',
                        'required'=>0
                    ];


                    foreach($resData['_embedded']['custom_fields'] as $fieldAmo){
                        $fieldBx = [
                            'ID'=>$fieldAmo['id'],
                            'CODE'=>'embedded[custom_fields]['.$fieldAmo['id'].']',
                            'isMultiple'=>0,
                            'type'=>$fieldAmo['type'],
                            'title'=>$fieldAmo['name']
                        ];
                        $fieldsHook['embedded[custom_fields]['.$fieldAmo['id'].']'] = $fieldBx;
                    }


                    //echo'<pre>';print_r($resData);echo'</pre>';
                }
                catch (\Exception $e){

                }
            }

            if(substr($url,-12) === 'crm.lead.add' || substr($url,-12) === 'crm.deal.add'){

                $res2 = $httpClient->get(str_replace(['.deal.','.lead.'],['.contact.','.contact.'],substr($url,0,-3)).'fields');
                $res3 = $httpClient->get(str_replace(['.deal.','.lead.'],['.company.','.company.'],substr($url,0,-3)).'fields');
                try {
                    $resDataContact = Json::decode($res2);
                }catch (\Exception $e){
                    $resDataContact = ['result'=>[]];
                }
                try {
                    $resDataCompany = Json::decode($res3);
                }catch (\Exception $e){
                    $resDataCompany = ['result'=>[]];
                }

                if(!empty($resDataContact['result'])){
                    foreach($resDataContact['result'] as $code=>$field){
                        $startCode = $code;
                        $code = '[CONTACT]'.$code;
                        //echo'<pre>';print_r($field);echo'</pre>';
                        if($field['isReadOnly']) continue;
                        $field['CODE'] = $code;
                        if($field['CODE'] === '[CONTACT]PHONE'){
                            $title = $field['title'];
                            $field['CODE'] = '[CONTACT]PHONE_WORK';
                            $field['title'] = $title.' WORK';
                            $fieldsHook[$field['CODE']] = $field;
                            $field['CODE'] = '[CONTACT]PHONE_MOBILE';
                            $field['title'] = $title.' MOBILE';
                            $fieldsHook[$field['CODE']] = $field;
                            $field['CODE'] = '[CONTACT]PHONE_HOME';
                            $field['title'] = $title.' HOME';
                            $fieldsHook[$field['CODE']] = $field;
                        }elseif($field['CODE'] === '[CONTACT]EMAIL'){
                            $title = $field['title'];
                            $field['CODE'] = '[CONTACT]EMAIL_WORK';
                            $field['title'] = $title.' WORK';
                            $fieldsHook[$field['CODE']] = $field;
                            $field['CODE'] = '[CONTACT]EMAIL_HOME';
                            $field['title'] = $title.' HOME';
                            $fieldsHook[$field['CODE']] = $field;
                        }else{
                            $fieldsHook[$code] = $field;
                        }
                        if($fieldsHook[$code]['title'] == $startCode){
                            if($fieldsHook[$code]['formLabel']){
                                $fieldsHook[$code]['title'] = $fieldsHook[$code]['formLabel'];
                            }
                        }
                        if($fieldsHook[$code]['title'] == $startCode){
                            if($fieldsHook[$code]['listLabel']){
                                $fieldsHook[$code]['title'] = $fieldsHook[$code]['listLabel'];
                            }
                        }
                    }
                }

                if(!empty($resDataCompany['result'])){
                    foreach($resDataCompany['result'] as $code=>$field){
                        $startCode = $code;
                        $code = '[COMPANY]'.$code;
                        if($field['isReadOnly']) continue;
                        $field['CODE'] = $code;
                        //echo'<pre>';print_r($field);echo'</pre>';
                        if($field['CODE'] === '[COMPANY]PHONE'){
                            $title = $field['title'];
                            $field['CODE'] = '[COMPANY]PHONE_WORK';
                            $field['title'] = $title.' WORK';
                            $fieldsHook[$field['CODE']] = $field;
                            $field['CODE'] = '[COMPANY]PHONE_MOBILE';
                            $field['title'] = $title.' MOBILE';
                            $fieldsHook[$field['CODE']] = $field;
                            $field['CODE'] = '[COMPANY]PHONE_HOME';
                            $field['title'] = $title.' HOME';
                            $fieldsHook[$field['CODE']] = $field;
                        }elseif($field['CODE'] === '[COMPANY]EMAIL'){
                            $title = $field['title'];
                            $field['CODE'] = '[COMPANY]EMAIL_WORK';
                            $field['title'] = $title.' WORK';
                            $fieldsHook[$field['CODE']] = $field;
                            $field['CODE'] = '[COMPANY]EMAIL_HOME';
                            $field['title'] = $title.' HOME';
                            $fieldsHook[$field['CODE']] = $field;
                        }else{
                            $fieldsHook[$code] = $field;
                        }
                        if($fieldsHook[$code]['title'] == $startCode){
                            if($fieldsHook[$code]['formLabel']){
                                $fieldsHook[$code]['title'] = $fieldsHook[$code]['formLabel'];
                            }
                        }
                        if($fieldsHook[$code]['title'] == $startCode){
                            if($fieldsHook[$code]['listLabel']){
                                $fieldsHook[$code]['title'] = $fieldsHook[$code]['listLabel'];
                            }
                        }
                    }

                    $fieldsHook['[COMPANY]NALOG_ID'] = [
                        'CODE'=>'[COMPANY]NALOG_ID',
                        'type'=>'string',
                        'title'=>'Налоговый идентификатор (для поиска компании)',
                        'required'=>0
                    ];
                }
            }

            $obCache->endDataCache($fieldsHook);
        }

        return $fieldsHook;
    }

    public static function executePhp($code, $arParams, $provider)
    {
        //print_r($code);
        //echo"\n";
        $result = eval('use \Bitrix\Main\Mail\EventMessageThemeCompiler; ob_start();?>' . $code . '<? return ob_get_clean();');
        return $result;
    }

    public static function addProductsBx24(int $dealId, array $products, string $url){
        /** @var $app bx24Catalog */
        $app = self::$app;
        if(!empty($products) && self::$app){
            $method = 'crm.deal.productrows.set';
            if(strpos($url,'.lead.')!==false){
                $method = 'crm.lead.productrows.set';
            }
            $res = $app->postMethod($method, [
                'id'=>$dealId,
                'rows'=>$products
            ]);
            if($res->isSuccess()){
                $resData = $res->getData();
                //print_r($resData);
                return true;
            }
        }
        return false;
    }

    public static function getProductBx24(int $id, int $catalogId, string $url){
        if(!Loader::includeModule('iblock')) return [];
        //print_r([$id, $catalogId, $url]);
        if(!self::$app) {
            self::$app = new bx24Catalog($url, [
                'CATALOG_ID'=>$catalogId
            ]);
        }
        return self::$app->getProduct($id);
    }

}