<?php
// Génère les icônes PWA (indigo + "CRM"). À lancer une fois : php gen_icons.php
function icon(int $size, string $file): void
{
    $im = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($im, 79, 70, 229); // indigo-600
    imagefilledrectangle($im, 0, 0, $size, $size, $bg);

    // "CRM" à partir de la police bitmap intégrée, mise à l'échelle.
    $tmp = imagecreatetruecolor(60, 20);
    $b2 = imagecolorallocate($tmp, 79, 70, 229);
    imagefilledrectangle($tmp, 0, 0, 60, 20, $b2);
    $w2 = imagecolorallocate($tmp, 255, 255, 255);
    imagestring($tmp, 5, 18, 3, 'CRM', $w2);
    imagecopyresampled(
        $im, $tmp,
        (int) ($size * 0.16), (int) ($size * 0.36),
        0, 0,
        (int) ($size * 0.68), (int) ($size * 0.28),
        60, 20
    );

    imagepng($im, $file);
    imagedestroy($im);
    imagedestroy($tmp);
}

@mkdir(__DIR__.'/public/icons', 0755, true);
icon(192, __DIR__.'/public/icons/icon-192.png');
icon(512, __DIR__.'/public/icons/icon-512.png');
icon(180, __DIR__.'/public/icons/apple-touch-icon.png');
echo "Icônes générées dans public/icons/\n";
