<?php
/**
 * redis Set结构的基本方法封装，继承rBase
 * 
 * @author lnczx
 */
class rBaseSet extends rBase {


    public function __construct() {
        parent::__construct();
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