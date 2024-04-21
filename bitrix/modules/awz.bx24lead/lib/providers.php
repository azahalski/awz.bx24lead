<?php
namespace Awz\Bx24Lead;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Data\Cache;

Loc::loadMessages(__FILE__);

class ProvidersTable extends DataManager{

    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_bx24lead_providers';
        /*
        CREATE TABLE IF NOT EXISTS `b_awz_bx24lead_providers` (
        `ID` int(18) NOT NULL AUTO_INCREMENT,
        `ENTITY_ID` varchar(65) NOT NULL,
        `HOOK` varchar(255) NOT NULL,
        `NAME` varchar(255) NOT NULL,
        `ACTIVE` varchar(1) DEFAULT "N",
        `PRM` varchar(6255) DEFAULT NULL,
        PRIMARY KEY (`ID`)
        ) AUTO_INCREMENT=1;
        */
    }

    public static function getMap()
    {
        return array(
            (new Fields\IntegerField('ID', array(
                    'title' => Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_ID_FIELD'),
                )
            ))->configurePrimary()->configureAutocomplete(),
            (new Fields\StringField('ENTITY_ID', array(
                    'title' => Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_ENTITY_ID_FIELD'),
                    'validation' => array(__CLASS__, 'validateEntityId'),
                )
            ))->configureRequired(),
            (new Fields\StringField('NAME', array(
                    'title' => Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_NAME_FIELD'),
                    'validation' => array(__CLASS__, 'validateName'),
                )
            ))->configureRequired(),
            (new Fields\StringField('HOOK', array(
                    'title' => Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_HOOK_FIELD'),
                    'validation' => array(__CLASS__, 'validateHook'),
                )
            ))->configureRequired(),
            (new Fields\ArrayField('PRM', array(
                    'title' => Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_PRM_FIELD')
                )
            ))->configureSerializeCallback(function ($value){
                $nValue = [];
                foreach($value as $code=>$v){
                    if(is_array($v)){
                        $vAr = [];
                        foreach($v as $code2=>$v2){
                            if(!$v2) continue;
                            if($v2=='-') continue;
                            $vAr[$code2] = $v2;
                        }
                        $nValue[$code] = $vAr;
                        continue;
                    }
                    if(!$v) continue;
                    if($v=='-') continue;
                    $nValue[$code] = $v;
                }
                return serialize($nValue);
            })->configureUnserializeCallback(function ($str) {
                return unserialize(
                    $str,
                    ['allowed_classes' => false]
                );
            }),
            (new Fields\BooleanField('ACTIVE', array(
                'values' => array('N', 'Y'),
                'default_value' => 'Y',
                'title' => Loc::getMessage('AWZ_BX24LEAD_PROVIDERS_ACTIVE_FIELD'),
            )
            ))
        );
    }

    public static function validateEntityId(){
        return array(
            new Fields\Validators\LengthValidator(3, 65),
        );
    }

    public static function validateName(){
        return array(
            new Fields\Validators\LengthValidator(3, 255),
        );
    }

    public static function validateHook(){
        return array(
            new Fields\Validators\LengthValidator(3, 255),
        );
    }

    public static function validatePrm(){
        return array(
            new Fields\Validators\LengthValidator(0, 6255),
        );
    }

    public static function getProvider(string $provider): array
    {
        return self::getAllProviders($provider);
    }

    public static function getAllProviders(string $provider=''): array
    {
        $providers = [];
        $filter = ['=ACTIVE'=>'Y'];
        if($provider){
            $filter['=ENTITY_ID'] = $provider;
        }
        $r = self::getList([
            'select'=>['*'],
            'filter'=>$filter,
            'cache'=>[
                'ttl' => Helper::CAHE_TIME,
                'cache_joins' => true
            ]
        ]);
        while($data = $r->fetch()){
            $providers[] = $data;
        }
        if($provider && isset($providers[0])){
            return $providers[0];
        }
        return $providers;
    }



    public static function clearCache(){
        self::getEntity()->cleanCache();
        $obCache = Cache::createInstance();
        $obCache::clearCache(true, Helper::CACHE_DIR);
    }

    public static function onAfterAdd(Event $event)
    {
        self::clearCache();
    }

    public static function onAfterUpdate(Event $event)
    {
        self::clearCache();
    }

    public static function onAfterDelete(Event $event)
    {
        self::clearCache();
    }

}