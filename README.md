# AWZ: Интеграция с CRM (awz.bx24lead)

<!-- desc-start -->

Позволяет записывать лиды с инфоблоков и заказов в Битрикс24.

**Поддерживаемые редакции CMS Битрикс:**<br>
«Старт», «Стандарт», «Малый бизнес», «Бизнес», «Корпоративный портал», «Энтерпрайз», «Интернет-магазин + CRM»

<!-- desc-end -->

<!-- doc-start -->

## поддерживаемые форматы хуков

| Формат                                                  | Описание                                           |
|---------------------------------------------------------|----------------------------------------------------|
| https://portal.bitrix24.by/rest/1/секретка/crm.lead.add | Права: crm, catalog. Добавление лидов в Битрикс24  |
| https://portal.bitrix24.by/rest/1/секретка/crm.deal.add | Права: crm, catalog. Добавление сделок в Битрикс24 |
| Хук amoCRM                                              | Добавление сделок в amoCRM                         |

**Формат ввода хука для amoCRM**
`amo|https://portal.amocrm.ru/api/v4/leads|ключ`

## Примеры настроек полей

![](https://zahalski.dev/images/modules/awz.bx24lead/001.png)

### Добавление товаров с ценами в б24

```php 
<?
$catalogBx24 = 25; //ид каталога в битрикс24
$products = [];
$order = \Bitrix\Sale\Order::load($arParams['ID']);
if($order instanceof \Bitrix\Sale\OrderBase){
    $basket=$order->getBasket();
    foreach($basket as $item){
        $productId=\Awz\Bx24Lead\Helper::getProductBx24($item->getProductId(), $catalogBx24, $provider['MAIN_HOOK']);
        if($productId){
            $products[] = [
                'PRODUCT_ID'=>$productId, 
                'PRICE'=>$item->getPrice(), 
                'QUANTITY'=>$item->getQuantity()
            ];
        }
    }
}
echo serialize($products);
?>
```

### Добавление сквозной аналитики Битрикс24

настройка поля

```php
<?=\Awz\Bx24Lead\bx24Trace::getTrace();?>
```

#### Условие: скрипт сквозной аналитики уже подключен на странице

```php
// init.php
if(\Bitrix\Main\Loader::includeModule('awz.bx24lead')){
    $eventManager = \Bitrix\Main\EventManager::getInstance();
    $eventManager->addEventHandlerCompatible("main", "OnProlog",
        ["\\Awz\\Bx24Lead\\bx24Trace", "OnProlog"]
    );
}
```

```js
//jquery добавление трейса б24 во все формы
$(document).ready(function(){
    try{
        setTimeout(function(){
            $('form').each(function(){
                if(!$(this).find('input[name="TRACE"]').length)
                    $(this).append('<input type="hidden" name="TRACE">');
                $(this).find('input[name="TRACE"]').val(b24Tracker.guest.getTrace());
            });
        },3000);
    }catch (e) {
        console.log(e);
    }
});
```

#### Подключаем скрипт сквозной аналитики на все страницы и отправляем данные в модуль фоновым запросом на странице оформления заказа

```php
<script>
    (function(w,d,u){
        var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
        var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
    })(window,document,'https://cdn-ru.bitrix24.by/b34062590/crm/tag/call.tracker.js');
</script>
<?
global $APPLICATION;
if(
    \Bitrix\Main\Loader::includeModule('awz.bx24lead') &&
    $APPLICATION->getCurPage(false) == '/personal/order/make/'
){
    $dynamicArea = new \Bitrix\Main\Composite\StaticArea("bx24lead_trace");
    $dynamicArea->startDynamicArea();
    ?>
    <script><?=\Awz\Bx24Lead\bx24Trace::getHitJs('bx');?></script>
    <?$dynamicArea->finishDynamicArea();?>
<?}?>
```

<!-- doc-end -->

<!-- cl-start -->
## История версий

https://github.com/azahalski/awz.bx24lead/blob/master/CHANGELOG.md

<!-- cl-end -->
