<?php
/**
 * @author Modava
 */

namespace modava\imagick;

class Imagick
{
    protected $image;

    /**
     * @var integer Opened image height
     */
    private $height;

    /**
     * @var integer Opened image width
     */
    private $width;

    /**
     * @var string Opened image name
     */

    public $filename = null;

    /**
     * @var string image name
     */
    private $name;

    /**
     * @var string Opened image ext
     */
    private $ext;

    /**
     *
     * @throws Null
     *
     */
    public function __construct($pathImage, $online = false)
    {
        if ($online == true) {
            $pathImage = $this->saveImageFromOnline($pathImage);
        }
        try {
            $this->image = new \Imagick($pathImage);
            $this->height = $this->image->getImageHeight();
            $this->width = $this->image->getImageWidth();
            $pathUrl = rtrim(str_replace('\\', '/', $this->image->getImageFilename()), '/\\');
            $arrFile = explode('/', $pathUrl);
            $arrFile = array_pop($arrFile);

            $arrFile = $this->filename == null ? $arrFile : $this->filename;

            $this->name = explode('.', $arrFile)[0];

            $this->ext = explode('.', $arrFile)[1];

            if (file_exists($pathImage)) {
                unlink($pathImage);
            }
        } catch (\ImagickException $ex) {
            throw new \Exception($ex);
        }
    }

    public function borderImage($width = 1, $color = '#ccc')
    {
        $border = new \ImagickDraw();
        $border->setFillColor('none');
        $border->setStrokeColor(new \ImagickPixel($color));
        $border->setStrokeWidth($width);
        $widthPart = $width / 2;
        $border->line(0, 0 + $widthPart, $this->width, 0 + $widthPart);
        $border->line(0, $this->height - $widthPart, $this->width, $this->height - $widthPart);
        $border->line(0 + $widthPart, 0, 0 + $widthPart, $this->height);
        $border->line($this->width - $widthPart, 0, $this->width - $widthPart, $this->height);
        $this->image->drawImage($border);
        return $this;
    }

    public function thumbnail($width, $height)
    {
        if (($this->width / $width) < ($this->height / $height)) {
            $this->image->cropImage($this->width, floor($height * $this->width / $width), 0, (($this->height - ($height * $this->width / $width)) / 2));
        } else {
            $this->image->cropImage(ceil($width * $this->height / $height), $this->height, (($this->width - ($width * $this->height / $height)) / 2), 0);
        }
        $this->image->ThumbnailImage($width, $height, true);
        return $this;
    }

    /**
     *
     * @throws Null
     *
     */
    public function resizeImage($width, $height)
    {
        if ($height != false && $width != false) {

            if (($this->width / $width) < ($this->height / $height)) {
                $this->image->cropImage($this->width, floor($height * $this->width / $width), 0, (($this->height - ($height * $this->width / $width)) / 2));
            } else {
                $this->image->cropImage(ceil($width * $this->height / $height), $this->height, (($this->width - ($width * $this->height / $height)) / 2), 0);
            }
            try {
                $this->image->adaptiveResizeImage($width, $height, true);
            } catch (\ImagickException $ex) {
                throw new \Exception($ex);
            }
            return $this;
        }
    }

    /**
     *
     * @throws Null
     *
     */
    public function cropImage($width, $height, $dst_x = null, $dst_y = null)
    {
        //if $dst_x = null and $dst_y = null, cropImage === resizeImage
        if ($dst_x == null && $dst_y == null) {
            if (($this->width / $width) < ($this->height / $height)) {
                $this->image->cropImage($this->width, floor($height * $this->width / $width), 0, (($this->height - ($height * $this->width / $width)) / 2));
            } else {
                $this->image->cropImage(ceil($width * $this->height / $height), $this->height, (($this->width - ($width * $this->height / $height)) / 2), 0);
            }
            try {
                $this->image->adaptiveResizeImage($width, $height, true);
            } catch (\ImagickException $ex) {
                throw new \Exception($ex);
            }
        } else {
            $this->image->cropImage($width, $height, $dst_x, $dst_y);
        }

        return $this;
    }


