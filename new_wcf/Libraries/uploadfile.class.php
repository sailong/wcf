<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 我们网 项目的一部分
//
// Copyright (c) 2005 - 2011 www.wmw.cn
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 COPYRIGHT 文件，
// 或者访问 http://www.wmw.cn/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * FILE_NAME : uploadfile.class.php   FILE_PATH : /libraries/uploadfile.class.php
 * ....上传文件操作类
 *
 * @copyright Copyright (c) 2005 - 2011 www.wmw.cn
 * @author javie jvsys999@hotmail.com
 * @package 
 * @subpackage 
 */

class uploadfile
{

    public $path;//文件的绝对路径
    public $subpath;//文件的相对路径
    public $allow_type = array
        (
         0 => "jpg",
         1 => "gif",
         2 => "png"
        );
    public $max_size = "2048";//上传文件最大允许路径
    public $overwrite = false;//如果有相同文件存在，是否覆盖
    public $renamed = false;//是否重新命名上传文件
    public $ifresize = false;//是否生成缩略图
    public $resize_width;//缩略图宽
    public $resize_height;//缩略高
    public $cut;
    public $upfile = array();
    public $ext = "";
    public $errormsg;
    public $filename;
    public $error_mode = 0;
    //上传附件目录
    public $attachmentspath;

    //重命名后的名字, 不包含扩展名
    public $newname;

    /** 
     * 初始化类
     *
     */
    function __construct($arrFile = array())
    {
        $this->_set_options($arrFile);
    }

    /** 
     * 上传文件
     * @param $field 上传文件表单名称
     * @param $options 上传配制文件属性数组
     * @return boolean
     */
    function upfile($field, $options = array())
    {
        if ($_FILES[$field]['tmp_name'] == "")
        {
            return "";
        }
        $this->_set_options($options);
        $this->_set_upfile($field);
        $this->_check();
        $this->path = $this->_set_path();
        $this->_set_filename();
        if (@copy($this->upfile['tmp_name'], $this->upfile['filename']))
        {
            //增加自定义图片文件名称的图片格式转换功能
            include_once('ConvertImage.class.php');
            $obj = new ConvertImage($this->upfile['filename']);
            if ($obj->isAllowType($this->ext)) {

                $this->_get_ext($this->upfile['name']);

                $tmp_name = substr($this->upfile['filename'], 0, strrpos($this->upfile['filename'], '.')).'.jpg';
                $obj->imageJpeg($tmp_name);
                if ($this->upfile['filename'] != $tmp_name) unlink($this->upfile['filename']);
                $this->upfile['getfilename'] = $this->upfile['filename'] = $tmp_name;
                $this->ext = 'jpg';
            }

            if ($this->ifresize == true && ($this->ext == "gif" || $this->ext == "jpg" || $this->ext == "png"))
            {
                include_once("resizeimage.class.php");
                $obj_small = new resizeimage($this->upfile['filename'], $this->resize_width, $this->resize_height, $this->cut);
                $this->upfile['getsmallfilename'] = $obj_small->dstimg;
            }
            return $this->upfile;
        }
        $this->error("upfile():".$this->upfile['filename']."上传失败。");
        return false;
    }

    /** 
     * 取得错误信息
     * @param void
     * @return boolean
     */
    function get_error()
    {
        if ($this->errormsg)
        {
            return $this->errormsg;
        }
        return false;
    }

    /** 
     * 生成上传文件带路径的文件名
     * @param void
     * @return void
     */
    function _set_filename()
    {
        if ($this->filename)
        {
            if (file_exists($this->filename))
            {
                @unlink($this->filename);
            }
            $this->upfile['filename'] = $this->filename;
            return true;
        }
        if (!file_exists($this->path))
        {
            $this->_mkpath($this->path);
        }
        if ($this->path[strlen($this->path) - 1] != "/")
        {
            $this->path .= "/";
        }
        if ($this->renamed && !empty($this->newname))
        {
            $upFileName = $this->newname.'.'.$this->ext;
            //如果重命名则覆盖以前的文件
            $this->overwrite = true;
        }
        else
        {
            $fileExt = $this->ext;
            if (empty($fileExt))
            {
                $fileExt = $this->_get_ext($this->upfile['name']);
            }
            $upFileName = uniqid(time()).".".$fileExt;
        }
        if (!$this->overwrite)
        {
            $i = 1;
            while (file_exists($this->path.$upFileName))
            {
                $pre_name = substr($upFileName, 0,strrpos($upFileName, '.'));
                $upFileName =$pre_name."_".$i.'.'.$this->ext;
                ++$i;
            }
        }
        else if (file_exists($this->path.$upFileName))
        {
            @unlink($this->path.$upFileName);
        }
        $this->upfile['filename'] = $this->path.$upFileName;
        $this->upfile['getfilename'] = $this->subpath."/".$upFileName;
        $this->upfile['ext'] = $this->ext;//扩展名
    }

    /** 
     * 检查上传文件
     *
     */
    function _check()
    {
        $this->_check_size();
        $this->_check_type();
    }

