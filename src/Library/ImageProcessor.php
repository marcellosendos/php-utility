<?php

/*
 * This file is part of marcellosendos/php-utility
 *
 * (c) Marcel Oehler <mo@marcellosendos.ch>
 */

namespace Marcellosendos\PhpUtility\Library;

use Exception;

class ImageProcessor
{

// --- CONSTANTS ----------------------------------------------------------------------------------

    const TYPE_GIF = 'gif';
    const TYPE_PNG = 'png';
    const TYPE_JPG = 'jpg';
    const TYPE_IMAGE = 'image';

    const SIZE_SMALL = 'smaller';
    const SIZE_SMALL_BIG = 'smaller_bigger';
    const SIZE_BIG = 'bigger';

    const SCALE_RATIO = 'ratio';
    const SCALE_CROP = 'crop';
    const SCALE_PROP = 'prop';
    const SCALE_RESIZE = 'resize';

    const SCALE_POS_NULL = 'null';

    const POS_X_LEFT = 'left';
    const POS_X_CENTER = 'center';
    const POS_X_RIGHT = 'right';
    const POS_Y_TOP = 'top';
    const POS_Y_MIDDLE = 'middle';
    const POS_Y_BOTTOM = 'bottom';

    const MIRROR_HORIZONTAL = 'horizontal';
    const MIRROR_VERTICAL = 'vertical';

    const ROTATE_90 = '90';
    const ROTATE_180 = '180';
    const ROTATE_270 = '270';

    const COORD_SRC_X = 'src_x';
    const COORD_SRC_Y = 'src_y';
    const COORD_SRC_WIDTH = 'src_width';
    const COORD_SRC_HEIGHT = 'src_height';

    const COORD_DEST_X = 'dest_x';
    const COORD_DEST_Y = 'dest_y';
    const COORD_DEST_WIDTH = 'dest_width';
    const COORD_DEST_HEIGHT = 'dest_height';

// --- CONFIG -------------------------------------------------------------------------------------

    /**
     * Image extension to image type map
     */
    protected static $EXT_TYPE_MAP = [
        'gif' => self::TYPE_GIF,
        'png' => self::TYPE_PNG,
        'jpg' => self::TYPE_JPG,
        'jpeg' => self::TYPE_JPG,
        'jpe' => self::TYPE_JPG
    ];

    /**
     * Image type to image extension map
     */
    protected static $TYPE_EXT_MAP = [
        self::TYPE_GIF => 'gif',
        self::TYPE_PNG => 'png',
        self::TYPE_JPG => 'jpg'
    ];

    /**
     * List of image mime types
     */
    protected static $MIME_TYPES = [
        self::TYPE_GIF => 'image/gif',
        self::TYPE_PNG => 'image/png',
        self::TYPE_JPG => 'image/jpeg'
    ];

    /**
     * Configuration of default image
     */
    protected static $DEFAULT_CONF = [
        'name' => 'default.png',
        'type' => self::TYPE_PNG,
        'color' => [255, 255, 255],
        'width' => 1,
        'height' => 1
    ];

    /**
     * Regular expression for matching urls
     */
    protected static $URL_REGEX = "/^http[s]?:\/\//";

    /**
     * Separator between file name and file extension
     */
    protected static $NAME_EXT_SEP = '.';

// --- RUNTIME ------------------------------------------------------------------------------------

    /**
     * File to process
     */
    protected $INPUT_FILE = '';

    /**
     * File data to process
     */
    protected $INPUT_DATA = '';

    /**
     * Path to output file
     */
    protected $OUTPUT_FILE = '';

    /**
     * Binary output data
     */
    protected $OUTPUT_DATA = '';

    /**
     * Name of output image
     */
    protected $OUTPUT_NAME = '';

    /**
     * Type of output image
     */
    protected $OUTPUT_TYPE = self::TYPE_IMAGE;

    /**
     * Quality of output image
     */
    protected $OUTPUT_QUALITY = 100;

    /**
     * List of modifications for given image
     */
    protected $MODIFICATIONS = [];

// === CLASS ======================================================================================

    /**
     * @param void
     * @throws  Exception
     */
    public function __construct()
    {
        // gd has to be enabled
        if (!function_exists('imagecopyresampled')) {
            throw new Exception('gd is not enabled');
        }
    }

// === SETTINGS ===================================================================================

    /**
     * @param string $file
     * @return void
     */
    public function setInputFile($file)
    {
        if (is_file($file) || preg_match(self::$URL_REGEX, $file)) {
            $this->INPUT_FILE = $file;
        }
    }

    /**
     * @param string $data
     * @return void
     */
    public function setInputData($data)
    {
        if (is_string($data) && strlen($data) > 0) {
            $this->INPUT_DATA = $data;
        }
    }

    /**
     * @param string $file
     * @return void
     */
    public function setOutputFile($file)
    {
        if (is_string($file) && strlen($file) > 0) {
            $this->OUTPUT_FILE = $file;
        }
    }

