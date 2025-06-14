<?php

namespace Awz\Bx24Lead;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Application;

use Psr\Log;
use Bitrix\Main\Diag;
use Psr\Log\LoggerInterface;

Loc::loadMessages(__FILE__);

class bx24Catalog {

    use Log\LoggerAwareTrait;

    const JSON_REQUEST = 'json';

    /**
     * Папка для кеша в /bitrix/cache/
     */
    const CACHE_DIR = '/awz/bx24lead/';
    const CACHE_TYPE_RESPONSE = 'cache';

    protected $config;

    private string $apiUrl = '';
    private array $auth = [];

    private $lastResponse;
    private $lastResponseType;

    private array $cacheParams = array();

    public function __construct(string $hook = '', array $config = []){

        $testHook = explode('/',$hook);
        $tmp = array_pop($testHook);
        if(strpos($tmp, '.')!==false){
            $hook = implode('/',$testHook).'/';
        }
        $this->apiUrl = $hook;
        $this->auth = ['access_token'=>''];

        $config['hook'] = $this->apiUrl;
        if(!isset($config['APP_ID'])) $config['APP_ID'] = md5($this->apiUrl);
        $this->config = $config;

    }


    public function getProduct(int $id = 0){
        if(!Loader::includeModule('iblock')) return 0;
        $catalogId = $this->getConfig('CATALOG_ID');
        $obEl = \CIblockElement::getById($id)->getNextElement();
        $mainFields = $obEl->getFields();
        $mainProperties = $obEl->getProperties();
        $mainId = 0;
        if(isset($mainProperties['CML2_LINK']) && $mainProperties['CML2_LINK']['VALUE']){
            $mainId = $this->getProduct($mainProperties['CML2_LINK']['VALUE']);
        }
        $catalogIblockId = 0;
        if($mainId){
            $this->setCacheParams(['crm.catalog',$catalogId]);
            $catalogRes = $this->postMethod('catalog.catalog.list', ['id'=>$catalogId])->getData();
            $this->clearCacheParams();
            foreach($catalogRes['result']['result']['catalogs'] as $catalog){
                if($catalog['productIblockId']==$catalogId){
                    $catalogIblockId = $catalog['iblockId'];
                    break;
                }
            }
        }
        $catalogRes = $this->postMethod('catalog.product.list', [
            'filter'=>['iblockId'=>($mainId && $catalogIblockId) ? $catalogIblockId : $catalogId,
                '=xmlId'=>$mainFields['XML_ID']],
            'select'=>['id','iblockId']
        ]);
        if($catalogRes->isSuccess()) {
            $catalogResData = $catalogRes->getData();
            if(!empty($catalogResData['result']['result']['products'])){
                return (int) $catalogResData['result']['result']['products'][0]['id'];
            }else{
                $fields = [
                    'iblockId'=>$catalogId,
                    'xmlId'=>$mainFields['XML_ID'],
                    'name'=>$mainFields['NAME'],
                    'active'=>$mainFields['ACTIVE'],
                ];
                if($mainFields['IBLOCK_SECTION_ID']){
                    if($sectionId = $this->getSection($mainFields['IBLOCK_SECTION_ID'])){
                        $fields['iblockSectionId'] = $sectionId;
                    }
                }
                if($mainId){
                    $fields['productType'] = 3;
                    $fields['parentId'] = $mainId;
                    $fields['iblockId'] = $catalogIblockId;
                    $catalogAdd = $this->postMethod('catalog.product.offer.add', ['fields'=>$fields]);
                }else{
                    $catalogAdd = $this->postMethod('catalog.product.add', ['fields'=>$fields]);
                }

                if($catalogAdd->isSuccess()){
                    $catalogAddData = $catalogAdd->getData();
                    if(isset($catalogAddData['result']['result']['element']))
                        return (int) $catalogAddData['result']['result']['element']['id'];
                    if(isset($catalogAddData['result']['result']['offer']))
                        return (int) $catalogAddData['result']['result']['offer']['id'];
                }
            }
        }

        return 0;
    }

