<?php
namespace Awz\Bx24Lead;

use Bitrix\Main\Application;
use Bitrix\Main\Event;

class Handlers {

    public static function OnAfterIblockElementAdd(&$arFields){
        if($arFields['IBLOCK_ID'] && $arFields['ID']){
            Application::getInstance()->addBackgroundJob(
                array("\Awz\Bx24Lead\Agent", "iblockJob"),
                array((int)$arFields['ID'],(int)$arFields['IBLOCK_ID']),
                Application::JOB_PRIORITY_NORMAL
            );
        }
    }

    public static function OnSaleOrderSaved(Event $event){
        /** @var Bitrix\Sale\Order $order */
        $order = $event->getParameter("ENTITY");
        if($order && $order->isNew()){
            Application::getInstance()->addBackgroundJob(
                array("\Awz\Bx24Lead\Agent", "saleJob"),
                array((int)$order->getId(), (int) $order->getPersonTypeId()),
                Application::JOB_PRIORITY_NORMAL
            );
        }
    }

}