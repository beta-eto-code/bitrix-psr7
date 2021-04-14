#Адаптер PSR-7 для Bitrix

Примеры использования:
```php
/**
 * @var \Bitrix\Main\HttpRequest $orginalBitrixRequest
 * @var \Bitrix\Main\HttpResponse $originalBitrixResponse
 */
$request = new \BitrixPSR7\Request($orginalBitrixRequest);
$request->getHeader('Content-type');
$request->getHeaderLine('Content-type');

$uri = $request->getUri();
$uri->getHost();
$uri->getPort();
$uri->getPath();

$response = new \BitrixPSR7\Response($originalBitrixResponse);
$response->getHeader('Content-type');
$response->getHeaderLine('Content-type');
$response->getStatusCode();
$response->getBody();
```