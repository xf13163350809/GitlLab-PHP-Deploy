<html>
<head>
    <title>自动部署</title>
    <style>
        table {
            width: 90%;
            /* font-family: verdana,arial,sans-serif; */
            font-family: Consolas,verdana,arial;
            font-size:14px;
            color:#333333;
            border-width: 1px;
            border-color: #ddd;
            border-collapse: collapse;
        }
        
        table td {
            border-width: 1px;
            padding: 8px;
            border-style: solid;
            border-color: #ddd;
            background-color: #fff;
        }
    </style>
</head>
<body>
<h2>项目部署工具</h2>
<form action="" method="post">
    <input type="submit" value="开始部署"><br><br>
    <table>
        <tr>
            <td>
                选择项目<label style="color: firebrick">*</label>
            </td>
            <td>
                <?php
                    $scripts = scandir('./scripts');
                    foreach ($scripts as $s) {
                        if ($s != '.' && $s != '..' && strpos($s, '_test')===FALSE) { //包含_test的脚本不列出来
                            $s = str_replace('.php', '', $s);
                            echo "<input type='checkbox' name='script_name[]' value='{$s}'> {$s} <br>";
                        }
                    }
                ?>
            </td>
            <td>注: 可多选</td>
        </tr>
        <tr>
            <td>指定commit id</td>
            <td>
                <input type="text" name="commits" value="" style="width: 90%;height: 100%;">
            </td>
            <td>
                注:例如: 5583753..8087c06
                <br>执行git log 命令, 然后取相应的commit id的前7位, 组装成 commit_id1..commit_id2, 其中commit_id1要早于commit_id2提交
            </td>
        </tr>
    </table>
</form>
</body>
</html>
<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $commits = $_POST['commits'];
    $scriptNames = $_POST['script_name'];
    
    if (empty($scriptNames)) {
        exit('<br><label style="color: firebrick">未选定要部署的项目.</label><br>');
    }
    
    $deployPath = '.'; //TODO 这里需要改成自己项目的地址
    $scriptPath = "{$deployPath}/scripts";
    
    require_once ("{$deployPath}/Tool.php");
    
    
    foreach ($scriptNames as $script) {
        $scriptFile = $scriptPath."/{$script}.php";
        include($scriptFile);
    
        $tool = new Tool(); //每次重新new防止互相影响
        $tool->isShowResult = TRUE;
        
        $obj = new $script;
        $obj->deploy($tool, $commits);
    }
}

?>
