<?php

class test1
{
    public function deploy(& $tool, $strCommitIds='')
    {
        $srcPath = '/www/develop'; //存放最新develop分支的目录
        $targetPath = '/www/weimai2'; //需要部署的目标目录
        $targetCachePath = array($targetPath . '/cache'); //目标目录的缓存目录, 每次部署会清空其内的文件

        //关键文件,不能被删除或修改
        $ignoreFiles = array(
//            $targetPath.'/config.php',
            $targetPath.'/index.php',
            $targetPath.'/production.php',
            $targetPath.'/preissue.php',
        );
        
        if (!empty($strCommitIds)) {
            $tool->ini($srcPath, $targetPath, $targetCachePath, $ignoreFiles)
                ->setCommitIds($strCommitIds) //指定commit版本部署
                ->gitDiff() //获取变化
                ->deploy() //同步到目标目录
                ->clearCache() //清除指定的缓存目录
                ->over(); //备用, 可以做一些收尾工作
        } else {
            $tool->ini($srcPath, $targetPath, $targetCachePath, $ignoreFiles)
                ->gitPull() //拉取最新代码
                ->gitDiff() //获取变化
                ->deploy() //同步到目标目录
                ->clearCache() //清除指定的缓存目录
                ->over(); //备用, 可以做一些收尾工作
        }
        
    }
}

