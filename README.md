# Line Notify SDK

## Install 

```shell
composer required "postoor/line-notify-sdk":"dev-main"
```

## Usage

### OAuth

```php
use postoor\LineNotify\Auth;

$clientId = 'KUU8987HJKKJHKJKJ';
$clientSecret = 'MYTOUYTGFREWFGHGHHJU9876786';

$auth = new Auth([
    "clientId" => $clientId,
    "clientSecret" => $clientSecret,
]);

$callbackUri = 'https://example.com/callback';
$crsfToken = 'IOUHGYTRTUIUHUHU';

return redirect($auth->getAuthorizeUrl($callbackUri, $crsfToken), 302);
```

### Callback & get Token

```php

$code = $_GET['code'] ?? null;
$callbackUri = $_GET['callbackUri'] ?? null;

$token = $auth->getToken($code, $callbackUri);
```

### Revoke

```php

$auth->doRevoke($token);

```

### Notify

```php
use postoor\LineNotify\Message;

$token = 'IOUUGYRTTYUGYV9867658658';

$message = new Message($token);

// Send Text Message
$message->send('星爆氣流斬');

// Upload Image
$message->send('C8763', ['imageFile' => '/tmp/c8763.jpg']);

// Send Image use URL
$message->send('地城餐飲', [
    "imageThumbnail" => "https://scontent.ftpe8-2.fna.fbcdn.net/v/t1.0-9/126326514_822021611704301_376409361755588684_n.png?_nc_cat=100&ccb=2&_nc_sid=dd9801&_nc_ohc=qgM2skn4aBAAX_-ajD9&_nc_ht=scontent.ftpe8-2.fna&oh=1362f98ef4e097fb66910017daa2aa94&oe=5FFE9DC9",
    "imageFullsize" => "https://scontent.ftpe8-2.fna.fbcdn.net/v/t1.0-9/126326514_822021611704301_376409361755588684_n.png?_nc_cat=100&ccb=2&_nc_sid=dd9801&_nc_ohc=qgM2skn4aBAAX_-ajD9&_nc_ht=scontent.ftpe8-2.fna&oh=1362f98ef4e097fb66910017daa2aa94&oe=5FFE9DC9",
]);

// Send Sticker
$message->send('blabla', [
    "stickerPackageId" => 2,
    "stickerId" => 520
]);

// Send Message, But Not NOtification
$message->send('系統下線啦！！！！！', ["notificationDisabled" => true]);
```

### getStatus & checkStatus

```php

$apiInfo = $message->getStatus();

if ($message->checkStatus()) {
    echo 'API正常運行中';
}
```

### Get API Rate Limit Info after lastest query

```php

var_dump($message->rateLimit);

```