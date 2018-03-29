<?php
# ======================================================================== #
#
#  Title      [PHP] Uploader
#  Author:    CreativeDream
#  Website:   https://github.com/CreativeDream/php-uploader
#  Version:   0.4
#  Date:      14-Sep-2016
#  Purpose:   Validate, Remove, Upload, Download files to server.
#  Information: Please don't forget to check your php.ini file for "upload_max_filesize", "post_max_size", "max_file_uploads"
#
# ======================================================================== #

class Uploader {

    protected $options = array(
        'limit' => null,
        'maxSize' => null,
        'extensions' => null,
        'required' => false,
        'uploadDir' => 'uploads/',
        'title' => array('auto', 10),
        'removeFiles' => true,
        'perms' => null,
        'replace' => true,
        'onCheck' => null,
        'onError' => null,
        'onSuccess' => null,
        'onUpload' => null,
        'onComplete' => null,
        'onRemove' => null
    );

    public $error_messages = array(
        1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
        2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
        3 => "The uploaded file was only partially uploaded.",
        4 => "No file was uploaded.",
        6 => "Missing a temporary folder.",
        7 => "Failed to write file to disk.",
        8 => "A PHP extension stopped the file upload.",
        'accept_file_types' => "Filetype not allowed",
        'file_uploads' => "File uploading option in disabled in php.ini",
        'post_max_size' => "The uploaded file exceeds the post_max_size directive in php.ini",
        'max_file_size' => "File is too big",
        'max_number_of_files' => "Maximum number of files exceeded",
        'required_and_no_file' => "No file was choosed. Please select one.",
        'no_download_content' => "File could't be download."
    );

    private $field = null;
    private $data = array(
        "hasErrors" => false,
        "hasWarnings" => false,
        "isSuccess" => false,
        "isComplete" => false,
        "data" => array(
            "files" => array(),
            "metas" => array()
        )
    );

    public function __construct(){
        // __construct function

        $this->cache_data = $this->data;
    }

    /**
     * upload method
     *
     * Return the initialize method
     * @param $field {Array, String}
     * @param $options {Array, null}
     * @return array
     */
    public function upload($field, $options = null){
        $this->data = $this->cache_data;
        return $this->initialize($field, $options);
    }

    /**
     * initialize method
     *
     * Initialize field values and properties.
     * Merge options
     * Prepare files
     * @param $field {Array, String}
     * @param $options {Array, null}
     * @return array
     */
    private function initialize($field, $options){
		if(is_array($field) && in_array($field, $_FILES)){
			$this->field = $field;
            $this->field['Field_Name'] = array_search($field, $_FILES);
            $this->field['Field_Type'] = 'input';

            if(!is_array($this->field['name'])){
           	$this->field = array_merge($this->field, array("name" => array($this->field['name']), "tmp_name"=>array($this->field['tmp_name']), "type"=>array($this->field['type']), "error"=>array($this->field['error']), "size"=>array($this->field['size'])));
            }

            foreach($this->field['name'] as $key=>$value){ if(empty($value)){ unset($this->field['name'][$key]); unset($this->field['type'][$key]); unset($this->field['tmp_name'][$key]); unset($this->field['error'][$key]); unset($this->field['size'][$key]); } }

            $this->field['length'] = count($this->field['name']);
		}elseif(is_string($field) && $this->isURL($field)){
            $this->field = array("name" => array($field), "size"=>array(), "type"=>array(), "error"=>array());
            $this->field['Field_Type'] = 'link';
            $this->field['length'] = 1;
        }else{
            return false;
        }

        if($options != null){
            $this->setOptions($options);
        }

		return $this->prepareFiles();
    }

    /**
     * setOptions method
     *
     * Merge options
     * @param $options {Array}
     */
    private function setOptions($options){
        if(!is_array($options)){return false;}
        $this->options = array_merge($this->options, $options);
    }

