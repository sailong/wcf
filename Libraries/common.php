<?php
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    $ckey_length = 4;
    $key = md5($key != '' ? $key : AUTH_KEY);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if($operation == 'DECODE') {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc.str_replace('=', '', base64_encode($result));
    }

}

function token_encode($string) {
    if(is_array($string)) $string = implode("%%t%%", $string);
    return authcode($string, 'ENCODE');
}

//token 解密
function token_decode($string) {
    if(!is_string($string)) return false;
    $result =  authcode($string);
    $token_arr = explode("%%t%%", $result);
    return $token_arr;
}

/**
 * 获取省市区的相关页面信息
 */
function getAreaidSelect($area_id = ''){
    $areaSelectStr = '
        <select name="select" class="pulldown_menu" id="address_1">
        <option value="-1">请选择省</option>
        </select>
        <select name="select" class="pulldown_menu" id="address_2">
        <option value="-1">请选择市</option>
        </select>
        <select name="select" class="pulldown_menu" id="address_3">
        <option value="-1">请选择县/区</option>
        </select>
        <input type="hidden" name="init_area_id" id="init_area_id" value="'.$area_id.'"/>
        <input type="hidden" name="area_id" id="area_id" value="'.$area_id.'"/>';

    return $areaSelectStr;
}


/**
 * 解码当前的地区编码信息 ， 解码规则：前3位代表省，中间3位代表市，后面3位代表区
 * @param   $area_id 要解析的地区的当前编码信息
 */
function decodeAreaId($area_id){
    $area_idStr = strval($area_id);
    $strlen = strlen($area_idStr);
    if($strlen >= 7){
        if($strlen < 9){
            $area_idStr = str_pad($area_idStr , 9 , '0' , STR_PAD_LEFT);
        }
        $provinceid = intval(substr($area_idStr , 0 , 3));
        $cityid = intval(substr($area_idStr , 3 , 3));
        $countyid = intval(substr($area_idStr , 6 , 3));
    } else {
        //页面传回的数据错误
        $provinceid = $cityid = $countyid = 0;
    }

    return array($provinceid , $cityid , $countyid);
}
/**
 * 编码当前的省市区信息
 * @param   $provinceid
 * @param   $cityid
 * @param   $countyid
 */
function encodeAreaId($provinceid , $cityid , $countyid){
    $arealist = array(
            'provinceStr' => strval($provinceid),
            'cityStr' => strval($cityid),
            'countyStr' => strval($countyid),
            );
    foreach($arealist as $key=>$str){
        $strlen = strlen($str);
        if($strlen < 3){
            $str = str_pad($str , 3 , '0' , STR_PAD_LEFT);
        }
        $arealist[$key] = $str;
    }
    $areaStr = implode('' , $arealist);

    return intval($areaStr);
}

/**
 * 获取省市区的名称列表
 * @param $areaid
 * @return 处理后的省市区的名称
 */
function getAreaNameList($area_id) {
    if(empty($area_id)) {
        return false;
    }
    require_once(CONFIGE_DIR .'/area.php');
    global $CONF_PROVINCE, $CONF_CITY, $CONF_COUNTY;
    list($provinceid , $cityid , $countyid) = decodeAreaId($area_id);

    if(empty($provinceid) || empty($cityid)) {
        return false;
    }
    $namelist = array();
    if(isset($CONF_PROVINCE[$provinceid])) {
        $namelist['province'] = $CONF_PROVINCE[$provinceid];
    }
    if(isset($CONF_CITY[$provinceid][$cityid])) {
        $namelist['city'] = $CONF_CITY[$provinceid][$cityid];
    }

    if(isset($CONF_COUNTY[$provinceid][$cityid][$countyid])) {
        $namelist['county'] = $CONF_COUNTY[$provinceid][$cityid][$countyid];
    } else {
        $namelist['county'] = '';
    }

    return !empty($namelist) ? $namelist : false;
}