    /**
     * @param string $type (ImageProcessor::TYPE_*)
     * @return void
     */
    public function setOutputType($type)
    {
        if (is_string($type) && strlen($type) > 0 && isset(self::$TYPE_EXT_MAP[$type])) {
            $this->OUTPUT_TYPE = $type;
        }
    }

    /**
     * @param int $quality (0-100)
     * @return void
     */
    public function setOutputQuality($quality)
    {
        if ($quality >= 0 && $quality <= 100) {
            $this->OUTPUT_QUALITY = $quality;
        }
    }

    /**
     * @return string
     */
    public function getOutputData()
    {
        return $this->OUTPUT_DATA;
    }

    /**
     * @return string
     */
    public function getOutputLength()
    {
        return strlen($this->OUTPUT_DATA);
    }

    /**
     * @return string
     */
    public function getOutputName()
    {
        return ($this->OUTPUT_NAME . (isset(self::$TYPE_EXT_MAP[$this->OUTPUT_TYPE]) ? self::$NAME_EXT_SEP . self::$TYPE_EXT_MAP[$this->OUTPUT_TYPE] : ''));
    }

    /**
     * @return string
     */
    public function getOutputType()
    {
        return (isset(self::$MIME_TYPES[$this->OUTPUT_TYPE]) ? self::$MIME_TYPES[$this->OUTPUT_TYPE] : '');
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->INPUT_FILE = $this->INPUT_DATA = $this->OUTPUT_FILE = $this->OUTPUT_DATA = $this->OUTPUT_NAME = '';

        $this->OUTPUT_TYPE = self::TYPE_IMAGE;
        $this->OUTPUT_QUALITY = 100;

        $this->MODIFICATIONS = [];
    }

// === MODIFICATIONS ==============================================================================

    /**
     * @param int $width
     * @param int $height
     * @param string $crop_x (ImageProcessor::POS_X_*)
     * @param string $crop_y (ImageProcessor::POS_Y_*)
     * @param string $scale (ImageProcessor::SCALE_*)
     * @param string $type (ImageProcessor::SIZE_*)
     * @return void
     */
    public function addMod_changeSize($width, $height, $crop_x = '', $crop_y = '', $scale = self::SCALE_RESIZE, $type = self::SIZE_SMALL)
    {
        $this->MODIFICATIONS[] = [
            'name' => 'change_size',
            'width' => $width,
            'height' => $height,
            'crop_x' => $crop_x,
            'crop_y' => $crop_y,
            'scale' => $scale,
            'type' => $type
        ];
    }

    /**
     * @param string $file
     * @param bool $alpha
     * @param string $pos_x (ImageProcessor::POS_Y_*)
     * @param string $pos_y (ImageProcessor::POS_Y_*)
     * @param int $min_width (0 for width of insert image)
     * @param int $min_height (0 for height of insert image)
     * @return void
     */
    public function addMod_insertImage($file, $alpha = false, $pos_x = self::POS_X_RIGHT, $pos_y = self::POS_Y_BOTTOM, $min_width = 0, $min_height = 0)
    {
        $this->MODIFICATIONS[] = [
            'name' => 'insert_image',
            'file' => $file,
            'alpha' => $alpha,
            'pos_x' => $pos_x,
            'pos_y' => $pos_y,
            'min_width' => $min_width,
            'min_height' => $min_height
        ];
    }

