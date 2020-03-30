<?php

namespace modules\templates\admin;

use m\custom_exception;
use m\module;
use m\view;
use m\registry;
use m\config;
use m\core;
use m\i18n;
use libraries\pclzip\PclZip;

class download extends module {

    public function _init()
    {
        if (!function_exists('gzopen')) {
            throw new custom_exception('Abort '.basename(__FILE__).' : Missing gzopen extensions');
        }

        if ($this->alias == 'download' || !$this->user->is_admin()) {
            core::redirect('/' . $this->config->admin_panel_alias . '/templates');
        }

        $template_path = config::get('root_path') . config::get('templates_path') . $this->site->id . '/';

        if (!is_dir($template_path . $this->alias)) {
            $template_path = config::get('root_path') . config::get('templates_path');
        }

        if (!is_dir($template_path . $this->alias)) {
            return view::set('content', $this->view->div_notice->prepare([
                'text' => i18n::get('This file not found'),
            ]));
        }

        $file_path = config::get('root_path') . config::get('tmp_path') . 'archive_' . microtime(true) . '.zip';

        $archive = new PclZip($file_path);

        chdir($template_path);
        $archive->add($this->alias);

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"template_" . $this->alias . ".zip\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($file_path));
        ob_end_flush();
        readfile($file_path);

        unlink($file_path);
    }

    private function list_2_archive($path, array $arr, $original_path)
    {
        $files = array_diff(scandir($path), ['.', '..']);

        if (!empty($files) && is_array($files))
            foreach ($files as $file) {

                //echo str_replace($original_path, '', $path) . '/' . $file . "<br>\n";

                if (is_file($path . '/' . $file))
                    $arr[] = substr(str_replace($original_path, '', $path) . '/' . $file, 1);
                else if (is_dir($path . '/' . $file))
                    $arr = $this->list_2_archive($path . '/' . $file, $arr, $original_path);
            }

        return $arr;
    }
}