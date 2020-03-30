<?php

namespace modules\templates\admin;

use m\functions;
use m\module;
use m\view;
use m\config;
use m\core;
use m\i18n;
use libraries\pclzip\PclZip;

class update_list extends module {

    public function _init()
    {
        $templates = [];

        $dir = config::get('root_path') . '/templates_shop/';

        $files = array_values(array_diff(scandir($dir), ['.', '..']));

        if (empty($files) || !is_array($files)) {
            $this->redirect('/' . $this->config->admin_panel_alias . '/templates/shop');
        }

        foreach ($files as $file) {

            if (mb_strtolower(pathinfo($file, PATHINFO_EXTENSION), 'UTF-8') !== 'zip') {
                continue;
            }

            $archive = new PclZip($file);
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($name, ['admin','install'])) {
                continue;
            }

            chdir($dir);

            if (($archive->extract(PCLZIP_OPT_PATH, $name) == 0 || !is_dir($dir . $name)) && $this->destroy_tmp($dir . $name)) {
                continue;
            }

            /**
             * Trying to read an array of files in extracted archive
             */
            $_files = array_values(array_diff(scandir($dir . $name), ['.', '..']));

            /**
             * Returns error if a file `template.json` is absent
             */
            if ((empty($_files) || empty($_files['0']) || !is_file($dir . $name . '/' . $_files['0'].'/template.json'))
                && $this->destroy_tmp($dir . $name)) {
                continue;
            }
            /**
             * Trying to read a data from `template.json` to archive
             */
            $template_json = @json_decode(@file_get_contents($dir . $name . '/' . $_files['0'] . '/template.json'), true);

            /**
             * Returns error if a data from `template.json` is absent or isn't an array
             */
            if (empty($template_json) || !is_array($template_json) && $this->destroy_tmp($dir . $name)) {
                continue;
            }

            /**
             * Checking if an array from `template.json` consist of important parameters
             */
            $important_parameters = ['title', 'name', 'description', 'cover', 'version', 'date', 'author',
                'compatible_cores', 'compatible_types', 'price'];
            $empty_params = [];
            foreach ($important_parameters as $important_parameter) {
                if (!isset($template_json[$important_parameter])) {
                    $empty_params[] = $important_parameter;
                }
            }

            /**
             * Returns error if some of important parameters absent in `template.json`
             */
            if (!empty($empty_params) && $this->destroy_tmp($dir . $name)) {
                continue;
            }

            if (!empty($template_json['cover']) && is_file($dir . $name . '/' . $_files['0'] . '/' . $template_json['cover'])) {
                rename($dir . $name . '/' . $_files['0'] . '/' . $template_json['cover'], $dir . $template_json['cover']);
                $template_json['cover'] = '//' . $this->site->host . '/templates_shop/' . $template_json['cover'];
            }

            if ($this->destroy_tmp($dir . $name)) {

                foreach ((array)$template_json['compatible_cores'] as $core) {

                    if (!isset($templates[$core])) {
                        $templates[$core] = [];
                    }

                    $template_json['archive_link'] = empty($template_json['price']) ? '//' . $this->site->host . '/templates_shop/' . $file : 'mailto:' . $template_json['author'];
                    $templates[$core][$template_json['name'] . $template_json['author']] = $template_json;
                }

            }
        }

        if (!empty($templates)) {

            foreach ($templates as $core => $_templates) {

                $_templates = array_values($_templates);

                file_put_contents($dir . 'templates.' . $core . '.json', json_encode($_templates));
            }
        }

        $this->redirect('/' . $this->config->admin_panel_alias . '/templates/shop');
    }

    function destroy_tmp($path)
    {
        if ($path == config::get('root_path') . '/templates_shop/' || $path == config::get('root_path') . '/templates_shop')
            return false;

        return functions::delete_recursively($path);
    }
}