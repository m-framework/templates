<?php

namespace modules\templates\admin;

use m\functions;
use m\module;
use m\view;
use m\registry;
use m\config;
use m\core;
use m\i18n;
use libraries\pclzip\PclZip;
use modules\files\models\files;

class install extends module {

    protected $js = ['/js/upload_template.js'];
    protected $css = ['/css/install.css'];

    public function _init()
    {
        if (!empty($_FILES['template'])) {
            header("Content-type: application/json; charset=utf-8");
            core::out(json_encode($this->upload_file()));
        }

        view::set('content', $this->view->install->prepare());
    }

    public function upload_file()
    {
        $arr = ['error' => i18n::get('Error') . ': ' . i18n::get('Sorry, can\'t install a template')];

        if (!empty($_FILES['template']['error']) || empty($_FILES['template']['size']) || empty($_FILES['template']['name']) || empty($this->user->profile)
            || !$this->user->is_admin())
            return $arr;

        $name = time() . '_' . pathinfo($_FILES['template']['name'], PATHINFO_FILENAME);

        $dir = config::get('root_path') . config::get('tmp_path');

        $template_path = config::get('root_path') . config::get('templates_path') . $this->site->id . '/';

        if (!move_uploaded_file($_FILES['template']['tmp_name'], $dir . $name . '.zip') == true)
            return $arr;

        $archive = new PclZip($name . '.zip');

        chdir($dir);

        if (($archive->extract(PCLZIP_OPT_PATH, $name) == 0 || !is_dir($dir . $name)) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('We can\'t to extract your file like archive')];

        $files = array_values(array_diff(scandir($dir . $name), ['.', '..']));

        if (is_dir($template_path . '/' . $files['0']) && !rename($template_path . '/' . $files['0'], $template_path . '/' . $files['0'] . '_' . time()))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('We can\'t to backup an existing template with same name. Try to remove it manually.')];

        if ((empty($files) || !is_array($files) || empty($files['0']) || !is_file($dir . $name . '/' . $files['0'] . '/template.json')) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('Uploaded archive don\'t consist of needed data')];

        $template_json = @json_decode(@file_get_contents($dir . $name . '/' . $files['0'] . '/template.json'), true);

        if (empty($template_json) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('Uploaded archive don\'t consist of needed data')];

        $important_parameters = ['title', 'name', 'description', 'cover', 'version', 'date', 'author',
            'compatible_cores', 'compatible_types', 'price'];
        $empty_params = [];
        foreach ($important_parameters as $important_parameter)
            if (!isset($template_json[$important_parameter]))
                $empty_params[] = $important_parameter;
        // TODO: use some casual function like `array_diff` or smth. else

        if (!empty($empty_params) && $this->destroy_tmp($dir . $name))
            return [
                'empty_params' => $empty_params,
                'error' => i18n::get('Error') . ': ' . i18n::get('There are absents important parameters in template file') . '`template.json`'
            ];

        if ((!is_array($template_json['compatible_cores']) || !in_array(core::get_version(), $template_json['compatible_cores'])) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('This template don\'t compatible with current core version')];

        if ((!is_array($template_json['compatible_types']) || !in_array($this->site->type, $template_json['compatible_types'])) && $this->destroy_tmp($dir . $name))
            return ['error' => i18n::get('Error') . ': ' . i18n::get('This template don\'t compatible with current site type')];

        if ($archive->extract(PCLZIP_OPT_PATH, $template_path) !== 0 && $this->destroy_tmp($dir . $name))
            return ['success' => i18n::get('New template') . ' `' . $template_json['name'] . '` ' . i18n::get('successfully installed'),];

        return $arr;
    }

    function destroy_tmp($path)
    {
        return functions::delete_recursively($path) && is_file($path . '.zip') ? unlink($path . '.zip') : false;
    }
}