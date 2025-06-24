<?php
namespace Awz\Bx24Lead;

use Bitrix\Main\Application;
use Bitrix\Main\Event;
use Bitrix\Main\Config\Option;

class Handlers {

    public static function OnAfterIblockElementAdd(&$arFields){
        if($arFields['IBLOCK_ID'] && $arFields['ID']){
            if(Option::get('awz.bx24lead', 'NOBJ', "N", "")=="Y"){
                Agent::iblockJob((int)$arFields['ID'],(int)$arFields['IBLOCK_ID']);
            }else{
                Application::getInstance()->addBackgroundJob(
                    array("\Awz\Bx24Lead\Agent", "iblockJob"),
                    array((int)$arFields['ID'],(int)$arFields['IBLOCK_ID']),
                    Application::JOB_PRIORITY_NORMAL
                );
            }
        }
    }

    public static function onAfterResultAdd($WEB_FORM_ID, $RESULT_ID){
        if(Option::get('awz.bx24lead', 'NOBJ', "N", "")=="Y"){
            Agent::formJob((int)$RESULT_ID,(int)$WEB_FORM_ID);
        }else{
            Application::getInstance()->addBackgroundJob(
                array("\Awz\Bx24Lead\Agent", "formJob"),
                array((int)$RESULT_ID,(int)$WEB_FORM_ID),
                Application::JOB_PRIORITY_NORMAL
            );
        }
    }

    public static function OnSaleOrderSaved(Event $event){
        /** @var Bitrix\Sale\Order $order */
        $order = $event->getParameter("ENTITY");
        if($order && $order->isNew()){
            if(Option::get('awz.bx24lead', 'NOBJ', "N", "")=="Y"){
                Agent::saleJob((int)$order->getId(), (int) $order->getPersonTypeId());
            }else{
                Application::getInstance()->addBackgroundJob(
                    array("\Awz\Bx24Lead\Agent", "saleJob"),
                    array((int)$order->getId(), (int) $order->getPersonTypeId()),
                    Application::JOB_PRIORITY_NORMAL
                );
            }
        }
    }

}