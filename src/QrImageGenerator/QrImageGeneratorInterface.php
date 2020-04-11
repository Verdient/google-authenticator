<?php
namespace Verdient\GoogleAuthenticator\QrImageGenerator;

/**
 * 二维码生成器接口
 * @author Verdient。
 */
interface QrImageGeneratorInterface
{
	/**
	 * 生成URI
	 * @param string $data 数据
	 * @return string
	 * @author Verdient。
	 */
	public function generateUri($data);
}