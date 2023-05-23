var Npwizard = (function($){

    var t;
    var current_step = '';
    var step_pointer = '';
    var callbacks = {
        do_next_step: function(btn) {
            do_next_step(btn);
        },
        install_plugins: function(btn){
            var plugins = new PluginManager();
            plugins.init(btn);
        },
        import_content: function(btn){
            var content = new ContentManager(btn.text);
            content.init(btn);
        },
        replace_content: function(btn){
            var content = new ContentManager(btn.text);
            content.init(btn);
        }
    };

    function window_loaded() {
        var maxHeight = 0;
        $('.npwizard-menu li.step').each(function(index) {
            $(this).attr('data-height', $(this).innerHeight());
            if($(this).innerHeight() > maxHeight) {
                maxHeight = $(this).innerHeight();
            }
        });
        $('.npwizard-menu li .detail').each(function(index) {
            $(this).attr('data-height', $(this).innerHeight());
            $(this).addClass('scale-down');
        });
        $('.npwizard-menu li.step').css('height', maxHeight);
        $('.npwizard-menu li.step:first-child').addClass('active-step');
        $('.npwizard-nav li:first-child').addClass('active-step');
        $('.npwizard-wrap').addClass('loaded');
        // init button clicks:
        $('.do-it').on('click', function(e) {
            e.preventDefault();
            step_pointer = $(this).data('step');
            current_step = $('.step-' + $(this).data('step'));
            $('.npwizard-wrap').addClass('spinning');
            if($(this).data('callback') && typeof callbacks[$(this).data('callback')] != 'undefined'){
                // we have to process a callback before continue with form submission
                callbacks[$(this).data('callback')](this);
                return false;
            } else {
                return true;
            }
        });
        $('.theme-presets a').on('click', function(e) {
            e.preventDefault();
            var $ul = $(this).parents('ul').first();
            $ul.find('.current').removeClass('current');
            var $li = $(this).parents('li').first();
            $li.addClass('current');
            var newcolor = $(this).data('style');
            $('#new_style').val(newcolor);
            return false;
        });
    }

    function do_next_step(btn) {
        current_step.removeClass('active-step');
        $('.nav-step-' + step_pointer).removeClass('active-step');
        current_step.addClass('done-step');
        $('.nav-step-' + step_pointer).addClass('done-step');
        current_step.fadeOut(500, function() {
            current_step = current_step.next();
            step_pointer = current_step.data('step');
            current_step.fadeIn();
            current_step.addClass('active-step');
            $('.nav-step-' + step_pointer).addClass('active-step');
            $('.npwizard-wrap').removeClass('spinning');
        });
    }

    function PluginManager(){

        var complete;
        var items_completed = 0;
        var current_item = '';
        var $current_node;
        var current_item_hash = '';

        function ajax_callback(response){
            if(typeof response == 'object' && typeof response.message != 'undefined'){
                $current_node.find('span').text(response.message);
                if(typeof response.url != 'undefined'){
                    // we have an ajax url action to perform.

                    if(response.hash == current_item_hash){
                        $current_node.find('span').text("failed");
                        $current_node.find('.spinner').css('visibility','hidden');
                        $('.npwizard-wrap').removeClass('spinning');
                        $('.npwizard-do-plugins').append('<p class="failed-activate-np">Unable to activate <strong>"' + response.current_np + '"</strong> plugin, because plugin <strong>"' + response.db_np + '"</strong> is already activated. Please <a href="' + response.plugin_url + '">deactivate</a> <strong>"' + response.db_np + '"</strong> first.</p>');
                        $('.button-primary:visible').hide();
                    }else {
                        current_item_hash = response.hash;
                        jQuery.post(response.url, response, function(response2) {
                            process_current();
                            $current_node.find('span').text(response.message + npwizard_params.verify_text);
                        }).fail(ajax_callback);
                    }

                }else if(typeof response.done != 'undefined'){
                    // finished processing this plugin, move onto next
                    find_next();
                }else{
                    // error processing this plugin
                    find_next();
                }
            }else{
                // error - try again with next plugin
                $current_node.find('span').text("ajax error");
                find_next();
            }
        }
        function process_current() {
            if(current_item){
                jQuery.post(npwizard_params.ajaxurl, {
                    action: 'setup_plugins',
                    wpnonce: npwizard_params.wpnonce,
                    slug: current_item
                }, ajax_callback).fail(ajax_callback);
            }
        }
        function find_next(){
            var do_next = false;
            if($current_node){
                if(!$current_node.data('done_item')){
                    items_completed++;
                    $current_node.data('done_item', 1);
                }
                $current_node.find('.spinner').css('visibility','hidden');
            }
            var $li = $('.npwizard-do-plugins li');
            $li.each(function(){
                if(current_item == '' || do_next){
                    current_item = $(this).data('slug');
                    $current_node = $(this);
                    process_current();
                    do_next = false;
                }else if($(this).data('slug') == current_item){
                    do_next = true;
                }
            });
            if(items_completed >= $li.length){
                // finished all plugins!
                complete();
            }
        }

        return {
            init: function(btn){
                complete = function(){
                    do_next_step();
                };
                find_next();
            }
        }
    }


    function ContentManager(btnText){

        function doAjax(action, url, _ajax_nonce, importOptions) {
            return $.ajax({
                url: url,
                type: 'GET',
                data: ({
                    action: action,
                    _ajax_nonce: _ajax_nonce,
                    importOptions: importOptions
                })
            });
        }

        var npAction;
        if (btnText === "Import Content") {
            npAction = npwizard_params.actionImportContent;
        } else {
            npAction = npwizard_params.actionReplaceContent
        }

        function stopWithError(msg) {
            $('.npwizard-wrap')
                .removeClass('spinning')
                .html(`<p>Failed to import content. An error occurred: <span style="color: red;">${msg}</span></p>`);
        }
        // set import options
        var importOptions = {};
        importOptions.importSidebarsContent = $('#importSidebarsContent').attr('checked') === 'checked';

        doAjax(npAction, npwizard_params.urlContent, npwizard_params.wpnonceContent, importOptions).done(function (response) {
            if (response && response.indexOf('{') === 0) {
                let responseOptions = JSON.parse(response);
                if (responseOptions && responseOptions.error) {
                    stopWithError(responseOptions.error);
                }
            }
            complete();
        }).fail(function (xhr, status, error) {
            if (error) {
                stopWithError(error);
            }
        });

        return {
            init: function(btn){
                complete = function(){
                    do_next_step();
                };
            }
        }
    }

    return {
        init: function(){
			t = this;
			$(window_loaded);
        },
        callback: function(func){
            console.log(func);
            console.log(this);
        }
    }

})(jQuery);

Npwizard.init();