<?php

class Tool
{
    public $srcPath = ''; //部署用的git最新代码目录
    public $targetPath = ''; //部署的目标目录
    public $targetCachePath = ''; //目标目录的缓存地址
    public $ignoreFiles = array(); //哪些文件是不需要被修改的
    public $logPath = ''; //部署过程中记录日志的文件
    public $logInfo = array();

    public $startCommitId = ''; //最近一次commit id
    public $endCommitId = ''; //上次合并时最后一次commit id
    
    public $addedFileList = array(); //git pull 添加的文件
    public $changedFileList = array(); //git pull 修改的文件
    public $deletedFileList = array(); //git pull 删除的文件
    
    public $unknown = array(); //未知文件改动
    public $isShowResult = false; //是否用echo显示出部署结果信息
    
    //目标文件的权限信息
    public $group = 'apache';
    public $user = 'apache';
    public $mode = '755';
    public $modex= 0755;
    
    public function __construct()
    {}
    
    public function ini($srcPath, $targetPath, $targetCachePath, $ignoreFiles)
    {
        $this->srcPath         = $srcPath;
        $this->targetPath      = $targetPath;
        $this->targetCachePath = $targetCachePath;
        $this->ignoreFiles     = $ignoreFiles;
    
        $str = date('Y-m-d H:i:s')." 开始部署: {$this->srcPath} -> {$this->targetPath}";
        $this->filelog($str);
        if ($this->isShowResult) {
            echo "<br><br><label style='color: forestgreen'>$str</label>";
        }
        
        return $this;
    }
    
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }
    
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
    
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }
    
    public function setCommitIds($strCommitIds)
    {
        if (empty($strCommitIds)) {
            return $this;
        }
        preg_match('/^([a-z0-9]+)\.\.([a-z0-9]+)$/', $strCommitIds, $matches);
        if (!empty($matches[1]) && !empty($matches[2])) {
            $this->startCommitId = $matches[1];
            $this->endCommitId = $matches[2];
        } else {
            $str = "commit id 格式错误, 必须得是两个点号隔开的同一个分支的两个commit id: {$strCommitIds}";
            $this->filelog($str, TRUE);
            if ($this->isShowResult) {
                echo "<br><label style='color: firebrick'>{$str}</label><br>";
        }
    }
    
        return $this;
    }
    
	//拉取最新代码
    public function gitPull()
    {
        //拉取最新代码
        //exec("git init");
        $command = "cd {$this->srcPath} && git pull origin test";# 2>&1
        $rs = exec($command, $output,$return_code);
        $this->filelog($command, TRUE);
        $this->filelog(implode(PHP_EOL, $output));
        if ($rs == 'Already up-to-date.') {
            $this->filelog('没有更新, 结束.', TRUE);
            if ($this->isShowResult) {
                echo '<br>没有更新<br>';
            }
            exit;
        } elseif (strpos($output[0], 'Updating') !== FALSE) {
            preg_match('/Updating\s([a-z0-9]+)\.\.([a-z0-9]+)/', $output[0], $matches);
            if (!empty($matches[1]) && !empty($matches[2])) {
                $this->startCommitId = $matches[1];
                $this->endCommitId = $matches[2];
                if ($this->isShowResult) {
                    echo "<br>{$matches[0]}<br>";
                }
            }
        } else {
            $this->filelog('出问题了 '.$command, TRUE);
        }
        return $this;
    }
    
    public function gitDiff()
    {
        $this->filelog('获取差异: ');
        $command = "cd {$this->srcPath} && git diff --name-status {$this->startCommitId} {$this->endCommitId} 2>&1";
        exec($command, $output);
        $this->filelog($command);
        $this->filelog(implode(PHP_EOL, $output));
    
        foreach ($output as $v) {
            $arr = explode("\t", $v);
            $path = $this->srcPath . '/'. $arr[1];
            switch ($arr[0]) {
                case 'A':
                    $this->addedFileList[] = $path;
                    break;
                case 'M':
                    $this->changedFileList[] = $path;
                    break;
                case 'D':
                    $this->deletedFileList[] = $path;
                    break;
                default:
                    $this->unknown[] = $v;
                    break;
            }
        }
                    
        if (!empty($this->unknown)) {
            $this->filelog('发现未知类型的改动:');
            $this->filelog(implode(PHP_EOL, $this->unknown));
        }
        
        return $this;
    }
    
	//将有改动的文件同步到目标目录
    public function deploy()
    {
        $this->filelog("开始同步到 {$this->targetPath} 目录");
        $result = array(); //记录结果
        //添加目标文件
        foreach ($this->addedFileList as $srcFile) {
            $targetFile = str_replace($this->srcPath, $this->targetPath, $srcFile);
            
            $targetFileDir = dirname($targetFile);
            if (!file_exists($targetFileDir)) {
                mkdir($targetFileDir, $this->modex, TRUE); //创建目录
                
                $command1 = " chown -R {$this->group}:{$this->user} {$targetFileDir}";
                $command2 = " chmod -R {$this->mode} {$targetFileDir}";
                exec($command1);
                exec($command2);
            }
        
            $command = "cp -ar {$srcFile} {$targetFile}";
            exec($command, $output, $status);
        
            if ($status == 0) {
                $result[] = "{$targetFile} 添加成功.";
                $command1 = " chown -R {$this->group}:{$this->user} {$targetFile}";
                $command2 = " chmod -R {$this->mode} {$targetFile}";
                exec($command1);
                exec($command2);
            } else {
                $output = implode(' ', $output);
                $result[] = "{$targetFile} 添加失败. 命令: {$command}";
            }
        }
        //覆盖目标文件
        foreach ($this->changedFileList as $srcFile) {
            $targetFile = str_replace($this->srcPath, $this->targetPath, $srcFile);
            if (in_array($targetFile, $this->ignoreFiles)) {
                $result[] = "{$targetFile} 修改失败, 不能修改此文件.";
                continue;
            }
        
            $targetFileDir = dirname($targetFile);
            if (!file_exists($targetFileDir)) {
                mkdir($targetFileDir, $this->modex, TRUE); //创建目录
    
                $command1 = " chown -R {$this->group}:{$this->user} {$targetFileDir}";
                $command2 = " chmod -R {$this->mode} {$targetFileDir}";
                exec($command1);
                exec($command2);
            }
        
            $command = "cp -ar {$srcFile} {$targetFile} 2>&1";
            exec($command, $output, $status);
            if ($status == 0) {
                $result[] = "{$targetFile} 修改成功.";
                $command1 = " chown -R {$this->group}:{$this->user} {$targetFile}";
                $command2 = " chmod -R {$this->mode} {$targetFile}";
                exec($command1);
                exec($command2);
            } else {
                $output = implode(' ', $output);
                $result[] = "{$targetFile} 修改失败. 命令: {$command}";
            }
        
        }
        //删除目标文件
        foreach ($this->deletedFileList as $srcFile) {
            $targetFile = str_replace($this->srcPath, $this->targetPath, $srcFile);
            if (in_array($targetFile, $this->ignoreFiles)) {
                $result[] = "{$targetFile} 删除失败, 不能删除此文件.";
                continue;
            }
        
            if (file_exists($targetFile)) {
                $command = " rm -f {$targetFile}";
                exec($command, $output, $status);
                if ($status == 0) {
                    $result[] = "{$targetFile} 删除成功.";
                } else {
                    $output = implode(' ', $output);
                    $result[] = "{$targetFile} 删除失败.  命令: {$command}";
                }
            } else {
                $result[] = "{$targetFile} 文件不存在, 删除成功.";
            }
        }
    
        if (empty($result)) {
            $this->filelog('本次部署没有文件发生变化.');
        } else {
            $this->filelog(implode(PHP_EOL, $result));
        }
    
        if ($this->isShowResult == TRUE) {
            if (empty($result)) {
                echo '<br>本次部署没有文件发生变化.<br>';
            } else {
                echo '<br>'.implode('<br>', $result).'<br>';
            }
        }
    
        return $this;
    }
    
	//清除目标目录的缓存文件夹
    public function clearCache()
    {
        if (is_string($this->targetCachePath)) {
            $this->targetCachePath = array($this->targetCachePath);
        }
        
        foreach ($this->targetCachePath as $cache) {
            if (!empty($cache) && file_exists($cache)) {
                
                if (substr_count($cache, '/') > 3) {
                    system(" rm -rf {$cache}");
                    $this->filelog('删除缓存目录: '.$cache);
                    
                } else {
                    $this->filelog('删除缓存目录: 路径太短, 请手工删除. '.$cache);
                }
            }
        }
    
        return $this;
    }
    
    //收尾的一些操作
    public function over()
    {
        $this->filelog('结束.', TRUE);
        
        $this->srcPath = '';
        $this->targetPath = '';
        $this->targetCachePath = '';
        $this->ignoreFiles = array();
        $this->logInfo = array();
        
        $this->startCommitId = ''; //最近一次commit id
        $this->endCommitId = ''; //上次合并时最后一次commit id
        
        $this->addedFileList = array(); //git pull 添加的文件
        $this->changedFileList = array(); //git pull 修改的文件
        $this->deletedFileList = array(); //git pull 删除的文件
        
        $this->unknown = array(); //未知文件改动
        $this->isShowResult = false; //是否用echo显示出部署结果信息
    }
    
    function filelog($text, $isWrite = FALSE)
    {
        if (!is_string($text)) {
            $text = json_encode($text);
        }
        
        if ($isWrite == FALSE) {
            $this->logInfo[] = $text;
        } else {
            $this->logInfo[] = $text;
            $str = implode(PHP_EOL, $this->logInfo);
            $this->logInfo = array();
            file_put_contents($this->logPath, $str.PHP_EOL.PHP_EOL, FILE_APPEND);
        }
    }
}