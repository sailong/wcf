<?php
/**
 * Gearman Connect 封装实现 - 单例
 * @author lnc 2012-11-27
 */
class GearmanIo {
    
    /**
     * @var \GearmanClient|\GearmanWorker wrapped instance
     */
    private static $gm_instance = null;    
    
    /**
     * @var int Value will be passed to wrapped GearmanClient(Worker)::setTimeout();
     */
    public $timeout = 1000;

    /**
     * @var Array of gearman options to be applied to wrapped instance
     */
    public $options;    
    
    
    /** 
     * 创建client对象，添加job服务器 
     * @return void 
     */    
    private function __construct() {
        //加载配置文件
        $config = C('GEARMAN_INFO');
        //如果没有配置使用默认配置
        if(empty($config)) {
            $config = array(
                'host' => '127.0.0.1',
                'port' => 4730,
            );
        }

        if (!extension_loaded('gearman')) {
            if(C('LOG_RECORD')) Log::write('The PECL::gearman extension is required.',Log::ERR);
            throw_exception('The PECL::gearman extension is required.');
        }   
        
        try {
            $gm = new GearmanClient();

            foreach ($config as $value) {   
                $host = trim(strval($value['host']));  
                $port = array_key_exists('port', $value) ? intval($value['port']) : 4730;  
                if(!$this->check_conn($host, $port)){  
                    continue;  
                } else {  
                    $gm->addServer($host,$port);
                }
            }
            $gm->setTimeout($this->timeout);

            self::$gm_instance = & $gm;
        } catch (Exception $e) {
            if(C('LOG_RECORD')) Log::write('Caught GearmanClient exception: ',  $e->getMessage(),Log::ERR);
            throw_exception('Caught GearmanClient exception: ');
        }
    }
    
    private function __clone() {}
    
    /**
     * 获取redis的实例
     */
    public static function getInstance() {
        if(!isset(self::$gm_instance) || is_null(self::$gm_instance)){
            new self();
        }
        
        return self::$gm_instance;
    }
    
    /** 
     * 网络检测 
     * @param   string  机器IP 
     * @param   string  机器port 
     * @return  bool            
     */  
    function check_conn($ip, $port = 4730)  
    {  
        // socket链接测试,200ms超时  
        @$fp = fsockopen($ip, $port, $errno, $errstr, 0.2);   
        if ($fp){         
            $fp && fclose($fp);  
            return true;     
        } else {  
            return false;     
        }  
    }      
    
}