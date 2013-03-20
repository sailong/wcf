<?php
/**
 * redis String结构的基本方法封装，继承rBase
 * 
 * @author lnczx
 */
class rBaseString extends rBase {

    public function __construct() {
        parent::__construct();
    }
    
    /********************************************************************************
     * String 封装基本方法
     ********************************************************************************/      
    
    /**
     * 获取String
     * @param $id = client_account
     */
    public function stringGet($id) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        return $this->get($redis_key);
    }
    
    /**
     * 设置String
     * @param $id = client_account
     */
    public function stringSet($id, $value) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        $this->set($redis_key, $value);
        if ($this->expire_time > 0) {
            $this->expireAt($redis_key, time() + $this->expire_time);
        }        
        
        return true;
    }     
    
    
	/**
     * 对指定一个或多个的KEY的值自增1
     * @param $ids = client_account
     */
    public function stringIncr($ids) {
        if(empty($ids)) {
            return false;
        }
        
        $ids = (array)$ids;
        
        $pipe = $this->multi(Redis::PIPELINE);        
        foreach($ids as $id) {
            
            $redis_key = $this->getKey($id);
            $pipe->incr($redis_key);
        }
        $replies = $pipe->exec();
        $action_nums = $this->getPipeSuccessNums($replies);        
        
        return $action_nums ? $action_nums : false;
    }
    
	/**
     * 对指定一个或多个的KEY自减1
     * @param $ids = client_account
     */
    public function stringDecr($id) {
        if(empty($id)) {
            return false;
        }
        
        
        $ids = (array)$ids;
        
        $pipe = $this->multi(Redis::PIPELINE);        
        foreach($ids as $id) {
            
            $redis_key = $this->getKey($id);
            $pipe->decr($redis_key);
        }
        $replies = $pipe->exec();
        $action_nums = $this->getPipeSuccessNums($replies);        
        
        return $action_nums ? $action_nums : false;
    }   

   	/**
     * 对指定一个或多个的KEY值进行发布
     * @param $ids = client_account
     */
    public function stringPublic($ids, $messsage = '') {
        if(empty($ids)) {
            return false;
        }
        
        $ids = (array)$ids;
        
        $pipe = $this->multi(Redis::PIPELINE);        
        foreach($ids as $id) {
            
            $redis_key = $this->getKey($id);
            $pipe->publish($redis_key, $messsage);
        }
        $replies = $pipe->exec();
        $action_nums = $this->getPipeSuccessNums($replies);
        
        return $action_nums ? $action_nums : false;
    } 
    
   	/**
     * 对指定的KEY的值进行订阅
     * @param $id = client_account
     */
    public function stringSubscribe($id) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        return $this->subscribe($redis_key, "callback_subscribe");
    }  
    
    
    public function callback_subscribe() {
        //extend me
    }

}