<?php
namespace Verdient\GoogleAuthenticator\QrImageGenerator;

/**
 * Topscan二维码生成器
 * @author Verdient。
 */
class TopscanGenerator implements QrImageGeneratorInterface
{
	/**
	 * @var int 二维码尺寸
	 * @author Verdient。
	 */
	public $size = 200;

	/**
	 * @var string 等级
	 *   h 高容错
	 *   q 较高容错
	 *   m 中等容错
	 *   l 较低容错
	 * @author Verdient。
	 */
	public $level = 'h';

	/**
	 * @inheritdoc
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