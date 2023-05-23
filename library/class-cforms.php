<?php
defined('ABSPATH') or die;

class CFormFields {
    public $fields = array();

    /**
     * Parse fields from publishHtml
     *
     * @param string $form_html
     */
    public function parseFromHtml($form_html) {
        preg_match_all('#<(input|textarea|select)([^>]*)>#', $form_html, $matches);

        $radioButtons = array();
        $checkboxIds = array();
        $checkboxElements = array();
        for ($i = 0; $i < count($matches[0]); $i++) {
            $attrs = $matches[2][$i];
            if (!preg_match('#name="([^"]*)#', $attrs, $m) || strpos($attrs, 'type="hidden"') !== false) {
                continue;
            }
            $name = $m[1];

            if ($name === 'name') { // see detect_unavailable_names
                $name = 'name1';
            }

            if ($matches[1][$i] === 'select') {
                $selectRegExp = '#<select [\s\S]+? name=["|\']' . $name . '["|\']([^>]*)>([\s\S]+?)<\/select>#';
                preg_match_all($selectRegExp, $form_html, $matchesSelect);
                $optionHtml = preg_replace('/data-calc=["\'][\s\S]*?["\'] ?/', '', $matchesSelect[2][0]);
                $optionHtml = preg_replace('/ +?selected="[\s\S]+?"/', '', $optionHtml);
                preg_match_all('#<option value=[\'|"]([\s\S]+?)[\'|"] *?>#', $optionHtml, $matchesOption);
            }

            $required = strpos($attrs, 'required') !== false;
            $multiple = strpos($attrs, 'multiple') !== false;

            $field = array(
                'required' => $required,
                'name' => $name,
            );
            if ($matches[1][$i] === 'select') {
                $field['option'] = $matchesOption[1];
                $field['multiple'] = $multiple;
                $field['type'] = 'select';
            }
            if (strpos($attrs, 'type="radio"') !== false) {
                preg_match('#value=["|\']([\s\S]+?)["|\']#', $attrs, $matchesValue);
                $field['value'] = $matchesValue[1];
                if (!array_key_exists($name, $radioButtons)) {
                    $this->fields[] = array();
                    $radioButtons[$name] = array(
                        'type' => 'radio',
                        'name' => $field['name'],
                        'default' => 'default:1',
                        'option' => array($field['value']),
                        'index' => count($this->fields) - 1,

                    );
                } else {
                    array_push($radioButtons[$name]['option'], $field['value']);
                }
            } else if (strpos($attrs, 'type="checkbox"') !== false) {
                preg_match('#value=["|\']([\s\S]+?)["|\']#', $attrs, $matchesValue);
                preg_match('#id=["|\']([\s\S]+?)["|\']#', $attrs, $matchesId);
                $field['value'] = $matchesValue[1];
                $checkboxId = isset($matchesId[1]) ? $matchesId[1] : 0;
                if (!array_key_exists($checkboxId, $checkboxIds)) {
                    //$this->fields[] = array();
                    if (!isset($checkboxElements[$name])) {
                        $checkboxElements[$name] = array();
                    }
                    $checkboxIds[$checkboxId] = $checkboxId;
                    $counter = count($checkboxElements[$name]);
                    $checkboxElements[$name][$counter] = array(
                        'type' => 'checkbox',
                        'name' => str_replace('[]', '', $field['name']),
                        'value' => $field['value'],
                    );
                }
            } else if (strpos($attrs, 'type="file"') !== false) {
                $field['type'] = "file";
                preg_match('#accept=["|\']([\s\S]*?)["|\']#', $attrs, $matchesValue);
                $field['accept'] = isset($matchesValue[1]) && $matchesValue[1] ? $matchesValue[1] : 'ALL';
                if (strpos($field['name'], '[') !== false) {
                    $field['name'] = str_replace(array('[', ']'), array('',''), $field['name']);
                }
                $this->fields[] = $field;
            } else {
                $this->fields[] = $field;
            }
        }
        foreach ($radioButtons as $key=> $radio) {
            $this->fields[$radio['index']] = $radio;
        }
        foreach ($checkboxElements as $key=> $checkbox) {
            if (count($checkbox) > 1) {
                $checkboxValues = array();
                foreach ($checkbox as $id=> $item) {
                    $checkboxValues[] = $item['value'];
                    if ($id + 1 === count($checkbox)) {
                        //last checkbox group element
                        $item['value'] = $checkboxValues;
                        $this->fields[] = $item;
                    }
                }
            } else {
                //single checkbox element
                $this->fields[] = $checkbox[0];
            }
        }
    }

