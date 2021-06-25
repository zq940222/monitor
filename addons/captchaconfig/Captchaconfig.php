<?php

namespace addons\captchaconfig;

use app\common\library\Menu;
use think\Addons;
use think\Config;

/**
 * 验证码配置插件
 */
class Captchaconfig extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        
        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {
        
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        
        return true;
    }

    public function AppBegin($dispatch)
    {   
        $config = $this->getConfig();

        if (!$config['captcha_is_close']) {
            Config::set('fastadmin.login_captcha', $config['captcha_is_close']);
        } elseif (is_array($dispatch) && isset($dispatch['method']) && in_array('\think\captcha\CaptchaController', $dispatch['method'])) {
            $hasRef = request()->server('HTTP_REFERER');
            if ($config['captcha_area'] == 'backend' && $hasRef) {
                return true;
            }
            if ($config['captcha_area'] == 'forntend' && !$hasRef) {
                return true;
            }

            Config::set([
                'captcha' => [
                    'codeSet' => $config['captcha_charset'],
                    'fontSize' => $config['captcha_fontsize'],
                    'useCurve' => $config['captcha_is_mixed'],
                    'useZh' => $config['captcha_is_cn'],
                    'imageH' => $config['captcha_height'],
                    'imageW' => $config['captcha_width'],//130/4=32.5
                    'length' => $config['captcha_size'],
                    'reset' => $config['captcha_is_reset'],
                ]
            ]);
        }
    }

    public function ModuleInit($request)
    {
        $config = $this->getConfig();

        if ($config['captcha_area'] == 'backend' || $config['captcha_area'] == 'all') {
            if ($request->module() === 'admin' && $request->action() == 'login') {
                Config::set('view_replace_str.length(4)', 'length('.$config['captcha_size'].')');
            }
        }

        if ($config['captcha_area'] == 'forntend' || $config['captcha_area'] == 'all') {
            if ($request->module() === 'index' && $request->action() == 'register') {
                Config::set('view_replace_str.length(4)', 'length('.$config['captcha_size'].')');
            }
        }
    }
}
