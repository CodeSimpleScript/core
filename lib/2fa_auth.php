<?php

// Based on / inspired by: https://github.com/PHPGangsta/GoogleAuthenticator
// Algorithms, digits, period etc. explained: https://github.com/google/google-authenticator/wiki/Key-Uri-Format
class TwoFactorAuth
{
    private $algorithm;
    private $period;
    private $digits;
    private $issuer;
    private $qrcodeprovider = null;
    private $rngprovider = null;
    private $timeprovider = null;
    private static $_base32dict = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567=';
    private static $_base32;
    private static $_base32lookup = array();
    private static $_supportedalgos = array('sha1', 'sha256', 'sha512', 'md5');

    function __construct($issuer = null, $digits = 6, $period = 30, $algorithm = 'sha1', IQRCodeProvider $qrcodeprovider = null, IRNGProvider $rngprovider = null, ITimeProvider $timeprovider = null)
    {
        $this->issuer = $issuer;
        if (!is_int($digits) || $digits <= 0)
            throw new TwoFactorAuthException('Digits must be int > 0');
        $this->digits = $digits;

        if (!is_int($period) || $period <= 0)
            throw new TwoFactorAuthException('Period must be int > 0');
        $this->period = $period;

        $algorithm = strtolower(trim($algorithm));
        if (!in_array($algorithm, self::$_supportedalgos))
            throw new TwoFactorAuthException('Unsupported algorithm: ' . $algorithm);
        $this->algorithm = $algorithm;
        $this->qrcodeprovider = $qrcodeprovider;
        $this->rngprovider = $rngprovider;
        $this->timeprovider = $timeprovider;

        self::$_base32 = str_split(self::$_base32dict);
        self::$_base32lookup = array_flip(self::$_base32);
    }

    /**
     * Create a new secret
     */
    public function createSecret($bits = 80, $requirecryptosecure = true)
    {
        $secret = '';
        $bytes = ceil($bits / 5);   //We use 5 bits of each byte (since we have a 32-character 'alphabet' / BASE32)
        $rngprovider = $this->getRngprovider();
        if ($requirecryptosecure && !$rngprovider->isCryptographicallySecure())
            throw new TwoFactorAuthException('RNG provider is not cryptographically secure');
        $rnd = $rngprovider->getRandomBytes($bytes);
        for ($i = 0; $i < $bytes; $i++)
            $secret .= self::$_base32[ord($rnd[$i]) & 31];  //Mask out left 3 bits for 0-31 values
        return $secret;
    }

