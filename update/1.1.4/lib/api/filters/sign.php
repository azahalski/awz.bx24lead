<?php

namespace Awz\Bx24Lead\Api\Filters;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

Loc::loadMessages(__FILE__);

class Sign extends Base {

    const ERROR_INVALID_PARAMS = 'invalid_sign';

    protected $keys = array();

    public function __construct(array $params = array())
    {
        $this->keys = $params;
        parent::__construct();
    }

    public function onBeforeAction(Event $event)
    {
        try {
            $signer = new Security\Sign\Signer();
            $params = $signer->unsign($this->getAction()->getController()->getRequest()->get('signed'));
            $params = unserialize(base64_decode($params), ["allowed_classes" => false]);
        }catch (\Exception $e){
            $this->addError(new Error(
                'signed error',
                self::ERROR_INVALID_PARAMS
            ));

            return new EventResult(EventResult::ERROR, null, 'awz.bx24lead', $this);
        }


        if (empty($params))
        {
            $this->addError(new Error(
                'signed error',
                self::ERROR_INVALID_PARAMS
            ));

            return new EventResult(EventResult::ERROR, null, 'awz.bx24lead', $this);
        }


        $httpRequest = $this->getAction()->getController()->getRequest();
        if ($httpRequest)
        {
            $httpRequest->addFilter(new Request\Sign($this->getKeys(), $params));
        }

        return null;
    }

    public function getKeys(){

        return $this->keys;

    }

}