<?php

class MagikeToTypecho_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function doImport()
    {
        $options = $this->widget('Widget_Options');
        $dbConfig = $options->plugin('MagikeToTypecho');

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
        $this->widget('Widget_Abstract_Users')->to($users)->delete($masterDb->sql()->where('uid <> 1'));
        $masterDb->query($masterDb->delete('table.relationships')->where('1 = 1'));
        $userId = $this->widget('Widget_User')->uid;
        
        /** 转换用户 */
        $rows = $db->fetchAll($db->select()->from('table.users'));
        foreach ($rows as $row) {
            if (1 != $row['user_id']) {
                $users->insert(array(
                    'uid'       =>  $row['user_id'],
                    'name'      =>  $row['user_name'],
                    'password'  =>  $row['user_password'],
                    'mail'      =>  $row['user_mail'],
                    'url'       =>  $row['user_url'],
                    'screenName'=>  $row['user_nick'],
                    'created'   => strtotime($row['user_register']),
                    'group'     => array_search($row['user_group'], $this->widget('Widget_User')->groups)
                ));
            }
        }
        
        /** 转换全局变量 */
        $rows = $db->fetchAll($db->select()->from('table.statics'));
        $static = array();
        foreach ($rows as $row) {
            $static[$row['static_name']] = $row['static_value'];
        }
        
        /** 转换文件 */
        $files = $db->fetchAll($db->select()->from('table.files'));
        if (!is_dir(__TYPECHO_ROOT_DIR__ . '/usr/uploads/')) {
            mkdir(__TYPECHO_ROOT_DIR__ . '/usr/uploads/', 0766);
        }
        
        $pattern = array();
        $replace = array();
        foreach ($files as $file) {
            $path = __TYPECHO_ROOT_DIR__ . '/data/upload/' . substr($file['file_guid'], 0, 2) . '/' .
            substr($file['file_guid'], 2, 2) . '/' . $file['file_guid'];
            
            if (file_exists($path)) {
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
        
        /** 转换评论 */
        $i = 1;
        
        while (true) {
            $result = $db->query($db->select()->from('table.comments')
            ->order('comment_id', Typecho_Db::SORT_ASC)->page($i, 100));
            $j = 0;
            
            while ($row = $db->fetchRow($result)) {
                $comments->insert(array(
                    'coid'      =>  $row['comment_id'],
                    'cid'       =>  $row['post_id'],
                    'created'   =>  $row['comment_date'],
                    'author'    =>  $row['comment_user'],
                    'authorId'  =>  $row['user_id'],
                    'ownerId'   =>  $userId,
                    'mail'      =>  $row['comment_email'],
                    'url'       =>  $row['comment_homepage'],
                    'ip'        =>  $row['comment_ip'],
                    'agent'     =>  $row['comment_agent'],
                    'text'      =>  $row['comment_text'],
                    'type'      =>  $row['comment_type'],
                    'status'    =>  $row['comment_publish'],
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
        
        /** 转换分类 */
        $cats = $db->fetchAll($db->select()->from('table.categories'));
        foreach ($cats as $cat) {
            $metas->insert(array(
                'mid'           =>  $cat['category_id'],
                'name'          =>  $cat['category_name'],
                'slug'          =>  $cat['category_postname'],
                'description'   =>  $cat['category_describe'],
                'count'         =>  0,
                'type'          =>  'category',
                'order'         =>  $cat['category_sort']
            ));
        }
        
        /** 转换内容 */
        $i = 1;
        
        while (true) {
            $result = $db->query($db->select()->from('table.posts')
            ->order('post_id', Typecho_Db::SORT_ASC)->page($i, 100));
            $j = 0;
            
            while ($row = $db->fetchRow($result)) {
                $row['post_content'] = preg_replace(
                array("/\s*<p>/is", "/\s*<\/p>\s*/is", "/\s*<br\s*\/>\s*/is",
                "/\s*<(div|blockquote|pre|table|ol|ul)>/is", "/<\/(div|blockquote|pre|table|ol|ul)>\s*/is"),
                array('', "\n\n", "\n", "\n\n<\\1>", "</\\1>\n\n"), 
                $row['post_content']);
            
                $contents->insert(array(
                    'cid'           =>  $row['post_id'],
                    'title'         =>  $row['post_title'],
                    'slug'          =>  $row['post_name'],
                    'created'       =>  $row['post_time'],
                    'modified'      =>  $row['post_edit_time'],
                    'text'          =>  preg_replace($pattern, $replace, $row['post_content']),
                    'order'         =>  0,
                    'authorId'      =>  $row['user_id'],
                    'template'      =>  NULL,
                    'type'          =>  $row['post_is_page'] ? 'page' : 'post',
                    'status'        =>  $row['post_is_draft'] ? 'draft' : 'publish',
                    'password'      =>  $row['post_password'],
                    'commentsNum'   =>  $row['post_comment_num'],
                    'allowComment'  =>  $row['post_allow_comment'],
                    'allowFeed'     =>  $row['post_allow_feed'],
                    'allowPing'     =>  $row['post_allow_ping']
                ));
                
                /** 插入分类关系 */
                $edit->setCategories($row['post_id'], array($row['category_id']), !$row['post_is_draft']);
                
                /** 设置标签 */
                $edit->setTags($row['post_id'], $row['post_tags'], !$row['post_is_draft']);
                
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
