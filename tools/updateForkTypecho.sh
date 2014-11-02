#!/bin/bash
#更新fork之后的Typecho至最新代码

#切换到主目录
cd ..

#增加Typecho源分支地址到本地项目远程分支列表
git remote add official https://github.com/typecho/typecho.git

#查看远程分支列表
#git remote -v

#更新Typecho源分支的新版本到本地
git fetch official

#合并Typecho源分支的代码
git merge official/master

#将合并后的代码push到你自己fork的Tyepcho项目上去
git push origin master
