<?php
/**
 * 用于格式化tab
 * 
 * @author qining
 * @category typecho
 * @package default
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/** 定义程序运行的根目录 */
define('ROOT_DIR', '../admin');

define('REPLACE', "/\\\$request-\>isSetParameter\('([^']+)'\)/is");

function replaceCallback($matches)
{
    return 'isset($request->'  . $matches[1] . ')';
}


//获取一个目录下的文件
function mgGetFile($inpath,$trim = false,$stamp = NULL)
{
    $files = array();
    if(!is_dir($inpath))
    {
        return $files;
    }
    
    $it = new DirectoryIterator($inpath);
    $stamp = (NULL === $stamp) ? NULL : explode("|",$stamp);

    foreach($it as $file) 
    {
        if($file->isFile())
        {
            $file = $file->__toString();
            $fileExt = (false === ($pos = strrpos($file,'.'))) ? NULL : substr($file,$pos + 1);
            $fileName = (false === $pos) ? $file : substr($file,0,$pos);
            
            if(NULL !== $stamp && in_array($fileExt,$stamp))
            {
                $files[] = $trim ? $file : $fileName;
            }
            else if(NULL === $stamp)
            {
                $files[] = $trim ? $file : $fileName;
            }
        }
    }
    return $files;
}

//获取一个目录下的目录
function mgGetDir($inpath)
{
    $dirs = array();
    
    if(!is_dir($inpath))
    {
        return $dirs;
    }
    $it = new DirectoryIterator($inpath);
    
    foreach($it as $dir)
    {
        if($dir->isDir() && !$dir->isDot()) 
        {
            $dirs[] = $dir->__toString();
        }
    }
    return $dirs;
}

function replace($dir = ROOT_DIR)
{
    if($files = mgGetFile($dir, true, 'php'))
    {
        foreach($files as $file)
        {
            if($contents = file_get_contents($dir . '/' . $file))
            {
                $contents = preg_replace_callback(REPLACE, 'replaceCallback', $contents);
                
                //echo $contents;
                
                file_put_contents($dir . '/' . $file, $contents);
            }
        }
    }
    
    
    if($dirs = mgGetDir($dir))
    {
        foreach($dirs as $indir)
        {
            replace($dir . '/' . $indir);
        }
    }
}

replace();