    public function getSection(int $sectionId = 0, $sectionPath = []):int
    {
        if(!Loader::includeModule('iblock')) return 0;
        if(!$sectionId) return 0;
        $catalogId = $this->getConfig('CATALOG_ID');
        $section = \Bitrix\Iblock\SectionTable::getRowById($sectionId);
        if($section && isset($section['IBLOCK_SECTION_ID']) && $section['IBLOCK_SECTION_ID']){
            $sectionPath[] = $section;
            return $this->getSection((int)$section['IBLOCK_SECTION_ID'], $sectionPath);
        }
        if(empty($section)) return 0;
        $sectionPath[] = $section;
        $sectionPath = array_reverse($sectionPath);

        $sectionId = (int) $sectionPath[0]['ID'];
        $section = $sectionPath[0];
        $lastSection = end($sectionPath);
        $catalogRes = $this->postMethod('catalog.section.list', [
            'filter'=>['iblockId'=>$catalogId, 'xmlId'=>$lastSection['XML_ID']],
            'select'=>['id']
        ]);
        if($catalogRes->isSuccess()){
            $catalogResData = $catalogRes->getData();
            if(empty($catalogResData['result']['result']['sections'])){
                $lastLevel = false;
                foreach($sectionPath as $key=>$sectionData){
                    $checkRes = $this->postMethod('catalog.section.list', [
                        'filter'=>['iblockId'=>$catalogId, 'xmlId'=>$sectionData['XML_ID']],
                        'select'=>['id']
                    ]);
                    if($checkRes->isSuccess()){
                        $checkResData = $checkRes->getData();
                        if(empty($checkResData['result']['result']['sections'])){
                            $fields = [
                                'name'=>$sectionData['NAME'],
                                'iblockId'=>$catalogId,
                                'xmlId'=>$sectionData['XML_ID']
                            ];
                            if($lastLevel){
                                $fields['iblockSectionId'] = $lastLevel;
                            }
                            $addSectRes = $this->postMethod('catalog.section.add',['fields'=>$fields]);
                            if($addSectRes->isSuccess()){
                                $addSectResData = $addSectRes->getData();
                                $lastLevel = $addSectResData['result']['result']['section']['id'];
                            }
                        }else{
                            $lastLevel = $checkResData['result']['result']['sections'][0]['id'];
                        }
                    }

                }
                return (int) $lastLevel;
            }else{
                return (int) $catalogResData['result']['result']['sections'][0]['id'];
            }
        }
        return 0;
    }

    /**
     * очистка параметров для кеша
     * должна вызываться после любого запроса через кеш
     */
    public function clearCacheParams()
    {
        $this->cacheParams = array();
    }

    /**
     * параметры для кеша результата запроса
     *
     * @param $cacheId ид кеша
     * @param $ttl время действия в секундах
     */
    public function setCacheParams($cacheId, $ttl=36000000)
    {
        if(is_array($cacheId)){
            $cacheId = md5(serialize($cacheId));
        }
        $this->cacheParams = array(
            'id'=>$cacheId,
            'ttl'=>$ttl
        );
    }

    public function getCacheDirAuth(): string
    {
        $addFolder = md5(serialize([$this->getConfig(),$this->getAuth()]));
        return $addFolder;
    }

    public function getCacheDir(): string
    {
        return self::CACHE_DIR.$this->getConfig('APP_ID').'/'.$this->getCacheDirAuth().'/';
    }

