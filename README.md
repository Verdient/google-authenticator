# GoogleAuthenticator

**谷歌验证器**

## 创建验证器实例

```php
use Verdient\GoogleAuthenticator\GoogleAuthenticator;

/**
 * 密钥长度 (可选)
 * 必须为8的倍数的正整数
 * 默认为32
 */
$sercetLength = 32;

/**
 * 类型 (可选)
 * 可选值 totp(基于时间)，hotp（基于计数器）
 * 默认为totp
 */
$type = 'totp';

/**
 * issuer (可选)
 * 发行方
 * 默认为null
 */
$issuer = null;

/**
 * 算法 (可选)
 * 可选 SHA1，SHA256，SHA512
 * 默认为SHA1
 */
$algorithm = 'SHA1';

/**
 * 位数 (可选)
 * 可选6，8
 * 默认为6
 */
$digits = 6;

/**
 * 周期 (可选，仅在type为totp时有效)
 * 默认为30
 */
$period = 30;

/**
 * 二维码生成器 (可选)
 * 默认Verdient\GoogleAuthenticator\QrImageGenerator\EndroidGenerator
 * EndroidGenerator需要通过 composer require endroid/qr-code 安装endroid/qr-code
 * 可选值 Verdient\GoogleAuthenticator\QrImageGenerator\GoQRGenerator,仅支持HTTP
 * 可选值 Verdient\GoogleAuthenticator\QrImageGenerator\TopscanGenerator,仅支持HTTPS
 */
$qrImageGenerator = 'Verdient\GoogleAuthenticator\QrImageGenerator\EndroidGenerator';

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
$name = '${名称}'; //名称
$secret = '${secret}'; //密钥，这里一般是用上面生成的密钥
$options = [
	'issuer' => '${issuer}', // 发行方
	'algorithm' => '${algorithm}', // 算法
	'digits' => '${digits}', // 位数
	'counter' => '${counter}', // 计数（仅当type为hotp时有效）
	'period' => '${period}' // 周期（仅当type为totp时有效）
]; // 选项，用于覆盖全局配置，一般情况下不用传
```
### 前端生成二维码

```php
$data = $authenticator->getUri($name, $secret, $options);
```
`$data` 为用于生成二维码的数据，可将`$data`和`$secret`一起返回给前端，由前端根据`$data`生成二维码并展示秘钥 `$secret` 来应对二维码扫描不了的情况
### 后端生成二维码
```php
$data = $authenticator->getQrImageUri($name, $secret, $options);
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