    /** 
     * 配制上传文件相关属性
     * @param $arrFile 上传文件配制数组
     * @return void
     */
    function _set_options($arrFile)
    {
        if (is_array($arrFile))
        {
            foreach ($arrFile as $key => $value)
            {
                if (in_array($key, array("path", "allow_type", "max_size", "overwrite", "renamed", "error_mode", "filename", "ifresize", "resize_width", "resize_height", "cut", "attachmentspath", "uploadsavetype", "newname")))
                {
                    $this->$key = $value;
                }
            }
        }
    }

    /** 
     * 检查文件类型
     * @param void
     * @return void
     */
    function _check_type()
    {
        $this->_get_ext($this->upfile['name']);
        if (in_array($this->ext, $this->allow_type))
        {
            $fileType = $this->upfile['type'];
            if (empty($fileType))
            {
                return true;
            }
            $this->_check_mine($fileType);
        }
        else
        {
            $this->error("_check_type():".$this->upfile['name']."文件类型".$this->ext."不符合。只允许上传".implode(",", $this->allow_type));
        }
    }

    /** 
     * 检查文件mine类型
     * @param $mine mine类型名
     * @return void
     */
    function _check_mine($mine)
    {
        require("filetype.php");
        $pass = false;
        foreach ($this->allow_type as $type)
        {
            if (is_array($filetype[$type]))
            {
                if (!in_array($mine, $filetype[$type]))
                {
                    continue;
                }
                $pass = true;
            }
            else
            {
                if (!($filetype[$type] == $mine))
                {
                    continue;
                }
                $pass = true;
            }
            break;
        }
        //echo $this->upfile['type'];exit;//测试文件mine类型
        if (!$pass)
        {
            $this->error("_check_mine():".$this->upfile['name']."文件mine类型不符合。只允许上传".implode(",", $this->allow_type));
        }
    }

    /** 
     * 获取上传文件扩展名
     * @param $filename 
     * @return string
     */
    function _get_ext($fileName)
    {
        $array = explode(".", $fileName);
        $array = $array[count($array) - 1];
        $this->ext = strtolower($array);
        return $this->ext;
    }

    /** 
     * 检查文件大小
     * @param void
     * @return void
     */
    function _check_size()
    {
        if ($this->max_size * 1024 < $this->upfile['size'])
        {
            $this->error("_check_size()：".$this->upfile['name']."文件大小超过了限制".$this->max_size."KB");
        }
    }

    /** 
     * 取得上传文件的数组
     * @param $name 表单名
     * @return void
     */
    function _set_upfile($name)
    {
        if (!$_FILES[$name])
        {
            $this->error("_set_upfile()：文件不存在！");
        }
        $this->upfile = $_FILES[$name];
    }

    /** 
     * 设置属性名和值
     * @param $name 属性名
     * @param $value 属性值
     * @return void
     */
    function __set($name, $value)
    {
        $this->$name = $value;
    }

    /** 
     * 创建上传文件目录
     * @param $path 上传文件目录
     * @param $mode 权限
     * @return boolean
     */
    function _mkpath($path, $mode = 0777)
    {
        $path = str_replace("\\", "/", $path);
        $arrPath = explode("/", $path);
        $num = count($arrPath);
        $path = "";
        for ($i = 0;	$i < $num;	++$i)
        {
            $path .= $arrPath[$i]."/";
            if (!empty($arrPath[$i]) && !file_exists($path) && !@mkdir($path, $mode))//上传目录不存在时创建目录
            {
                $this->error("_mkpath(),创建".$path."出错！因为我们为了分布式文件存储适应大规模商业应用，但是您的服务器或虚拟主机并不提供此类支持。");
            }
        }
        return true;
    }

    /** 
     * 格式化文件大小
     * @param $fileSize 文件大小
     * @return integer
     */
    function format_size($fileSize)
    {
        if ($fileSize < 1024)
        {
            return $fileSize."B";
        }
        if ($fileSize < 1048576)
        {
            return number_format((double)($fileSize / 1024), 2)."KB";
        }
        return number_format((double)($fileSize / 1048576), 2)."MB";
    }

    /** 
     * 显示错误信息
     * @param $msg 错误信息
     * @return void
     */
    function error($msg)
    {
        if ($this->error_mode == 0)
        {
            exit("ERROR : file ".__FILE__." function ".$msg);
        }
        $this->errormsg .= "ERROR : file ".__FILE__." function ".$msg."n";
    }

    /** 
     * 设置上传路径
     * @param void
     * @return string
     */
    function _set_path()
    {
        //如果自己定义名称
        if($this->renamed) {
            return $this->attachmentspath;
        }
        if ($this->path == "")
        {
            switch ($this->uploadsavetype)
            {
                case "1" :
                    $this->subpath = $this->attachmentspath."/".$this->ext;
                break;
                case "2" :
                    $this->subpath = $this->attachmentspath."/".$this->ext."/".date("Y", time());
                break;
                case "3" :
                    $this->subpath = $this->attachmentspath."/".$this->ext."/".date("Y", time())."/".date("m", time());
                break;
                case "4" :
                    $this->subpath = $this->attachmentspath."/".$this->ext."/".date("Y", time())."/".date("m", time())."/".date("d", time());
                break;
                default :
                $this->subpath = $this->attachmentspath."/".$this->ext."/".date("Y", time())."/".date("m", time())."/".date("d", time());
                break;
            }
            return $this->subpath;
        }
        return $this->path;
    }

}

?>
