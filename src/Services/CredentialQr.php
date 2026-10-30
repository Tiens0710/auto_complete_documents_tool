<?php

declare(strict_types=1);

namespace AIVANBAN\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;

final class CredentialQr
{
    public static function svg(string $url): string
    {
        $options = new QROptions();
        $options->outputInterface = QRMarkupSVG::class;
        $options->outputBase64 = false;
        $options->scale = 7;

        return (new QRCode($options))->render($url);
    }

    public static function pngDataUri(string $url): string
    {
        $options = new QROptions();
        $options->outputInterface = QRGdImagePNG::class;
        $options->outputBase64 = true;
        $options->scale = 8;

        return (new QRCode($options))->render($url);
    }
}
