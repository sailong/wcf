<?php
/**
 * redis Zset结构的基本方法封装，继承rBase
 * 
 * @author lnczx
 */
class rBaseZset extends rBase {

    /**
     * 
     * 有序集合存储的最大个数
     * @var uint
     */    
    protected $zset_max_size = 200;
    
    public function __construct() {
        parent::__construct();
    }

    /********************************************************************************
     * ZSet 封装基本方法
     ********************************************************************************/        
    
    /**
     * 返回key对应的有序集合中指定区间的所有元素,Score值从大到小排列
     * @param $id
     * @param $lastId 上次查询的id,默认为0 
     * @param $limit
     */
    public function zGetRange($id, $lastId = 0, $limit = 10) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        
        //1. 首先获取lastId 在集合中的索引位置
        $start_pos = 0;
        if ($lastId > 0) {
            $start_pos = $this->zGetByRank($id, $lastId);
            
            //如果找不到则重置为0
            if (empty($start_pos)) $start_pos = 0;
        }
        
        //2. 在获取lastId 索引位置往后limit 的元素
        
        return $this->zRevRange($redis_key, $start_pos + 1, $start_pos + $limit, true);
    }
    
    /**
     * 回key对应的有序集合中member元素的索引值, 使用ZREVRANK可以获得从高到低排列的元素的rank
     * @param $id
     * @param $offset = 上次查询的id,默认为0 
     * @param $limit
     */
    public function zGetByRank($id, $lastId = 0) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        return $this->zRevRank($redis_key, $lastId);
    }    
    
    
    /**
     * 判断是否已经存储最大值.
     * @param $id
     * @param $limit
     */
    public function isMaxSize($id) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        $size = $this->zSize($redis_key);
        
        return ($size >= $this->zset_max_size) ? true : false;
    }        
    
    /**
     * 添加单个有序集合列表
     * @param $id
     * @param $score : 有序集合的score值
     * $param $val
     */
    public function zSet($id, $score, $value) {
        if(empty($id) || empty($value)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);

        $add_nums = $this->zAdd($redis_key, $score, $value);
        
        //如果添加成功，修整有序集合的长度
        if($add_nums > 0) {
            $this->resizeZset($id);
        }
        
        return $add_nums ? $add_nums : false;
    }    
    
	/**
     * 添加多个有序集合列表
     * @param $values	array  数据格式:<p>
     * array(<p>
     * 		0 => array(
     * 				'value' => '动态id',
     * 				'score' => '动态的添加时间'
     * 			),
     * 		1 => array(
     * 				'value' => '动态id',
     * 				'score' => '动态的添加时间'
     * 			),
     * )
     * 
     */
    public function zSets($id, $values) {
        if(empty($id) || !is_array($values)) {
            return false;
        }
        
        
        //分组处理，每次批量处理最多200个命令
        $chunk_arr = array_chunk($values, 200, true);
        unset($values);
        
        $redis_key = $this->getKey($id);
        
        $add_nums = 0;
        $class_code_list = array();
        foreach($chunk_arr as $key => $chunk_list) {
            $pipe = $this->multi(Redis::PIPELINE);
            foreach($chunk_list as $datas) {
                
                $score = $datas['score'];
                $value = $datas['value'];
                if(empty($value) || empty($score)) {
                    continue;
                }
                                
                $pipe->zAdd($redis_key, $score, $value);
            }
            $replies = $pipe->exec();
            
            $add_nums += intval($this->getPipeSuccessNums($replies));
            
            unset($chunk_arr[$key]);
        }
        
        //如果添加成功，修整有序集合的长度
        if ($add_nums > 0) {
            $this->resizeZset($id);
        }
        
        return $add_nums ? $add_nums : false;
    }     
    

    /**
     * 获取有序集合中最小的id值
     * @param $class_code
     */
    public function zGetLastId($id) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        $result = $this->zRange($redis_key, 0, 0);
        $last_id = !empty($result) ? reset($result) : 0;
        
        return $last_id;
    }

    /**
     * 删除指定集合中的某些值
     * @param $class_code
     * @param $feed_ids
     */
    public function delZsets($id, $values) {
        if(empty($id) || empty($values)) {
            return false;
        }
        
        $values = array_unique((array)$values);
        $redids_key = RedisFeedKey::getClassFeedAllZsetKey($id);
        
        $pipe = $this->multi(Redis::PIPELINE);
        foreach($values as $value) {
           $pipe->zDelete($redids_key, $value);
        }
        $replies = $pipe->exec();
        $delete_nums = $this->getPipeSuccessNums($replies);
        
        return $delete_nums ? $delete_nums : false;
    }
    
    /**
     * 清除有序集合中多余的元素，使集合的长度保持一定
     * @param $id
     */
    private function resizeZset($id) {
        if(empty($id)) {
            return false;
        }
        
        $redis_key = $this->getKey($id);
        
        $size = $this->zSize($redis_key);
        if($size > $this->zset_max_size) {
            return $this->zRemRangeByRank($redis_key, 0, $size - $this->zset_max_size - 1);
        }
        
        return true;
    }    
    
    
    
}