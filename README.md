# GoogleAuthenticator

**谷歌验证器**

## 创建验证器实例

```php
use GoogleAuthenticator\GoogleAuthenticator;

/**
 * 密钥长度 (可选)
 * 必须为8的倍数的正整数
 * 默认32
 */
$sercetLength = 32;

/**
 * 二维码生成器 (可选)
 * 默认GoogleAuthenticator\QrImageGenerator\EndroidGenerator
 * EndroidGenerator需要通过 composer require endroid/qr-code 安装endroid/qr-code
 * 可选值 GoogleAuthenticator\QrImageGenerator\GoQRGenerator,仅支持HTTP
 * 可选值 GoogleAuthenticator\QrImageGenerator\TopscanGenerator,仅支持HTTPS
 */
$qrImageGenerator = 'GoogleAuthenticator\QrImageGenerator\EndroidGenerator';

$authenticator = new GoogleAuthenticator([
	'sercetLength' => $sercetLength,
	'qrImageGenerator' => $qrImageGenerator
]);
```
## 生成密钥
```php

/**
 * 秘钥长度(可选)
 * 该参数为空时秘钥长度以sercetLength的配置为准
 */
$length = null;
$secret = $authenticator->generateSecret($length);
```

## 获取绑定数据
首先准备基础数据

```php
$title = '${标题'}; //标题
$name = '${名称}'; //名称
$secret = '${secret}'; //密钥 这里一般是用上面生成的密钥
```
### 前端生成二维码

```php
$data = $authenticator->getUri($title, $name, $secret);
```
`$data` 为用于生成二维码的数据，可将`$data`和`$secret`一起返回给前端，由前端根据`$data`生成二维码并展示秘钥 `$secret` 来应对二维码扫描不了的情况
### 后端生成二维码
```php
$data = $authenticator->getQrImageUri($title, $name, $secret);
```
`$data`为生成好的二维码URI，前端直接`<img src="${data}">`就可以了


>推荐使用前端生成二维码

## 验证

```php
/**
 * 验证码 (必填)
 * 由用户输入，用于验证
 */
$captcha = '123456';

/**
 * 秘钥 (必填)
 * 一般从数据库中获取
 */
$secret = '';

/**
 * 允许向前和向后偏移的时间窗口 (可选)
 * 默认为1
 * 必须为0或正整数
 */
$window = 1;

$authenticator->validate($captcha, $secret, $window);
```
> 验证器每`30`秒为一个窗口，如果`$window`为`0`, 则在窗口交替的时候会有验证不通过的问题
比如当前窗口的验证码为`1`，前一窗口的验证码为`2`，后一窗口的验证码为`3`
则在`$window`为`0`时，只有`1`可以通过验证
而在`$window`为`1`时，`1`，`2`，`3`都可以通过验证
以此类推