<?php

class WordpressToTypecho_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function doImport()
    {
        $options = $this->widget('Widget_Options');
        $dbConfig = $options->plugin('WordpressToTypecho');

        /** 初始化一个db */
        if (Typecho_Db_Adapter_Mysql::isAvailable()) {
            $db = new Typecho_Db('Mysql', $dbConfig->prefix);
        } else {
            $db = new Typecho_Db('Pdo_Mysql', $dbConfig->prefix);
        }
        
        /** 只读即可 */
        $db->addServer(array (
          'host' => $dbConfig->host,
          'user' => $dbConfig->user,
          'password' => $dbConfig->password,
          'charset' => 'utf8',
          'port' => $dbConfig->port,
          'database' => $dbConfig->database
        ), Typecho_Db::READ);
        
        /** 删除当前内容 */
        $masterDb = Typecho_Db::get();
        $this->widget('Widget_Abstract_Contents')->to($contents)->delete($masterDb->sql()->where('1 = 1'));
        $this->widget('Widget_Abstract_Comments')->to($comments)->delete($masterDb->sql()->where('1 = 1'));
        $this->widget('Widget_Abstract_Metas')->to($metas)->delete($masterDb->sql()->where('1 = 1'));
        $this->widget('Widget_Contents_Post_Edit')->to($edit);
        $masterDb->query($masterDb->delete('table.relationships')->where('1 = 1'));
        $userId = $this->widget('Widget_User')->uid;
        
        /** 获取时区偏移 */
        $gmtOffset = idate('Z');
        
        /** 转换全局变量 */
		/** 
        $rows = $db->fetchAll($db->select()->from('table.statics'));
        $static = array();
        foreach ($rows as $row) {
            $static[$row['static_name']] = $row['static_value'];
        }*/
        
        /** 转换文件 */
        /**$files = $db->fetchAll($db->select()->from('table.files'));
        if (!is_dir(__TYPECHO_ROOT_DIR__ . '/usr/uploads/')) {
            mkdir(__TYPECHO_ROOT_DIR__ . '/usr/uploads/', 0766);
        }
        
        $pattern = array();
        $replace = array();
        foreach ($files as $file) {
            $path = __TYPECHO_ROOT_DIR__ . '/data/upload/' . substr($file['file_guid'], 0, 2) . '/' .
            substr($file['file_guid'], 2, 2) . '/' . $file['file_guid'];
            
            if (is_file($path)) {
                $file['file_time'] = empty($file['file_time']) ? $options->gmtTime : $file['file_time'];
                $year = idate('Y', $file['file_time']);
                $month = idate('m', $file['file_time']);
                $day = idate('d', $file['file_time']);
                
                if (!is_dir(__TYPECHO_ROOT_DIR__ . "/usr/uploads/{$year}")) {
                    mkdir(__TYPECHO_ROOT_DIR__ . "/usr/uploads/{$year}", 0766);
                }
                
                if (!is_dir(__TYPECHO_ROOT_DIR__ . "/usr/uploads/{$year}/{$month}")) {
                    mkdir(__TYPECHO_ROOT_DIR__ . "/usr/uploads/{$year}/{$month}", 0766);
                }
                
                if (!is_dir(__TYPECHO_ROOT_DIR__ . "/usr/uploads/{$year}/{$month}/{$day}")) {
                    mkdir(__TYPECHO_ROOT_DIR__ . "/usr/uploads/{$year}/{$month}/{$day}", 0766);
                }
                
                $parts = explode('.', $file['file_name']);
                $ext = array_pop($parts);
                copy($path, __TYPECHO_ROOT_DIR__ . "/usr/uploads/{$year}/{$month}/{$day}/{$file['file_id']}.{$ext}");
                
                $new = Typecho_Common::url("/usr/uploads/{$year}/{$month}/{$day}/{$file['file_id']}.{$ext}", $options->siteUrl);
                $old = Typecho_Common::url("/res/{$file['file_id']}/{$file['file_name']}", $static['siteurl'] . '/index.php');
                $pattern[] = '/' . str_replace('\/index\.php', '(\/index\.php)?', preg_quote($old, '/')) . '/is';
                $replace[] = $new;
            }
        }
        */
        /** 转换评论 */
        $i = 1;
        
        while (true) {
            $result = $db->query($db->select()->from('table.comments')
            ->order('comment_ID', Typecho_Db::SORT_ASC)->page($i, 100));
            $j = 0;
            
            while ($row = $db->fetchRow($result)) {
                $status = $row['comment_approved'];
                if ('spam' == $row['comment_approved']) {
                    $status = 'spam';
                } else if ('0' == $row['comment_approved']) {
                    $status = 'waiting';
                } else {
                    $status = 'approved';
                }
                
                $row['comment_content'] = preg_replace(
                array("/\s*<p>/is", "/\s*<\/p>\s*/is", "/\s*<br\s*\/>\s*/is",
                "/\s*<(div|blockquote|pre|table|ol|ul)>/is", "/<\/(div|blockquote|pre|table|ol|ul)>\s*/is"),
                array('', "\n\n", "\n", "\n\n<\\1>", "</\\1>\n\n"), 
                $row['comment_content']);
            
                $comments->insert(array(
                    'coid'      =>  $row['comment_ID'],
                    'cid'       =>  $row['comment_post_ID'],
                    'created'   =>  strtotime($row['comment_date_gmt']) + $gmtOffset,
                    'author'    =>  $row['comment_author'],
                    'authorId'  =>  $row['user_id'],
                    'ownerId'   =>  1,
                    'mail'      =>  $row['comment_author_email'],
                    'url'       =>  $row['comment_author_url'],
                    'ip'        =>  $row['comment_author_IP'],
                    'agent'     =>  $row['comment_agent'],
                    'text'      =>  $row['comment_content'],
                    'type'      =>  empty($row['comment_type']) ? 'comment' : $row['comment_type'],
                    'status'    =>  $status,
                    'parent'    =>  $row['comment_parent']
                ));
                $j ++;
                unset($row);
            }
            
            if ($j < 100) {
                break;
            }
            
            $i ++;
            unset($result);
        }
		
		/** 转换Wordpress的term_taxonomy表 */
		$terms = $db->fetchAll($db->select()->from('table.term_taxonomy')
        ->join('table.terms', 'table.term_taxonomy.term_id = table.terms.term_id')
        ->where('taxonomy = ? OR taxonomy = ?', 'category', 'post_tag'));
        foreach ($terms as $term) {
            $metas->insert(array(
                'mid'           =>  $term['term_taxonomy_id'],
                'name'          =>  $term['name'],
                'slug'          =>  'post_tag' == $term['taxonomy'] ? Typecho_Common::slugName($term['name']) : $term['slug'],
                'type'      	=>  'post_tag' == $term['taxonomy'] ? 'tag' : 'category',
                'description'   =>  $term['description'],
                'count'      	=>  $term['count'],
            ));
            
            /** 转换关系表 */
            $relationships = $db->fetchAll($db->select()->from('table.term_relationships')
            ->where('term_taxonomy_id = ?', $term['term_taxonomy_id']));
            foreach ($relationships as $relationship) {
                $masterDb->query($masterDb->insert('table.relationships')->rows(array(
                    'cid'      	=>  $relationship['object_id'],
                    'mid'   	=>  $relationship['term_taxonomy_id'],
                )));
            }
        }
		
        /** 转换内容 */
        $i = 1;
        
        while (true) {
            $result = $db->query($db->select()->from('table.posts')
            ->where('post_type = ? OR post_type = ?', 'post', 'page')
            ->order('ID', Typecho_Db::SORT_ASC)->page($i, 100));
            $j = 0;
            
            while ($row = $db->fetchRow($result)) {
                $contents->insert(array(
                    'cid'           =>  $row['ID'],
                    'title'         =>  $row['post_title'],
                    'slug'          =>  Typecho_Common::slugName(urldecode($row['post_name']), $row['ID'], 128),
                    'created'       =>  strtotime($row['post_date_gmt']) + $gmtOffset,
                    'modified'      =>  strtotime($row['post_modified_gmt']) + $gmtOffset,
                    'text'          =>  $row['post_content'],
                    'order'         =>  $row['menu_order'],
                    'authorId'      =>  $row['post_author'],
                    'template'      =>  NULL,
                    'type'          =>  'page' == $row['post_type'] ? 'page' : 'post',
                    'status'        =>  'publish' == $row['post_status'] ? 'publish' : 'draft',
                    'password'      =>  $row['post_password'],
                    'commentsNum'   =>  $row['comment_count'],
                    'allowComment'  =>  'open' == $row['comment_status']? '1' : '0',
                    'allowFeed'     =>  '1',
                    'allowPing'     =>  'open' == $row['ping_status']? '1' : '0',
                ));
                
                $j ++;
                unset($row);
            }
            
            if ($j < 100) {
                break;
            }
            
            $i ++;
            unset($result);
        }
        
        $this->widget('Widget_Notice')->set(_t("数据已经转换完成"), NULL, 'success');
        $this->response->goBack();
    }

    public function action()
    {
        $this->widget('Widget_User')->pass('administrator');
        $this->on($this->request->isPost())->doImport();
    }
}
