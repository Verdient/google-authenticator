<?php
namespace GoogleAuthenticator\QrImageGenerator;

use chorus\ObjectHelper;

/**
 * Endroid
 * Endroid二维码生成器
 * -----------------
 * @author Verdient。
 */
class EndroidGenerator implements QrImageGeneratorInterface
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
	 * generateUri(String $data)
	 * 生成URI
	 * -------------------------
	 * @param String $data 数据
	 * ------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function generateUri($data){
		$qrCodeClass = 'Endroid\QrCode\QrCode';
		if(!class_exists($qrCodeClass)){
			throw new \Exception('Install endroid/qr-code (via composer require endroid/qr-code) to use the qr-code first');
		}
		$qrCode = ObjectHelper::create($qrCodeClass);
		$qrCode = new $qrCodeClass($data);
		$qrCode->setSize($this->size);
		$qrCode->setWriterByName($this->format);
		return $qrCode->writeDataUri();
	}
}