    /**
     * @param string $text
     * @param string $fontfile
     * @param int $size
     * @param int $x
     * @param int $y
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $angle
     * @param string $align (ImageProcessor::POS_X_*)
     * @param string $valign (ImageProcessor::POS_Y_*)
     * @return void
     */
    public function addMod_insertText($text, $fontfile, $size, $x, $y, $red = 0, $green = 0, $blue = 0, $angle = 0, $align = self::POS_X_LEFT, $valign = self::POS_Y_BOTTOM)
    {
        $this->MODIFICATIONS[] = [
            'name' => 'insert_text',
            'text' => $text,
            'fontfile' => $fontfile,
            'size' => $size,
            'x' => $x,
            'y' => $y,
            'red' => $red,
            'green' => $green,
            'blue' => $blue,
            'angle' => $angle,
            'align' => $align,
            'valign' => $valign
        ];
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $width (0 for remaining width of image)
     * @param int $height (0 for remaining height of image)
     * @return void
     */
    public function addMod_extract($x, $y, $width = 0, $height = 0)
    {
        $this->MODIFICATIONS[] = [
            'name' => 'extract',
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height
        ];
    }

    /**
     * @param string $type (ImageProcessor::MIRROR_*)
     * @return void
     */
    public function addMod_mirror($type)
    {
        $this->MODIFICATIONS[] = [
            'name' => 'mirror',
            'type' => $type
        ];
    }

    /**
     * @param string $type (ImageProcessor::ROTATE_*)
     * @return void
     */
    public function addMod_rotate($type)
    {
        $this->MODIFICATIONS[] = [
            'name' => 'rotate',
            'type' => $type
        ];
    }

    /**
     * @param void
     * @return void
     */
    public function addMod_greyscale()
    {
        $this->MODIFICATIONS[] = [
            'name' => 'greyscale'
        ];
    }

    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $percent
     * @return void
     */
    public function addMod_colorize($red, $green, $blue, $percent)
    {
        $this->MODIFICATIONS[] = [
            'name' => 'colorize',
            'red' => $red,
            'green' => $green,
            'blue' => $blue,
            'percent' => $percent
        ];
    }

    /**
     * @param void
     * @return void
     */
    public function addMod_sharpen()
    {
        $this->MODIFICATIONS[] = [
            'name' => 'sharpen'
        ];
    }

    /**
     * @param void
     * @return void
     */
    public function addMod_blur()
    {
        $this->MODIFICATIONS[] = [
            'name' => 'blur'
        ];
    }

    /**
     * @return void
     */
    public function resetMod()
    {
        $this->MODIFICATIONS = [];
    }

// === EXECUTE ====================================================================================

    /**
     * @param void
     * @return bool
     */
    public function execute()
    {
// --- INPUT --------------------------------------------------------------------------------------

        // nothing to do
        if (empty($this->INPUT_FILE) && empty($this->INPUT_DATA)) {
            return false;
        }

        // flag for file / data distinction - 1 for file, 0 for data
        $use_input_file = !empty($this->INPUT_FILE);

        if ($use_input_file) {
            // set name and image type based on filename
            [$input_name, $input_ext] = self::getNameExtension($this->INPUT_FILE);
            $input_type = (isset(self::$EXT_TYPE_MAP[$input_ext]) ? self::$EXT_TYPE_MAP[$input_ext] : self::TYPE_IMAGE);
        } else {
            // set pseudo name and generic image type
            $input_name = sha1($this->INPUT_DATA);
            $input_type = self::TYPE_IMAGE;
        }

// --- OUTPUT -------------------------------------------------------------------------------------

        // flag for file / data distinction - 1 for file, 0 for data
        $use_output_file = !empty($this->OUTPUT_FILE);

        if ($use_output_file) {
            // set name and image type based on filename
            [$this->OUTPUT_NAME, $output_ext] = self::getNameExtension($this->OUTPUT_FILE);
            $this->OUTPUT_TYPE = (isset(self::$EXT_TYPE_MAP[$output_ext]) ? self::$EXT_TYPE_MAP[$output_ext] : $input_type);
        } else {
            // set name and image type base on input file
            $this->OUTPUT_NAME = $input_name;
            $this->OUTPUT_TYPE = $input_type;
        }

        // image type fallback
        if ($this->OUTPUT_TYPE == self::TYPE_IMAGE) {
            $this->OUTPUT_TYPE = self::TYPE_JPG;
        }

// --- IMAGE --------------------------------------------------------------------------------------

        try {
            // image tunneling for images with maximum quality and no modifications
            if ($this->OUTPUT_TYPE == $input_type && $this->OUTPUT_QUALITY == 100 && count($this->MODIFICATIONS) == 0) {
                if ($use_output_file) {
                    // write output file with data from file or string
                    //file_put_contents($this->OUTPUT_FILE, $use_input_file ? file_get_contents($this->INPUT_FILE) : $this->INPUT_DATA);

                    if ($use_input_file) {
                        // copy input file to output file
                        copy($this->INPUT_FILE, $this->OUTPUT_FILE);
                    } else {
                        // write output file with input data
                        file_put_contents($this->OUTPUT_FILE, $this->INPUT_DATA);
                    }
                } else {
                    // get output data from file or string
                    $this->OUTPUT_DATA = $use_input_file ? file_get_contents($this->INPUT_FILE) : $this->INPUT_DATA;
                }
            } // image processing for others
            else {
                // create image resource from file or string
                //$image = $use_input_file ? imagecreatefromstring(file_get_contents($this->INPUT_FILE)) : imagecreatefromstring($this->INPUT_DATA);
                $image = $this->createImage($this->INPUT_FILE, $this->INPUT_DATA, $input_type);

                if ($image === false) {
                    // image resource could not be created, if return value is false
                    return false;
                }

                // process image resource
                $image = $this->processImage($image);

                if ($use_output_file) {
                    // write to output file
                    $this->writeImage($image, $this->OUTPUT_FILE, $this->OUTPUT_TYPE, $this->OUTPUT_QUALITY);
                } else {
                    // set output data
                    $this->OUTPUT_DATA = $this->writeImage($image, null, $this->OUTPUT_TYPE, $this->OUTPUT_QUALITY);
                }
            }
        } catch (Exception $e) {
            //echo 'exception thrown in execute()';
            return false;
        }

        return true;
    }

// === PROCESSED IMAGE ============================================================================

    /**
     * @param resource $image
     * @return resource
     */
    protected function processImage($image)
    {
        foreach ($this->MODIFICATIONS as $mod) {
            switch ($mod['name']) {
                case 'change_size':
                {
                    $image = $this->process_changeSize($image, $mod['width'], $mod['height'], $mod['crop_x'], $mod['crop_y'], $mod['scale'], $mod['type']);
                    break;
                }

                case 'insert_image':
                {
                    $this->process_insertImage($image, $mod['file'], $mod['alpha'], $mod['pos_x'], $mod['pos_y'], $mod['min_width'], $mod['min_height']);
                    break;
                }

                case 'insert_text':
                {
                    $this->process_insertText($image, $mod['text'], $mod['fontfile'], $mod['size'], $mod['x'], $mod['y'], $mod['red'], $mod['green'], $mod['blue'], $mod['angle'], $mod['align'], $mod['valign']);
                    break;
                }

                case 'extract':
                {
                    $image = $this->process_extract($image, $mod['x'], $mod['y'], $mod['width'], $mod['height']);
                    break;
                }

                case 'mirror':
                {
                    $image = $this->process_mirror($image, $mod['type']);
                    break;
                }

                case 'rotate':
                {
                    $image = $this->process_rotate($image, $mod['type']);
                    break;
                }

                case 'greyscale':
                {
                    $this->process_greyscale($image);
                    break;
                }

                case 'colorize':
                {
                    $this->process_colorize($image, $mod['red'], $mod['green'], $mod['blue'], $mod['percent']);
                    break;
                }

                case 'sharpen':
                {
                    $this->process_sharpen($image);
                    break;
                }

                case 'blur':
                {
                    $this->process_blur($image);
                    break;
                }

                default:
                {
                    // nothing to do
                }
            } // switch
        } // foreach

        return $image;
    }

    /**
     * @param resource $image_src
     * @param int $width
     * @param int $height
     * @param string $crop_x (ImageProcessor::POS_X_*)
     * @param string $crop_y (ImageProcessor::POS_Y_*)
     * @param string $scale (ImageProcessor::SCALE_*)
     * @param string $type (ImageProcessor::SIZE_*)
     * @return resource
     */
    protected function process_changeSize($image_src, $width, $height, $crop_x, $crop_y, $scale, $type)
    {
        // calculate image coordinates
        $coord = self::calculateCoordinates(imagesx($image_src), imagesy($image_src), $width, $height, $crop_x, $crop_y, $scale, $type);

        // resize image
        $image_dest = imagecreatetruecolor($coord[self::COORD_DEST_WIDTH], $coord[self::COORD_DEST_HEIGHT]);
        imagecopyresampled(
            $image_dest, $image_src,
            $coord[self::COORD_DEST_X], $coord[self::COORD_DEST_Y], $coord[self::COORD_SRC_X], $coord[self::COORD_SRC_Y],
            $coord[self::COORD_DEST_WIDTH], $coord[self::COORD_DEST_HEIGHT], $coord[self::COORD_SRC_WIDTH], $coord[self::COORD_SRC_HEIGHT]
        );

        // php >= 5.5.0
        /*
        $image_crop  = imagecrop($image_src, array(
            'x'      => $coord[self::COORD_SRC_X],
            'y'      => $coord[self::COORD_SRC_Y],
            'width'  => $coord[self::COORD_SRC_WIDTH],
            'height' => $coord[self::COORD_SRC_HEIGHT]
        ));
        $image_scale = imagescale($image_crop, $coord[self::COORD_DEST_WIDTH], $coord[self::COORD_DEST_HEIGHT]);
        */

        // free memory used by source image
        imagedestroy($image_src);

        return $image_dest;
    }

    /**
     * Usage with imagemagick
     * identify -format '%w %h' src_file
     * convert src_file -crop '{$src_width}x{$src_height}+{$src_x}+{$src_y}!' -resize '{$dest_width}x{$dest_height}!' dest_file
     *
     * @param int $width_in
     * @param int $height_in
     * @param int $width_out
     * @param int $height_out
     * @param string $crop_x (ImageProcessor::POS_X_*)
     * @param string $crop_y (ImageProcessor::POS_Y_*)
     * @param string $scale (ImageProcessor::SCALE_*)
     * @param string $type (ImageProcessor::SIZE_*)
     * @return array
     */
    public static function calculateCoordinates($width_in, $height_in, $width_out = 0, $height_out = 0, $crop_x = '', $crop_y = '', $scale = '', $type = '')
    {
        $pattern =
            '/^(' . self::SCALE_CROP . '|' . self::SCALE_PROP . ')_' .
            '(' . self::POS_X_LEFT . '|' . self::POS_X_CENTER . '|' . self::POS_X_RIGHT . ')_' .
            '(' . self::POS_Y_TOP . '|' . self::POS_Y_MIDDLE . '|' . self::POS_Y_BOTTOM . ')$/';

        if (preg_match($pattern, $scale, $matches)) {
            $scale = $matches[1];
            $crop_x = $matches[2];
            $crop_y = $matches[3];
        }

// --- SIZE ---------------------------------------------------------------------------------------

        switch ($type) {
            case self::SIZE_BIG:
            {
                // if out image shall be smaller, use original sizes
                if ($width_out < $width_in) {
                    $width_out = $width_in;
                }

                if ($height_out < $height_in) {
                    $height_out = $height_in;
                }

                break;
            }

            case self::SIZE_SMALL_BIG:
            {
                // nothing to do
                break;
            }

            case self::SIZE_SMALL:
            default:
            {
                // if out image shall be bigger, use original sizes
                if ($width_out > $width_in) {
                    $width_out = $width_in;
                }

                if ($height_out > $height_in) {
                    $height_out = $height_in;
                }
            }
        }

// --- ASPECT RATIO -------------------------------------------------------------------------------

        // calculate aspect ratio of in image
        $aspect_ratio_in = $width_in / $height_in;

        // special treatment for apect_ratio of out image
        if (empty($width_out) || empty($height_out)) {
            // if just one size of out image is given, scale image keeping aspect ratio ...
            if (!empty($width_out)) {
                $height_out = floor($width_out * $height_in / $width_in);
            } elseif (!empty($height_out)) {
                $width_out = floor($height_out * $width_in / $height_in);
            } else {
                $width_out = $width_in;
                $height_out = $height_in;
            }

            $aspect_ratio_out = $aspect_ratio_in;

            $scale = self::SCALE_RATIO;
        } else {
            // ... otherwise proceed with given parameters
            $aspect_ratio_out = $width_out / $height_out;
        }

// --- CROP ---------------------------------------------------------------------------------------

        if (!empty($crop_x) && $crop_x != self::SCALE_POS_NULL || !empty($crop_y) && $crop_y != self::SCALE_POS_NULL) {
            if (empty($scale) || $scale == self::SCALE_POS_NULL) {
                $scale = self::SCALE_CROP;
            }
        }

// --- SCALE --------------------------------------------------------------------------------------

        // scale image
        switch ($scale) {
            case self::SCALE_RATIO:
            {
                // scale image with same aspect ratio
                if ($aspect_ratio_in < $aspect_ratio_out) {
                    $width_dest = $height_out * $aspect_ratio_in;
                    $height_dest = $height_out;
                } elseif ($aspect_ratio_in > $aspect_ratio_out) {
                    $width_dest = $width_out;
                    $height_dest = floor($width_out / $aspect_ratio_in);
                } else {
                    $width_dest = $width_out;
                    $height_dest = $height_out;
                }

                // source coordinates stay the same
                $x_src = 0;
                $y_src = 0;

                $width_src = $width_in;
                $height_src = $height_in;

                break;
            }

            case self::SCALE_CROP:
            case self::SCALE_PROP:
            {
                // scale image with same aspect ratio and crop overlapping parts
                if ($aspect_ratio_in < $aspect_ratio_out) {
                    $width_src = $width_in;
                    $height_src = floor($width_in / $aspect_ratio_out);
                } elseif ($aspect_ratio_in > $aspect_ratio_out) {
                    $width_src = $height_in * $aspect_ratio_out;
                    $height_src = $height_in;
                } else {
                    $width_src = $width_in;
                    $height_src = $height_in;
                }

                // crop horizontaly
                switch ($crop_x) {
                    case self::POS_X_RIGHT:
                    {
                        // crop overlapping left parts
                        $x_src = $width_in - $width_src;
                        break;
                    }

                    case self::POS_X_LEFT:
                    {
                        // crop overlapping right parts
                        $x_src = 0;
                        break;
                    }

                    case self::POS_X_CENTER:
                    default:
                    {
                        // crop overlapping left and right parts
                        $x_src = floor(($width_in - $width_src) / 2);
                        break;
                    }
                }

                // crop verticaly
                switch ($crop_y) {
                    case self::POS_Y_BOTTOM:
                    {
                        // crop overlapping top parts
                        $y_src = $height_in - $height_src;
                        break;
                    }

                    case self::POS_Y_TOP:
                    {
                        // crop overlapping bottom parts
                        $y_src = 0;
                        break;
                    }

                    case self::POS_Y_MIDDLE:
                    default:
                    {
                        // crop overlapping top and bottom parts
                        $y_src = floor(($height_in - $height_src) / 2);
                        break;
                    }
                }

                if ($scale == self::SCALE_PROP) {
                    // image is cropped to fit maximal size
                    $width_dest = $width_src;
                    $height_dest = $height_src;
                } else {
                    // image is cropped to fit given size
                    $width_dest = $width_out;
                    $height_dest = $height_out;
                }

                break;
            }

            case self::SCALE_RESIZE:
            default:
            {
                // scale image with given size
                $width_dest = $width_out;
                $height_dest = $height_out;

                // source coordinates stay the same
                $x_src = 0;
                $y_src = 0;

                $width_src = $width_in;
                $height_src = $height_in;
            }
        }

        return [
            self::COORD_SRC_X => $x_src,
            self::COORD_SRC_Y => $y_src,
            self::COORD_SRC_WIDTH => $width_src,
            self::COORD_SRC_HEIGHT => $height_src,
            self::COORD_DEST_X => 0,
            self::COORD_DEST_Y => 0,
            self::COORD_DEST_WIDTH => $width_dest,
            self::COORD_DEST_HEIGHT => $height_dest
        ];
    }

    /**
     * @param resource $image
     * @param string $file
     * @param bool $alpha
     * @param int $pos_x (ImageProcessor::POS_X_*)
     * @param int $pos_y (ImageProcessor::POS_Y_*)
     * @param int $min_width (0 for width of insert image)
     * @param int $min_height (0 for height of insert image)
     * @return bool
     */
    protected function process_insertImage($image, $file, $alpha, $pos_x, $pos_y, $min_width, $min_height)
    {
// --- DATA ---------------------------------------------------------------------------------------

        // file to insert does not exist
        if (!is_file($file)) {
            return false;
        }

        // get insert file extension and corresponding type
        [, $extension] = self::getNameExtension($file);

        if (empty(self::$EXT_TYPE_MAP[$extension])) {
            return false;
        }

        $type = self::$EXT_TYPE_MAP[$extension];

        // load insert file
        switch ($type) {
            case self::TYPE_JPG:
            {
                $image_ins = imagecreatefromjpeg($file);
                break;
            }

            case self::TYPE_PNG:
            {
                $image_ins = imagecreatefrompng($file);
                break;
            }

            case self::TYPE_GIF:
            {
                $image_ins = imagecreatefromgif($file);
                break;
            }

            default:
            {
                return false;
            }
        }

        // get sizes of insert file
        $width_ins = imagesx($image_ins);
        $height_ins = imagesy($image_ins);

        // get minimal width and height of destination image for inserting file
        $ins_min_width = empty($min_width) ? $width_ins : $min_width;
        $ins_min_height = empty($min_height) ? $height_ins : $min_height;

        // get height and width of image
        $width = imagesx($image);
        $height = imagesy($image);

        // return if destination image is to small to insert file
        if ($width < $ins_min_width || $height < $ins_min_height) {
            return false;
        }

// --- INSERT -------------------------------------------------------------------------------------

        // set horizontal insert position
        switch ($pos_x) {
            case self::POS_X_LEFT:
            {
                $x_dest = 0;
                break;
            }

            case self::POS_X_CENTER:
            {
                $x_dest = floor(($width - $width_ins) / 2);
                break;
            }

            case self::POS_X_RIGHT:
            default:
            {
                $x_dest = $width - $width_ins;
            }
        }

        // set vertical insert position
        switch ($pos_y) {
            case self::POS_Y_TOP:
            {
                $y_dest = 0;
                break;
            }

            case self::POS_Y_MIDDLE:
            {
                $y_dest = floor(($height - $height_ins) / 2);
                break;
            }

            case self::POS_Y_BOTTOM:
            default:
            {
                $y_dest = $height - $height_ins;
            }
        }

        // set alpha blending in destination image
        imagealphablending($image, $alpha);

        // insert file into destination image
        imagecopy($image, $image_ins, $x_dest, $y_dest, 0, 0, $width_ins, $height_ins);

        // destroy insert
        imagedestroy($image_ins);

        return true;
    }

    /**
     * @param resource $image
     * @param string $text
     * @param string $fontfile
     * @param int $size
     * @param int $x
     * @param int $y
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $angle
     * @param string $align (ImageProcessor::POS_X_*)
     * @param string $valign (ImageProcessor::POS_Y_*)
     * @return void
     */
    protected function process_insertText($image, $text, $fontfile, $size, $x, $y, $red, $green, $blue, $angle, $align, $valign)
    {
        // consideration of angle is not implemented yet
        if ($angle != 0) {
            $angle = 0;
        }

        // get coordinates of text bounding box
        $bbox = imagettfbbox($size, $angle, $fontfile, $text);

        // set insert position according to horizontal alignment
        switch ($align) {
            case self::POS_X_RIGHT:
            {
                $x -= $bbox[0] + $bbox[4];
                break;
            }

            case self::POS_X_CENTER:
            {
                $x -= round(($bbox[0] + $bbox[4]) / 2);
                break;
            }

            case self::POS_X_LEFT:
            default:
            {
                // nothing to do
            }
        }

        // set insert position according to vertical alignment
        switch ($valign) {
            case self::POS_Y_TOP:
            {
                $y += $bbox[1] - $bbox[5];
                break;
            }

            case self::POS_Y_MIDDLE:
            {
                $y += floor(($bbox[1] - $bbox[5]) / 2);
                break;
            }

            case self::POS_Y_BOTTOM:
            default:
            {
                // nothing to do
            }
        }

        $color = imagecolorallocate($image, $red, $green, $blue);
        imagettftext($image, $size, $angle, $x, $y, $color, $fontfile, $text);
    }

    /**
     * @param resource $image_src
     * @param int $x
     * @param int $y
     * @param int $width (0 for remaining width of image)
     * @param int $height (0 for remaining height of image)
     * @return resource
     */
    protected function process_extract($image_src, $x, $y, $width, $height)
    {
        // get height and width of source image
        $width_src = imagesx($image_src);
        $height_src = imagesy($image_src);

        // starting coordinates have to be inside source image
        if ($x < 0 || $x >= $width_src) {
            $x = 0;
        }

        if ($y < 0 || $y >= $height_src) {
            $y = 0;
        }

        // width and height have to be inside source image
        if ($width <= 0 || $width > $width_src - $x) {
            $width = $width_src - $x;
        }

        if ($height <= 0 || $height > $height_src - $y) {
            $height = $height_src - $y;
        }

        // nothing to do
        if ($x == 0 && $y == 0 && $width == $width_src && $height == $height_src) {
            return $image_src;
        }

        // extract image
        $image_dest = imagecreatetruecolor($width, $height);
        imagecopy($image_dest, $image_src, 0, 0, $x, $y, $width, $height);

        // free memory used by source image
        imagedestroy($image_src);

        return $image_dest;
    }

    /**
     * @param resource $image_src
     * @param string $type (ImageProcessor::MIRROR_*)
     * @return resource
     */
    protected function process_mirror($image_src, $type)
    {
        // if no mirroring is required, just return source image
        if ($type != self::MIRROR_HORIZONTAL && $type != self::MIRROR_VERTICAL) {
            return $image_src;
        }

        // get height and width of source image
        $width = imagesx($image_src);
        $height = imagesy($image_src);

        // destination image has same size as source image
        $image_dest = imagecreatetruecolor($width, $height);

        switch ($type) {
            case self::MIRROR_HORIZONTAL:
            {
                // go through each pixel
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        // set pixel in destination image
                        imagesetpixel(
                            $image_dest, $x, $y,
                            imagecolorat($image_src, $width - $x, $y)
                        );
                    }
                }

                break;
            }

            case self::MIRROR_VERTICAL:
            {
                // go through each pixel
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        // set pixel in destination image
                        imagesetpixel(
                            $image_dest, $x, $y,
                            imagecolorat($image_src, $x, $height - $y)
                        );
                    }
                }

