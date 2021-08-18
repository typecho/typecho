<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 升级动作
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 升级组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Widget_Upgrade extends Widget_Abstract_Options implements Widget_Interface_Do
{
    /**
     * 当前内部版本号
     *
     * @access private
     * @var string
     */
    private $_currentVersion;

    /**
     * 对升级包按版本进行排序
     *
     * @access public
     *
     * @param string $a a版本
     * @param string $b b版本
     *
     * @return integer
     */
    public function sortPackage($a, $b)
    {
        [$ver, $rev] = explode('r', $a);
        $a = str_replace('_', '.', $rev);

        [$ver, $rev] = explode('r', $b);
        $b = str_replace('_', '.', $rev);

        return version_compare($a, $b, '>') ? 1 : -1;
    }

    /**
     * 过滤低版本的升级包
     *
     * @access public
     *
     * @param string $version 版本号
     *
     * @return boolean
     */
    public function filterPackage($version)
    {
        [$ver, $rev] = explode('r', $version);
        $rev = str_replace('_', '.', $rev);
        return version_compare($rev, $this->_currentVersion, '>');
    }

    /**
     * 执行升级程序
     *
     * @access public
     * @return void
     */
    public function upgrade()
    {
        [$prefix, $this->_currentVersion] = explode('/', $this->options->generator);
        $packages = get_class_methods('Upgrade');
        $packages = array_filter($packages, [$this, 'filterPackage']);
        usort($packages, [$this, 'sortPackage']);

        $message = [];

        foreach ($packages as $package) {
            $options = $this->widget('Widget_Options@' . $package);

            /** 执行升级脚本 */
            try {
                $result = call_user_func(['Upgrade', $package], $this->db, $options);
                if (!empty($result)) {
                    $message[] = $result;
                }
            } catch (Typecho_Exception $e) {
                $this->widget('Widget_Notice')->set($e->getMessage(), 'error');
                $this->response->goBack();
                return;
            }

            [$ver, $rev] = explode('r', $package);
            $ver = substr(str_replace('_', '.', $ver), 1);
            $rev = str_replace('_', '.', $rev);

            /** 更新版本号 */
            $this->update(['value' => 'Typecho ' . $ver . '/' . $rev],
                $this->db->sql()->where('name = ?', 'generator'));

            $this->destroy('Widget_Options@' . $package);
        }

        /** 更新版本号 */
        $this->update(['value' => 'Typecho ' . Typecho_Common::VERSION],
            $this->db->sql()->where('name = ?', 'generator'));

        $this->widget('Widget_Notice')->set(empty($message) ? _t("升级已经完成") : $message,
            empty($message) ? 'success' : 'notice');
    }

    /**
     * 初始化函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost())->upgrade();
        $this->response->redirect($this->options->adminUrl);
    }
}