    /**
     * Convert to contact7 format
     *
     * @return string
     */
    public function toString() {
        if (!function_exists('_arr')) {
            /**
             * Get array value by specified key
             *
             * @param array      $array
             * @param string|int $key
             * @param mixed      $default
             *
             * @return mixed
             */
            function _arr(&$array, $key, $default = false) {
                if (isset($array[$key])) {
                    return $array[$key];
                }
                return $default;
            }
        }
        $result = '';
        foreach ($this->fields as $field) {
            $type = isset($field['type']) ? $field['type'] : 'text';
            if (isset($field['option'])) {
                $optionStr = '';
                foreach ($field['option'] as $option) {
                    $optionStr .= ' "' . $option . '"';
                }

                $tagName = _arr(self::$_nameTags, $field['name'], $type);
                $required = isset($field['required']) && $field['required'] ? '*' : '';
                $multiple = isset($field['multiple']) && $field['multiple'] ? ' multiple' : '';
                $default = isset($field['default']) ? (' ' . $field['default']) : '';
                $result .= sprintf("[%s%s %s%s%s%s]\n", $tagName, $required, $field['name'], $multiple, $default, $optionStr);
            } else if ($type === 'file') {
                if (isset(self::$formats[$field['accept']])) {
                    $allowed_formats = self::$formats[$field['accept']] ? ' filetypes:' . self::$formats[$field['accept']] : '';
                } else {
                    // custom formats
                    $allowed_formats = ' filetypes:' . str_replace(array(',', '.'), array('|', ''), $field['accept']);
                }
                $max_file_size = ' limit:10485760'; // 10mb
                $result .= sprintf("[%s%s %s%s%s]\n", _arr(self::$_nameTags, $field['name'], $field['type']), $field['required'] ? '*' : '', $field['name'], $max_file_size, $allowed_formats);
            } else if ($type === 'checkbox') {
                $valuesStr = '';
                if (isset($field['value'])) {
                    foreach ($field['value'] as $value) {
                        $valuesStr .= ' "' . $value . '"';
                    }
                }
                $result .= sprintf("[%s%s %s%s]\n", _arr(self::$_nameTags, $field['name'], $field['type']), $field['required'] ? '*' : '', $field['name'], $valuesStr);
            } else {
                $result .= sprintf("[%s%s %s]\n", _arr(self::$_nameTags, $field['name'], $type), $field['required'] ? '*' : '', $field['name']);
            }
        }
        $result .= "[submit]\n";
        return $result;
    }