    /**
     * Calculate the code with given secret and point in time
     */
    public function getCode($secret, $time = null)
    {
        $secretkey = $this->base32Decode($secret);

        $timestamp = "\0\0\0\0" . pack('N*', $this->getTimeSlice($this->getTime($time)));  // Pack time into binary string
        $hashhmac = hash_hmac($this->algorithm, $timestamp, $secretkey, true);             // Hash it with users secret key
        $hashpart = substr($hashhmac, ord(substr($hashhmac, -1)) & 0x0F, 4);               // Use last nibble of result as index/offset and grab 4 bytes of the result
        $value = unpack('N', $hashpart);                                                   // Unpack binary value
        $value = $value[1] & 0x7FFFFFFF;                                                   // Drop MSB, keep only 31 bits

        return str_pad($value % pow(10, $this->digits), $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Check if the code is correct. This will accept codes starting from ($discrepancy * $period) sec ago to ($discrepancy * period) sec from now
     */
    public function verifyCode($secret, $code, $discrepancy = 1, $time = null)
    {
        $result = false;
        $timetamp = $this->getTime($time);

        // To keep safe from timing-attachs we iterate *all* possible codes even though we already may have verified a code is correct
        for ($i = -$discrepancy; $i <= $discrepancy; $i++)
            $result |= $this->codeEquals($this->getCode($secret, $timetamp + ($i * $this->period)), $code);

        return (bool)$result;
    }

    /**
     * Timing-attack safe comparison of 2 codes (see http://blog.ircmaxell.com/2014/11/its-all-about-time.html)
     */
    private function codeEquals($safe, $user) {
        if (function_exists('hash_equals')) {
            return hash_equals($safe, $user);
        }
        // In general, it's not possible to prevent length leaks. So it's OK to leak the length. The important part is that
        // we don't leak information about the difference of the two strings.
        if (strlen($safe)===strlen($user)) {
            $result = 0;
            for ($i = 0; $i < strlen($safe); $i++)
                $result |= (ord($safe[$i]) ^ ord($user[$i]));
            return $result === 0;
        }
        return false;
    }

    /**
     * Get data-uri of QRCode
     */
    public function getQRCodeImageAsDataUri($label, $secret, $size = 200)
    {
        if (!is_int($size) || $size <= 0)
            throw new TwoFactorAuthException('Size must be int > 0');

        $qrcodeprovider = $this->getQrCodeProvider();
        return 'data:'
            . $qrcodeprovider->getMimeType()
            . ';base64,'
            . base64_encode($qrcodeprovider->getQRCodeImage($this->getQRText($label, $secret), $size));
    }

    /**
     * Compare default timeprovider with specified timeproviders and ensure the time is within the specified number of seconds (leniency)
     */
    public function ensureCorrectTime(array $timeproviders = null, $leniency = 5)
    {
        if ($timeproviders != null && !is_array($timeproviders))
            throw new TwoFactorAuthException('No timeproviders specified');

        if ($timeproviders == null)
            $timeproviders = array(
                new ConvertUnixTimeDotComTimeProvider(),
                new HttpTimeProvider()
            );

        // Get default time provider
        $timeprovider = $this->getTimeProvider();

        // Iterate specified time providers
        foreach ($timeproviders as $t) {
            if (!($t instanceof ITimeProvider))
                throw new TwoFactorAuthException('Object does not implement ITimeProvider');

            // Get time from default time provider and compare to specific time provider and throw if time difference is more than specified number of seconds leniency
            if (abs($timeprovider->getTime() - $t->getTime()) > $leniency)
                throw new TwoFactorAuthException(sprintf('Time for timeprovider is off by more than %d seconds when compared to %s', $leniency, get_class($t)));
        }
    }

    private function getTime($time)
    {
        return ($time === null) ? $this->getTimeProvider()->getTime() : $time;
    }

    private function getTimeSlice($time = null, $offset = 0)
    {
        return (int)floor($time / $this->period) + ($offset * $this->period);
    }

    /**
     * Builds a string to be encoded in a QR code
     */
    public function getQRText($label, $secret)
    {
        return 'otpauth://totp/' . rawurlencode($label)
            . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($this->issuer)
            . '&period=' . intval($this->period)
            . '&algorithm=' . rawurlencode(strtoupper($this->algorithm))
            . '&digits=' . intval($this->digits);
    }

    private function base32Decode($value)
    {
        if (strlen($value)==0) return '';

        if (preg_match('/[^'.preg_quote(self::$_base32dict).']/', $value) !== 0)
            throw new TwoFactorAuthException('Invalid base32 string');

        $buffer = '';
        foreach (str_split($value) as $char)
        {
            if ($char !== '=')
                $buffer .= str_pad(decbin(self::$_base32lookup[$char]), 5, 0, STR_PAD_LEFT);
        }
        $length = strlen($buffer);
        $blocks = trim(chunk_split(substr($buffer, 0, $length - ($length % 8)), 8, ' '));

        $output = '';
        foreach (explode(' ', $blocks) as $block)
            $output .= chr(bindec(str_pad($block, 8, 0, STR_PAD_RIGHT)));
        return $output;
    }

    /**
     * @return IQRCodeProvider
     * @throws TwoFactorAuthException
     */
    public function getQrCodeProvider()
    {
        // Set default QR Code provider if none was specified
        if (null === $this->qrcodeprovider) {
            return $this->qrcodeprovider = new GoogleQRCodeProvider();
        }
        return $this->qrcodeprovider;
    }

    /**
     * @return IRNGProvider
     * @throws TwoFactorAuthException
     */
    public function getRngprovider()
    {
        if (null !== $this->rngprovider) {
            return $this->rngprovider;
        }
        if (function_exists('random_bytes')) {
            return $this->rngprovider = new CSRNGProvider();
        }
        if (function_exists('mcrypt_create_iv')) {
            return $this->rngprovider = new MCryptRNGProvider();
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return $this->rngprovider = new OpenSSLRNGProvider();
        }
        if (function_exists('hash')) {
            return $this->rngprovider = new HashRNGProvider();
        }
        throw new TwoFactorAuthException('Unable to find a suited RNGProvider');
    }

    /**
     * @return ITimeProvider
     * @throws TwoFactorAuthException
     */
    public function getTimeProvider()
    {
        // Set default time provider if none was specified
        if (null === $this->timeprovider) {
            return $this->timeprovider = new LocalMachineTimeProvider();
        }
        return $this->timeprovider;
    }
}

class TwoFactorAuthException extends \Exception {}

//BaseHTTPQRCodeProvider
abstract class BaseHTTPQRCodeProvider implements IQRCodeProvider
{
    protected $verifyssl;

    protected function getContent($url)
    {
        $curlhandle = curl_init();
        
        curl_setopt_array($curlhandle, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_DNS_CACHE_TIMEOUT => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => $this->verifyssl,
            CURLOPT_USERAGENT => 'TwoFactorAuth'
        ));
        $data = curl_exec($curlhandle);
        
        curl_close($curlhandle);
        return $data;
    }
}

//GoogleQRCodeProvider
class GoogleQRCodeProvider extends BaseHTTPQRCodeProvider 
{
    public $errorcorrectionlevel;
    public $margin;

