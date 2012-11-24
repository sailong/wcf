<?php
class EchoExcel{
    public function savexlsx($titarr,$valarr){
        header("content-type:application/vnd.ms-excel");
        header("content-disposition:attachment;filename=echoexcel.xls" );
        foreach($titarr as $key=>$val) {
            echo iconv("utf-8", "gbk",$val)."\t";
        }
        echo "\n";
        foreach($valarr as $key=>$val) {
            foreach($val as $val1) {
               echo iconv("utf-8", "gbk",$val1)."\t";
            }
            echo "\n";
        }
    }
}
?>
