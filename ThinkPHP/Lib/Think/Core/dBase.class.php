<?php
/**
 * 数据层基类
 * @author 杨益(yangyi@wmw.cn)
 * @copyright wmw.cn
 * @package Libraries
 * @since 2011-8-17
 */
class dBase extends Model {
    
    protected $_chunk_size = 200;        //分组查询的数组大小
    
    //数据库相关配置
    protected $_tablename = null;        //数据库表名
    protected $_fields = array();        //数据库表字段列表
    protected $_pk = null;               //数据库表中的主键
    protected $_index_list = array();    //数据库表的索引列表
    /**
     * curl的get请求
     *
     * @param string $url url网址
     * @param int $timeout 超时时间
     * @return false 或 执行返回结果
     */
    public static function requestByGet($url, $timeout=2) {
        $retry = 1;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        for($i = 1; $i <= $retry; $i++) {
            $re = curl_exec($ch);

            if ( is_string($re) && strlen($re) ) {
                curl_close($ch);
            } else {
                if($i == $retry) {
                    $curl_error = curl_error($ch);
                    curl_close($ch);
                }
            }

            if(!empty($re)) return $re;
        }
        error_log(__METHOD__ . ':' . $url . ':' . $curl_error);
        return false;
    }

    /**
     * curl的post请求
     *
     * @param string $url url网址
     * @param array $data post数组
     * @param int $timeout 超时时间
     * @return false 或 执行返回结果
     */
    public static function requestByPost($url, $data, $timeout=3) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $result = curl_exec($ch);
		
