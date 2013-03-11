<?php
/**
 * Gearman异步队列客户端
 * @author lnczx
 */

define('PRIORITY_NORMAL', 'normal');
define('PRIORITY_HIGH', 'high');
define('PRIORITY_LOW', 'low');

class Gearman {
    
    /**
     * Run a task in the background
     * Translates calls to particular method by priority
     *
     * @param string $functionName A registered function the worker is to execute
     * @param string $workload     Data to be processed
     * @param string $priority     Priority the task should be performed with. May be one of 'normal', 'low' or 'high'
     * @param bool $background     Whether job should be performed in background mode
     *
     * @return string The job handle for the submitted task or task result for foregroung jobs
     */
    public static function send($functionName, $workload = '', $priority = PRIORITY_NORMAL, $background = true) {

        if (empty($functionName)) {
            return false;
        }
        $args = array($functionName, $workload);
        
        $gmclient = GearmanIo::getInstance();
        switch ($priority) {
        case PRIORITY_LOW:
            $doMethod = 'doLow';
            break;
        case PRIORITY_HIGH:
            $doMethod = 'doHigh';
            break;
        default:
            $doMethod = !$background && method_exists($gmclient, 'doNormal') ? 'doNormal' : 'do';
            break;
        }

        if ($background) {
            $doMethod .= 'Background';
        }


        try {
            $result = call_user_func_array(array($gmclient, $doMethod), $args);
            return $result;
        } catch (Exception $e) {
            if(C('LOG_RECORD')) Log::write('Job submission failed:',  $e->getMessage(),Log::ERR);
            return false;
        }

    }    
    
    
}