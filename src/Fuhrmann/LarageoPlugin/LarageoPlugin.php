<?php namespace Fuhrmann\LarageoPlugin;

use Illuminate\Support\Facades\Cache;

class LarageoPlugin {

    /**
     * Is the current IP stored in the cache?
     * @var bool
     */
    public $isCached = false;
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
        $hex = $this->ipToHex($ip);
        $me = $this;

        // Check if the IP is in the cache
        if (Cache::has($hex))
        {
            $this->isCached = true;
        }
        // Use the IP info stored in cache or store it
        $ipInfo = Cache::remember($hex, 10080, function() use ($me, $url)
        {
            return $me->fetchInfo($url);
        });

        $ipInfo->geoplugin_cached = $this->isCached;

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
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_IPV6) === false) {
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

        if (isset($response->geoplugin_status) && $response->geoplugin_status == 404)
        {
            throw new \Exception("Ops! Your request returned a 404 error! Is " . $url . ' acessible?');
        }

        return $response;
    }

    /**
     * Return a hex string of the current IP.  Used as the key for cache storage
     *
     * @param $ipAddress
     *
     * @return bool|string
     */
    public function ipToHex($ipAddress)
    {
        $hex = '';
        if (strpos($ipAddress, ',') !== false)
        {
            $splitIp = explode(',', $ipAddress);
            $ipAddress = trim($splitIp[0]);
        }
        $isIpV6 = false;
        $isIpV4 = false;
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false)
        {
            $isIpV6 = true;
        } else if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false)
        {
            $isIpV4 = true;
        }
        if (! $isIpV4 && ! $isIpV6)
        {
            return false;
        }
        // IPv4 format
        if ($isIpV4)
        {
            $parts = explode('.', $ipAddress);
            for ($i = 0; $i < 4; $i ++)
            {
                $parts[ $i ] = str_pad(dechex($parts[ $i ]), 2, '0', STR_PAD_LEFT);
            }
            $ipAddress = '::' . $parts[0] . $parts[1] . ':' . $parts[2] . $parts[3];
            $hex = join('', $parts);
        } // IPv6 format
        else
        {
            $parts = explode(':', $ipAddress);
            // If this is mixed IPv6/IPv4, convert end to IPv6 value
            if (filter_var($parts[ count($parts) - 1 ], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false)
            {
                $partsV4 = explode('.', $parts[ count($parts) - 1 ]);
                for ($i = 0; $i < 4; $i ++)
                {
                    $partsV4[ $i ] = str_pad(dechex($partsV4[ $i ]), 2, '0', STR_PAD_LEFT);
                }
                $parts[ count($parts) - 1 ] = $partsV4[0] . $partsV4[1];
                $parts[] = $partsV4[2] . $partsV4[3];
            }
            $numMissing = 8 - count($parts);
            $expandedParts = array();
            $expansionDone = false;
            foreach ($parts as $part)
            {
                if (! $expansionDone && $part == '')
                {
                    for ($i = 0; $i <= $numMissing; $i ++)
                    {
                        $expandedParts[] = '0000';
                    }
                    $expansionDone = true;
                } else
                {
                    $expandedParts[] = $part;
                }
            }
            foreach ($expandedParts as &$part)
            {
                $part = str_pad($part, 4, '0', STR_PAD_LEFT);
            }
            $ipAddress = join(':', $expandedParts);
            $hex = join('', $expandedParts);
        }
        // Validate the final IP
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP))
        {
            return false;
        }

        return strtolower(str_pad($hex, 32, '0', STR_PAD_LEFT));
    }
}