    function __construct($verifyssl = false, $errorcorrectionlevel = 'L', $margin = 1) 
    {
        if (!is_bool($verifyssl))
            throw new \QRException('VerifySSL must be bool');

        $this->verifyssl = $verifyssl;
        
        $this->errorcorrectionlevel = $errorcorrectionlevel;
        $this->margin = $margin;
    }
    
    public function getMimeType() 
    {
        return 'image/png';
    }
    
    public function getQRCodeImage($qrtext, $size) 
    {
        return $this->getContent($this->getUrl($qrtext, $size));
    }
    
    public function getUrl($qrtext, $size) 
    {
        return 'https://chart.googleapis.com/chart?cht=qr'
            . '&chs=' . $size . 'x' . $size
            . '&chld=' . $this->errorcorrectionlevel . '|' . $this->margin
            . '&chl=' . rawurlencode($qrtext);
    }
}

//IQRCodeProvider
interface IQRCodeProvider
{
    public function getQRCodeImage($qrtext, $size);
    public function getMimeType();
}

//QRException
class QRException extends TwoFactorAuthException {}

//QRicketProvider
class QRicketProvider extends BaseHTTPQRCodeProvider 
{
    public $errorcorrectionlevel;
    public $margin;
    public $qzone;
    public $bgcolor;
    public $color;
    public $format;

    function __construct($errorcorrectionlevel = 'L', $bgcolor = 'ffffff', $color = '000000', $format = 'p') 
    {
        $this->verifyssl = false;
        
        $this->errorcorrectionlevel = $errorcorrectionlevel;
        $this->bgcolor = $bgcolor;
        $this->color = $color;
        $this->format = $format;
    }
    
    public function getMimeType() 
    {
        switch (strtolower($this->format))
        {
        	case 'p':
                return 'image/png';
        	case 'g':
                return 'image/gif';
        	case 'j':
                return 'image/jpeg';
        }
        throw new \QRException(sprintf('Unknown MIME-type: %s', $this->format));
    }
    
    public function getQRCodeImage($qrtext, $size) 
    {
        return $this->getContent($this->getUrl($qrtext, $size));
    }
    
    public function getUrl($qrtext, $size) 
    {
        return 'http://qrickit.com/api/qr'
            . '?qrsize=' . $size
            . '&e=' . strtolower($this->errorcorrectionlevel)
            . '&bgdcolor=' . $this->bgcolor
            . '&fgdcolor=' . $this->color
            . '&t=' . strtolower($this->format)
            . '&d=' . rawurlencode($qrtext);
    }
}

//QRServerProvider
class QRServerProvider extends BaseHTTPQRCodeProvider 
{
    public $errorcorrectionlevel;
    public $margin;
    public $qzone;
    public $bgcolor;
    public $color;
    public $format;

    function __construct($verifyssl = false, $errorcorrectionlevel = 'L', $margin = 4, $qzone = 1, $bgcolor = 'ffffff', $color = '000000', $format = 'png') 
    {
        if (!is_bool($verifyssl))
            throw new QRException('VerifySSL must be bool');

        $this->verifyssl = $verifyssl;
        
        $this->errorcorrectionlevel = $errorcorrectionlevel;
        $this->margin = $margin;
        $this->qzone = $qzone;
        $this->bgcolor = $bgcolor;
        $this->color = $color;
        $this->format = $format;
    }
    
    public function getMimeType() 
    {
        switch (strtolower($this->format))
        {
        	case 'png':
                return 'image/png';
        	case 'gif':
                return 'image/gif';
        	case 'jpg':
        	case 'jpeg':
                return 'image/jpeg';
        	case 'svg':
                return 'image/svg+xml';
        	case 'eps':
                return 'application/postscript';
        }
        throw new \QRException(sprintf('Unknown MIME-type: %s', $this->format));
    }
    
    public function getQRCodeImage($qrtext, $size) 
    {
        return $this->getContent($this->getUrl($qrtext, $size));
    }
    
    private function decodeColor($value) 
    {
        return vsprintf('%d-%d-%d', sscanf($value, "%02x%02x%02x"));
    }
    
    public function getUrl($qrtext, $size) 
    {
        return 'https://api.qrserver.com/v1/create-qr-code/'
            . '?size=' . $size . 'x' . $size
            . '&ecc=' . strtoupper($this->errorcorrectionlevel)
            . '&margin=' . $this->margin
            . '&qzone=' . $this->qzone
            . '&bgcolor=' . $this->decodeColor($this->bgcolor)
            . '&color=' . $this->decodeColor($this->color)
            . '&format=' . strtolower($this->format)
            . '&data=' . rawurlencode($qrtext);
    }
}

//CSRNGProvider
class CSRNGProvider implements IRNGProvider
{
    public function getRandomBytes($bytecount) {
        return random_bytes($bytecount);    // PHP7+
    }
    