	/**
     * validation method
     *
     * Check the field and files
     * @return boolean
     */
    private function validate($file = null){
        $field = $this->field;
        $errors = array();
        $options = $this->options;

        if($file == null){
            $ini = array(ini_get('file_uploads'),((int) ini_get('upload_max_filesize')),((int) ini_get('post_max_size')), ((int) ini_get('max_file_uploads')));

            if(!isset($field) || empty($field)) return false;
            if(!$ini[0]) $errors[] = $this->error_messages['file_uploads'];

            if($options['required'] && $field['length'] == 0) $errors[] = $this->error_messages['required_and_no_file'];
            if(($options['limit'] && $field['length'] > $options['limit']) || ($field['length']) > $ini[3]) $errors[] = $this->error_messages['max_number_of_files'];
            if(!file_exists($options['uploadDir']) && !is_dir($options['uploadDir']) && mkdir($options['uploadDir'], 750, true)){
                $this->data['hasWarnings'] = true;
                $this->data['warnings'] = "A new directory was created in " . realpath($options['uploadDir']);
            }
            if(!is_writable($options['uploadDir'])) @chmod($options['uploadDir'], 750);

            if($field['Field_Type'] == "input"){
                $total_size = 0; foreach($this->field['size'] as $key=>$value){ $total_size += $value; } $total_size = $total_size/1048576;
                if($options['maxSize'] && $total_size > $options['maxSize']) $errors[] = $this->error_messages['max_file_size'];

                if($ini[1] != 0 && $total_size > $ini[1]) $errors[] = $this->error_messages[1];
                if($ini[2] != 0 && $total_size > $ini[2]) $errors[] = $this->error_messages['post_max_size'];
            }
        }else{
            if(@$field['error'][$file['index']] > 0 && array_key_exists($field['error'][$file['index']], $this->error_messages)) $errors[] = $this->error_messages[$field['error'][$file['index']]];
            if($options['extensions'] && !in_array($file['extension'], $options['extensions'])) $errors[] = $this->error_messages['accept_file_types'];
            if($file['type'][0] == "image" && @!is_array(getimagesize($file['tmp']))) $errors[] = $this->error_messages['accept_file_types'];
            if($options['maxSize'] && $file['size'][0] > $options['maxSize']) $errors[] = $this->error_messages['max_file_size'];

            if($field['Field_Type'] == 'link' && empty($this->cache_download_content)) $errors[] = "";
        }

        $custom = $this->_onCheck($file); if($custom) $errors = array_merge($errors, $custom);

        if(!empty($errors)){
            $this->data['hasErrors'] = true;
            if(!isset($this->data['errors'])) $this->data['errors'] = array();

            $this->data['errors'][] = $errors;
            $custom = $this->_onError($errors, $file);

            return false;
        }else{
            return true;
        }
	}

