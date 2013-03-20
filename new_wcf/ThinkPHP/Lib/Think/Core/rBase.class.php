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
     * @var uint
     */
    protected $expire_time = 0;
    
    /**
     * 
     * 有序集合存储的最大个数
     * @var uint
     */    
    protected $zset_max_size = 100;
    
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

    
    /**
     * 获取实际的操作Key,由子类实现
     * @param $id
     */
    public function getKey($id) {
       //extend me
    }
}