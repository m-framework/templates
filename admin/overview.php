<?php

namespace modules\templates\admin;

use m\module;
use m\registry;
use m\view;
use m\i18n;
use m\config;
use m\form;
use modules\admin\admin\overview_data;

class overview extends module {

    public function _init()
    {
        if (!isset($this->view->overview) || !isset($this->view->overview_item)
            || !isset($this->view->overview_current_item) || !isset($this->view->overview_protected_item)) {
            return false;
        }

        $templates_arr = [];

        $templates_path = config::get('root_path') . config::get('templates_path') . $this->site->id . '/';

        $templates = array_diff(scandir($templates_path), ['.', '..']);

        $templates_arr = $this->walk_templates_overview($templates_path, $templates, $templates_arr);

        $templates_path = config::get('root_path') . config::get('templates_path');

        $templates = array_diff(scandir($templates_path), ['.', '..', 'admin', 'install']);

        $templates_arr = $this->walk_templates_overview($templates_path, $templates, $templates_arr);

        return view::set('content', $this->view->overview->prepare([
            'items' => implode("\n", $templates_arr),
        ]));
    }

    private function walk_templates_overview($templates_path, $templates, $templates_arr)
    {
        if (!empty($templates)) {
            foreach ($templates as $template) {

                if (!is_dir($templates_path . $template) || !is_file($templates_path . $template . '/template.json')
                    || !empty($templates_arr[$template]))
                    continue;

                $template_json = json_decode(file_get_contents($templates_path . $template . '/template.json'), true);

                if (is_dir($templates_path . $template . '/i18n/')) {
                    i18n::init(str_replace(config::get('root_path'), '', $templates_path) . $template . '/i18n/');
                }

                $view_name = 'overview_item';

                if ($template == $this->site->template) {
                    $view_name = 'overview_current_item';
                }
                else if (strrpos($templates_path, '/' . $this->site->id) === false) {
                    $view_name = 'overview_protected_item';
                }

                $templates_arr[$template] = $this->view->{$view_name}->prepare([
                    'template' => $template,
                    'name' => i18n::get($template_json['title']),
                    'author' => $template_json['author'],
                    'version' => $template_json['version'],
                    'date' => $template_json['date'],
                    'integration_date' => date('Y-m-d H:i:s', filemtime($templates_path . $template)),
                ]);
            }
        }

        return $templates_arr;
    }


}
