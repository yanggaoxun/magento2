<?php
header("Content-type:application/vnd.ms-excel");
header("Content-Disposition:attachment;filename=Export_test.xls");
$tab="\t"; $br="\n";
$head="编号".$tab."备注".$br;
//输出内容如下：
echo $head.$br;
echo  "test321318312".$tab;
echo  "string1";
echo  $br;

echo  "330181199006061234".$tab;  //直接输出会被Excel识别为数字类型
echo  "number";
echo  $br;

echo  "=\"330181199006061234\"".$tab;  //原样输出需要处理
echo  "string2";
echo  $br;
?>