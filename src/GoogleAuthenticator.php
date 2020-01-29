<?php
namespace GoogleAuthenticator;

use Base32\Base32;
use chorus\ObjectHelper;
use GoogleAuthenticator\QrImageGenerator\QrImageGeneratorInterface;

/**
 * GoogleAuthenticator
 * 谷歌验证器
 * -------------------
 * @author Verdient。
 */
class GoogleAuthenticator extends \chorus\BaseObject
{
	/**
	 * @var const CODE_LENGTH
	 * 代码长度
	 * ----------------------
	 * @author Verdient。
	 */
	const CODE_LENGTH = 6;

	/**
	 * @var Integer $sercetLength
	 * 秘钥长度
	 * --------------------------
	 * @author Verdient。
	 */
	public $sercetLength = 32;

	/**
	 * @var Mixed $qrImageGenerator
	 * 二维码生成器
	 * ----------------------------
	 * @author Verdient。
	 */
	public $qrImageGenerator = 'GoogleAuthenticator\QrImageGenerator\EndroidGenerator';

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
	 * @throws Exception
	 * @return Boolean
	 * @author Verdient。
	 */
	protected function checkSecretlength($value, $throwException = true){
		$result = true;
		if(!is_integer($value)){
			$result = new \Exception('Secret length must be an integer');
		}
		if($value < 16){
			$result = new \Exception('Secret length can\'t be less than 16');
		}
		if($value > 128){
			$result = new \Exception('Secret length can\'t be more than 128');
		}
		if($value % 8 > 0){
			$result = new \Exception('Secret length must be divisible by 8');
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
	 * calculateCaptcha(String $secret[, Integer $timeSlice = null])
	 * 计算验证码
	 * -------------------------------------------------------------
	 * @param String $secret 秘钥
	 * @param Integer $timeSlice 时间分片
	 * ---------------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function calculateCaptcha($secret, $timeSlice = null){
		if($timeSlice === null){
			$timeSlice = $this->getTimeSlice();
		}
		$time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
		$hm = hash_hmac('SHA1', $time, Base32::decode($secret), true);
		$offset = ord(substr($hm, -1)) & 0x0F;
		$hashpart = substr($hm, $offset, 4);
		$value = unpack('N', $hashpart);
		$value = $value[1];
		$value = $value & 0x7FFFFFFF;
		$modulo = pow(10, static::CODE_LENGTH);
		return str_pad($value % $modulo, static::CODE_LENGTH, '0', STR_PAD_LEFT);
	}

	/**
	 * validate(String $captcha, String $secret[, Integer $window = 0])
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
		$time = time();
		for($i = -$window; $i <= $window; $i++){
			$timeSlice = $this->getTimeSlice($time, $i);
			if($this->isEqual($this->calculateCaptcha($secret, $timeSlice), (string) $captcha)){
				return true;
			}
		}
		return false;
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
	 * getUri(String $title, String $name, String $secret)
	 * 获取URI
	 * ---------------------------------------------------
	 * @param String $title 标题
	 * @param String $name 名称
	 * @param String $secret 秘钥
	 * --------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function getUri($title, $name, $secret){
		$uri = 'otpauth://totp/' . $name . '?' . http_build_query([
			'secret' => $secret,
			'issuer' => $title
		]);
		return $uri;
	}

	/**
	 * getQrImageUri(String $title, String $name, String $secret)
	 * 获取二维码URI
	 * ----------------------------------------------------------
	 * @param String $title 标题
	 * @param String $name 名称
	 * @param String $secret 秘钥
	 * --------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function getQrImageUri($title, $name, $secret){
		return $this->getQrImageGenerator()->generateUri($this->getUri($title, $name, $secret));
	}
}