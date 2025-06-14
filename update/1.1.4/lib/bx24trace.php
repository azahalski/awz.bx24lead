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

    public static function getHitJs(string $type="", int $timeout=3000, bool $addDomReady = false): string
    {
        $context = Application::getInstance()->getContext();
        $signer = new \Bitrix\Main\Security\Sign\Signer();
        $signedParameters = $signer->sign(base64_encode(serialize([
            'time'=>time()+60,
            's_id'=>bitrix_sessid(),
        ])));
        if($type=='bx'){
            \CJSCore::init(["ajax"]);
            $html = '(function(){try{';
            if($addDomReady) $html .= 'BX.ready(function(){';
            $html .= 'setTimeout(function(){';
            $html .= 'BX.ajax({';
            $html .= 'url:"/bitrix/services/main/ajax.php?action=awz:bx24lead.api.trace.save",';
            $html .= 'method:"POST",';
            $html .= 'data:{"signed":"'.$signedParameters.'","TRACE":b24Tracker.guest.getTrace()}';
            $html .= '});';
            $html .= '},'.$timeout.');';
            if($addDomReady) $html .= '});';
            $html .= '}catch(e){console.log(e);}})();';
        }else{
            $html = '(function(){try{';
            if($addDomReady) $html .= 'document.addEventListener("DOMContentLoaded", function(){';
            $html .= 'setTimeout(function(){';
            $html .= "let url = new URL('https://".$context->getServer()->getHttpHost()."/bitrix/services/main/ajax.php?action=awz:bx24lead.api.trace.save');";
            $html .= "url.searchParams.set('signed', '".$signedParameters."');";
            $html .= "url.searchParams.set('TRACE', b24Tracker.guest.getTrace());fetch(url);})";
            $html .= "},'.$timeout.');";
            if($addDomReady) $html .= "});";
            $html .= '}catch(e){console.log(e);}})();';
        }
        return $html;
    }

}