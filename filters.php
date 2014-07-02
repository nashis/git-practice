<?php

/**
 * This interface defines all methods that should be implemented by the plugins
 * @param - defines the effect related params, like for blur specify the pixels
 */
interface ImgFilter {

    public function execute($param);
}

/*
 * Implements the execute method from ImgFilter interface
 * @param - defines image blur effect related params 
 */

class Blur implements ImgFilter {

    public function execute($param) {
        // Install ImageMagick library and comment out following code
        /*
        $img = new Imagick();
        $img->readImage($param['image_path']);
        $img->resizeimage($param['cols'], $param['rows'], $param['filter'],
                $param['blur']);
        $img->writeimage();
        $img->clear();
        $img->destroy();
         *
         */
    }

}

/*
 * Implements the execute method from ImgFilter interface
 * @param - defines image convert effect related params 
 */

class Convert implements ImgFilter {

    public function execute($param) {
        $img = imagecreatefromgd2($param['image_path']);
        $retVal = false;
        if ($img) {
            if (imagefilter($img, $param['filter_type'])) {
                $retVal = true;
            }
        }

        imagedestroy($img);
        return $retVal;
    }

}

/*
 * Implements the execute method from ImgFilter interface
 * @param - defines image resize effect related params 
 */

class Resize implements ImgFilter {

    public function execute($param) {
        // Install ImageMagick library and comment out following code
        /*
        $img = new Imagick();
        $img->readImage($param['image_path']);
        $img->resizeimage($param['cols'], $param['rows'], $param['filter'],
                $param['blur']);
        $img->writeimage();
        $img->clear();
        $img->destroy();
         * 
         */
    }

}

/*
 * Responsible for getting the image effect object
 */

class ImgFactory {
    /*
     * @param - image effects, defined in Effects class
     * @Return - Associated effect class, else null
     */

    public function getImgEffect($effect = '') {

        switch ($effect) {
            case Effects::$effects['effect1']:
                return new Blur();
                break;
            case Effects::$effects['effect2']:
                return new Convert();
                break;
            case Effects::$effects['effect3']:
                return new Resize();
                break;
            default :
                return null;
                break;
        }
    }

}

/*
 * Image class that deifnes our image
 */

class Image {

    protected $id;

    /*
     * construct the image object
     */

    public function __construct($id) {
        $this->id = $id;
    }

    /*
     * @return - image ID which is unique identifier
     */

    public function getId() {
        return $this->id;
    }

    /*
     * @param - effect to be added and its value, where effects defined in Effects class
     * @return - success value
     */

    public function addEffect($effectId, $value) {
        if (!Effects::isValidEffect($effectId)) {
            error_log('Invalid effect specified: ' . $effectId);
            return false;
        }

        $propName = Effects::$effects[$effectId];
        $this->$propName = $value;
        return true;
    }

    /*
     * @param - effect to be removed, where effects defined in Effects class
     * @return - success value
     */

    public function removeEffect($effectId) {
        if (!Effects::isValidEffect($effectId)) {
            error_log('Invalid effect specified: ' . $effectId);
            return false;
        }

        $propName = Effects::$effects[$effectId];
        unset($this->$propName);
        return true;
    }

    /*
     * @param - effect to be applied, where effects defined in Effects class
     * @return - success value
     */

    public function applyEffect() {
        $objEffects = array_keys($this->toArray());
        $totEffects = Effects::getAllEffects();
        $validEffects = array_intersect($totEffects, $objEffects);
        $imgFact = new ImgFactory();
        if (!empty($validEffects) && $imgFact) {
            foreach ($validEffects as $validEffect) {
                $effObj = $imgFact->getImgEffect($validEffect);
                if ($effObj) {
                    $effObj->execute($this);
                }
            }
        }
    }

    /*
     * @param - none
     * @return - array notation of the Image object
     */

    public function toArray() {
        /*
         * toArray may seem redundant as of now, will implement nested toArray when properties become multi-dimensional
         */
        return get_object_vars($this);
    }

}

/*
 * All image effects are handled here
 */

class Effects {

    const ADD_EFFECT = 1;
    const REMOVE_EFFECT = 0;

    public static $effects = array('effect1' => 'blur', 'effect2' => 'convert', 'effect3' => 'resize');

    /*
     * @param - effect ID to be validate
     * @return - true if effect is valid else false
     */

    public static function isValidEffect($effectId) {
        return isset(self::$effects[$effectId]);
    }

    /*
     * @param - none
     * @return - return all the effects that could be used
     */

    public static function getAllEffects() {
        return array_values(self::$effects);
    }

}

/*
 * Class that works on the image data and entry point to the API
 */

class imageHandler {
    /*
     * @param - image data, associative array of the form Array(imgId=>Array(operationId=>Array('effectId'=>effectId, 'value'=>effectValue)))
     * sample data - array(0=>array(1=>array('id'=>'effect1','val'=>5)),1=>array(1=>array('id'=>'effect3','val'=>50)));
     * @return - none
     */

    public function processImageData($data) {
        if (!isset($data) || !is_array($data) || empty($data)) {
            error_log('Invalid data passed: ' . __METHOD__);
            return false;
        }

        $processed = array();
        foreach ($data as $key => $datum) {
            if (!is_numeric($key)) {
                error_log('Invalid image id passed: ' . $key);
                continue;
            }

            if (isset($processed[$key])) {
                error_log('Already processed data for image with id: ' . $key . ' ' . __METHOD__);
                continue;
            }

            $processed[$key] = 1;
            $imgObj = new Image($key);
            foreach ($datum as $operation => $value) {
                switch ($operation) {
                    case Effects::ADD_EFFECT:
                        $imgObj->addEffect($value['id'], $value['val']);
                        break;
                    case Effects::REMOVE_EFFECT:
                        $imgObj->removeEffect($value['id']);
                        break;
                    default:
                        error_log('Invalid operation passed: ' . $operation . ' ' . __METHOD__);
                        break;
                }
            }
            //echo var_dump($imgObj);
            $imgObj->applyEffect();
        }
    }

}