    /**
     * Check for existing field with such name
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasField($name) {
        foreach ($this->fields as $field) {
            if ($field['name'] === $name) {
                return true;
            }
        }
        return false;
    }

    private static $_nameTags = array(
        'email' => 'email',
        'tel' => 'tel',
        'message' => 'textarea',
        'select' => 'select',
        'radio' => 'radio',
        'file' => 'file',
        'checkbox' => 'checkbox',
    );

    public static $formats = array (
        'IMAGES' => 'jpg|jpeg|png|gif',
        'DOCUMENTS' => 'pdf|doc|docx|ppt|pptx|odt',
        'VIDEO' => 'avi|mov|mp4|mpg|wmv',
        'AUDIO' => 'ogg|m4a|mp3|wav',
        'ALL' => '',
    );
}

class CForms {
    /**
     * Update forms data sources
     * Create new if needed
     *
     * @param array $forms
     * @param string $template
     *
     * @return array form data sources array
     */
    public static function _updateForms($forms, $template) {
        if (!class_exists('WPCF7_ContactForm')) {
            return array();
        }
        $count = count($forms);
        $prev_data_sources = "";
        if ($template === "header") {
            $prev_data_sources = get_option('header_forms_theme');
        }
        if ($template === "footer") {
            $prev_data_sources = get_option('footer_forms_theme');
        }
        if ($template === "custom") {
            $prev_data_sources = get_option('custom_forms_theme');
        }
        $data_sources = array();

        for ($i = 0; $i < $count; $i++) {
            $form_html = $forms[$i];
            $form_id = isset($prev_data_sources[$i]['id']) ? $prev_data_sources[$i]['id'] : 0;
            $contact_form = null;

            if ($form_id) {
                $contact_form = wpcf7_contact_form($form_id);
            }

            if (!$form_id || !$contact_form) {
                $form_id = 0;
                $contact_form = WPCF7_ContactForm::get_template();
                $form_title = "";
                if ($template === "footer") {
                    $form_title = sprintf(__('Form: %s', 'website4829605'), "Footer");
                }
                if ($template === "header") {
                    $form_title = sprintf(__('Form: %s', 'website4829605'), "Header");
                }
                if ($template === "custom") {
                    if (is_singular()) {
                        global $post;
                        $id = isset($post->ID) ? $post->ID : '';
                    } else {
                        $id = 'template-' . $i;
                    }
                    $form_title = sprintf(__('Form: %s', 'website4829605'), "Custom-" . $id);
                }
                if ($count > 1) {
                    $form_title .= ' (' . ($i + 1) . ')';
                }
                $contact_form->set_title($form_title);
            }

            $fields = new CFormFields();
            $fields->parseFromHtml($form_html);

            $properties = $contact_form->get_properties();
            $properties['form'] = $fields->toString();

            if (!$form_id) {
                $defaultMail = array(
                    '"[your-subject]"',
                    "Subject: [your-subject]\n",
                    '[your-email]',
                    '[your-name]',
                    '[your-message]',
                );

                $actualMail = array(
                    __('feedback', 'website4829605'),
                    '',
                    '[email]',
                    $fields->hasField('name1') ? '[name1]' : '',
                );

                $customFields = '';
                $attachmentsFields = '';
                foreach ($fields->fields as $field) {
                    if ($field['name'] !== 'email' && $field['name'] !== 'name1') {
                        $customField = $fields->hasField($field['name']) ? '[' . $field['name'] . ']' : '';
                        $customFields .= $field['name'] . ': ' . $customField . ', ';
                    }
                    if (isset($field['type']) && $field['type'] === 'file') {
                        $attachmentsFields .= '[' . $field['name'] . ']';
                    }
                }
                $actualMail[] = $customFields;

                foreach (array('mail', 'mail_2') as $mail_key) {
                    foreach ($properties[$mail_key] as $key => &$prop) {
                        if (is_string($prop)) {
                            if ($key === 'attachments') {
                                $prop = $attachmentsFields;
                            } else {
                                $prop = str_replace(
                                    $defaultMail,
                                    $actualMail,
                                    $prop
                                );
                            }
                        }
                    }
                }
            }
            $contact_form->set_properties($properties);

            $form_id = $contact_form->save();

            $data_sources[] = array(
                'id' => $form_id,
            );
        }
        if ($template === "header") {
            update_option('header_forms_theme', $data_sources);
        }
        if ($template === "footer") {
            update_option('footer_forms_theme', $data_sources);
        }
        if ($template === "custom") {
            if(count($data_sources) > 0) {
                update_option('custom_forms_theme', $data_sources);
            }
        }
        return $data_sources;
    }

    public static $_formHtml;
    public static $_formIdx = 0;

    /**
     * Filter on wpcf7_form_elements
     * Replace default contact7 fields with Np fields
     *
     * @param string $html
     *
     * @return string
     */
    public static function _formElementsFilter($html) {
        $fields_html = preg_replace('#<form[^>]*>#', '', self::$_formHtml);
        $fields_html = str_replace('</form>', '', $fields_html);
        preg_match('/type="file"[\s\S]*?name="([\s\S]*?)"/', $fields_html, $matches);
        $file_input_name = isset($matches[1]) ? $matches[1] : '';
        if (strpos($file_input_name, '[') !== false) {
            $file_input_name = str_replace(array('[', ']'), array('',''), $file_input_name);
            $fields_html = str_replace($matches[1], $file_input_name, $fields_html);
        }
        $html = str_replace(
            array(
                'name="name"',
                'u-input ',
                'u-form-submit',
                'u-form-group',
                'u-file-group',
                'u-btn-submit ',
                'accept="IMAGES"',
                'accept="DOCUMENTS"',
                'accept="VIDEO"',
                'accept="AUDIO"',
                'multiple="multiple"',
            ),
            array(
                'name="name1"',
                'u-input wpcf7-form-control ',
                'u-form-submit wpcf7-form-control',
                'u-form-group wpcf7-form-control-wrap',
                'u-file-group wpcf7-form-control-wrap ' . $file_input_name,
                'u-btn-submit wpcf7-submit ',
                'accept=".jpg,.jpeg,.png,.gif"',
                'accept=".pdf,.doc,.docx,.ppt,.pptx,.odt"',
                'accept=".avi,.mov,.mp4,.mpg,.wmv"',
                'accept=".ogg,.m4a,.mp3,.wav"',
                '',
            ),
            $fields_html
        );
        $html .= '<input type="hidden" name="_contact7_backend" value="1">';
        return $html;
    }