    public function cleanCache($cacheId='')
    {
        $obCache = Cache::createInstance();
        if(!$cacheId && $this->cacheParams && isset($this->cacheParams['id'])){
            $cacheId = $this->cacheParams['id'];
        }
        if($cacheId)
            $obCache->clean($cacheId, $this->getCacheDir());
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function getConfig($param=false, $def=false)
    {
        if(!$param) return $this->config;
        return isset($this->config[$param]) ? $this->config[$param] : $def;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getToken(): string
    {
        $authData = $this->auth;
        if(isset($authData['access_token']))
            return $authData['access_token'];
        return '';
    }

    public function getEndpoint(): string
    {
        $authData = $this->auth;
        if(isset($authData['client_endpoint']))
            return $authData['client_endpoint'];
        return $this->apiUrl;
    }

    public function getMethod($method, array $params=array())
    {
        $result = $this->sendRequest($method, $params, HttpClient::HTTP_GET);
        return $result;
    }

    public function postMethod($method, $params=array())
    {
        $result = $this->sendRequest($method, $params);
        return $result;
    }

    protected function sendRequest($url, $data = array(), $type=HttpClient::HTTP_POST)
    {

        $result = new Result();

        if(strpos($url, 'https://')===false){
            $endpoint = $this->getEndpoint();
            if(!$endpoint || strpos($endpoint, 'https://')===false){
                $result->addError(new Error('Неверный адрес портала'));
                return $result;
            }
            $url = $endpoint.$url;
            $authToken = $this->getToken();
            if($authToken){
                $url .= '?auth='.$authToken;
            }
        }
        //print_r($data);

        $startCacheId = '';
        if(!empty($this->cacheParams)){
            $startCacheId = $this->cacheParams['id'];
            $obCache = Cache::createInstance();
            if( $obCache->initCache($this->cacheParams['ttl'],$this->cacheParams['id'],$this->getCacheDir()) ){
                $res = $obCache->getVars();
            }
            if($res){
                if($logger = $this->getLogger()){
                    $logger->debug("[from_cache] - {date}\n{page}\n{url}\n{data}\n", [
                        'page' => $startCacheId,
                        'url'=>$url,
                        'data'=>$data
                    ]);
                }
            }
            $this->clearCacheParams();
        }

        if(!$res){
            $httpClient = new HttpClient();
            $httpClient->disableSslVerification();
            if($type == HttpClient::HTTP_GET){
                if(!empty($data)) {
                    $url .= strpos($url, '?')!==false ? '&' : '?';
                    $url .= http_build_query($data);
                }
                $res = $httpClient->get($url);
            }elseif($type == self::JSON_REQUEST){
                $res = $httpClient->post($url, Json::encode($data));
            }else{
                $res = $httpClient->post($url, $data);
            }
            $this->setLastResponse($httpClient);

        }else{
            $this->setLastResponse(null, self::CACHE_TYPE_RESPONSE);
        }


        if(!$res){
            $result->addError(
                new Error('empty request')
            );
        }else{
            try {

                $json = Json::decode($res);
                $result->setData(array('result'=>$json));

                if(isset($json['error']) && $json['error']){
                    $errText = $json['error'];
                    if(isset($json['error_description']) && $json['error_description']){
                        $errText = $json['error'].': '.$json['error_description'];
                    }
                    $result->addError(
                        new Error($errText)
                    );
                }elseif(isset($json['error_description']) && $json['error_description']){
                    $result->addError(
                        new Error($json['error_description'])
                    );
                }

            }catch (\Exception  $ex){
                $result->addError(
                    new Error($ex->getMessage(), $ex->getCode())
                );
            }
        }

        if($result->isSuccess() && $this->lastResponse){
            if($obCache){
                if($obCache->startDataCache()){
                    $obCache->endDataCache($res);

                    if($logger = $this->getLogger()){
                        $logger->debug("[add_cache] - {date}\n{page}\n{url}\n{data}\n", [
                            'page' => $startCacheId,
                            'url'=>$url,
                            'data'=>$data
                        ]);
                    }

                }
            }
        }

        return $result;

    }

    /**
     * Получение последнего запроса
     *
     * @return null|HttpClient
     */
    public function getLastResponse(): ?HttpClient
    {
        return $this->lastResponse;
    }

    public function getLastResponseType(){
        return $this->lastResponseType;
    }

    public function getLogger(): ?LoggerInterface
    {
        if ($this->logger === null)
        {
            $logger = Diag\Logger::create('awz.bx24lead.catalog', [$this]);

            if ($logger !== null)
            {
                $this->setLogger($logger);
            }
        }

        return $this->logger;
    }

    /**
     * Запись последнего запроса
     *
     * @param null $resp
     * @param string $type
     * @return HttpClient|null
     */
    private function setLastResponse($resp = null, $type=''): ?HttpClient
    {
        if($resp && !($resp instanceof HttpClient)){
            $resp = null;
        }
        $this->lastResponse = $resp;
        $this->lastResponseType = $type;
        return $this->lastResponse;
    }

}