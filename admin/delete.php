<?php

namespace modules\templates\admin;

use m\functions;
use m\module;
use m\view;
use m\registry;
use m\config;
use m\core;
use m\i18n;

class delete extends module {

    public function _init()
    {
        if (in_array($this->alias, ['delete','modules']) || !$this->user->is_admin())
            core::redirect('/' . $this->config->admin_panel_alias . '/templates');

        $template_path = config::get('root_path') . config::get('templates_path') . $this->site->id . '/';

        if (!is_dir($template_path . $this->alias)) {
            return view::set('content', $this->view->div_notice->prepare([
                'text' => i18n::get('This file not found'),
            ]));
        }

        if ($this->alias == $this->site->template) {
            return view::set('content', $this->view->div_notice->prepare([
                'text' => i18n::get('Template is used on this website'),
            ]));
        }

        functions::delete_recursively($template_path . $this->alias);

        core::redirect('/' . $this->config->admin_panel_alias . '/templates');
    }
}