# Summer-PHP-Deploy

## 起因
某种原因, 不能使用Jenkins, 于是用PHP写了一个自动部署脚本

## 项目介绍
- 用PHP写的部署脚本
- 支持在网页上操作部署
- 支持gitlab push hook, 精确到分支, 支持同时往多个目录部署
- 支持在命令行触发
- 支持部署后删除缓存
- 支持指定文件不部署


## 软件架构

### 准备
- 目录A, 用来存放git分支
- 目录B, 运行网站的代码目录
- 目录C, 部署用的脚本目录, C里边的脚本用来把A里边的代码更新同步到B里边
- gitlab, 添加push hook, hook中指定的url可以执行C中的代码
- 配置域名, 使浏览器可以访问到index_webui.php
- php version 5.4+


### 目录C中的文件说明:
|文件名|作用|
|:---|:---|
|index_webui.php|入口1: 通过网页界面操作触发的部署|
|index_webhook.php|入口2: gitlab push hook触发的部署|
|index_cli.php|入口3: 命令行部署|
|Tool.php|工具类, 用来拉取最新代码, 找出差异, 同步代码到目标目录|
|msg.log|用来存放部署过程中的输出或错误信息|
|scripts/ |存放不同项目的部署脚本|

### 命令行部署使用举例:
- php index_cli.php -p=dev
- php index_cli.php -p=dev -v=1234567..89abcde

### 命令行参数说明: 
- 命令 "index_cli.php -p=dev" 是指index_cli.php调用scripts/dev.php 进行部署, 把git最新的改动同步到目标目录
- 命令 "index_cli.php -p=dev -v=1234567..89abcde " 是指index_cli.php调用scripts/dev.php 把某分支从1234567到89abcde两次commit之间的变动同步到目标目录(commit_id:1234567 提交在前)
- scripts/xxx.php里定义了部署用的路径配置信息, 自己写部署脚本时请参考dev.php中的写法
- 其中-v后边的参数可以从 git pull 或  git log --merges 命令获取 

## 待开发功能
- 压缩备份, 回退, SQL部署