    /**
     *
     * @throws Null
     *
     */
    public function watermarkImage(
        $watermarkPath,
        $xPos,
        $yPos,
        $xSize = false,
        $ySize = false,
        $xOffset = false,
        $yOffset = false
    )
    {
        if ($watermarkPath !== null) {
            try {
                $watermark = new \Imagick($watermarkPath);
            } catch (\ImagickException $ex) {
                throw new \Exception($ex);
            }

            // resize watermark
            $newSizeX = false;
            $newSizeY = false;
            if ($xSize !== false) {
                if (is_numeric($xSize)) {
                    $newSizeX = $xSize;
                } elseif (is_string($xSize) && substr($xSize, -1) === '%') {
                    $float = str_replace('%', '', $xSize) / 100;
                    $newSizeX = $this->width * ((float)$float);
                }
            }
            if ($ySize !== false) {
                if (is_numeric($ySize)) {
                    $newSizeY = $ySize;
                } elseif (is_string($ySize) && substr($ySize, -1) === '%') {
                    $float = str_replace('%', '', $ySize) / 100;
                    $newSizeY = $this->height * ((float)$float);
                }
            }
            if ($newSizeX !== false && $newSizeY !== false) {
                try {
                    $watermark->adaptiveResizeImage($newSizeX, $newSizeY);
                } catch (\ImagickException $ex) {
                    throw new \Exception($ex);
                }
            } elseif ($newSizeX !== false && $newSizeY === false) {
                try {
                    $watermark->adaptiveResizeImage($newSizeX, 0);
                } catch (\ImagickException $ex) {
                    throw new \Exception($ex);
                }
            } elseif ($newSizeX === false && $newSizeY !== false) {
                try {
                    $watermark->adaptiveResizeImage(0, $newSizeY);
                } catch (\ImagickException $ex) {
                    throw new \Exception($ex);
                }

            }

            $startX = false;
            $startY = false;
            $watermarkSize = $watermark->getImageGeometry();
            if ($yPos === 'top') {
                $startY = 0;
                if ($yOffset !== false) {
                    $startY += $yOffset;
                }
            } elseif ($yPos === 'bottom') {
                $startY = $this->height - $watermarkSize['height'];
                if ($yOffset !== false) {
                    $startY -= $yOffset;
                }
            } elseif ($yPos === 'center') {
                $startY = ($this->height / 2) - ($watermarkSize['height'] / 2);
            } else {
                throw new \Exception('Param $yPos should be "top", "bottom" or "center" insteed "' . $yPos . '"');
            }

            if ($xPos === 'left') {
                $startX = 0;
                if ($xOffset !== false) {
                    $startX += $xOffset;
                }
            } elseif ($xPos === 'right') {
                $startX = $this->width - $watermarkSize['width'];
                if ($xOffset !== false) {
                    $startX -= $xOffset;
                }
            } elseif ($xPos === 'center') {
                $startX = ($this->width / 2) - ($watermarkSize['width'] / 2);
            } else {
                throw new \Exception('Param $xPos should be "left", "right" or "center" insteed "' . $xPos . '"');
            }

            $this->image->compositeImage($watermark, \Imagick::COMPOSITE_OVER, $startX, $startY);
        }
        return $this;
    }

    public function blur($radius, $delta)
    {
        $this->image->blurImage($radius, $delta);
        return $this;
    }

    public function flip()
    {
        $this->image->flipImage();
        return $this;
    }

    public function flop()
    {
        $this->image->flopImage();
        return $this;
    }


    public function saveTo($path)
    {
        $filename = Helper::createAlias($this->name);
        if($this->filename == null) {
            $fileNameResult = $filename . '.' . $this->ext;
            if (file_exists($path.$fileNameResult)) {
                $fileNameResult = $filename . '-' . time() . '.' . $this->ext;
            }
        } else {
            $fileNameResult = $this->filename;
        }
        $this->image->writeImage($path . $fileNameResult);
        $this->image->destroy();

        return $fileNameResult;
    }

    private function saveImageFromOnline($url)
    {
        $path = "tmp";
        if (!is_dir($path)) {
            mkdir($path);
        }

        $arrFile = explode('/', $url);
        $arrFile = array_pop($arrFile);
        $img = $path . '/' . $arrFile;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $raw = curl_exec($ch);
        curl_close($ch);
        if (file_exists($img)) {
            unlink($img);
        }
        $fp = fopen($img, 'x');
        fwrite($fp, $raw);
        fclose($fp);

        return $img;
    }
}