    public function isCryptographicallySecure() {
        return true;
    }
}

//HashRNGProvider
class HashRNGProvider implements IRNGProvider
{
    private $algorithm;
    
    function __construct($algorithm = 'sha256' ) {
        $algos = array_values(hash_algos());
        if (!in_array($algorithm, $algos, true))
            throw new \RNGException('Unsupported algorithm specified');
        $this->algorithm = $algorithm;
    }
    
    public function getRandomBytes($bytecount) {
        $result = '';
        $hash = mt_rand();
        for ($i = 0; $i < $bytecount; $i++) {
            $hash = hash($this->algorithm, $hash.mt_rand(), true);
            $result .= $hash[mt_rand(0, sizeof($hash))];
        }
        return $result;
    }
    
    public function isCryptographicallySecure() {
        return false;
    }
}


//IRNGProvider
interface IRNGProvider
{
    public function getRandomBytes($bytecount);
    public function isCryptographicallySecure();
}

//MCryptRNGProvider
class MCryptRNGProvider implements IRNGProvider
{
    private $source;
    
    function __construct($source = MCRYPT_DEV_URANDOM) {
        $this->source = $source;
    }
    
    public function getRandomBytes($bytecount) {
        $result = mcrypt_create_iv($bytecount, $this->source);
        if ($result === false)
            throw new \RNGException('mcrypt_create_iv returned an invalid value');
        return $result;
    }
    
    public function isCryptographicallySecure() {
        return true;
    }
}

//OpenSSLRNGProvider
class OpenSSLRNGProvider implements IRNGProvider
{
    private $requirestrong;
    
    function __construct($requirestrong = true) {
        $this->requirestrong = $requirestrong;
    }
    
    public function getRandomBytes($bytecount) {
        $result = openssl_random_pseudo_bytes($bytecount, $crypto_strong);
        if ($this->requirestrong && ($crypto_strong === false))
            throw new \RNGException('openssl_random_pseudo_bytes returned non-cryptographically strong value');
        if ($result === false)
            throw new \RNGException('openssl_random_pseudo_bytes returned an invalid value');
        return $result;
    }
    
    public function isCryptographicallySecure() {
        return $this->requirestrong;
    }
}

class RNGException extends TwoFactorAuthException {}


//ConvertUnixTimeDotComTimeProvider
class ConvertUnixTimeDotComTimeProvider implements ITimeProvider
{
    public function getTime() {
        $json = @json_decode(
            @file_get_contents('http://www.convert-unix-time.com/api?timestamp=now')
        );
        if ($json === null || !is_int($json->timestamp))
            throw new \TimeException('Unable to retrieve time from convert-unix-time.com');
        return $json->timestamp;
    }
}

//HttpTimeProvider
class HttpTimeProvider implements ITimeProvider
{
    public $url;
    public $options;
    public $expectedtimeformat;

    function __construct($url = 'https://google.com', $expectedtimeformat = 'D, d M Y H:i:s O+', array $options = null)
    {
        $this->url = $url;
        $this->expectedtimeformat = $expectedtimeformat;
        $this->options = $options;
        if ($this->options === null) {
            $this->options = array(
                'http' => array(
                    'method' => 'HEAD',
                    'follow_location' => false,
                    'ignore_errors' => true,
                    'max_redirects' => 0,
                    'request_fulluri' => true,
                    'header' => array(
                        'Connection: close',
                        'User-agent: TwoFactorAuth HttpTimeProvider (https://github.com/RobThree/TwoFactorAuth)'
                    )
                )
            );
        }
    }

    public function getTime() {
        try {
            $context  = stream_context_create($this->options);
            $fd = fopen($this->url, 'rb', false, $context);
            $headers = stream_get_meta_data($fd);
            fclose($fd);

            foreach ($headers['wrapper_data'] as $h) {
                if (strcasecmp(substr($h, 0, 5), 'Date:') === 0)
                    return \DateTime::createFromFormat($this->expectedtimeformat, trim(substr($h,5)))->getTimestamp();
            }
            throw new \TimeException(sprintf('Unable to retrieve time from %s (Invalid or no "Date:" header found)', $this->url));
        }
        catch (Exception $ex) {
            throw new \TimeException(sprintf('Unable to retrieve time from %s (%s)', $this->url, $ex->getMessage()));
        }
    }
}

//ITimeProvider
interface ITimeProvider
{
    public function getTime();
}

//LocalMachineTimeProvider
class LocalMachineTimeProvider implements ITimeProvider {
    public function getTime() {
        return time();
    }
}

class TimeException extends TwoFactorAuthException {}

?>
