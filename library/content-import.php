<?php
defined('ABSPATH') or die;

class ThemeContentImport {

    /**
     * Action on admin_notices
     *
     * @global TGM_Plugin_Activation $tgmpa
     */
    public static function contentImportNoticeAction() {
        global $tgmpa;
        $import_content_can = has_action('nicepage_import_content');
        $installation_required = !defined('APP_PLUGIN_VERSION');
        $import_href = $installation_required
            ? add_query_arg(array('np-import' => '1'), $tgmpa->get_tgmpa_url())
            : '#';
        ?>
        <div id="content-import-notice" class="updated">
            <p>
                <?php echo __('Do you want to import Content?', 'website4829605'); ?>
                &nbsp; &nbsp; &nbsp; &nbsp;
                <a id="import-import-content" class="import-button button" href="<?php echo $import_href; ?>"><?php echo __('Import content', 'website4829605'); ?></a>
                <a id="import-replace-content" class="<?php if (get_option('np_imported_content') === false) echo 'hidden'; ?> import-button button" href="<?php echo $import_href; ?>"><?php echo __('Replace previously imported content', 'website4829605'); ?></a>
                <a id="import-hide-notice" class="import-button button" href="#"><?php echo __('Hide notice', 'website4829605'); ?></a>
            </p>
        </div>
        <style>
            .import-button {
                text-decoration: none;
            }
            .import-button.importing:before {
                content: '';
                background-image: url('<?php if (defined('APP_PLUGIN_URL')) { echo APP_PLUGIN_URL; } ?>/importer/assets/images/preloader-01.gif');
                display: inline-block;
                width: 13px;
                height: 13px;
                background-size: 100% 100%;
                margin-right: 5px;
            }
        </style>
        <script>
            jQuery(document).ready(function ($) {
                function doAjax(action) {
                    return $.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'GET',
                        data: ({
                            action: action,
                            _ajax_nonce: '<?php echo wp_create_nonce('theme-content-importer'); ?>'
                        })
                    });
                }
                function bindImportAction(action, btn) {

                    var successMsg;
                    var captchaNotice = "<p>Keys for <strong>recaptcha</strong> replaced. If necessary, you can change keys manually in 'Site Settings' in the <strong>Nicepage</strong> plugin</p>";
                    var import_content_can = "<?php echo $import_content_can; ?>";
                    var failMsg = <?php echo json_encode(__('An error occurred while importing.', 'website4829605')); ?>;
                    var captchaKeys;
                    btn.unbind("click").click(function() {
                        $(this).addClass('importing');
                        doAjax(action).done(function (response) {
                            try {
                                captchaKeys = JSON.parse(response);
                            } catch (e) {
                                captchaKeys = null;
                            }
                            var captchaMsg = captchaKeys
                            && captchaKeys["newKeysEmpty"] === false
                            && captchaKeys["oldKeysEmpty"] === false ? captchaNotice : '';
                            if (import_content_can) {
                                successMsg = <?php echo json_encode(__('Content was successfully imported.', 'website4829605')); ?>;
                                $('#content-import-notice').html('<p>' + successMsg + '</p>' + captchaMsg);
                            } else {
                                successMsg = <?php echo json_encode(__('Please install and activate <a href="themes.php?page=tgmpa-install-plugins&plugin_status=install">Nicepage plugin</a> for import content.', 'website4829605')); ?>;
                                $('#content-import-notice').removeClass('updated').addClass('error').html('<p>' + successMsg + '</p>' + captchaMsg);
                            }
                        }).fail(function () {
                            $('#content-import-notice')
                                .removeClass('updated').addClass('error')
                                .html('<p>' + failMsg + '</p>');
                        });
                    });
                }
                <?php if (!$installation_required): ?>
                bindImportAction('theme_import_content', $('#import-import-content'));
                bindImportAction('theme_replace_content', $('#import-replace-content'));
                <?php endif; ?>

                $('#import-hide-notice').unbind("click").click(function() {
                    $('#content-import-notice').remove();
                    doAjax('theme_hide_import_notice');
                });
            });
        </script>
        <?php
    }

    /**
     * Action on admin_notices
     *
     * @global TGM_Plugin_Activation $tgmpa
     */
    public static function pluginRequiredNoticeAction() {
        global $tgmpa;
        ?>
        <div id="content-import-notice" class="notice notice-warning">
            <p><?php _e('Import process required plugin to be active.', 'website4829605'); ?></p>
            <p><?php $tgmpa->is_plugin_installed('nicepage')
                    ? _e('Please activate plugin to continue.', 'website4829605')
                    : _e('Please install and activate plugin to continue.', 'website4829605'); ?></p>
        </div>
        <?php
    }

    /**
     * Action on init
     */
    public static function addImportNoticeAction() {
        // hide old message import content because now wizard have import
        return;

        remove_action('admin_notices', 'themler_content_import_notice');

        if (!file_exists(get_template_directory() . '/content/content.json')) {
            return;
        }

        remove_action('admin_notices', 'NpImportNotice::contentImportNoticeAction');

        if (!empty($_GET['np-import'])) {
            add_action('admin_notices', 'ThemeContentImport::pluginRequiredNoticeAction');
        } else if (!self::getImportNoticeOption() && (!isset($_GET['page']) || $_GET['page'] !== 'tgmpa-install-plugins')) {
            // if plugin active
            if (class_exists('NpImportNotice')) {
                $plugin_content = file_exists(APP_PLUGIN_PATH . 'content/content.json');
                $theme_content = file_exists(get_template_directory() . '/content/content.json');
                if ($plugin_content && !$theme_content) {
                    add_action('admin_notices', 'NpImportNotice::contentImportNoticeAction');
                } elseif ($theme_content && !$plugin_content) {
                    add_action('admin_notices', 'ThemeContentImport::contentImportNoticeAction');
                } elseif ($theme_content && $plugin_content){
                    if(get_option('content_import_from_theme') == 'ok') {
                        add_action('admin_notices', 'ThemeContentImport::contentImportNoticeAction');
                    } else {
                        add_action('admin_notices', 'NpImportNotice::contentImportNoticeAction');
                    }
                }
            }
            else {
                add_action('admin_notices', 'ThemeContentImport::contentImportNoticeAction');
            }
        }
    }

    public static function addImportNoticeOption() {
        update_option('themler_hide_import_notice', true);
    }

    public static function getImportNoticeOption() {
        return get_option('themler_hide_import_notice');
    }

    public static function removeImportNoticeOption() {
        delete_option('themler_hide_import_notice');
    }

    /**
     * Action on wp_ajax_theme_hide_import_notice
     */
    public static function hideImportNoticeAction() {
        check_ajax_referer('theme-content-importer');
        self::addImportNoticeOption();
    }

    /**
     * Get php errors when failed import content
     *
     * @return string
     */
    public static function getResponseOptions() {
        $data = json_decode(get_option('np_captcha_keys_options', ''), true);
        $error_data = get_option('np_error_import');
        if ($error_data) {
            $error_data = json_decode($error_data, true);
            $data = array_merge($data, $error_data);
        }
        return json_encode($data);
    }

    /**
     * Action on wp_ajax_theme_import_content
     */
    public static function importContentAction() {
        check_ajax_referer('theme-content-importer');
        self::_importData(false);
        echo self::getResponseOptions();
        exit;
    }

    /**
     * Action on wp_ajax_theme_replace_content
     */
    public static function replaceContentAction() {
        check_ajax_referer('theme-content-importer');
        self::_importData(true);
        echo self::getResponseOptions();
        exit;
    }

    /**
     * Replace reCaptcha keys for import / change site settings
     */
    public static function replaceCaptchaKeysContact7Form() {
        $site_settings = json_decode(NpMeta::get('site_settings'));
        $result = array('newKeysEmpty' => true, 'oldKeysEmpty' => true);
        if (!isset($site_settings->captchaSiteKey) && !isset($site_settings->captchaSecretKey)) {
            return $result;
        }
        if (class_exists('WPCF7')) {
            if (method_exists('WPCF7', 'get_option') && method_exists('WPCF7', 'update_option')) {
                if ($site_settings->captchaSiteKey !== "" && $site_settings->captchaSecretKey !== "") {
                    $cf7_keys = WPCF7::get_option('recaptcha');
                    $new_keys = array($site_settings->captchaSiteKey => $site_settings->captchaSecretKey);
                    if (empty($cf7_keys)) {
                        WPCF7::update_option('recaptcha', $new_keys);
                    } else if ($cf7_keys !== $new_keys) {
                        WPCF7::update_option('recaptcha', $new_keys);
                    }
                    $result['newKeysEmpty'] = false;
                    $result['oldKeysEmpty'] = empty($cf7_keys);
                } else {
                    WPCF7::update_option('recaptcha', array());
                }
            }
        }
        update_option('np_captcha_keys_options', json_encode($result));
    }

    private static function _importData($remove_prev = false) {
        $content_dir = get_template_directory() . '/content';
        self::addImportNoticeOption();

        do_action('nicepage_import_content', $content_dir, $remove_prev);
    }

    /**
     * After activate/switch theme import content from theme folder again
     */
    public static function startThemeImportContent() {
        update_option('content_import_from_theme', 'ok');
    }
}

add_action('after_switch_theme', 'ThemeContentImport::startThemeImportContent');
add_action('init', 'ThemeContentImport::addImportNoticeAction', 100); // after after_switch_theme executing
add_action('wp_ajax_theme_hide_import_notice', 'ThemeContentImport::hideImportNoticeAction', 9);
add_action('wp_ajax_theme_import_content', 'ThemeContentImport::importContentAction', 9);
add_action('wp_ajax_theme_replace_content', 'ThemeContentImport::replaceContentAction', 9);

// enable import content banner after switch theme
add_action('after_switch_theme', 'ThemeContentImport::removeImportNoticeOption');