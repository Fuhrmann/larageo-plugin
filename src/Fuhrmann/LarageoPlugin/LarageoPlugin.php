<?php namespace Fuhrmann\LarageoPlugin;

use Illuminate\Support\Facades\Cache;

class LarageoPlugin {

    /**
     * The URL of the geoPlugin API (json).
     *
     * @var string
     */
    protected $api_adress = 'http://www.geoplugin.net/json.gp?ip={IP}';

    /**
     * Return all the information in an array.
     *
     * @param $ip IP to search for
     * @return array Info from the IP parameter
     */
    public function getInfo($ip = null) {
        if ($ip == NULL) $ip = $this->getIpAdress();

        $url = str_replace('{IP}', $ip, $this->api_adress);
        $subnet = substr($ip, 0, strrpos ($ip, '.'));

        $me = $this;

        // Use the IP info stored in cache or store it
        $ipInfo = Cache::remember($subnet, 10080, function() use ($me, $url)
        {
            return $me->fetchInfo($url);
        });

        return $ipInfo;
    }

    /**
     * Get the IP adress.
     *
     * @link https://gist.github.com/cballou/2201933
     * @return bool|string
     */
    public function getIpAdress() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if ($this->validateIp($ip)) {
                        return $ip;
                    }
                }
            }
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     */
    protected function validateIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        return true;
    }

    /**
     * Fetch the info from IP using CURL or file_get_contents.
     *
     * @param $url
     * @throws \Exception
     * @return mixed
     */
    public function fetchInfo($url) {
        $response = null;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'LarageoPlugin Package v1.0');
            $response = curl_exec($ch);
            curl_close ($ch);
        } elseif ( ini_get('allow_url_fopen') ) {
            $response = file_get_contents($url, 'r');
        } else {
            throw new \Exception('LarageoPlugin requires the CURL PHP extension or allow_url_fopen set to 1!');
        }

        $response = json_decode($response);

        if(empty($response))
        {
            throw new \Exception("Ops! The data is empty! Is " . $url . ' acessible?');
        }

        if($response->geoplugin_status != 200)
        {
            throw new \Exception("Ops! Your request returned a " . $response->geoplugin_status . " error! Is " . $url . ' acessible?');
        }

        return $response;
    }
}
