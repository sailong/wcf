<?php
/**
 * 单例模式下的phpredis实现
 * @author $anlicheng 2012-11-26
 */
class RedisIo {
    const timeOut = 3; //设置默认3秒超时
    private static $instance = null;    //redis实例
    
    private function __construct() {
        //加载配置文件
        $redis_config = C('REDIS_INFO');
        //如果没有配置使用默认配置
        if(empty($redis_config)) {
            $redis_config = array(
                'host' => '127.0.0.1',
                'port' => 6379,
            );
        }
        
        if (!extension_loaded('phpredis')) {
            if(C('LOG_RECORD')) Log::write('The phpredis extension is required.',Log::ERR);
        }           
        
        try {
            $redis = new Redis();
            $redis->connect($redis_config['host'], $redis_config['port'], self::timeOut);
            
            self::$instance = & $redis;
        } catch (Exception $e) {
            echo 'Caught Redis exception: ',  $e->getMessage(), "\n";
        }
    }
    
    private function __clone() {}
    
    /**
     * 获取redis的实例
     */
    public static function getInstance() {
        if(!isset(self::$instance) || is_null(self::$instance)){
            new self();
        }
        
        return self::$instance;
    }
}