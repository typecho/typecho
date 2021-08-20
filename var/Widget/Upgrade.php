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
     * 执行升级程序
     *
     * @access public
     * @return void
     * @throws Typecho_Exception
     */
    public function upgrade()
    {
        $packages = get_class_methods('Upgrade');

        preg_match("/^\w+ ([0-9\.]+)(\/[0-9\.]+)?$/i", $this->options->generator, $matches);
        $currentVersion = $matches[1];
        $currentMinor = '0';
        if (isset($matches[2])) {
            $currentMinor = substr($matches[2], 1);
        }

        $message = [];

        foreach ($packages as $package) {
            preg_match("/^v([_0-9]+)(r[_0-9]+)?$/", $package, $matches);

            $version = str_replace('_', '.', $matches[1]);

            if (version_compare($currentVersion, $version, '>')) {
                break;
            }

            if (isset($matches[2])) {
                $minor = substr(str_replace('_', '.', $matches[2]), 1);

                if (version_compare($currentVersion, $version, '=')
                    && version_compare($currentMinor, $minor, '>=')) {
                    break;
                }

                $version .= '/' . $minor;
            }

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

            /** 更新版本号 */
            $this->update(['value' => 'Typecho ' . $version],
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
