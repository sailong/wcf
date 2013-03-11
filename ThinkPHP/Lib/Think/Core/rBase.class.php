<?php
/**
 * 
 * @author $anlicheng
 * 注明：
 * 1. 为了使phpredis的功能更加灵活和满足不同的应用需求，采用的是继承的方式；
 *
 */
class rBase {
    protected $redis = null;    //Redis实例
    
    /**
     * 
     * 要进行array->json转换的字段，通常用于二维数组，第二维Value也是数组的情况.
     * 如果要全部转换，则设置为$hash_json_fields = true;
     * @var array
     */
    protected $hash_json_fields = array();
    
    /**
     * 
     * 设定需要过期的时间，默认为0，不过期，单位为unixtime;
     * @var unknown_type
     */
    protected $expire_time = 0;
    
    public function __construct() {
        $this->redis = RedisIo::getInstance();
    }
    
    /**
     * 代理函数，支持对redis的函数的调用
     * @param $method
     * @param $args
     */
    public function __call($method, $args) {
        if(!method_exists($this->redis, $method)) {
            $reflect = new ReflectionObject($this);
            $class_name = $reflect->getName();
            unset($reflect);
            
            trigger_error("the method $class_name::$method don't exist!", E_USER_ERROR);
        }
        
        return call_user_func_array(array($this->redis, $method), $args);
    }
    
    /**
     * 获取管道处理操作成功的命令数
     * @param $replies
     */
    protected function getPipeSuccessNums($replies) {
        if(empty($replies)) {
            return 0;
        }
        
        $success_nums = 0;
        foreach((array)$replies as $val) {
            if(!empty($val)) {
                $success_nums++;
            }
        }
        
        return $success_nums;
    }
    
    
    /********************************************************************************
     * 封装基本方法
     ********************************************************************************/      
    
    /**
     * 判断对象的Key是否存在
     * @param $id
     */    
    
    public function isExist($id) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        return $this->exists($redis_key);        
    }
    
    /**
     * 删除对象的Key
     * @param $id
     */
    public function keyDel($id) {
        if(empty($id)) {
            return false;
        }
        $redis_key = $this->getKey($id);
        return $this->delete($redis_key);
    }        
        
    
    /********************************************************************************
     * HASH 封装基本方法
     ********************************************************************************/    
    

    /**
     * 获取HASH对象
     * @param $id
     */
    public function hashGet($id) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        $result = $this->hGetAll($redis_key);
            
        return $this->hashUnPack($result);
    }
    
    /**
     * 添加HASH对象
     * @param $id
     * @param $datas
     */
    public function hashSet($id, $datas) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
                
        $hash_datas = $this->hashPack($datas);
        $result = $this->hMset($redis_key, (array)$hash_datas);
        if ($this->expire_time > 0) {
            $this->expireAt($redis_key, time() + $this->expire_time);
        }
        
        return true;
    }
    
    /**
     * 删除HASH对象的Key-value
     * @param $id
     */
    public function hashDel($id, $datas) {
        if(empty($id) || empty($datas)) {
            return false;
        }
        $redis_key = $this->getKey($id);
        
        $fields = implode(" ", $datas);
        
        return $this->hDel($redis_key, $fields);
    }      
    
    /**
     * 格式化hash数据
     * @param $datas
     */
    private function hashPack($datas) {
        if(empty($datas) || !is_array($datas)) {
            return array();
        }
        
        foreach($datas as $key=>$val) {
            if( $this->hash_json_fields == true || 
                ( is_array($this->hash_json_fields) && isset($this->hash_json_fields[$key]) )
               ) {
                $val = json_encode($val);
            }
            $datas[$key] = $val;
        }
        
        return $datas;
    }    
    
    /**
     * 格式化hash数据
     * @param $datas
     */
    private function hashUnPack($datas) {
        if(empty($datas) || !is_array($datas)) {
            return array();
        }
        
        foreach($datas as $key=>$val) {
           if( $this->hash_json_fields == true || 
                ( is_array($this->hash_json_fields) && isset($this->hash_json_fields[$key]) )
              ) {
                $val = json_decode($val, true);
            }
            $datas[$key] = $val;
        }
        
        return $datas;
    }    
    
    /********************************************************************************
     * SET 封装基本方法
     ********************************************************************************/      
    
    /**
     * 获取用户对应集合
     * @param $id = client_account
     */
    public function sGet($id) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        return $this->sMembers($redis_key);
    }
    
    /**
     * 设置用户对应的集合
     * @param $id = client_account
     */
    public function sSet($id, $datas) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        $pipe = $this->multi(Redis::PIPELINE);
        foreach($datas as $item) {
            $pipe->sAdd($redis_key, $item);
        }
        
        if ($this->expire_time > 0) {
            $pipe->expireAt($redis_key, time() + $this->expire_time);
        }
        
        $replies = $pipe->exec();
        $add_nums = $this->getPipeSuccessNums($replies);
        
        return $add_nums ? $add_nums : false;
    }     

    
    /**
     * 移除单个VALUE从SET容器中
     * @param $id
     */
    public function sDel($id, $value) {
        if(empty($id)) {
            return false;
        }
        $redis_key = $this->getKey($id);
        return $this->sRem($redis_key, $value);
    }
    
    /**
     * 移除多个VALUE从SET容器中
     * @param $id
     */    
    
    public function sDels($id, $datas) {
        if(empty($id) || empty($datas)) {
            return false;
        }
        
        if (!is_array($datas)) {
            return $this->sRem($redis_key, $datas);
        }        
                
        $redis_key = $this->getKey($id);
        
        $pipe = $this->multi(Redis::PIPELINE);        
        foreach($datas as $val) {
            $pipe->sRem($redis_key, $val);
        }
        $replies = $pipe->exec();
        $delete_nums = $this->getPipeSuccessNums($replies);
        
        return $delete_nums ? $delete_nums : false;
    }    
    
}