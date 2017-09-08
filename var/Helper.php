<?php
/**
 * 插件帮手将默认出现在所有的typecho发行版中.
 * 因此你可以放心使用它的功能, 以方便你的插件安装在用户的系统里.
 *
 * @package Helper
 * @author qining
 * @version 1.0.0
 * @link http://typecho.org
 */
class Helper
{
    /**
     * 获取Widget_Options对象
     *
     * @access public
     * @return Widget_Options
     */
    public static function options()
    {
        return Typecho_Widget::widget('Widget_Options');
    }

    /**
     * 获取Widget_Security对象
     *
     * @return Widget_Security
     */
    public static function security()
    {
        return Typecho_Widget::widget('Widget_Security');
    }

    /**
     * 根据ID获取单个Widget对象
     *
     * @param string $table 表名, 支持 contents, comments, metas, users
     * @return Widget_Abstract
     */
    public static function widgetById($table, $pkId)
    {
        $table = ucfirst($table);
        if (!in_array($table, array('Contents', 'Comments', 'Metas', 'Users'))) {
            return NULL;
        }

        $keys = array(
            'Contents'  =>  'cid',
            'Comments'  =>  'coid',
            'Metas'     =>  'mid',
            'Users'     =>  'uid'
        );

        $className = "Widget_Abstract_{$table}";
        $key = $keys[$table];
        $db = Typecho_Db::get();
        $widget = new $className;
        
        $db->fetchRow(
            $widget->select()->where("{$key} = ?", $pkId)->limit(1),
                array($widget, 'push'));

        return $widget;
    }

    /**
     * 强行删除某个插件
     *
     * @access public
     * @param string $pluginName 插件名称
     * @return void
     */
    public static function removePlugin($pluginName)
    {
        try {
            /** 获取插件入口 */
            list($pluginFileName, $className) = Typecho_Plugin::portal($pluginName, __TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__);

            /** 获取已启用插件 */
            $plugins = Typecho_Plugin::export();
            $activatedPlugins = $plugins['activated'];

            /** 载入插件 */
            require_once $pluginFileName;

            /** 判断实例化是否成功 */
            if (!isset($activatedPlugins[$pluginName]) || !class_exists($className)
            || !method_exists($className, 'deactivate')) {
                throw new Typecho_Widget_Exception(_t('无法禁用插件'), 500);
            }

            $result = call_user_func(array($className, 'deactivate'));

        } catch (Exception $e) {
            //nothing to do
        }

        $db = Typecho_Db::get();

        try {
            Typecho_Plugin::deactivate($pluginName);
            $db->query($db->update('table.options')
            ->rows(array('value' => serialize(Typecho_Plugin::export())))
            ->where('name = ?', 'plugins'));
        } catch (Typecho_Plugin_Exception $e) {
            //nothing to do
        }

        $db->query($db->delete('table.options')->where('name = ?', 'plugin:' . $pluginName));
    }

    /**
     * 导入语言项
     *
     * @access public
     * @param string $domain
     * @return void
     */
    public static function lang($domain)
    {
        $currentLang = Typecho_I18n::getLang();
        if ($currentLang) {
            $currentLang = basename($currentLang);
            $fileName = dirname(__FILE__) . '/' . $domain . '/lang/' . $currentLang;
            if (file_exists($fileName)) {
                Typecho_I18n::addLang($fileName);
            }
        }
    }

    /**
     * 增加路由
     *
     * @access public
     * @param string $name 路由名称
     * @param string $url 路由路径
     * @param string $widget 组件名称
     * @param string $action 组件动作
     * @param string $after 在某个路由后面
     * @return integer
     */
    public static function addRoute($name, $url, $widget, $action = NULL, $after = NULL)
    {
        $routingTable = self::options()->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $pos = 0;
        foreach ($routingTable as $key => $val) {
            $pos ++;

            if ($key == $after) {
                break;
            }
        }

        $pre = array_slice($routingTable, 0, $pos);
        $next = array_slice($routingTable, $pos);

        $routingTable = array_merge($pre, array($name => array(
            'url'       =>  $url,
            'widget'    =>  $widget,
            'action'    =>  $action
        )), $next);
        self::options()->routingTable = $routingTable;

        $db = Typecho_Db::get();
        return Typecho_Widget::widget('Widget_Abstract_Options')->update(array('value' => serialize($routingTable))
        , $db->sql()->where('name = ?', 'routingTable'));
    }

    /**
     * 移除路由
     *
     * @access public
     * @param string $name 路由名称
     * @return integer
     */
    public static function removeRoute($name)
    {
        $routingTable = self::options()->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        unset($routingTable[$name]);
        self::options()->routingTable = $routingTable;

        $db = Typecho_Db::get();
        return Typecho_Widget::widget('Widget_Abstract_Options')->update(array('value' => serialize($routingTable))
        , $db->sql()->where('name = ?', 'routingTable'));
    }

