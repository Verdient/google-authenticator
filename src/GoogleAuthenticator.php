<?php
namespace Verdient\GoogleAuthenticator;

use Base32\Base32;
use chorus\BaseObject;
use chorus\Configurable;
use chorus\InvalidConfigException;
use chorus\ObjectHelper;
use Verdient\GoogleAuthenticator\QrImageGenerator\QrImageGeneratorInterface;

/**
 * 谷歌验证器
 * @author Verdient。
 */
class GoogleAuthenticator extends BaseObject
{
    use Configurable;

    /**
     * @var int 秘钥长度
     * @author Verdient。
     */
    public $secretLength = 32;

    /**
     * @var string 类型
     * @author Verdient。
     */
    public $type = 'totp';

    /**
     * @var string 发行方
     * @author Verdient。
     */
    public $issuer = null;

    /**
     * @var string 算法
     * @author Verdient。
     */
    public $algorithm = 'SHA1';

    /**
     * @var int 位数
     * @author Verdient。
     */
    public $digits = 6;

    /**
     * @var int 周期
     * @author Verdient。
     */
    public $period = 30;

    /**
     * @var mixed 二维码生成器
     * @author Verdient。
     */
    public $qrImageGenerator = 'Verdient\GoogleAuthenticator\QrImageGenerator\EndroidGenerator';

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function __construct($config = [])
    {
        $this->configuration($config);
        $this->checkSecretlength($this->secretLength);
    }

    /**
     * 获取二维码生成器
     * @return QrImageGeneratorInterface
     * @author Verdient。
     */
    public function getQrImageGenerator()
    {
        if(!is_object($this->qrImageGenerator)){
            $this->qrImageGenerator = ObjectHelper::create($this->qrImageGenerator);
        }
        return $this->qrImageGenerator;
    }

    /**
     * 检查秘钥长度
     * @param mixed $value 值
     * @param bool $throwException 是否抛出异常
     * @throws InvalidConfigException
     * @return bool
     * @author Verdient。
     */
    protected function checkSecretlength($value, $throwException = true)
    {
        $result = true;
        if(!is_integer($value)){
            $result = new InvalidConfigException('Secret length must be an integer');
        }
        if($value < 16){
            $result = new InvalidConfigException('Secret length can\'t be less than 16');
        }
        if($value > 128){
            $result = new InvalidConfigException('Secret length can\'t be more than 128');
        }
        if($value % 8 > 0){
            $result = new InvalidConfigException('Secret length must be divisible by 8');
        }
        if($result !== true && $throwException === true){
            throw $result;
        }
        return $result === true;
    }

    /**
     * 生成秘钥
     * @param int $length 长度
     * @return string
     * @author Verdient。
     */
    public function generateSecret($length = null)
    {
        if($length === null){
            $length = $this->secretLength;
        }else{
            $this->checkSecretlength($length);
        }
        $key = Base32::encode(random_bytes($length * 2));
        return substr($key, 0, $length);
    }

    /**
     * 计算验证码
     * @param string $secret 秘钥
     * @param int $counter 计数
     * @param int $digits 位数
     * @return string
     * @author Verdient。
     */
    public function calculateCaptcha($secret, $counter, $digits = null)
    {
        if($digits === null){
            $digits = $this->digits;
        }
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $counter);
        $hm = hash_hmac($this->algorithm, $time, Base32::decode($secret), true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack('N', $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, $digits);
        return str_pad($value % $modulo, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * 校验
     * @param string $captcha 验证码
     * @param string $secret 秘钥
     * @param int $window 允许偏移的窗口
     * @return bool
     * @author Verdient。
     */
    public function validate($captcha, $secret, $window = 1)
    {
        return $this->tValidate($captcha, $secret, $window);
    }

    /**
     * TOTP校验
     * @param string $captcha 验证码
     * @param string $secret 秘钥
     * @param int $window 允许偏移的窗口
     * @return bool
     * @author Verdient。
     */
    public function tValidate($captcha, $secret, $window = 1)
    {
        if($this->validateFormat($captcha)){
            $captcha = (string) $captcha;
            $digits = mb_strlen($captcha);
            if($digits !== 6 && $digits !== 8){
                return false;
            }
            $time = time();
            for($i = -$window; $i <= $window; $i++){
                $timeSlice = $this->getTimeSlice($time, $i);
                if($this->isEqual($this->calculateCaptcha($secret, $timeSlice, $digits), $captcha)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * HOTP校验
     * @param string $captcha 验证码
     * @param string $secret 秘钥
     * @param int $counter 计数器
     * @return bool
     * @author Verdient。
     */
    public function hValidate($captcha, $secret, $counter)
    {
        if($this->validateFormat($captcha)){
            $captcha = (string) $captcha;
            $digits = mb_strlen($captcha);
            if($digits !== 6 && $digits !== 8){
                return false;
            }
            if($this->isEqual($this->calculateCaptcha($secret, $counter, $digits), $captcha)){
                return true;
            }
        }
        return false;
    }

    /**
     * 校验格式
     * @param string $captcha 验证码
     * @return bool
     * @author Verdient。
     */
    protected function validateFormat($captcha)
    {
        return (bool) preg_match('/^[1-9][0-9]*$/', $captcha);
    }

    /**
     * 是否相等
     * @param string $string1 字符串1
     * @param string $string2 字符串2
     * @return bool
     * @author Verdient。
     */
    protected function isEqual($string1, $string2)
    {
        if(function_exists('hash_equals')){
            return hash_equals($string1, $string2);
        }
        return substr_count($string1 ^ $string2, "\0") * 2 === strlen($string1 . $string2);
    }

    /**
     * 获取时间分片
     * @param int $time 时间戳
     * @param int $offset 偏移量
     * @return int
     * @author Verdient。
     */
    public function getTimeSlice($time = null, $offset = 0)
    {
        if($time === null){
            $time = time();
        }
        return floor($time / 30) + $offset;
    }

    /**
     * 获取URI
     * @param string $label 标签
     * @param string $secret 秘钥
     * @param array $options 属性
     * @return string
     * @author Verdient。
     */
    public function getUri($label, $secret, $options = [])
    {
        $params = [
            'secret' => $secret
        ];
        foreach(['issuer', 'algorithm', 'digits', 'counter', 'period'] as $name){
            if(isset($options[$name])){
                $params[$name] = $options[$name];
            }else{
                if(property_exists($this, $name) && !empty($this->$name)){
                    $params[$name] = $this->$name;
                }
            }
        }
        $uri = 'otpauth://' . $this->type . '/' . $label . '?' . http_build_query($params);
        return $uri;
    }

    /**
     * 获取二维码URI
     * @param string $label 标签
     * @param string $secret 秘钥
     * @param array $options 属性
     * @return string
     * @author Verdient。
     */
    public function getQrImageUri($label, $secret, $options = [])
    {
        return $this->getQrImageGenerator()->generateUri($this->getUri($label, $secret, $options));
    }
}