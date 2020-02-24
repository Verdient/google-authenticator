<?php
namespace Verdient\GoogleAuthenticator;

use Base32\Base32;
use chorus\InvalidConfigException;
use chorus\ObjectHelper;
use Verdient\GoogleAuthenticator\QrImageGenerator\QrImageGeneratorInterface;

/**
 * GoogleAuthenticator
 * 谷歌验证器
 * -------------------
 * @author Verdient。
 */
class GoogleAuthenticator extends \chorus\BaseObject
{
	/**
	 * @var Integer $sercetLength
	 * 秘钥长度
	 * --------------------------
	 * @author Verdient。
	 */
	public $sercetLength = 32;

	/**
	 * @var String $type
	 * 类型
	 * -----------------
	 * @author Verdient。
	 */
	public $type = 'totp';

	/**
	 * @var String $issuer
	 * 发行方
	 * -------------------
	 * @author Verdient。
	 */
	public $issuer = null;

	/**
	 * @var String $algorithm
	 * 算法
	 * ----------------------
	 * @author Verdient。
	 */
	public $algorithm = 'SHA1';

	/**
	 * @var Integer $digits
	 * 位数
	 * --------------------
	 * @author Verdient。
	 */
	public $digits = 6;

	/**
	 * @var Integer $period
	 * 周期
	 * --------------------
	 * @author Verdient。
	 */
	public $period = 30;

	/**
	 * @var Mixed $qrImageGenerator
	 * 二维码生成器
	 * ----------------------------
	 * @author Verdient。
	 */
	public $qrImageGenerator = 'Verdient\GoogleAuthenticator\QrImageGenerator\EndroidGenerator';

	/**
	 * init()
	 * 初始化
	 * ------
	 * @inheritdoc
	 * -----------
	 * @author Verdient。
	 */
	public function init(){
		parent::init();
		$this->checkSecretlength($this->sercetLength);
	}

	/**
	 * getQrImageGenerator()
	 * 获取二维码生成器
	 * ---------------------
	 * @return QrImageGeneratorInterface
	 * @author Verdient。
	 */
	public function getQrImageGenerator(){
		if(!is_object($this->qrImageGenerator)){
			$this->qrImageGenerator = ObjectHelper::create($this->qrImageGenerator);
		}
		return $this->qrImageGenerator;
	}

	/**
	 * checkSecretlength(Mixed $value[, Boolean $throwException = true])
	 * 检查秘钥长度
	 * -----------------------------------------------------------------
	 * @param Mixed $value 值
	 * @param Boolean $throwException 是否抛出异常
	 * -----------------------------------------
	 * @throws InvalidConfigException
	 * @return Boolean
	 * @author Verdient。
	 */
	protected function checkSecretlength($value, $throwException = true){
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
	 * generateSecret([Integer $length = null])
	 * 生成秘钥
	 * ----------------------------------------
	 * @param Integer $length 长度
	 * --------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function generateSecret($length = null){
		if($length === null){
			$length = $this->sercetLength;
		}else{
			$this->checkSecretlength($length);
		}
		$key = Base32::encode(random_bytes($length * 2));
		return substr($key, 0, $length);
	}

	/**
	 * calculateCaptcha(String $secret, Integer $counter[, Integer $digits = null])
	 * 计算验证码
	 * ----------------------------------------------------------------------------
	 * @param String $secret 秘钥
	 * @param Integer $counter 计数
	 * @param Integer $digits 位数
	 * ----------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function calculateCaptcha($secret, $counter, $digits = null){
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
	 * validate(String $captcha, String $secret[, Integer $window = 1])
	 * 校验
	 * ----------------------------------------------------------------
	 * @param String $captcha 验证码
	 * @param String $secret 秘钥
	 * @param Integer $window 允许偏移的窗口
	 * -----------------------------------
	 * @return Boolean
	 * @author Verdient。
	 */
	public function validate($captcha, $secret, $window = 1){
		return $this->tValidate($captcha, $secret, $window);
	}

	/**
	 * tValidate(String $captcha, String $secret[, Integer $window = 1])
	 * TOTP校验
	 * ----------------------------------------------------------------
	 * @param String $captcha 验证码
	 * @param String $secret 秘钥
	 * @param Integer $window 允许偏移的窗口
	 * -----------------------------------
	 * @return Boolean
	 * @author Verdient。
	 */
	public function tValidate($captcha, $secret, $window = 1){
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
	 * hValidate(String $captcha, String $secret, Integer $counter)
	 * HOTP校验
	 * ------------------------------------------------------------
	 * @param String $captcha 验证码
	 * @param String $secret 秘钥
	 * @param Integer $counter 计数器
	 * -----------------------------
	 * @return Boolean
	 * @author Verdient。
	 */
	public function hValidate($captcha, $secret, $counter){
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
	 * validateFormat(String $captcha)
	 * 校验格式
	 * -------------------------------
	 * @param String $captcha 验证码
	 * ----------------------------
	 * @return Boolean
	 * @author Verdient。
	 */
	protected function validateFormat($captcha){
		return (bool) preg_match('/^[1-9][0-9]*$/', $captcha);
	}

	/**
	 * isEqual(String $string1, String $string2)
	 * 是否相等
	 * -----------------------------------------
	 * @param String $string1 字符串1
	 * @param String $string2 字符串2
	 * -----------------------------
	 * @return Boolean
	 * @author Verdient。
	 */
	protected function isEqual($string1, $string2){
		if(function_exists('hash_equals')){
			return hash_equals($string1, $string2);
		}
		return substr_count($string1 ^ $string2, "\0") * 2 === strlen($string1 . $string2);
	}

	/**
	 * getTimeSlice([Integer $time = null, Integer $offset = 0])
	 * 获取时间分片
	 * ---------------------------------------------------------
	 * @param Integer $time 时间戳
	 * @param Integer $offset 偏移量
	 * ----------------------------
	 * @return Integer
	 * @author Verdient。
	 */
	public function getTimeSlice($time = null, $offset = 0){
		if($time === null){
			$time = time();
		}
		return floor($time / 30) + $offset;
	}

	/**
	 * getUri(String $title, String $name, String $secret[, Array $options = []])
	 * 获取URI
	 * --------------------------------------------------------------------------
	 * @param String $label 标签
	 * @param String $secret 秘钥
	 * @param Array $options 属性
	 * -------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function getUri($label, $secret, $options = []){
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
	 * getQrImageUri(String $title, String $name, String $secret[, Array $options = []])
	 * 获取二维码URI
	 * ---------------------------------------------------------------------------------
	 * @param String $label 标签
	 * @param String $secret 秘钥
	 * @param Array $options 属性
	 * -------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function getQrImageUri($label, $secret, $options = []){
		return $this->getQrImageGenerator()->generateUri($this->getUri($label, $secret, $options));
	}
}