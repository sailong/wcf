<?php
/**
 * redis Hash结构的基本方法封装，继承rBase
 * 
 * @author lnczx
 */
class rBaseHash extends rBase {

    /**
     * 
     * 要进行array->json转换的字段，通常用于二维数组，第二维Value也是数组的情况.
     * 如果要全部转换，则设置为$hash_json_fields = true;
     * @var array
     */
    protected $hash_json_fields = array();
            
    public function __construct() {
        parent::__construct();
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
}