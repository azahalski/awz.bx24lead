<?php

namespace Awz\Bx24Lead;

use Bitrix\Main\Application;

class bx24Trace {

    public static function OnProlog()
    {
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $session = Application::getInstance()->getSession();
        if($request->get('TRACE'))
            $session->set('BX24_TRACE', $request->get('TRACE'));
    }

    public static function getTrace()
    {
        $session = Application::getInstance()->getSession();
        return $session->get('BX24_TRACE');
    }

    public static function getRef(): string
    {
        $session = Application::getInstance()->getSession();
        if($session->get('BX24_TRACE')){
            try{
                $data = \Bitrix\Main\Web\Json::decode($session->get('BX24_TRACE'));
                return $data['ref'];
            }catch (\Exception $e){

            }
        }
        return '';
    }

}