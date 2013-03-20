<?php
/**
 * 获取输入统一入口
 * @author yangyi<yangyi@wmw.cn>
 */
class Request
{
    private  $data;    //存储的数据

    static 	 $_instance;  //本类的实例

    private function __construct()
    {
        //控制层调整完后，将开启下边的注释，将$_GET,$_POST,$_FILES,$_COOKIE进行销毁
        $this->data['get']    = self::formatData($_GET);
        //$_GET = array();
        $this->data['post']   = self::formatData($_POST);
        //$_POST = array();
        $this->data['cookie'] = self::formatData($_COOKIE);
        //$_COOKIE = array();
    }

    public function __call($method, $args) {
        //数据类型
        $data_type = substr($method, -3);
        //输入类型
        $input_type = substr($method, 0, -3);
        if(in_array($input_type, array('get', 'post', 'cookie')) && in_array($data_type, array('Int', 'Str', 'Arr'))) {
            array_splice($args, 1, 0, array($input_type));
            return call_user_func_array(array($this, '_get' . $data_type), $args);
        }	
    }

    public static function getInstance() {
        if(!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 获取传递过来的数值变量
     *
     * @param string $key
     * @param string $input_type 输入类型
     * @return int or big int
     */
    private	function _getInt($key, $input_type)
    {
        if(is_array($key)) return false;
        return $this->toInt($this->data[$input_type][$key]);
    }

    /**
     * 获取传递过来的字符串变量
     *
     * @param string $key
     * @param string $input_type 输入类型
     * @param boolen $isfilter 是否过滤
     * @return string
     */
    private	function _getStr($key, $input_type, $isfilter=true)
    {
        if(is_array($key)) return false;
        $data = $this->data[$input_type][$key];
        if($isfilter) {
            if(!is_string($data)) return false;
            return $this->filterString($data);
        } else {
            return $data;
        }
        return false;
    }
    /**
     * 获取传递过来的数组变量
     *
     * @param string $key
     * @param string $input_type 输入类型
     * @param boolen $isfilter 是否过滤
     * @return string
     */
    private function _getArr($key, $input_type, $isfilter = true) {
        $data = $this->data[$input_type][$key];
        if(!is_array($data)) return false;
        $result = $this->_filterArr($data, $isfilter);
        return $result;
    }

    private function _filterArr($data, $isfilter = true) {
        if(is_array($data)) {
            foreach($data as $key=>$value) {
                unset($data[$key]);
                if($isfilter) {
                    $key = $this->filterString($key);
                }
                $data[$key] = $this->_filterArr($value, $isfilter);
            }
        } else {
            if($isfilter) {
                $data = $this->filterString($data);
            }
        }
        return $data;
    }

    /**
     * 格式化数据,将数据转存
     *
     * @param array $data
     */
    private	function formatData($data)
    {
        $result = array();
        reset($data);
        while(list($key, $value) = each($data))
        {
            if(!is_array($value)) {
                $result[$key] = trim($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    /**
     * 转换为数字
     *
     * @param string $num
     * @return int or big int or false
     */
    private function toInt($num)
    {
        if(is_numeric($num))
        {
            return intval($num);
        } else {
            return false;
        }
    }
    /**
     * 转换过滤字符串
     *
     * @param string $string
     * @return string
     */
    private function filterString($string)
    {
        if($string === NULL)
        {
            return false;
        }
        return htmlspecialchars($string, ENT_QUOTES);
    }
}
?>