        if ($result !== false) {
            curl_close($ch);
            return $result;//$result;
        } else {
            $curl_error = curl_error($ch);
            $_data = is_array($data) ? implode('&', $data) : $data;
            curl_close($ch);
            error_log(__METHOD__ . ':' . $url . '?' . $_data . ':' . $curl_error);
            return false;
        }
    }

    public function connectDb($alias, $master = false) {
        static $_db = array();
        $linkNum = $alias . intval($master);
        if(!isset($_db[$linkNum])) {
            // 创建一个新的实例
            $_db[$linkNum]            =    Db::getInstance($alias, $master);
        }
        // 切换数据库连接
        $this->db   =    $_db[$linkNum];
        return $this;
    }
    
    /**
     * 检测id值
     * @param $ids 可以单个值或者数组
     * @return 满足条件idarray
     */
    function checkIds($ids) {
        if(empty($ids)) {
            return false;
        }
        
        $ids = array_unique((array)$ids);
        
        foreach($ids as $key=>$id) {
            if(empty($id)) {
                unset($ids[$key]);
            }
        }
        
        return !empty($ids) ? $ids : false;
    }
    
    protected function isIndex($fk) {
        return !empty($this->_index_list) && in_array($fk, $this->_index_list) ? true : false;
    }
    
 	/*添加表信息
     * @param $data
     * @param $tableName
     * @param $is_return_id
     * return $effect_rows OR $insert_id
     */
    public function add($datas, $is_return_id = false) {
        if(empty($datas) || !is_array($datas)) {
            return false;
        }
        //插入前回调函数
        if(method_exists($this, "before_add")) {
            $this->before_add();
        }
        
        $setsql = $this->joinFields($datas);
        if(!empty($setsql)) {
            $sql = "insert into {$this->_tablename} set $setsql";
            $effect_rows = $this->execute($sql);
            if(empty($effect_rows)) {
                error_log(__METHOD__ . "SQL ：$sql \r\n" );
                return false;
            }
            //成功后回调函数
            if(method_exists($this, 'after_add')) {
                $this->after_add();
            }
            //是否要求返回插入id
            if(!empty($is_return_id)) {
                $insert_id = $this->getLastInsID();
                return !empty($insert_id) ? $insert_id : false;
            }
            return $effect_rows;
        }
        return false;
    }
    
    
   /**
     * 同一张表批量增加对应的记录
     * @param $dataarr
     */
    public function addBat($dataarr) {
        if (empty($dataarr) || !is_array($dataarr)) {
            return false;
        }
        
        $field_vals = array();
        //获取fields数据
        $new_arr = $fields = array();
        foreach($dataarr as $key=>$datas) {
            //检查并得到正确的数据
            $datas = $this->checkFields($datas);
            if(empty($datas)) {
                continue;
            }
            
            $new_arr[] = $datas;
            $fields = array_merge($fields, (array)array_keys($datas));
        }
        unset($dataarr);
        
        //得到正确的字段名
        $fields = array_unique($fields);
        if (empty($fields) || empty($new_arr)) {
        	return false;
        } 
        
        //排序
        sort($fields);

        //insert 的values
        $fields_values = array();
        foreach($new_arr as $datas) {
            if (empty($datas) || !is_array($datas)) {
                continue;
            }
            
            $diff = array_diff($fields, (array)array_keys($datas));
            if (!empty($diff)) {
                //没有数据的字段默认为空
                $datas = array_merge($datas, array_combine($diff, array_fill(0, count($diff), null)));
            }
            //字段排序
            ksort($datas);
            
            $vals = array();
            foreach($datas as $val) {
                $val = $this->getEscapedString($val);
                $vals[] = is_null($val) ? 'DEFAULT' : "'$val'";
            }
            $fields_values[] = "(" . implode(',', $vals) . ")";
        }
        
        $fields_str = "(" . implode(",", $fields) . ")";
        $values_str = implode(",", $fields_values);
        
        $sql = "insert into {$this->_tablename}$fields_str values $values_str";

        $effect_rows = $this->execute($sql);
        if(empty($effect_rows)) {
            error_log(__METHOD__ . "SQL: $sql; \r\n");
            return false;
        }
        
        return $effect_rows;
    }
    
    
    /*通过主键删除
     * @param $pk
     * $param $tableName
     * return $effect_rows
     */
    public function delete($id) {
        if(empty($id)) {
            return false;
        }
        
        if(method_exists($this, 'before_delete')) {
            $this->before_delete();
        }
        
        $id = is_array($id) ? array_shift($id) : $id;
        $sql = "delete from {$this->_tablename} where {$this->_pk}='$id' limit 1";
        $effect_rows = $this->execute($sql);
        if(empty($effect_rows)) {
            error_log(__METHOD__ . "SQL ：$sql" );
            return false;
        }
        
		if(method_exists($this, 'after_delete')) {
		    $this->after_delete();
		}
		
		return $effect_rows;
    }
    
    /*修改表信息
     * @param $data
     * @param $id
     * @param $tableName
     * return $effect_rows
     */
    public function modify($datas, $id) {
		if(empty($datas) || !is_array($datas) || empty($id)){
		   return false;  
		}
		
		if(method_exists($this, 'before_update')) {
		    $this->before_update();
		}
		
		$setsql = $this->joinFields($datas);
		if(!empty($setsql)) {
		    $condition = "where {$this->_pk}='$id' limit 1";
		    $sql = "update {$this->_tablename} set $setsql $condition";
		    $effect_rows = $this->execute($sql);
		    
    		if(empty($effect_rows)){
                error_log(__METHOD__ . " SQL ：$sql");
                return false;
            }
            
    		if(method_exists($this, 'after_update')){
    		    $this->after_update();
    		}
    		
    		return $effect_rows;
		}
		
		return false;
   }
   
	/*主键查询
     * @param $key
     * @param $fields
     * return $info_list
     */
    public function getInfoByPk($ids, $orderby = null, $offset = null, $limit = null) {
        if(empty($ids)) {
            return false;
        }
        $info_list = $this->getData($ids, $this->_pk, $orderby, $offset, $limit);
        
        $new_info_list = array();
        if(!empty($info_list)) {
            foreach($info_list as $key => $info) {
                $new_info_list[$info[$this->_pk]] = $info;
                unset($info_list[$key]);
            }
        }
        return !empty($new_info_list) ? $new_info_list : false;
    }
    
    /*外键键查询
     * @param $key
     * @param $fields
     * return $info_arr
     */
    public function getInfoByFk($ids = array(), $fk, $orderby = null, $offset = null, $limit = null){
        if(empty($ids) || !$this->isIndex($fk)) {
            return false;
        }
        
        $info_list = $this->getData($ids, $fk, $orderby, $offset, $limit);
        $new_info_arr = array();
        if(!empty($info_list)) {
            foreach($info_list as $key => $info) {
                $new_info_arr[$info[$fk]][$info[$this->_pk]] = $info;
                unset($info_list[$key]);
            }
        }
        return !empty($new_info_arr) ? $new_info_arr : false;
    }
	
    /*得到数据库数据
     * @param $ids
     * @param $fields
     * return $info_list
     */
    protected function getData($ids = array(), $field, $orderby = null, $offset = null, $limit = null) {
        $ids = (array)$this->checkIds($ids);
        if(empty($ids) || empty($field)) {
            return false;
        }
        
         $wheresql = "where $field in('" . implode("','", (array)$ids) . "')";
         $orderbysql = !is_null($orderby) ? "order by $orderby" : "";
         
         $limitsql = "";
         if(!is_null($offset) && !is_null($limit)) {
             $offset = max(intval($offset), 0);
             $limit = max(intval($limit), 0);
             if($limit > 0) {
                 $limitsql = "limit $offset,$limit";
             }
         }

         $sql = "select * from {$this->_tablename} $wheresql $orderbysql $limitsql";
         return $this->query($sql);
    }
    
    /**
     * 通用查询接口
     * @param $wheresql 查询的where条件,忽略where关键词
     * @param $orderby	排序规则，可选
     * @param $offset	获取数据的起始位置
     * @param $length	获取的数据记录条数
     * @return 			返回以主键为拼装的2维数组
     */
    public function getInfo($wherearr = array(), $orderby = null, $offset = null, $limit = null) {
        
        $wheresql = !empty($wherearr) ? "where " . implode(' and ', (array)$wherearr) : "";
        $orderbysql = !is_null($orderby) ? "order by " . $orderby : "";
        
        $limitsql = "";
        if(!is_null($offset) && !is_null($limit)) {
            $offset = max(0, intval($offset));
            $limit = max(0, intval($limit));
            if($limit > 0) {
                $limitsql = "limit $offset,$limit";
            }
        }

        $sql = "select * from {$this->_tablename} $wheresql $orderbysql $limitsql";
        $record_list = $this->query($sql);
        
        $new_record_list = array();
        if(!empty($record_list)) {
            foreach($record_list as $key=>$data) {
                $new_record_list[$data[$this->_pk]] = $data;
                unset($record_list[$key]);
            }
        }
        
        return !empty($new_record_list) ? $new_record_list : false;
    }
    
    /**
     * 通用的统计函数
     * @param $wherearr
     */
    public function getCount($wherearr = array()) {
        $wheresql = !empty($wherearr) ? "where " . implode(' and ', (array)$wherearr) : "";
        

        $sql = "select count(*) as stat_nums from {$this->_tablename} $wheresql";

        $stat_result_list = $this->query("select count(*) as stat_nums from {$this->_tablename} $wheresql");
        
        $stat_result = reset($stat_result_list);
        
        return intval($stat_result['stat_nums']);
    }
    
    /**
     * 数据字段合并
     * @param $dataarr
     */
    protected function joinFields($dataarr) {
        if(empty($dataarr) || !is_array($dataarr)) {
            return false;
        }
        
        $dataarr = $this->checkFields($dataarr);
        if(!empty($dataarr)){
        	$arr = array();
        	foreach($dataarr as $key=>$value){
        		$arr[] = $this->formartQueryString($key, $value);
        	}
        	return implode(',' , $arr);
        }
        
        return false;
    }
    
    /**
     * 格式化查询字符串的拼接
     * @param $field
     * @param $val
     */
     protected function formartQueryString($field, $val) {
        if(empty($field)) {
            return false;
        }
        
        $field = trim($field);
        if(stripos($val, '%') !== false) {
            //如果类型的格式为: array('name' => '%name+1%');
            if(preg_match("/^%(\s)*($field)([^%]+)%$/", $val, $matches)) {
                return "`$field`=`$field`" . $this->getEscapedString($matches[3]);
            } else if(preg_match("/^%([^%]+)%$/", $val)) {
                return "`$field`=" . $this->getEscapedString(str_replace("%", "", $val));
            }
        }
        
        return "`$field`='" . $this->getEscapedString($val) . "'";
    }
    
    /**
     * 获取转义后的字符串
     * @param $str
     */
    protected function getEscapedString($str) {
        if(empty($str)) {
            return $str;
        }
        
        return mysql_escape_string(stripslashes($str));
    }
    
 	/**
     * 检测数据中的字段是否正确，不正确的unset掉
     * @param $dataarr	要检测的数组
     * @param $table_fields	数据库表中的字段组成的数组
     */
    protected function checkFields($dataarr) {
        if(empty($dataarr)) {
            return false;
        } if(empty($this->_fields)) {
            return $dataarr;
        }
        
        $check_fields = array_keys($dataarr);
        $diff_arr = array_diff((array)$check_fields, (array)$this->_fields);
        if(!empty($diff_arr)) {
            foreach($diff_arr as $field) {
                unset($dataarr[$field]);
            }
        }
        
        return !empty($dataarr) ? $dataarr : false;
    }
    
    
    /**
     * 活动和雏鹰项目相应的D层数据字段合并
     * @param $dataarr
     */
    protected function joinFieldsForZscy($dataarr, $fields = array()) {
        if(empty($dataarr) || !is_array($dataarr)) {
            return false;
        }
        
        //检测字段信息是否合法
        if(!empty($fields) && is_array($fields)) {
            $diff_arr = array_diff(array_keys($dataarr), $fields);
            if(!empty($diff_arr)) {
                foreach($diff_arr as $field) {
                    unset($dataarr[$field]);
                }
            }
        }
        
        if(!empty($dataarr)){
        	$arr = array();
        	foreach($dataarr as $key=>$value){
        		$arr[] = $this->formartQueryString($key, $value);
        	}
        	return implode(',' , $arr);
        }
        
        return false;
    }
}
