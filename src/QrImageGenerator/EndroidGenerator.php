<?php
namespace Verdient\GoogleAuthenticator\QrImageGenerator;

use chorus\ObjectHelper;
use chorus\UnknownClassException;

/**
 * Endroid二维码生成器
 * @author Verdient。
 */
class EndroidGenerator implements QrImageGeneratorInterface
{
    /**
     * @var int 二维码尺寸
     * @author Verdient。
     */
    public $size = 200;

    /**
     * @var string 图片格式
     * @author Verdient。
     */
    public $format = 'png';

    /**
     * 生成URI
     * @inheritdoc
     * @author Verdient。
     */
    public function generateUri($data){
        $qrCodeClass = 'Endroid\QrCode\QrCode';
        if(!class_exists($qrCodeClass)){
            throw new UnknownClassException('Install endroid/qr-code (via composer require endroid/qr-code) to use the qr-code first');
        }
        $qrCode = new $qrCodeClass($data);
        $qrCode->setSize($this->size);
        $qrCode->setWriterByName($this->format);
        return $qrCode->writeDataUri();
    }
}