<?php
namespace GoogleAuthenticator\QrImageGenerator;

/**
 * QrImageGeneratorInterface
 * 二维码生成器接口
 * -------------------------
 * @author Verdient。
 */
interface QrImageGeneratorInterface
{
	/**
	 * generateUri(String $data)
	 * 生成URI
	 * -------------------------
	 * @param String $data 数据
	 * ------------------------
	 * @return String
	 * @author Verdient。
	 */
	public function generateUri($data);
}