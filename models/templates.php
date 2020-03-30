<?php

namespace modules\templates\models;

use m\model;
use m\i18n;
use m\config;
use m\registry;

class templates extends model
{
    public function _before_save()
    {

    }


    public static function get_templates_options_arr($templates_path = null)
    {
        $templates_arr = [];
        $array_diff_params = ['.', '..'];

        if (empty($templates_path)) {
            $templates_path = config::get('root_path') . config::get('templates_path');

            if (registry::has('site') && !empty(registry::get('site')->id)) {
                $templates_arr = static::get_templates_options_arr($templates_path . registry::get('site')->id . '/');
                $array_diff_params = ['.', '..', 'admin', 'install'];
            }
        }

        $templates = array_diff(scandir($templates_path), $array_diff_params);

        foreach ($templates as $template) {

            if (!is_dir($templates_path . $template)
                || !is_file($templates_path . $template . '/template.json')
                || !is_file($templates_path . $template . '/index.html')
            )
                continue;

            $template_json = json_decode(file_get_contents($templates_path . $template . '/template.json'), true);

            if (is_dir($templates_path . $template . '/i18n/')) {
                i18n::init(str_replace(config::get('root_path'), '', $templates_path) . $template . '/i18n/');
            }

            $templates_arr[] = ['value' => $template, 'name' => i18n::get($template_json['title'])];
        }

        return $templates_arr;
    }
}