<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */


class pHueApi {
    /*     * *************************Attributs****************************** */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'jeedom',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => array("Accept: application/json"),
    );

    protected $_ip = null;
    protected $_client_key = null;

    /*     * ***********************Methode static*************************** */


    public static function makeRequest($_path, $_method = 'GET', $_params = array(), $_headers = array(), $_timeout = 60) {
        $ch = curl_init();
        $opts = self::$CURL_OPTS;
        if ($_params) {
            switch ($_method) {
                case 'GET':
                    $_path .= '?' . http_build_query($_params, null, '&');
                    break;
                case 'PUT':
                    $opts[CURLOPT_CUSTOMREQUEST] = "PUT";
                    $opts[CURLOPT_POSTFIELDS] = $_params;
                    break;
                default:
                    $opts[CURLOPT_POSTFIELDS] = $_params;
                    break;
            }
        }
        if (count($_headers) > 0) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $_headers);
        }
        $opts[CURLOPT_URL] = $_path;
        $opts[CURLOPT_TIMEOUT] = $_timeout;
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        if ($result === false) {
            $e = new Exception(curl_errno($ch) . ' | ' . curl_error($ch));
            curl_close($ch);
            throw $e;
        }
        curl_close($ch);
        $decode = json_decode($result, true);
        if (!$decode) {
            return $result;
        }
        return $decode;
    }

    public static function discover() {
        return self::makeRequest('https://discovery.meethue.com/');
    }

    public static function convertRGBToXY($red, $green, $blue) {
        $normalizedToOne['red'] = $red / 255;
        $normalizedToOne['green'] = $green / 255;
        $normalizedToOne['blue'] = $blue / 255;
        foreach ($normalizedToOne as $key => $normalized) {
            if ($normalized > 0.04045) {
                $color[$key] = pow(($normalized + 0.055) / (1.0 + 0.055), 2.4);
            } else {
                $color[$key] = $normalized / 12.92;
            }
        }
        $xyz['x'] = $color['red'] * 0.664511 + $color['green'] * 0.154324 + $color['blue'] * 0.162028;
        $xyz['y'] = $color['red'] * 0.283881 + $color['green'] * 0.668433 + $color['blue'] * 0.047685;
        $xyz['z'] = $color['red'] * 0.000000 + $color['green'] * 0.072310 + $color['blue'] * 0.986039;
        if (array_sum($xyz) == 0) {
            $x = 0;
            $y = 0;
        } else {
            $x = $xyz['x'] / array_sum($xyz);
            $y = $xyz['y'] / array_sum($xyz);
        }
        return array('x'   => $x, 'y'   => $y, 'bri' => round($xyz['y'] * 100));
    }

    public static function convertXYToRGB($x, $y, $bri = 255) {
        if ($bri > 195) {
            $bri = 195;
        }
        $z = 1.0 - $x - $y;
        $xyz['y'] = $bri / 255;
        $xyz['x'] = ($xyz['y'] / $y) * $x;
        $xyz['z'] = ($xyz['y'] / $y) * $z;
        $color['red'] = $xyz['x'] * 1.656492 - $xyz['y'] * 0.354851 - $xyz['z'] * 0.255038;
        $color['green'] = -$xyz['x'] * 0.707196 + $xyz['y'] * 1.655397 + $xyz['z'] * 0.036152;
        $color['blue'] = $xyz['x'] * 0.051713 - $xyz['y'] * 0.121364 + $xyz['z'] * 1.011530;
        foreach ($color as $key => $normalized) {
            if ($normalized <= 0.0031308) {
                $color[$key] = 12.92 * $normalized;
            } else {
                $color[$key] = (1.0 + 0.055) * pow($normalized, 1.0 / 2.4) - 0.055;
            }
            $color[$key] = round($color[$key] * 255);
            if ($color[$key] < 0) {
                $color[$key] = 0;
            }
            if ($color[$key] > 255) {
                $color[$key] = 255;
            }
        }
        return $color;
    }



    /*     * *********************Methode d'instance************************* */

    public function __construct($_ip, $_client_key = null) {
        $this->_ip = $_ip;
        $this->_client_key = $_client_key;

        if ($_ip == null) {
            throw new Exception("IP can not be null");
        }
    }

    public function request($_path, $_method = 'GET', $_params = array(), $_timeout = 60) {
        $headers = array();
        if ($this->_client_key != null) {
            $headers = array('hue-application-key: ' . $this->_client_key);
        }
        $result = self::makeRequest('https://' . $this->_ip . $_path, $_method, $_params, $headers, $_timeout);
        if (isset($result['errors']) && count($result['errors']) > 0) {
            throw new Exception(json_encode($result['errors']));
        }

        return $result;
    }

    public function generateClientKey($_duration = 60) {
        $starttime = strtotime('now');
        $result = null;
        while ($result == null) {
            $result = $this->request('/api', 'POST', '{"devicetype":"jeedom", "generateclientkey":true}');
            if (strtotime('now') - $starttime > $_duration) {
                break;
            }
            if (isset($result[0]['error'])) {
                $result = null;
                sleep(1);
            }
        }
        return $result;
    }

    public function device() {
        return $this->request('/clip/v2/resource/device');
    }

    public function light($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/light');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/light/' . $_id);
        }
        if (isset($_data['dynamics']['duration']) && $_data['dynamics']['duration'] > 6000000) {
            $_data['dynamics']['duration'] = 6000000;
        }
        return $this->request('/clip/v2/resource/light/' . $_id, 'PUT', json_encode($_data));
    }

    public function grouped_light($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/grouped_light');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/grouped_light/' . $_id);
        }
        if (isset($_data['dynamics']['duration']) && $_data['dynamics']['duration'] > 6000000) {
            $_data['dynamics']['duration'] = 6000000;
        }
        return $this->request('/clip/v2/resource/grouped_light/' . $_id, 'PUT', json_encode($_data));
    }

    public function room($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/room');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/room/' . $_id);
        }
        return $this->request('/clip/v2/resource/room/' . $_id, 'PUT', json_encode($_data));
    }

    public function zone($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/zone');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/zone/' . $_id);
        }
        return $this->request('/clip/v2/resource/zone/' . $_id, 'PUT', json_encode($_data));
    }

    public function scene($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/scene');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/scene/' . $_id);
        }
        return $this->request('/clip/v2/resource/scene/' . $_id, 'PUT', json_encode($_data));
    }
  
    public function smart_scene($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/smart_scene');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/smart_scene/' . $_id);
        }
        return $this->request('/clip/v2/resource/smart_scene/' . $_id, 'PUT', json_encode($_data));
    }

    public function motion($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/motion');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/motion/' . $_id);
        }
        return $this->request('/clip/v2/resource/motion/' . $_id, 'PUT', json_encode($_data));
    }

    public function temperature($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/temperature');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/temperature/' . $_id);
        }
        return $this->request('/clip/v2/resource/temperature/' . $_id, 'PUT', json_encode($_data));
    }

    public function zigbee_connectivity($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/zigbee_connectivity');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/zigbee_connectivity/' . $_id);
        }
        return $this->request('/clip/v2/resource/zigbee_connectivity/' . $_id, 'PUT', json_encode($_data));
    }

    public function device_power($_id = null, $_data = null) {
        if ($_id == null) {
            return $this->request('/clip/v2/resource/device_power');
        }
        if ($_data == null) {
            return $this->request('/clip/v2/resource/device_power/' . $_id);
        }
        return $this->request('/clip/v2/resource/device_power/' . $_id, 'PUT', json_encode($_data));
    }

    public function event() {
        return $this->request('/eventstream/clip/v2', 'GET', array(), 1200);
    }
}