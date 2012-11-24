<?php
/**
 * 
 * @author $anlicheng
 * 注明：
 * 1. 为了使phpredis的功能更加灵活和满足不同的应用需求，采用的是继承的方式；
 *
 */
class rBase extends Redis {
    private $redis_config = array(); //redis配置    
        
    public function __construct() {
        parent::__construct();
        $this->initConfig();

        $link = $this->pconnect($this->redis_config['host'], $this->redis_config['port']);
    }
    
    /**
     *初始化redis的服务器的相关配置信息 
     */
    private function initConfig() {
        $redis_info = C('REDIS_INFO');
        //如果没有配置使用默认配置
        if(empty($redis_info)) {
            $redis_info = array(
                'host' => '127.0.0.1',
                'port' => 6379,
            );
        }
        
        $this->redis_config = $redis_info;
    }
}