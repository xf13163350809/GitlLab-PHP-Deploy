<?php
/**
 * 适用于gitlab触发的 push hook 部署
 */

//定义部署脚本路径
$deployDir = dirname(__FILE__); //默认部署脚本跟入口文件在同一目录
$scriptDir = $deployDir.'/scripts';

//加载工具类, Tool.php
include_once($deployDir.'/Tool.php');
$tool = new Tool();
$tool->logPath = $deployDir.'/log/msg'.date('Y-m-d').'.log';

//读取gitlab hook发送的数据
$content = file_get_contents('php://input', 'r');

$tool->filelog($content);
$obj = json_decode($content);
if (empty($obj)) {
    $tool->filelog('json数据解析失败!');
}

//只对下边列出的git版本库以及指定分支生效
//格式: '版本库=>分支名' => '部署用的脚本名' (值可以是数组, 这样就可以一次push多目录部署)
$map = array(
    //测试用
//    'git@192.168.1.209:9898/operation/xiaoketang_service.git=>refs/heads/test' => 'xiaoketang',
    'http://192.168.1.209:9898/operation/xiaoketang_service.git=>refs/heads/test'=> 'test1'
);

//push钩子, 通过gitlab的merge request功能也有push操作
if (!empty($obj->object_kind) && $obj->object_kind == 'push') {
    $tool->filelog('push 事件开始: ');
    
    $repoUrl = $obj->repository->git_http_url; //版本库地址
    $branch = $obj->ref; //分支名
    $key = $repoUrl.'=>'.$branch;

    $tool->filelog('key: '.$key);
    
    if (array_key_exists($key, $map)) {
        $script = $map[$key];
        if (is_string($script)) {
            $scriptFile = "{$scriptDir}/{$script}.php"; //子部署脚本
            include($scriptFile);
            $obj = new $script;
            $obj->deploy($tool);
            
        } elseif (is_array($script)) {
            foreach ($script as $s) {
                $scriptFile = "{$scriptDir}/{$s}.php";
                include($scriptFile);
                $obj = new $s;
                $obj->deploy($tool);
            }
        }
        
    } else {
        $tool->filelog("{$key} 不存在.");
    }
    
    $tool->filelog('push 事件处理完毕.');

}

$tool->filelog('结束.', TRUE);