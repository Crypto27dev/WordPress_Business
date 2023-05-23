/**
 * Add a listener to the Color Scheme control to update other color controls to new values/defaults.
 * Also trigger an update of the Color Scheme CSS when a color is changed.
 */

(function (api) {
    function onFirstReady(func) {
        var doOnce = function () {
            wp.customize.previewer.unbind('ready', doOnce);
            func();
        };
        wp.customize.previewer.bind('ready', doOnce);
    }

    // Generate the CSS for the current Color Scheme.
    function updateColorsCSS() {
        var colorsArray = {
            bgColor: api('color_background')(),
            bodyColors: [api('color_white_contrast')(), api('color_shading_contrast')()],
            colors: [api('color_1')(), api('color_2')(), api('color_3')(), api('color_4')(), api('color_5')()],
            customColors: nicepageThemeSettings && nicepageThemeSettings.colorScheme && nicepageThemeSettings.colorScheme.customColors
        };

        var win = wp.customize.previewer.targetWindow();

        var colorSchemeName = ColorSchemes.createPaletteFromColorArray(colorsArray);
        var colorSchemeObject = ColorSchemes.getColorSchemeByName(colorSchemeName);
        var styleName = win.jQuery('style[color-style]').attr('color-style');
        var colorStyleObject = ColorStyles.getColorStyleByName(styleName);
        if (!colorSchemeObject.palettes) {
            ColorLab.createPalettes(colorSchemeObject);
        }
        var colorSchemeCss = ColorLab.getCss(colorSchemeObject, colorStyleObject);

        colorSchemeCss = colorSchemeCss.replace('id="color-scheme"', 'id="theme-customized-color-scheme"');
        api('colors_css').set(colorSchemeCss);
        api.previewer.send('update-color-scheme-css', colorSchemeCss);
    }

    var colorProps = ['color_1', 'color_2', 'color_3', 'color_4', 'color_5', 'color_background', 'color_white_contrast', 'color_shading_contrast'];

    _.each(colorProps, function (prop) {
        api(prop, function (setting) {
            setting.bind(updateColorsCSS);
        });
    });

    function changeColorScheme(name, setDefaultsOnly) {
        var scheme = ColorSchemes.getColorSchemeByName(name);
        colorProps.forEach(function (prop) {
            var value = getPropertyFromColorScheme(prop, scheme);
            if (value && !setDefaultsOnly) {
                api(prop).set(value);
            }
            // change default color
            jQuery('#customize-control-' + prop)
                .find('[data-default-color]')
                .attr('data-default-color', value)
                .data('defaultColor', value)
                .data('wpWpColorPicker').options.defaultColor = value;
        });
    }

    api('color_scheme', function (setting) {
        setting.bind(function (name) {
            changeColorScheme(name);
        });
    });

    jQuery(function () {
        jQuery('[id*=customize-control-color_]').addClass('disable-control');

        onFirstReady(function () {
            var win = wp.customize.previewer.targetWindow();
            var computedStyle = win.getComputedStyle(win.document.querySelector('.u-body'));

            var customizedStyle = win.jQuery('[color-style]').eq(1);
            customizedStyle.detach();

            var get = function (_var) {
                return (computedStyle.getPropertyValue('--' + _var) || '').trim();
            };

            // create default scheme
            ColorSchemes.createPaletteFromColorArray(nicepageThemeSettings.colorScheme);
            // set name and move to first
            var defaultScheme = ColorSchemes.pop();
            defaultScheme.isDefault = true;
            defaultScheme.name = 'default';
            ColorSchemes.unshift(defaultScheme);

            win.jQuery('head').append(customizedStyle);

            api('color_1').set(get('palette-1-base'));
            api('color_2').set(get('palette-2-base'));
            api('color_3').set(get('palette-3-base'));
            api('color_4').set(get('palette-4-base'));
            api('color_5').set(get('palette-5-base'));
            api('color_background').set(get('bg-color'));
            api('color_white_contrast').set(get('white-contrast'));
            api('color_shading_contrast').set(get('shading-contrast'));

            var schemesSelect = jQuery('#customize-control-color_scheme select');

            ColorSchemes.forEach(function (scheme) {
                if (scheme.isDefault) {
                    schemesSelect.append('<option value="' + scheme.name + '">' + (scheme.name === 'default' ? 'Theme Colors' : capitalize(scheme.name)) + '</option>');
                }
            });

            changeColorScheme(api('color_scheme')(), true);
            schemesSelect.val(api('color_scheme')());

            jQuery('[id*=customize-control-color_]').removeClass('disable-control');
        });
    });

    function getPropertyFromColorScheme(prop, scheme) {
        switch (prop) {
            case 'color_background':
                return scheme.bgColor;
            case 'color_white_contrast':
                return scheme.bodyColors[0];
            case 'color_shading_contrast':
                return scheme.bodyColors[1];
            default:
                var idx = parseInt(prop.replace('color_', ''));
                return scheme.colors[idx - 1];
        }
    }


    function updateFontsCSS() {
        var fontsArray = {
            name: '',
            fonts: {
                heading: api('font_heading')(),
                text: api('font_text')()
            }
        };

        var schemeName = PageFontSchemes.createFontScheme(fontsArray);
        var scheme = PageFontSchemes.getFontSchemeByName(schemeName);
        var fontSchemeCss = FontLab.getCss(scheme);
        var fonts = FontsToLink.getThemeList(scheme.name);
        var subsetParam = FontsToLink.getSubsetParam(scheme.name);
        var subset = subsetParam ? '&' + subsetParam : '';
        var fontSchemeLink = '';
        if (fonts && fonts.length) {
            fontSchemeLink = '<link id="custom-google-font" rel="stylesheet" href="https://fonts.googleapis.com/css?family=' + fonts.join('|') + subset + '" />';
        }

        fontSchemeCss = fontSchemeCss.replace('id="font-scheme"', 'id="theme-customized-font-scheme"');
        api('fonts_css').set(fontSchemeCss);
        api('fonts_link').set(fontSchemeLink);
        api.previewer.send('update-font-scheme-css', fontSchemeCss);
        api.previewer.send('update-font-scheme-link', fontSchemeLink);
    }

    api('font_heading', function (setting) {
        setting.bind(updateFontsCSS);
    });
    api('font_text', function (setting) {
        setting.bind(updateFontsCSS);
    });

    function changeFontScheme(name) {
        var scheme = PageFontSchemes.getFontSchemeByName(name);
        api('font_heading').set(scheme.fonts.heading);
        api('font_text').set(scheme.fonts.text);
    }

    api('font_scheme', function (setting) {
        setting.bind(function (name) {
            changeFontScheme(name);
        });
    });

    jQuery(function () {
        jQuery('[id*=customize-control-font_]').addClass('disable-control');

        onFirstReady(function () {
            var win = wp.customize.previewer.targetWindow();

            // create default scheme
            var defaultStyle = win.jQuery('#theme-font-scheme');
            var defaultScheme = PageFontSchemes.parseFontSchemeFromDom(defaultStyle);
            defaultScheme.name = 'default';
            PageFontSchemes.unshift(defaultScheme);

            // create current scheme
            var currentStyle = win.jQuery('#theme-customized-font-scheme');
            if (!currentStyle.length) {
                currentStyle = defaultStyle;
            }
            var currentScheme = PageFontSchemes.parseFontSchemeFromDom(currentStyle);

            var foundScheme = PageFontSchemes.find(function (scheme) {
                return scheme.fonts.heading === currentScheme.fonts.heading &&
                    scheme.fonts.text === currentScheme.fonts.text;
            });

            var schemesSelect = jQuery('#customize-control-font_scheme select');
            if (!foundScheme) {
                currentScheme.name = 'current';
                PageFontSchemes.unshift(currentScheme);
                foundScheme = currentScheme;
            }

            PageFontSchemes.forEach(function (scheme) {
                schemesSelect.append('<option value="' + scheme.name + '">' +
                    (scheme.name === 'default' ? 'Theme Fonts (' : scheme.name === 'current' ? 'Current (' : '') +
                    scheme.fonts.headingTitle + ' - ' + scheme.fonts.textTitle +
                    (scheme.name === 'default' ? ')' : scheme.name === 'current' ? ')' : '') +
                    '</option>');
            });
            schemesSelect.val(foundScheme.name);

            api('font_heading').set(currentScheme.fonts.heading);
            api('font_text').set(currentScheme.fonts.text);

            var allFonts = AllFonts.get(),
                headingFontSelect = jQuery('#customize-control-font_heading select'),
                textFontSelect = jQuery('#customize-control-font_text select');
            allFonts.forEach(function (font) {
                var optionHtml = '<option value="' + AllFonts.classNameToFontFamily(font.className) + '">' + font['font-family'].replace(/:.*/g, '') + '</option>';
                headingFontSelect.append(optionHtml);
                textFontSelect.append(optionHtml);
            });

            headingFontSelect.val(api('font_heading')());
            textFontSelect.val(api('font_text')());

            jQuery('[id*=customize-control-font_]').removeClass('disable-control');
        });
    });


    jQuery(function () {
        jQuery('[id*=customize-control-typography_]').addClass('disable-control');

        onFirstReady(function () {
            var win = wp.customize.previewer.targetWindow();
            var schemesSelect = jQuery('#customize-control-typography_scheme select');
            schemesSelect.append('<option value="default">Theme Typography</option>');

            PageTypographies.forEach(function (scheme) {
                schemesSelect.append('<option value="' + scheme.name + '">' + scheme.name + '</option>');
            });
            schemesSelect.val(api('typography_scheme')());
            var baseFontSize = parseInt(win.jQuery('html').css('font-size'));
            jQuery('#customize-control-typography_base_size input').val(baseFontSize);

            jQuery('[id*=customize-control-typography_]').removeClass('disable-control');
        });
    });

    function setTypographyCss(css) {
        css = css.replace(/id="[^"]*?"/g, 'id="theme-customized-typography"');
        api('typography_css').set(css);
        api.previewer.send('update-typography-scheme-css', css);
    }

    api('typography_scheme', function (setting) {
        setting.bind(function (name) {
            var win = wp.customize.previewer.targetWindow();
            if (name === 'default') {
                setTypographyCss('');
            } else {
                var scheme = PageTypographies.getTypoByName(name);

                var typographyCss = TypographyLab.getCss(scheme, undefined, {
                    colorScheme: win.jQuery('style[color-scheme]').last().attr('color-scheme'),
                    colorStyle: win.jQuery('style[color-style]').last().attr('color-style')
                });
                setTypographyCss(typographyCss);

                setTimeout(function () {
                    api('typography_base_size').set(scheme.htmlBaseSize || 16);
                    api('typography_heading_weight').set(scheme.h1['font-weight'] || 400);
                }, 0);
            }
        });
    });

    api('typography_base_size', function (setting) {
        setting.bind(function (size) {
            api.previewer.send('update-base-font-size', size);
        });
    });

    api('typography_heading_weight', function (setting) {
        setting.bind(function (weight) {
            var win = wp.customize.previewer.targetWindow();
            var style = win.jQuery('#theme-customized-typography')[0] || win.jQuery('#theme-typography')[0];
            if (!style) {
                return;
            }
            var css = style.outerHTML;
            css = css.replace(/(--(title|subtitle|h\d)-font-weight: )[^;]*?;/g, '$1' + weight + ';');

            setTypographyCss(css);
        });
    });

    function updateResolvedCss() {
        var win = wp.customize.previewer.targetWindow();
        var siteStyleCss = jQuery('<div>')
            .append(api('colors_css').get() || win.jQuery('style[color-scheme]').last().clone())
            .append(api('typography_css').get() || win.jQuery('style[typography]').last().clone())
            .append(api('fonts_css').get() || win.jQuery('style[font-scheme]').last().clone())
            .html();

        var css = PublishNPCssCreator.create(nicepageStyleTemplate, siteStyleCss);
        api('resolved_css').set(css);
    }

    ['colors_css', 'typography_css', 'fonts_css'].forEach(function (prop) {
        api(prop, function (setting) {
            setting.bind(updateResolvedCss);
        });
    });


    api.bind('ready', function () {
        // Inject additional heading into the menu_options section's head container.
        api.section('menu_options', function (section) {
            section.headContainer.prepend(
                wp.template('nav-menu-theme-options-header')(api.Menus.data)
            );
        });
    });

    var dependency = function (controlSelector, checkboxProperty) {
        var update = function () {
            jQuery(controlSelector).toggleClass('disable-control', !api(checkboxProperty)());
        };

        api.bind('ready', update);
        api(checkboxProperty, function (value) {
            value.bind(update);
        });
    };

    dependency('#customize-control-excerpt_words', 'excerpt_auto');
    dependency('#customize-control-excerpt_min_remainder', 'excerpt_auto');
    dependency('#customize-control-excerpt_allowed_tags', 'excerpt_use_tag_filter');
    dependency('#customize-control-morelink_template', 'show_morelink');

    dependency('#customize-control-menu_trim_len', 'menu_trim_title');
    dependency('#customize-control-submenu_trim_len', 'menu_trim_title');
    dependency('#customize-control-menu_allowed_tags', 'menu_use_tag_filter');

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.substring(1).toLowerCase();
    }
})(wp.customize);
