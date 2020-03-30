<?php

namespace modules\templates\admin;

use m\module;
use m\view;
use m\core;
use m\registry;
use m\config;
use m\i18n;

class shop extends module {

    protected $css = [
        '/css/shop.css'
    ];

    public function _init()
    {
        $items = $modules = [];

        $json_path = 'https://m-framework.com/templates_shop/templates.' . core::get_version() . '.json';
        $modules_json = file_get_contents($json_path);

        if (!empty($modules_json) && $modules = json_decode($modules_json, true))
            foreach ((array)$modules as $module) {

                $module['archive_link'] = empty($module['price']) ? $module['archive_link'] : 'mailto:' . $module['author'];
                $module['price'] = empty($module['price']) ? '<i class="fa fa-download" aria-hidden="true"></i> *Download*' : '<i class="fa fa-shopping-bag" aria-hidden="true"></i> €' . $module['price'];
                $module['compatible_cores'] = implode(', ', $module['compatible_cores']);

                $items[] = $this->view->shop_item->prepare($module);
            }

        view::set('content', $this->view->shop->prepare([
            'items' => implode('', $items),
            'core_version' => core::get_version(),
        ]));
    }
}