<?php
class Captcha {
    public static function gd() {
        $len = 4;
        $text = self::generateText($len);
        $font = 5;
        $fontWidth = imagefontwidth($font);
        $fontHeight = imagefontheight($font);
        
        $width = $fontWidth * $len * 2;
        $height = $fontHeight * 2;
        $im   = imagecreate($width, $height);
        $bg  = imagecolorallocate($im, 253, 246, 236);
        $fontWidth = intval($width / strlen($text));

        for($i = 0; $i < strlen($text); $i++) {
            $charIm = self::drawChar($text[$i], $font);
            imagecopy($im, $charIm, $fontWidth * $i , 0, 0, 0, imagesx($charIm), $height * 2);
            imagedestroy($charIm);
        }
        imagefill($im, 0, 0, $bg);
        

        self::drawLine($im, $width, $height);
        //distort not work
        //$im = self::distort($im, $width, $height, $bg);
        return $im;
    }
    
    public static function outputCaptcha() {
        $im = self::gd();
        
        if($im) {
            header('Content-Type: image/jpeg');
            imagejpeg($im);
            imagedestroy($im);
        }
    }

    public static function drawLine($im, $width, $height) {
        $lineColor  = imagecolorallocate($im, mt_rand(50, 255), mt_rand(50, 255), mt_rand(50, 255));

        if (mt_rand(0, 1)) {
            $Xa   = mt_rand(0, $width/2);
            $Ya   = mt_rand(0, $height);
            $Xb   = mt_rand($width/2, $width);
            $Yb   = mt_rand(0, $height);
        } else {
            $Xa   = mt_rand(0, $width);
            $Ya   = mt_rand(0, $height/2);
            $Xb   = mt_rand(0, $width);
            $Yb   = mt_rand($height/2, $height);
        }
        imagesetthickness($im, mt_rand(1, 3));
        imageline($im, $Xa, $Ya, $Xb, $Yb, $lineColor);
    }

    public static function distort($image, $width, $height, $bg) {
        $newImage = imagecreatetruecolor($width, $height);
        $X = mt_rand(0, $width);
        $Y = mt_rand(0, $height);
        $phase = mt_rand(0, 10);
        $scale = 1.1 + mt_rand(0, 10000) / 30000;
        for($x = 0; $x < $width; $x++) {
            for($y = 0; $y < $height; $y++) {
                $Vx = $x - $X;
                $Vy = $y - $Y;
                $Vn = sqrt($Vx * $Vx + $Vy * $Vy);
                if ($Vn != 0) {
                    $Vn2 = $Vn + 4 * sin($Vn / 30);
                    $nX  = $X + ($Vx * $Vn2 / $Vn);
                    $nY  = $Y + ($Vy * $Vn2 / $Vn);
                } else {
                    $nX = $X;
                    $nY = $Y;
                }
                $nY = $nY + $scale * sin($phase + $nX * 0.2);
                //$p = getColor($image, round($nX), round($nY), $bg);
                $p = interpolate(
                    $nX - floor($nX),
                    $nY - floor($nY),
                    getColor($image, floor($nX), floor($nY), $bg),
                    getColor($image, ceil($nX), floor($nY), $bg),
                    getColor($image, floor($nX), ceil($nY), $bg),
                    getColor($image, ceil($nX), ceil($nY), $bg)
                );
                
                imagesetpixel($newImage, $x, $y, $p);
            }
        }
        imagedestroy($image);
        return $newImage;
    }

    public static function drawChar($char, $font) {
        $fontWidth = imagefontwidth($font);
        $fontHeight = imagefontheight($font);
        $width = $fontWidth * 2;
        $height = $fontHeight * 2;
        
        $im = imagecreate($width, $height);
        $bgdColor  = imagecolorallocate($im, 253, 246, 236);
        $fontColor = imagecolorallocate($im, 0, 0, 0);
        $font = 5;
        imagechar($im, $font, ($width - $fontWidth) / 2, ($height - $fontWidth) / 2, $char, $fontColor);
        $isRight = mt_rand(0, 1);
        $angle = $isRight ? mt_rand(310, 360) : mt_rand(0, 35);
        $rotateIm = imagerotate($im, $angle, $bgdColor);
        imagedestroy($im);
        return $rotateIm;
    }

    public static function generateText($count) {
        //todo: remove 0 and o
        $alphas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $nums = '0123456789';
        $text = '';
        for($i = 0; $i < $count; $i++) {
            if(mt_rand(1, 2) === 1) {
                $text .= $alphas[mt_rand(0, 51)];
            } else {
                $text .= $nums[mt_rand(0, 9)];
            }
        }
        return $text;
    }
}

function getColor($im, $x, $y, $bg) {
    $L = imagesx($im);
    $H = imagesy($im);
    if ($x < 0 || $x >= $L || $y < 0 || $y >= $H) {
        return $bg;
    }

    return imagecolorat($im, $x, $y);
}

function interpolate($x, $y, $nw, $ne, $sw, $se) {
    list($r0, $g0, $b0) = $this->getRGB($nw);
    list($r1, $g1, $b1) = $this->getRGB($ne);
    list($r2, $g2, $b2) = $this->getRGB($sw);
    list($r3, $g3, $b3) = $this->getRGB($se);
    $cx = 1.0 - $x;
    $cy = 1.0 - $y;
    $m0 = $cx * $r0 + $x * $r1;
    $m1 = $cx * $r2 + $x * $r3;
    $r  = (int) ($cy * $m0 + $y * $m1);
    $m0 = $cx * $g0 + $x * $g1;
    $m1 = $cx * $g2 + $x * $g3;
    $g  = (int) ($cy * $m0 + $y * $m1);
    $m0 = $cx * $b0 + $x * $b1;
    $m1 = $cx * $b2 + $x * $b3;
    $b  = (int) ($cy * $m0 + $y * $m1);
    return ($r << 16) | ($g << 8) | $b;
}