                break;
            }

            default:
            {
                // we should never reach this point...
                imagedestroy($image_dest);

                return $image_src;
            }
        }

        // free memory used by source image
        imagedestroy($image_src);

        return $image_dest;
    }

    /**
     * @param resource $image_src
     * @param string $type (ImageProcessor::ROTATE_*)
     * @return resource
     */
    protected function process_rotate($image_src, $type)
    {
        // get height and width of source image
        // $width  = imagesx($image_src);
        // $height = imagesy($image_src);

        switch ($type) {
            case self::ROTATE_90:
            {
                $image_dest = imagerotate($image_src, 90, 0);
                break;
            }

            case self::ROTATE_180:
            {
                $image_dest = imagerotate($image_src, 180, 0);
                break;
            }

            case self::ROTATE_270:
            {
                $image_dest = imagerotate($image_src, 270, 0);
                break;
            }

            default:
            {
                return $image_src;
            }
        }

        // free memory used by source image
        imagedestroy($image_src);

        return $image_dest;
    }

    /**
     * @param resource $image
     * @return void
     */
    protected function process_greyscale($image)
    {
        imagefilter($image, IMG_FILTER_GRAYSCALE);

// --- FAST ---------------------------------------------------------------------------------------
// but with lesser quality
        /*
        // there are only 256 greys
        imagetruecolortopalette($image, true, 256);

        // get total number of colors (should be 255 now)
        $total = imagecolorstotal($image);

        // convert each color to its grey equivalent
        for ($i = 0; $i < $total; $i++)
        {
            // get old color of palette
            $color = imagecolorsforindex($image, $i);

            // calculate grey equivalent for this color
            $grey = (int)(($color['red'] + $color['green'] + $color['blue']) / 3);

            // set old color to grey equivalent
            imagecolorset($image, $i, $grey, $grey, $grey);
        }
        */
// --- SLOW ---------------------------------------------------------------------------------------
// but with better quality
        /*
        // get height and width of image
        $width  = imagesx($image);
        $height = imagesy($image);

        // go through each pixel
        for ($y = 0; $y < $height; $y++)
        {
            for ($x = 0; $x < $width; $x++)
            {
                // get color at pixel position
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));

                // calculate grey for this color
                $grey = (int)(($color['red'] + $color['green'] + $color['blue']) / 3);

                // set grey at this pixel position
                imagesetpixel($image, $x, $y, imagecolorresolve($image, $grey, $grey, $grey));
            }
        }
        */
    }

    /**
     * @param resource $image
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $percent
     * @return void
     */
    protected function process_colorize($image, $red, $green, $blue, $percent)
    {
        //imagefilter($image, IMG_FILTER_GRAYSCALE);
        //imagefilter($image, IMG_FILTER_COLORIZE, $red*$percent/100, $green*$percent/100, $blue*$percent/100);

        // first we have to grey the image
        $this->process_greyscale($image);

        // get height and width of image
        $width = imagesx($image);
        $height = imagesy($image);

        // create layover image with desired color
        $image_over = imagecreate($width, $height);
        imagecolorallocate($image_over, $red, $green, $blue);

        // merge layover image with image
        imagecopymerge($image, $image_over, 0, 0, 0, 0, $width, $height, $percent);

        // destroy layover image
        imagedestroy($image_over);
    }

    /**
     * @param resource $image
     * @return void
     */
    protected function process_sharpen($image)
    {
        $matrix = [
            [-1.0, -1.0, -1.0],
            [-1.0, 16.0, -1.0],
            [-1.0, -1.0, -1.0]
        ];

        $divisor = array_sum(array_map('array_sum', $matrix));

        imageconvolution($image, $matrix, $divisor, 0);
    }

    /**
     * @param resource $image
     * @return void
     */
    protected function process_blur($image)
    {
        //imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);

        $matrix = [
            [1.0, 2.0, 1.0],
            [2.0, 4.0, 2.0],
            [1.0, 2.0, 1.0]
        ];

        $divisor = array_sum(array_map('array_sum', $matrix));

        imageconvolution($image, $matrix, $divisor, 0);
    }

