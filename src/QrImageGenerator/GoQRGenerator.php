<?php
namespace Verdient\GoogleAuthenticator\QrImageGenerator;

/**
 * GoQR二维码生成器
 * @author Verdient。
 */
class GoQRGenerator implements QrImageGeneratorInterface
{
	/**
	 * @var int 二维码尺寸
	 * @author Verdient。
	 */
	public $size = 200;

	/**
	 * @var string 格式
	 * @author Verdient。
	 */
	public $format = 'png';

	/**
	 * @var string 等级
	 * @author Verdient。
	 */
	public $level = 'H';

	/**
	 * @inheritdoc
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