<?php
namespace ajwilco;

use Unirest\Request;

class imgur {
    const   CLIENT_ID   = '';
    const   SECRET      = '';
    const   API_PATH    = 'https://api.imgur.com/3/';
    const   IMAGE_PATH  = 'image';
    const   ALBUM_PATH  = 'album';
    const   RATES_PATH	= 'credits';

    /**
     * Upload a given image to imgur anonymously.
     * @param array|string $file File data straight from a form, or link.
     * @param array $details Parameters to accompany the image.
     * @return array
     */
    public static function upload($file, $details = []) {
        if (is_string($file))
            $details['image'] = $file;
        else {
            if (substr($file['type'], 0, 5)<>"image") {
				Print 'This file is not an image!';
				Die;
			}

            list($width, $height) = getimagesize($file['tmp_name']);
            if ($width > 960 || $height > 720) {
                $magic = new Imagick($file['tmp_name']);
                if ($width>$height)
                    $magic->scaleImage(960, 0);
                else
                    $magic->scaleImage(0, 720);
                $magic->writeImage($file['tmp_name']);
                $magic->clear();
                $magic->destroy();
            }

            $details['image'] = file_get_contents($file['tmp_name']);
        }

        $result = self::post(self::getPath('image'), $details);
	    
        Return $result->body->data;
    }

    /**
     * Delete a given photo. Use deletehash if the image is anonymous, or ID if the image is yours.
     * @param string $hash
     * @return \Unirest\Response
     */
    public static function deleteImage($hash) {
        $result = self::delete(self::getPath('image') . '/' . $hash);

        Return $result;
    }

    /**
     * Create a new, empty, anonymous Album.
     * @param array $details All parameters for the new Album
     * @return \Unirest\Response
     */
    public static function createAlbum($details) {
        $result = self::post(self::getPath('album'), $details);
        
        Return $result;
    }

    /**
     * Return your current call limits.
     * @return \Unirest\Response
     */
    public static function getRates() {
        $result = self::get(self::getPath('rates'));
        
        Return $result;
    }

    /**
     * Completes a Unirest Get.
     * @param $path
     * @param array $details All parameters to send in _GET.
     * @return \Unirest\Response
     */
    private static function get($path, $details = []) {
        $details = array_filter($details);

        $attempt = Request::get($path, self::getHeader(), $details);
        if ($attempt->code != 200) self::error($attempt);

        Return $attempt;
    }

    /**
     * Completes a Unirest Post.
     * @param string $path
     * @param array $details All parameters to send in _POST.
     * @return \Unirest\Response
     */
    private static function post($path, $details) {
        $details = array_filter($details);

        $attempt = Request::post($path, self::getHeader(), $details);
        if ($attempt->code != 200) self::error($attempt);

        Return $attempt;
    }

    /**
     * Completes a Unirest Delete.
     * @param string $path Should be an API path + object ID
     * @return \Unirest\Response
     */
    private static function delete($path) {

        $attempt = Request::delete($path, self::getHeader());
        if ($attempt->code != 200) self::error($attempt);

        Return $attempt;
    }

    /**
     * Returns the fully qualified API path.
     * @param string $path image|album|rates
     * @return string
     */
    private static function getPath($path) {
        if     ($path == 'image') $addr = self::IMAGE_PATH;
        elseif ($path == 'album') $addr = self::ALBUM_PATH;
        elseif ($path == 'rates') $addr = self::RATES_PATH;
        else {
            Print 'Path was not recognized.';
            Die;
        }

        Return self::API_PATH . $addr;
    }

    /**
     * Fetches a generic header with app credentials, to act anonymously.
     * @return array
     */
    private static function getHeader() {
        $header = [
            'Authorization' => 'Client-ID ' . self::CLIENT_ID
        ];

        Return $header;
    }

    /**
     * Handle failed Requests, display the error, and details.
     * @param object $attempt
     */
    private static function error($attempt) {
        Print '<strong>imgur Error:</strong> '. $attempt->body->data->error . '<br>' . $attempt->raw_body);
		Die;
    }
}