// === NON-PROCESSED IMAGE ========================================================================

    /**
     * @param void
     * @return bool
     */
    public function createDefault()
    {
        // create image and allocate transparent color
        $image = imagecreate(self::$DEFAULT_CONF['width'], self::$DEFAULT_CONF['height']);
        $color = imagecolorallocate($image, self::$DEFAULT_CONF['color'][0], self::$DEFAULT_CONF['color'][1], self::$DEFAULT_CONF['color'][2]);

        imagefilledrectangle($image, 0, 0, self::$DEFAULT_CONF['width'], self::$DEFAULT_CONF['height'], $color);
        imagesetpixel($image, 0, 0, $color); // ??? //
        imagecolortransparent($image, $color);

        // set output data, type and name
        $this->OUTPUT_DATA = $this->writeImage($image, null, self::$DEFAULT_CONF['type']);
        $this->OUTPUT_TYPE = self::$DEFAULT_CONF['type'];
        $this->OUTPUT_NAME = self::$DEFAULT_CONF['name'];

        return true;
    }

// === IMAGE I/O ==================================================================================

    /**
     * @param string $file
     * @param string $data
     * @param string $type
     * @return mixed
     */
    protected function createImage($file = null, $data = null, $type = self::TYPE_IMAGE)
    {
        if (!empty($file)) {
            switch ($type) {
                case self::TYPE_GIF:
                {
                    return imagecreatefromgif($file);
                }

                case self::TYPE_PNG:
                {
                    return imagecreatefrompng($file);
                }

                case self::TYPE_JPG:
                {
                    return imagecreatefromjpeg($file);
                }

                default:
                {
                    return imagecreatefromstring(file_get_contents($file));
                }
            }
        } elseif (!empty($data)) {
            return imagecreatefromstring($data);
        } else {
            return false;
        }
    }

    /**
     * @param resource $image
     * @param string $file
     * @param string $type
     * @param int $quality
     * @return mixed
     */
    protected function writeImage($image, $file = null, $type = self::TYPE_IMAGE, $quality = 100)
    {
        $use_buffer = empty($file);

        // store image into output buffer
        if ($use_buffer) {
            ob_start();
        }

        // create output depending on type
        switch ($type) {
            case self::TYPE_GIF:
            {
                $result = imagegif($image, $file);
                break;
            }

            case self::TYPE_PNG:
            {
                $result = imagepng($image, $file);
                break;
            }

            case self::TYPE_JPG:
            default:
            {
                $result = imagejpeg($image, $file, $quality);
                break;
            }
        }

        if ($use_buffer) {
            $output = ob_get_contents();

            // cleanup image resource and output buffer
            imagedestroy($image);
            ob_end_clean();

            return $output;
        } else {
            // cleanup image resource
            imagedestroy($image);

            return $result;
        }
    }

// === HELPER =====================================================================================

    /**
     * @param string $file
     * @return array
     */
    public static function getNameExtension($file)
    {
        $filelist = explode(self::$NAME_EXT_SEP, basename($file));

        $extension = strtolower(array_pop($filelist));
        $name = implode(self::$NAME_EXT_SEP, $filelist);

        return [$name, $extension];
    }

    /**
     * @param   $file
     * @return bool
     */
    public static function isProcessableImageFile($file)
    {
        [, $extension] = self::getNameExtension($file);

        return isset(self::$EXT_TYPE_MAP[$extension]);
    }
}