    /**
     * 增加action扩展
     *
     * @access public
     * @param string $actionName 需要扩展的action名称
     * @param string $widgetName 需要扩展的widget名称
     * @return integer
     */
    public static function addAction($actionName, $widgetName)
    {
        $actionTable = unserialize(self::options()->actionTable);
        $actionTable = empty($actionTable) ? array() : $actionTable;
        $actionTable[$actionName] = $widgetName;

        $db = Typecho_Db::get();
        return Typecho_Widget::widget('Widget_Abstract_Options')->update(array('value' => (self::options()->actionTable = serialize($actionTable)))
        , $db->sql()->where('name = ?', 'actionTable'));
    }

    /**
     * 删除action扩展
     *
     * @access public
     * @param string $actionName
     * @return Typecho_Widget
     */
    public static function removeAction($actionName)
    {
        $actionTable = unserialize(self::options()->actionTable);
        $actionTable = empty($actionTable) ? array() : $actionTable;

        if (isset($actionTable[$actionName])) {
            unset($actionTable[$actionName]);
            reset($actionTable);
        }

        $db = Typecho_Db::get();
        return Typecho_Widget::widget('Widget_Abstract_Options')->update(array('value' => (self::options()->actionTable = serialize($actionTable)))
        , $db->sql()->where('name = ?', 'actionTable'));
    }

    /**
     * 增加一个菜单
     *
     * @access public
     * @param string $menuName 菜单名
     * @return integer
     */
    public static function addMenu($menuName)
    {
        $panelTable = unserialize(self::options()->panelTable);
        $panelTable['parent'] = empty($panelTable['parent']) ? array() : $panelTable['parent'];
        $panelTable['parent'][] = $menuName;

        $db = Typecho_Db::get();
        Typecho_Widget::widget('Widget_Abstract_Options')->update(array('value' => (self::options()->panelTable = serialize($panelTable)))
        , $db->sql()->where('name = ?', 'panelTable'));

        end($panelTable['parent']);
        return key($panelTable['parent']) + 10;
    }

    /**
     * 移除一个菜单
     *
     * @access public
     * @param string $menuName 菜单名
     * @return integer
     */
    public static function removeMenu($menuName)
    {
        $panelTable = unserialize(self::options()->panelTable);
        $panelTable['parent'] = empty($panelTable['parent']) ? array() : $panelTable['parent'];

        if (false !== ($index = array_search($menuName, $panelTable['parent']))) {
            unset($panelTable['parent'][$index]);
        }

        $db = Typecho_Db::get();
        Typecho_Widget::widget('Widget_Abstract_Options')->update(array('value' => (self::options()->panelTable = serialize($panelTable)))
        , $db->sql()->where('name = ?', 'panelTable'));

        return $index + 10;
    }

    /**
     * 增加一个面板
     *
     * @access public
     * @param integer $index 菜单索引
     * @param string $fileName 文件名称
     * @param string $title 面板标题
     * @param string $subTitle 面板副标题
     * @param string $level 进入权限
     * @param boolean $hidden 是否隐藏
     * @param string $addLink 新增项目链接, 会显示在页面标题之后
     * @return integer
     */
    public static function addPanel($index, $fileName, $title, $subTitle, $level, $hidden = false, $addLink = '')
    {
        $panelTable = unserialize(self::options()->panelTable);
        $panelTable['child'] = empty($panelTable['child']) ? array() : $panelTable['child'];
        $panelTable['child'][$index] = empty($panelTable['child'][$index]) ? array() : $panelTable['child'][$index];
        $fileName = urlencode(trim($fileName, '/'));
        $panelTable['child'][$index][] = array($title, $subTitle, 'extending.php?panel=' . $fileName, $level, $hidden, $addLink);

        $panelTable['file'] = empty($panelTable['file']) ? array() : $panelTable['file'];
        $panelTable['file'][] = $fileName;
        $panelTable['file'] = array_unique($panelTable['file']);

        $db = Typecho_Db::get();
        Typecho_Widget::widget('Widget_Abstract_Options')->update(array('value' => (self::options()->panelTable = serialize($panelTable)))
        , $db->sql()->where('name = ?', 'panelTable'));

        end($panelTable['child'][$index]);
        return key($panelTable['child'][$index]);
    }

