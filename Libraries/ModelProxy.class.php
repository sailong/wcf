<?php
/**
 * 模型层代理类
 * @author 杨益(yangyi@wmw.cn)
 * @copyright wmw.cn
 * @package Libraries
 * @since 2011-8-17
 */

//模型层代理类，所有控制等调用模型层，都应该通过这个代理，这个代理，会在控制层使用类创建器创建模型层的时候，自动返回本代理的实例。
class ModelProxy {
    private $classname = null;   //被代理的模型层的类名
    private $obj = null;       //代理的模型层实例
	
//    public function __construct($classname) {
//        $this->classname = $classname;
//    }

    public function __construct($obj) {
        if(is_object($obj)) {
            $this->obj = & $obj;
            
            //通过对象反射获取类名
            $reflect = new ReflectionObject($this->obj);
            $this->classname = $reflect->getName();
        }
    }

    /**
     * 执行代理
     *
     * @param string $method 代理的方法
     * @param array $args 代理方法参数数组
     * @return false 或 执行代理的返回结果
     */
	private function execute($method, $args) {
        //if(!is_object($this->obj)) $this->obj = ClsFactory::CreateNoCheck($this->classname);
        if(!is_null($this->obj) && method_exists($this->obj, $method)) {
            return call_user_func_array(array($this->obj, $method), $args);
        } else {
            throw new Exception("[Fatal error], the method[{$this->classname}::$method] not exist!");
        }
    }

	public function __call($method, $args) {
        //开始执行
        $startTime = getmicrotime();
        $res = $this->execute($method, $args);

        $runTime = number_format(getmicrotime() - $startTime, 10);
        if($runTime > 0.2) {
            error_log($runTime.'|'.$this->classname.'|'.$method.'|'.$_SERVER['REQUEST_URI'].'|'.date('Y-m-d H:i:s')."\n", 3, LOG_METHOD_PATH . '/method_'.date('Y-m-d').'.log');
        }
        return $res;
    }
}
