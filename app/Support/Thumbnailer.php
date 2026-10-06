<?php

namespace App\Support;

/** Membuat thumbnail JPEG kecil untuk daftar laporan (butuh ekstensi GD; kalau tidak ada, dilewati). */
class Thumbnailer
{
    public static function make(string $source, string $target, int $max = 480): bool
    {
        if (! extension_loaded('gd') || ! is_file($source)) {
            return false;
        }

        $info = @getimagesize($source);
        if ($info === false) {
            return false;
        }

        [$width, $height, $type] = $info;

        $image = false;
        if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
            $image = @imagecreatefromjpeg($source);
        } elseif ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $image = @imagecreatefrompng($source);
        } elseif ($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($source);
        }

        if (! $image || $width < 1 || $height < 1) {
            return false;
        }

        $ratio = min(1, $max / max($width, $height));
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return imagejpeg($canvas, $target, 78);
    }
}
