<?php

class GoogleCodeSVN_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{
    private function parseFileName($fileName, $repositoryPath)
    {
        $result = array(
            'do'            =>  'publish',
            'allowComment'  =>  $this->options->defaultAllowComment,
            'allowPing'     =>  $this->options->defaultAllowPing,
            'allowFeed'     =>  $this->options->defaultAllowFeed
        );
    
        $basePath = Helper::options()->plugin('GoogleCodeSVN')->basePath;
        $basePath = '/' . trim($basePath, '/') . '/';
        
        if (0 !== strpos($fileName, $basePath)) {
            return false;
        }
        
        $path = substr($fileName, strlen($basePath));
        $part = explode('/', $path);
        
        if (2 != count($part)) {
            return false;
        }
        
        list($categoryName, $baseName) = $part;
        list($slug) = explode('.', $baseName);
        
        $result['slug'] = $slug;
        
        $post = $this->db->fetchRow($this->db->select()
        ->from('table.contents')->where('slug = ?', $slug)->limit(1));
        
        if (!empty($post)) {
            if ('post' != $post['type']) {
                return false;
            } else {
                $result['cid'] = $post['cid'];
            }
        }
        
        /** 将目录作为分类缩略名处理 */
        $categorySlug = Typecho_Common::slugName($categoryName);
        
        $category = $this->db->fetchRow($this->db->select()
        ->from('table.metas')->where('slug = ? OR name = ?', $categorySlug, $categoryName)
        ->where('type = ?', 'category')->limit(1));
        
        /** 如果分类不存在则直接重建分类 */
        if (empty($category)) {
            $input['name'] = $categoryName;
            $input['slug'] = $categorySlug;
            $input['type'] = 'category';
            $input['description'] = $categoryName;
            $input['do'] = 'insert';
            
            $this->widget('Widget_Metas_Category_Edit', NULL, $input, false)->action();
            $result['category'] = array($this->widget('Widget_Notice')->getHighlightId());
        } else {
            $result['category'] = array($category['mid']);
        }
        
        $url = rtrim($repositoryPath, '/') . $fileName;
        
        $client = Typecho_Http_Client::get('Curl', 'Socket');
        if (false == $client) {
            return false;
        }
        
        $client->send($url);
        $result['text'] = '';
        $result['title'] = '';
        
        if (200 == $client->getResponseStatus() || 304 == $client->getResponseStatus()) {
            $response = trim($client->getResponseBody());
            
            list($title, $text) = explode("\n", $response, 2);
            $result['title'] = $title;
            $result['text'] = $text;
        }
        
        return $result;
    }

    public function action()
    {
        /** 验证合法性 */
        if (!isset($_SERVER['HTTP_GOOGLE_CODE_PROJECT_HOSTING_HOOK_HMAC'])) {
            return;
        }
    
        $googleSecretInfo = $_SERVER['HTTP_GOOGLE_CODE_PROJECT_HOSTING_HOOK_HMAC'];
        $revisionData = file_get_contents('php://input');
        
        if (empty($revisionData)) {
            return;
        }
        
        $secretVerify = hash_hmac("md5", $revisionData, Helper::options()->plugin('GoogleCodeSVN')->secretKey);
        
        if ($googleSecretInfo != $secretVerify) {
            return;
        }
        
        $data = Typecho_Json::decode($revisionData);
        
        if (!$data) {
            return;
        }
        
        /** 登录用户 */
        $master = $this->db->fetchRow($this->db->select()->from('table.users')
            ->where('group = ?', 'administrator')
            ->order('uid', Typecho_Db::SORT_ASC)
            ->limit(1));
        
        if (empty($master)) {
            return false;
        } else if (!$this->user->simpleLogin($master['uid'])) {
            return false;
        }
        
        if (isset($data->revisions) && is_array($data->revisions)) {
            foreach ($data->revisions as $revision) {
                if (!empty($revision->added)) {
                    foreach ($revision->added as $file) {
                        $input = $this->parseFileName($file, $data->repository_path);
                        if ($input) {
                            $this->widget('Widget_Contents_Post_Edit', NULL, $input, false)->action();
                        }
                    }
                }
                
                if (!empty($revision->modified)) {
                    foreach ($revision->modified as $file) {
                        $input = $this->parseFileName($file, $data->repository_path);
                        if ($input) {
                            $this->widget('Widget_Contents_Post_Edit', NULL, $input, false)->action();
                        }
                    }
                }
                
                if (!empty($revision->removed)) {
                    foreach ($revision->removed as $file) {
                        $input = $this->parseFileName($file, $data->repository_path);
                        if ($input && isset($input['cid'])) {
                            $postId = $input['cid'];
                            $this->widget('Widget_Contents_Post_Edit', NULL, "cid={$postId}", false)->deletePost();
                        }
                    }
                }
            }
        }
    }
}
