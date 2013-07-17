<?php

/** 参数不存在则退出 */
if (!isset($argv[1])) {
    echo 'no args';
    exit(1);
}

//获取一个目录下的文件
function mgGetFile($inpath, $trim = false,$stamp = NULL)
{
        $file = array();

        if(!is_dir($inpath))
        {
                return $file;
        }

        $handle=opendir($inpath);
        if(NULL != $stamp)
        {
                $stamp = explode("|",$stamp);
        }

        while ($tmp = readdir($handle)) 
        {
                if(file_exists($inpath."/".$tmp) && eregi("^([_@0-9a-zA-Z\x80-\xff\^\.\%-]{0,})[\.]([0-9a-zA-Z]{1,})$",$tmp,$file_name))
                {
                        if($stamp != NULL && in_array($file_name[2],$stamp))
                        {
                                $file[] = $trim ? $file_name[0] : $file_name[1];
                        }
                        else if($stamp == NULL)
                        {
                                $file[] = $trim ? $file_name[0] : $file_name[1];
                        }
                }
        }
        closedir($handle);
        return $file;
}

//获取一个目录下的目录
function mgGetDir($inpath)
{
        $handle=opendir($inpath);
        $dir = array();
        while ($tmp = readdir($handle))
        {
                if(is_dir($inpath."/".$tmp) && $tmp != ".." && $tmp != "." && 0 !== stripos($tmp,'.')) 
                {
                        $dir[] = $tmp;
                }
        }
        closedir($handle);
        return $dir;
}

function listFile($inpath, $stamp)
{
    $files = mgGetFile($inpath, true, $stamp);
    $dirs = mgGetDir($inpath);
    
    if ($dirs) {
        foreach ($dirs as $dir) {
            $files = array_merge($files, listFile($dir, $stamp));
        }
    }
    
    return $files;
}

echo implode("\n", listFile($argv[1], 'php'));