function getmicrotime() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * 中英文字符串截取
 *
 * @param string $string 需要截取的字符串
 * @param array $length 截取宽度,即多少个英文字母,2个英文字母相当一个汉字的宽度
 * @param bool $append ture后增加...,false没有后缀
 * @return string 返回结果
 */
function cutstr($string, $length, $append = false) {
    $l = strlen($string);
    if($l <= $length) {
        return $string;
    }

    $pre = chr(1);
    $end = chr(1);
    //html实体
    $entity_arr = array(
            '&amp;', 
            '&quot;', 
            '&lt;', 
            '&gt;',
            '&nbsp;'
            );
    //实体对应字符串
    $char_arr = array(
            $pre.'&'.$end, 
            $pre.'"'.$end, 
            $pre.'<'.$end, 
            $pre.'>'.$end,
            $pre.' '.$end
            );
    $string = str_replace($entity_arr, $char_arr, $string);

    $strcut = '';

    $n = $tn = $noc = 0;
    while($n < $l) {

        $t = ord($string[$n]);
        if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
            $tn = 1; $n++; $noc++;
        } elseif(194 <= $t && $t <= 223) {
            $tn = 2; $n += 2; $noc += 1;
        } elseif(224 <= $t && $t <= 239) {
            $tn = 3; $n += 3; $noc += 2;
        } elseif(240 <= $t && $t <= 247) {
            $tn = 4; $n += 4; $noc += 1;
        } elseif(248 <= $t && $t <= 251) {
            $tn = 5; $n += 5; $noc += 1;
        } elseif($t == 252 || $t == 253) {
            $tn = 6; $n += 6; $noc += 1;
        } else {
            $n++;
        }

        if($noc >= $length) {
            break;
        }

    }
    if($noc > $length) {
        $n -= $tn;
    }

    $strcut = substr($string, 0, $n);


    $strcut = str_replace($char_arr, $entity_arr, $strcut);

    $pos = strrpos($strcut, chr(1));
    if($pos !== false) {
        $strcut = substr($strcut,0,$pos);
    }
    if($append && strlen($strcut) < $l) {
        $strcut .= '...';
    }
    return $strcut;
}
  
   
/**
 * 中英文字符串截取，
 * 注意：通上面的cutstr不同的是，所有字符均是为一个占字符。
 * 添加了起始位置的参数。
 *
 * @param string $string 需要截取的字符串
 * @param array $length 截取宽度,无论哪种字符，均视为一个占字符
 * @param bool $append ture后增加...,false没有后缀
 * @return string 返回结果
 */
    function mbcutstr($string, $start, $length, $append = false) {
        $l = strlen($string);
         //截取的起始位置
        $start = $start > 0 ? $start : 0;
        
        //参数的检测
        if($length <=0 || $l == 0 || ($start > 0 && $l < $start)) {
            return false;
        }
        $pre = chr(1);
        $end = chr(1);
        //html实体
        $entity_arr = array(
                '&amp;', 
                '&quot;', 
                '&lt;', 
                '&gt;',
                '&nbsp;'
                );
        //实体对应字符串
        $char_arr = array(
                $pre.'&'.$end, 
                $pre.'"'.$end, 
                $pre.'<'.$end, 
                $pre.'>'.$end,
                $pre.' '.$end
                );
        $string = str_replace($entity_arr, $char_arr, $string);
    
        $strcut = '';
        $n = $tn = $noc = $start_pos = 0;
        
        while($n < $l) {
            $t = ord($string[$n]);
            if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1; $n++; $noc++;
            } elseif(194 <= $t && $t <= 223) {
                $tn = 2; $n += 2; $noc += 1;
            } elseif(224 <= $t && $t <= 239) {
                $tn = 3; $n += 3; $noc += 1;
            } elseif(240 <= $t && $t <= 247) {
                $tn = 4; $n += 4; $noc += 1;
            } elseif(248 <= $t && $t <= 251) {
                $tn = 5; $n += 5; $noc += 1;
            } elseif($t == 252 || $t == 253) {
                $tn = 6; $n += 6; $noc += 1;
            } else {
                $n++;
            }
            //获取截取的开始位置
            if($noc == $start) {
                $start_pos = $n;
            }
            if($noc - $start >= $length) {
                break;
            }
        }
        if($noc - $start > $length) {
            $n -= $tn;
        }
        //获取要截取的字符串长度
        if($n > $start_pos) {
            $strcut = substr($string, $start_pos, $n - $start_pos);
            $strcut = str_replace($char_arr, $entity_arr, $strcut);
            $pos = strrpos($strcut, chr(1));
            if($pos !== false) {
                $strcut = substr($strcut,0,$pos);
            }
            if($append) {
                $strcut .= '...';
            }
            return $strcut;
        } else {
            return false;
        }
    } 
    
    /*检测字符串长度 
    *所有字符如中英文，特殊字符，空格等均视为一个占字符。
    *
    **/
    function mbStrLenth($string) {

    	if(strlen($string) <= 0) {
    		return 0;
    	}
    	$string = trim($string);
    	$pre = chr(1);
    	$end = chr(1);
    	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), $string);
    	$n = $length = 0;
		$strlen = strlen($string);
		while($n < $strlen) {
			$t = ord($string[$n]);
			if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$n++;
			} elseif(194 <= $t && $t <= 223) {
				$n += 2; 
			} elseif(224 <= $t && $t <= 239) {
				$n += 3; 
			} elseif(240 <= $t && $t <= 247) {
				$n += 4; 
			} elseif(248 <= $t && $t <= 251) {
				$n += 5;
			} elseif($t == 252 || $t == 253) {
				$n += 6;
			} else {
				$n++;
			}
			$length ++;
		}
    	return $length;
    }
    function is_utf8($string) {
        return preg_match('%^(?:
        	[\x09\x0A\x0D\x20-\x7E] # ASCII
            | [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
            )*$%xs', $string);
    } 

/** 
 * A better alternative (RFC 2109 compatible) to the php setcookie() function 
 * 
 * @param string Name of the cookie 
 * @param string Value of the cookie 
 * @param int Lifetime of the cookie 
 * @param string Path where the cookie can be used 
 * @param string Domain which can read the cookie 
 * @param bool Secure mode? 
 * @param bool Only allow HTTP usage? 
 * @return string True or false whether the method has successfully run 
 */ 
function getCookieStr($name, $value='', $expires=0, $path='', $domain='', $secure=false, $HTTPOnly=false) 
{ 
    $ob = ini_get('output_buffering'); 

    // Abort the method if headers have already been sent, except when output buffering has been enabled 
    if ( headers_sent() && (bool) $ob === false || strtolower($ob) == 'off' ) 
        return false; 

    if ( !empty($domain) ) 
    { 
        // Fix the domain to accept domains with and without 'www.'. 
        if ( strtolower( substr($domain, 0, 4) ) == 'www.' ) $domain = substr($domain, 4); 
        // Add the dot prefix to ensure compatibility with subdomains 
        if ( substr($domain, 0, 1) != '.' ) $domain = '.'.$domain; 

        // Remove port information. 
        $port = strpos($domain, ':'); 

        if ( $port !== false ) $domain = substr($domain, 0, $port); 
    } 

    // Prevent "headers already sent" error with utf8 support (BOM) 
    //if ( utf8_support ) header('Content-Type: text/html; charset=utf-8'); 

    return 'Set-Cookie: '.rawurlencode($name).'='.rawurlencode($value) 
            .(empty($domain) ? '' : '; Domain='.$domain) 
            .(empty($expires) ? '' : '; expires='.gmdate('D, d-M-Y H:i:s', $expires)).' GMT'
            .(empty($path) ? '' : '; Path='.$path) 
            .(!$secure ? '' : '; Secure') 
            .(!$HTTPOnly ? '' : '; HttpOnly'); 
} 
