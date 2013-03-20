<?php
/**
 * 类控制器
 * @author 杨益(yangyi@wmw.cn)
 * @copyright wmw.cn
 * @package Libraries
 * @since 2011-8-16
 */
require_once('ModelProxy.class.php');
class ClsFactory {

    /**
     * 创建一个类实例,使用规则类似import,以后加入对模型层类的方法进行监控
     * @param   string  $class
     * @return  object
     */
    public static function Create($class) {
        $obj = self::CreateNoCheck($class);
        //通过代理类增加对调用函数的监控
        if(self::needProxy($class)) {
            $obj = new ModelProxy($obj);
        }
        
        return $obj;
    }
    
    /**
     * 判断是否是需要代理的模块
     * @param $module
     */
    private static function needProxy($class) {
        $allow_proxy_modules = array(
            'model',
        );
        
        $class_strut = self::parseClassStrut($class);
        if($class_strut[0] === '@') {
            array_shift($class_strut);
        }
        
        return in_array(strtolower($class_strut[0]), $allow_proxy_modules) ? true : false;
    }

    /**
     * 创建一个类实例（不检测是否曾创建过）
     * @param   string  $class
     * @return  object
     */
    public static function CreateNoCheck($class) {
       $res = import($class); 
       if(!$res) {
           return false;
       }
       
       $class_strut = self::parseClassStrut($class);
       $classname = end($class_strut);
       $obj = new $classname;
       
       return $obj;
    }
    
    /*****************************************************************
     * 辅助方法
     ****************************************************************/
    /**
     * 预处理，解析别名相关的信息
     * @param $class
     */
    protected static function parseClassStrut($class) {
        $class_str = str_replace(array('.', '#'), array('/', '.'), $class);
        
        return explode('/', $class_str);
    }
}