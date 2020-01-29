<?php
namespace GoogleAuthenticator\QrImageGenerator;

/**
 * TopscanGenerator
 * Topscan二维码生成器
 * -----------------
 * @author Verdient。
 */
class TopscanGenerator implements QrImageGeneratorInterface
{
	/**
	 * @var Integer $size
	 * 二维码尺寸
	 * ------------------
	 * @author Verdient。
	 */
	public $size = 200;

	/**
	 * @var String $level
	 * 等级
	 * ------------------
	 * @example h 高容错
	 * @example q 较高容错
	 * @example m 中等容错
	 * @example l 较低容错
	 * ------------------
	 * @author Verdient。
	 */
	public $level = 'h';

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
		return 'http://qr.topscan.com/api.php?' . http_build_query([
			'text' => $data,
			'w' => $this->size,
			'el' => $this->level
		]);
	}
}