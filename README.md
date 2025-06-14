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

<!-- doc-end -->

<!-- cl-start -->
## История версий

https://github.com/azahalski/awz.bx24lead/blob/master/CHANGELOG.md

<!-- cl-end -->
