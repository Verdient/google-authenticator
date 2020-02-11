<?php
namespace Verdient\GoogleAuthenticator\QrImageGenerator;

/**
 * GoQRGenerator
 * GoQR二维码生成器
 * --------------
 * @author Verdient。
 */
class GoQRGenerator implements QrImageGeneratorInterface
{
	/**
	 * @var Integer $size
	 * 二维码尺寸
	 * ------------------
	 * @author Verdient。
	 */
	public $size = 200;

	/**
	 * @var String $format
	 * 格式
	 * -------------------
	 * @author Verdient。
	 */
	public $format = 'png';

	/**
	 * @var String $level
	 * 等级
	 * ------------------
	 * @author Verdient。
	 */
	public $level = 'H';

	/**
	 * generateUri(String $data)
	 * 生成URI
	 * -------------------------
	 * @param String $data 数据
	 * ------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function generateUri($data){
		$size = $this->size . 'x' . $this->size;
		return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
			'data' => $data,
			'size' => $size,
			'ecc' => $this->level,
			'format' => $this->format
		]);
	}
}