	/**
     * prepareFiles method
     *
     * Prepare files for upload/download and generate meta infos
     * @return $this->data
     */
	private function prepareFiles(){
        $field = $this->field;
        $validate = $this->validate();

        if($validate){
            $files = array();
            $removed_files = $this->removeFiles();
            $isAddMoreMode = count(preg_grep('/^(\d+)\:\/\/(.*)/i', $removed_files)) > 0;
            $addMoreMatches = array();

            for($i = 0; $i < count($field['name']); $i++){

                $metas = array();

                if($field['Field_Type'] == 'input'){
                    $tmp_name = $field['tmp_name'][$i];
                }elseif($field['Field_Type'] == 'link'){
                    $link = $this->downloadFile($field['name'][0], false, true);

                    $tmp_name = $field['name'][0];
                    $field['name'][0] = pathinfo($field['name'][0], PATHINFO_BASENAME);
                    $field['type'][0] = $link['type'];
                    $field['size'][0] = $link['size'];
                    $field['error'][0] = 0;
                }

                $metas['extension'] = substr(strrchr($field['name'][$i], "."),1);
                $metas['type'] = preg_split('[/]', $field['type'][$i]);
                $metas['extension'] = $field['Field_Type'] == 'link' && empty($metas['extension']) ? $metas['type'][1] : $metas['extension'];
                $metas['old_name'] = $field['name'][$i];
                $metas['size'] = $field['size'][$i];
                $metas['size2'] = $this->formatSize($metas['size']);
                $metas['name'] = $this->generateFileName($this->options['title'], array('name'=>substr($metas['old_name'], 0, (!empty($metas['extension']) ? -(strlen($metas['extension'])+1) : strlen($metas['old_name']))), 'size'=>$metas['size'], 'extension'=> $metas['extension']));
                $metas['file'] = $this->options['uploadDir'] . $metas['name'];
                $metas['replaced'] = file_exists($metas['file']);
                $metas['date'] = date('r');

                $is_file_removed = in_array($field['name'][$i], $removed_files);
                if($isAddMoreMode){
                    $addMoreMatches[$field['name'][$i]][] = $i;
                    $matches = preg_grep('/^'.(count($addMoreMatches[$field['name'][$i]])-1).'\:\/\/'.$field['name'][$i].'/i', $removed_files);
                    if(count($matches) == 1) {
                        $is_file_removed = true;
                    }
                }

                if(!$is_file_removed && $this->validate(array_merge($metas, array('index'=>$i, 'tmp'=>$tmp_name))) && $this->uploadFile($tmp_name, $metas['file'])){
                    if($this->options['perms']) @chmod($metas['file'], $this->options['perms']);

                    $custom = $this->_onUpload($metas, $this->field); if($custom && is_array($custom)) $metas = array_merge($custom, $metas);

                    ksort($metas);

                    $files[] = $metas['file'];
                    $this->data['data']['metas'][] = $metas;
                }
            }

            $this->data['isSuccess'] = count($field['name']) - count($removed_files) == count($files);
            $this->data['data']['files'] = $files;

            if($this->data['isSuccess']) $custom = $this->_onSuccess($this->data['data']['files'], $this->data['data']['metas']);

            $this->data['isComplete'] = true;
            $custom = $this->_onComplete($this->data['data']['files'], $this->data['data']['metas']);
        }

        return $this->data;
	}

    /**
     * uploadFile method
     *
     * Upload/Download files to server
     * @return boolean
     */
    private function uploadFile($source, $destination){
        if($this->field['Field_Type'] == 'input'){
            return @move_uploaded_file($source, $destination);
        }elseif($this->field['Field_Type'] == 'link'){
            return $this->downloadFile($source, $destination);
        }
    }

	/**
     * removeFiles method
     *
     * Remove files or cancel upload for them
     * @return array
     */
	private function removeFiles(){
        $removed_files = array();
        if($this->options['removeFiles'] !== false){
            foreach($_POST as $key=>$value){
                preg_match((is_string($this->options['removeFiles']) ? $this->options['removeFiles'] : '/jfiler-items-exclude-'.$this->field['Field_Name'].'-(\d+)/'), $key, $matches);
                if(isset($matches) && !empty($matches)){
                    $input = $_POST[$matches[0]];
                    if($this->isJson($input)) $removed_files = json_decode($input, true);

                    $custom = $this->_onRemove($removed_files, $this->field); if($custom && is_array($custom)) $removed_files = $custom;
                }
            }
        }

        return $removed_files;
	}

    /**
     * downloadFile method
     *
     * Download file to server
     * @return boolean
     */
    private function downloadFile($source, $destination, $info = false){
        set_time_limit(80);

        $forInfo = array(
            "size" => 1,
            "type" => "text/plain"
        );

        if(!isset($this->cache_download_content)){
            $file_content = file_get_contents($source);
            if($info){
                $headers = implode(" ", $http_response_header);
                if(preg_match('/Content-Length: (\d+)/', $headers, $matches)) $forInfo['size'] = $matches[1];
                if(preg_match('/Content-Type: (\w+\/\w+)/', $headers, $matches)) $forInfo['type'] = $matches[1];

                $this->cache_download_content = $file_content;

                return $forInfo;
            }
        }else{
            $file_content = $this->cache_download_content;
        }

        $downloaded_file = @fopen($destination, 'w');
        $written = @fwrite($downloaded_file, $file_content);
        @fclose($downloaded_file);

        return $written;
    }

