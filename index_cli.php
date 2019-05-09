<?php
/**
 * 适用于命令行触发的部署
 */

//读取命令行参数
$arrArg = getopt('p:v::');
if (empty($arrArg['p'])) {
    echo '-p参数值不存在'.PHP_EOL."用法举例: php ./deploy.php -p dev".PHP_EOL;
}

//定义部署脚本路径
$deployDir = dirname(__FILE__); //默认部署脚本跟入口文件在同一目录
$scriptDir = $deployDir.'/scripts';

//加载工具类, Tool.php
include_once($deployDir.'/Tool.php');
$tool = new Tool();
$tool->logPath = $deployDir.'/msg.log';

//获取需要部署的项目名称, 也是项目的目录名
$strProjName = $arrArg['p'];
$strCommits = isset($arrArg['v']) ? $arrArg['v'] : '';

$scriptFile = "{$scriptDir}/{$strProjName}.php"; //子部署脚本

if (file_exists($scriptFile)) {
    include($scriptFile);
    $obj = new $strProjName;
    $obj->deploy($tool, $strCommits);
} else {
    echo "脚本: {$scriptFile} 不存在";
}

// 每5s执行一次
//for ($i = 0; $i < 10; $i++) {
//    //执行对应的部署脚本
//    echo date('Y-m-d H:i:s').PHP_EOL;
//    include("/usr/local/bin/deploy/{$strProjName}.php");
//    sleep(5);
//}