    /**
     * 移除一个面板
     *
     * @access public
     * @param integer $index 菜单索引
     * @param string $fileName 文件名称
     * @return integer
     */
    public static function removePanel($index, $fileName)
    {
        $panelTable = unserialize(self::options()->panelTable);
        $panelTable['child'] = empty($panelTable['child']) ? array() : $panelTable['child'];
        $panelTable['child'][$index] = empty($panelTable['child'][$index]) ? array() : $panelTable['child'][$index];
        $panelTable['file'] = empty($panelTable['file']) ? array() : $panelTable['file'];
        $fileName = urlencode(trim($fileName, '/'));

        if (false !== ($key = array_search($fileName, $panelTable['file']))) {
            unset($panelTable['file'][$key]);
        }

        $return = 0;
        foreach ($panelTable['child'][$index] as $key => $val) {
            if ($val[2] == 'extending.php?panel=' . $fileName) {
                unset($panelTable['child'][$index][$key]);
                $return = $key;
            }
        }

        $db = Typecho_Db::get();
        Typecho_Widget::widget('Widget_Abstract_Options')->update(array('value' => (self::options()->panelTable = serialize($panelTable)))
        , $db->sql()->where('name = ?', 'panelTable'));
        return $return;
    }

    /**
     * 获取面板url
     *
     * @access public
     * @param string $fileName
     * @return string
     */
    public static function url($fileName)
    {
        return Typecho_Common::url('extending.php?panel=' . (trim($fileName, '/')), self::options()->adminUrl);
    }
    
    /**
     * 手动配置插件变量
     * 
     * @access public
     * @static
     * @param mixed $pluginName 插件名称
     * @param array $settings 变量键值对
     * @param bool $isPersonal. (default: false) 是否为私人变量
     * @return void
     */
    public static function configPlugin($pluginName, array $settings, $isPersonal = false)
    {
        if (empty($settings)) {
            return;
        }
        
        Widget_Plugins_Edit::configPlugin($pluginName, $settings, $isPersonal);
    }

    /**
     * 评论回复按钮
     *
     * @access public
     * @param string $theId 评论元素id
     * @param integer $coid 评论id
     * @param string $word 按钮文字
     * @param string $formId 表单id
     * @param integer $style 样式类型
     * @return void
     */
    public static function replyLink($theId, $coid, $word = 'Reply', $formId = 'respond', $style = 2)
    {
        if (self::options()->commentsThreaded) {
            echo '<a href="#' . $formId . '" rel="nofollow" onclick="return typechoAddCommentReply(\'' .
            $theId . '\', ' . $coid . ', \'' . $formId . '\', ' . $style . ');">' . $word . '</a>';
        }
    }

    /**
     * 评论取消按钮
     *
     * @access public
     * @param string $word 按钮文字
     * @param string $formId 表单id
     * @return void
     */
    public static function cancelCommentReplyLink($word = 'Cancel', $formId = 'respond')
    {
        if (self::options()->commentsThreaded) {
            echo '<a href="#' . $formId . '" rel="nofollow" onclick="return typechoCancelCommentReply(\'' .
            $formId . '\');">' . $word . '</a>';
        }
    }

    /**
     * 评论回复js脚本
     *
     * @access public
     * @return void
     */
    public static function threadedCommentsScript()
    {
        if (self::options()->commentsThreaded) {
            echo
<<<EOF
<script type="text/javascript">
var typechoAddCommentReply = function (cid, coid, cfid, style) {
    var _ce = document.getElementById(cid), _cp = _ce.parentNode;
    var _cf = document.getElementById(cfid);

    var _pi = document.getElementById('comment-parent');
    if (null == _pi) {
        _pi = document.createElement('input');
        _pi.setAttribute('type', 'hidden');
        _pi.setAttribute('name', 'parent');
        _pi.setAttribute('id', 'comment-parent');

        var _form = 'form' == _cf.tagName ? _cf : _cf.getElementsByTagName('form')[0];

        _form.appendChild(_pi);
    }
    _pi.setAttribute('value', coid);

    if (null == document.getElementById('comment-form-place-holder')) {
        var _cfh = document.createElement('div');
        _cfh.setAttribute('id', 'comment-form-place-holder');
        _cf.parentNode.insertBefore(_cfh, _cf);
    }

    1 == style ? (null == _ce.nextSibling ? _cp.appendChild(_cf)
    : _cp.insertBefore(_cf, _ce.nextSibling)) : _ce.appendChild(_cf);

    return false;
};

var typechoCancelCommentReply = function (cfid) {
    var _cf = document.getElementById(cfid),
    _cfh = document.getElementById('comment-form-place-holder');

    var _pi = document.getElementById('comment-parent');
    if (null != _pi) {
        _pi.parentNode.removeChild(_pi);
    }

    if (null == _cfh) {
        return true;
    }

    _cfh.parentNode.insertBefore(_cf, _cfh);
    return false;
};
</script>
EOF;
        }
    }
}