	/**
     * generateFileName method
     *
     * Generated a file name by uploading
     * @return boolean
     */
	private function generateFileName($conf, $file, $skip_replace_check = false){
        $file['name'] = preg_replace("[^\w\s\d\.\-_~,;:\[\]\(\]]", '', $file['name']);
		$type = is_array($conf) && isset($conf[0]) ? $conf[0] : $conf;
        $type = $type ? $type : 'name';
		$length = is_array($conf) && isset($conf[1]) ? $conf[1] : null;
		$random_string = substr(str_shuffle("_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length ? $length : 10);
        $extension = !empty($file['extension']) ? "." . $file['extension'] : "";
        $string = "";
        $is_extension_used = false;

		switch($type){
			case "auto":
				$string = $random_string;
			break;
			case "name":
				$string = $file['name'];
			break;
			default:
				$string = $type;

				if(strpos($string, "{{random}}") !== false){
					$string = str_replace("{{random}}", $random_string, $string);
				}
				if(strpos($string, "{{file_name}}") !== false){
					$string = str_replace("{{file_name}}", $file['name'], $string);
				}
				if(strpos($string, "{{file_size}}") !== false){
					$string = str_replace("{{file_size}}", $file['size'], $string);
				}
				if(strpos($string, "{{timestamp}}") !== false){
					$string = str_replace("{{timestamp}}", time(), $string);
				}
				if(strpos($string, "{{date}}") !== false){
					$string = str_replace("{{date}}", date('Y-n-d_H:i:s'), $string);
				}
                if(strpos($string, "{{extension}}") !== false){
                    $is_extension_used = true;
                    $string = str_replace("{{extension}}", $file['extension'], $string);
                }
                if(strpos($string, "{{.extension}}") !== false){
                    $is_extension_used = true;
                    $string = str_replace("{{.extension}}", $extension, $string);
                }
		}

        if(!$is_extension_used)
            $string .= $extension;

        if(!$this->options['replace'] && !$skip_replace_check){
            $name = $file['name'];
            $i = 1;
            while (file_exists($this->options['uploadDir'].$string)) {
                $file['name'] = $name . " ({$i})";
                $string = $this->generateFileName($conf, $file, true);
                $i++;
            }
        }

		return $string;
	}

    /**
     * isJson method
     *
     * Check if string is a valid json
     * @return boolean
     */
    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * isURL method
     *
     * Check if string $url is a link
     * @return boolean
     */
    private function isURL($url){
        return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
    }

    /**
     * formatSize method
     *
     * Convert file size
     * @return float
     */
    private function formatSize($bytes){
        if ($bytes >= 1073741824){
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }elseif ($bytes >= 1048576){
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }elseif ($bytes > 0){
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }else{
            $bytes = '0 bytes';
        }

        return $bytes;
    }

	private function _onCheck(){
		$arguments = func_get_args();
		return $this->options['onCheck'] != null && function_exists($this->options['onCheck']) ? $this->options['onCheck'](@$arguments[0]) : null;
	}

	private function _onSuccess(){
		$arguments = func_get_args();
		return $this->options['onSuccess'] != null && function_exists($this->options['onSuccess']) ? $this->options['onSuccess'](@$arguments[0], @$arguments[1]) : null;
	}

	private function _onError(){
		$arguments = func_get_args();
		return $this->options['onError'] && function_exists($this->options['onError']) ? $this->options['onError'](@$arguments[0], @$arguments[1]) : null;
	}

	private function _onUpload(){
		$arguments = func_get_args();
		return $this->options['onUpload'] && function_exists($this->options['onUpload']) ? $this->options['onUpload'](@$arguments[0], @$arguments[1]) : null;
	}

    private function _onComplete(){
		$arguments = func_get_args();
		return $this->options['onComplete'] != null && function_exists($this->options['onComplete']) ? $this->options['onComplete'](@$arguments[0], @$arguments[1]) : null;
	}

    private function _onRemove(){
		$arguments = func_get_args();
		return $this->options['onRemove'] && function_exists($this->options['onRemove']) ? $this->options['onRemove'](@$arguments[0], @$arguments[1]) : null;
	}
}
?>