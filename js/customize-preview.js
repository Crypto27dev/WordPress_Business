/**
 * Live-update changed settings in real time in the Customizer preview.
 */

(function ($) {
    var api = wp.customize;

    // Site title.
    api('blogname', function (value) {
        value.bind(function (to) {
            $('.site-title a').text(to);
        });
    });

    // Site tagline.
    api('blogdescription', function (value) {
        value.bind(function (to) {
            $('.site-description').text(to);
        });
    });

    // Add custom-background-image body class when background image is added.
    api('background_image', function (value) {
        value.bind(function (to) {
            $('body').toggleClass('custom-background-image', '' !== to);
        });
    });

    // Color Scheme CSS.
    api.bind('preview-ready', function () {
        api.preview.bind('update-color-scheme-css', function (css) {
            $('style[color-style]').replaceWith(css);
        });
        api.preview.bind('update-font-scheme-css', function (css) {
            $('style[font-scheme]').replaceWith(css);
        });
        api.preview.bind('update-font-scheme-link', function (css) {
            var prevLink = $('#custom-google-font');
            if (prevLink.length) {
                prevLink.replaceWith(css);
            } else {
                $('#u-google-font').after(css);
            }
        });
        api.preview.bind('update-typography-scheme-css', function (css) {
            if ($('#theme-customized-typography').length) {
                $('#theme-customized-typography').replaceWith(css);
            } else {
                $('#theme-typography').after(css);
            }
        });
        api.preview.bind('update-base-font-size', function (size) {
            $('html').css('font-size', size + 'px');
        });
        api.selectiveRefresh.bind('partial-content-rendered', function(placement) {
            if (placement.container.is('.custom-logo-link')) {
                var newSrc = placement.container.find('img').attr('src');
                if (!newSrc) {
                    newSrc = placement.removedNodes.attr('data-default-src');
                }
                placement.removedNodes.find('img').attr('src', newSrc);
                placement.removedNodes.removeClass('customize-partial-refreshing');
                placement.container.replaceWith(placement.removedNodes); // revert changes back
            }
        });
    });

    ['width', 'height'].forEach(function (prop) {
        api('logo_' + prop, function (value) {
            value.bind(function (to) {
                var style = $('#theme-logo-css' + prop);
                if (!style.length) {
                    style = $('head').append('<style type="text/css" id="theme-logo-css-' + prop + '" />').find('#theme-logo-css-' + prop);
                }

                if (to) {
                    style.html('.u-logo img { max-' + prop + ': ' + _val(to) + ' !important; }');
                } else {
                    style.html('');
                }
            });
        });
    });

    function _val(str) {
        return str.indexOf('px') === -1 ? str + 'px' : str;
    }
})(jQuery);