    /**
     * Process Np form html
     *
     * @param string|int $form_id
     * @param string     $form_raw_html
     *
     * @return string
     */
    public static function getHtml($form_id, $form_raw_html) {
        if (function_exists('wpcf7_contact_form') && $form_id && ($contact_form = wpcf7_contact_form($form_id))) {
            self::$_formHtml = $form_raw_html;

            add_filter('wpcf7_form_elements', 'CForms::_formElementsFilter', 9);
            add_filter('wpcf7_form_novalidate', '__return_false');

            $form_class = '';
            if (preg_match('#<form.*?class="([^"]*)#', $form_raw_html, $m)) {
                $form_class = $m[1];
            }
            $form_html = $contact_form->form_html(array('html_class' => $form_class . ' u-form-custom-backend'));
            if (strpos($form_raw_html, 'redirect="true"') !== false && preg_match('#redirect-address="([^"]*)"#', $form_raw_html, $m)) {
                $form_html = str_replace('<form', '<form redirect-address="' . $m[1] . '"', $form_html);
            }

            remove_filter('wpcf7_form_elements', 'CForms::_formElementsFilter', 9);
            remove_filter('wpcf7_form_novalidate', '__return_false');
        } else {
            $form_html = preg_replace('#action="[^"]*#', 'action="#', $form_raw_html);
        }
        if (self::$_formIdx === 0) {
            $form_html = CForms::getScriptsAndStyles() . "\n" . $form_html;
        }
        self::$_formIdx++;
        return $form_html;
    }

    /**
     * Common scripts and styles for all forms
     *
     * @return string
     */
    public static function getScriptsAndStyles() {
        ob_start();
        ?>
        <script>
            function onSuccess(event) {
                var msgContainer = jQuery(event.currentTarget).find('.wpcf7-response-output');
                msgContainer.removeClass('u-form-send-error').addClass('u-form-send-message u-form-send-success');
                msgContainer.show();
                var redirectAddress = jQuery(event.currentTarget).find('[redirect-address]').attr('redirect-address');
                if (redirectAddress) {
                    setTimeout(function () {
                        location.replace(redirectAddress);
                    }, 2000);
                }
            }
            function onError(event) {
                var msgContainer = jQuery(event.currentTarget).find('.wpcf7-response-output');
                msgContainer.removeClass('u-form-send-success').addClass('u-form-send-message u-form-send-error');
                msgContainer.show();
            }

            jQuery('body')
                .on('wpcf7mailsent',   '.u-form .wpcf7', onSuccess)
                .on('wpcf7invalid',    '.u-form .wpcf7', onError)
                .on('wpcf7:unaccepted', '.u-form .wpcf7', onError)
                .on('wpcf7spam',       '.u-form .wpcf7', onError)
                .on('wpcf7:aborted',    '.u-form .wpcf7', onError)
                .on('wpcf7mailfailed', '.u-form .wpcf7', onError);
        </script>
        <style>
            .u-form .wpcf7-response-output {
                /*position: relative !important;*/
                margin: 0 !important;
                bottom: -70px!important;
            }
            .u-form .wpcf7 .ajax-loader {
                margin-left: -24px;
                margin-right: 0;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Filter on wpcf7_ajax_json_echo
     * Replace selectors for Np forms
     *
     * @param array $items
     *
     * @return array
     */
    public static function _ajaxJsonEchoFilter($items) {
        if (isset($_POST['_contact7_backend']) && !empty($items['invalids'])) {
            foreach ($items['invalids'] as &$invalid) {
                $invalid['into'] = str_replace('span.wpcf7-form-control-wrap.', 'div.u-form-group.u-form-', $invalid['into']);
            }
        }
        return $items;
    }
}

add_filter('wpcf7_ajax_json_echo', 'CForms::_ajaxJsonEchoFilter');