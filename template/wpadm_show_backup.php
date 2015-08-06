<div class="wrap">
    <script src="<?php echo plugin_dir_url(__FILE__) . 'js/jquery.arcticmodal-0.3.min.js'?>" type="text/javascript"></script>
    <link rel='stylesheet'  href='<?php echo plugin_dir_url(__FILE__) . 'js/jquery.arcticmodal-0.3.css'?>' type='text/css' media='all' />
    <style>
        .pointer {
            cursor: pointer;
        }
    </style>
    <script>
        var home_url = '<?php echo SITE_HOME; ?>';
        function blickForm(id, t)
        {
            if(t.checked == true) {
                t.checked = false;
            }
            l = jQuery('#' + id).length;
            showRegistInfo(false);
            if (l > 0) {
                blick(id);
            } 
        }
        function showRegistInfo(show)
        {
            display = jQuery('#cf_activate').css('display');
            if (display == 'none') {
                jQuery('#cf_activate').show('slow');
                jQuery('#registr-show').html("<?php langWPADM::get('Hide'); ?>");
                jQuery('#title-regisr').css("padding" , "0px 0px");
                jQuery('#registr-choice-icon').removeClass("dashicons-arrow-down").addClass('dashicons-arrow-up');
            } else {
                if (show) {
                    jQuery('#cf_activate').hide('slow');
                    jQuery('#registr-show').html("<?php langWPADM::get('Show'); ?>");
                    jQuery('#title-regisr').css("padding" , "20px 0px");
                    jQuery('#registr-choice-icon').removeClass("dashicons-arrow-up").addClass('dashicons-arrow-down');
                }
            }
        }
        function showSetting(show)
        {
            display = jQuery('#setting_active').css('display');
            if (display == 'none') {
                jQuery('#setting_active').show(1000);
                jQuery('#setting-show').html("<?php langWPADM::get('Hide'); ?>");
                jQuery('#title-setting').css("padding" , "0px 0px");
                jQuery('#setting-choice-icon').removeClass("dashicons-arrow-down").addClass('dashicons-arrow-up');
            } else {
                if (show) {
                    jQuery('#setting_active').hide('slow');
                    jQuery('#setting-show').html("<?php langWPADM::get('Show'); ?>");
                    jQuery('#title-setting').css("padding" , "20px 0px");
                    jQuery('#setting-choice-icon').removeClass("dashicons-arrow-up").addClass('dashicons-arrow-down');
                }
            }
        }
        process_flag = 0;
        function start_local_backup()
        {
            d = new Date();
            var data_backup = {
                'action': 'wpadm_local_backup',
                'time': Math.ceil(  (d.getTime() + (-d.getTimezoneOffset() * 60000 ) ) / 1000 )
            };  
            jQuery("#logs-form").show("slow");
            jQuery("#action-buttons").css('margin-top', '8px');
            jQuery("#support-button").css('margin-top', '8px');
            jQuery("#log-backup").html('');
            jQuery(".title-logs").css('display', 'block');
            jQuery(".title-status").css('display', 'none');
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: data_backup,
                beforeSend: function(){
                    process_flag = 1
                    processBar();
                    showTime();

                },
                success: function(data){
                    process_flag = 0;
                    if (data.result == 'success') {
                        jQuery('.title-logs').css('display', 'none');
                        jQuery('.title-status').css({'display':'block', 'color':'green'});
                        jQuery('.title-status').html('<?php langWPADM::get('Local Backup was created successfully'); ?>');
                    } else {
                        jQuery('.title-logs').css('display', 'none');
                        jQuery('.title-status').css({'display':'block', 'color':'red'});
                        jQuery('.title-status').html("<?php langWPADM::get('Local Backup wasn\'t created'); ?>");
                    }
                    showData(data);
                    jQuery('.table').css('display', 'table');

                },
                error: function(jqXHR, textStatus, errorThrown){
                    processStop();
                    AjaxAlert(jqXHR, textStatus, errorThrown);
                },
                dataType: 'json'
            });
        }
        function AjaxAlert(jqXHR, textStatus, errorThrown)
        {
            var msg = 'Website "' + home_url + '" returned an error during operation with return:<br /><br /> <span style="font-size:13px; font-style: italic;">code: ' + jqXHR.status + ', text status: ' + textStatus + ', text: ' + errorThrown + "</span>";
            jQuery("#ajax-message").html(msg);
            jQuery("#msg_ajax").val(msg);
            jQuery('#ajax-alert').arcticmodal({
                beforeOpen: function(data, el) {
                    jQuery('#ajax-alert').css('display','block');
                },
                afterClose: function(data, el) {
                    jQuery('#ajax-alert').css('display','none');
                }
            });
        }

        var auth_param = <?php echo isset($dropbox_options['app_key']) && isset($dropbox_options['app_secret']) && isset($dropbox_options['uid']) && $dropbox_options['uid'] != '' ? 'false' : 'true' ?>;
        function start_dropbox_backup()
        {
            if (auth_param === false) {
                d = new Date();
                process_flag = 0;
                var data_backup = {
                    'action': 'wpadm_dropbox_create',
                    'time': Math.ceil(  (d.getTime() + (-d.getTimezoneOffset() * 60000 ) ) / 1000 ),
                };  
                jQuery("#logs-form").show("slow");
                jQuery("#action-buttons").css('margin-top', '8px');
                jQuery("#support-button").css('margin-top', '8px');
                jQuery("#log-backup").html('');
                jQuery(".title-logs").css('display', 'block');
                jQuery(".title-status").css('display', 'none');
                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data_backup,
                    beforeSend: function(){
                        process_flag = 1
                        processBar();
                        showTime();

                    },
                    success: function(data){
                        process_flag = 0;
                        if (data.result == 'success') {
                            jQuery('.title-logs').css('display', 'none');
                            jQuery('.title-status').css({'display':'block', 'color':'green'});
                            jQuery('.title-status').html('<?php langWPADM::get('Dropbox Backup was created successfully'); ?>');
                        } else {
                            jQuery('.title-logs').css('display', 'none');
                            jQuery('.title-status').css({'display':'block', 'color':'red'});
                            jQuery('.title-status').html("<?php langWPADM::get('Dropbox Backup wasn\'t created. '); ?>" + data.error);
                        }
                        showData(data);
                        jQuery('.table').css('display', 'table');

                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        processStop();
                        AjaxAlert(jqXHR, textStatus, errorThrown);
                    },
                    dataType: 'json'
                });
            } else {
                jQuery('#is-dropbox-auth').arcticmodal({
                    beforeOpen: function(data, el) {
                        jQuery('#is-dropbox-auth').css('display','block');

                    },
                    afterClose: function(data, el) {
                        jQuery('#is-dropbox-auth').css('display','none');
                        showSetting(false);
                        blick('app_key', 4);
                        blick('app_secret', 4);
                    }
                });
            }
        }
        function showData(data)
        {
            size_backup = data.size / 1024 / 1024;
            if (data.size != 0 || data.result != 'error') {
                var img_table = 
                '<img src="<?php echo plugin_dir_url(__FILE__) . "ok.png" ;?>" title="Successful" alt="Successful" style="float: left; width: 20px; height: 20px;" />' +
                '<div style="margin-top :1px;float: left;"><?php echo langWPADM::get('Successful', false);?></div>';
                name_backup = data.name;
            } else {
                var img_table =
                '<img src="<?php echo plugin_dir_url(__FILE__) . "not-ok.png" ;?>" title="fail" alt="fail" style="float: left; width: 20px; height: 20px;" />' +
                '<div style="margin-top :1px;float: left;"><?php echo langWPADM::get('Fail', false);?>&nbsp;&nbsp;(<a style="text-decoration:underline;"><?php echo langWPADM::get('Show Details', false);?></a>)</div>';
                name_backup = '<?php echo langWPADM::get('Not available', false);?>';
            }
            info = "";
            if (data.data) {
                for(i = 0; i < data.data.length; i++) {
                    e = data.data[i].split('/');
                    info += '<tr style="border: 0;">' +
                    '<td style="border: 0;padding: 0px;"><a href="<?php echo content_url(WPADM_DIR_NAME) . '/'; ?>' + data.name + '/' + e[e.length - 1] + '">' + e[e.length - 1] + '</td>' +
                    '</tr>' ;
                }

                co = jQuery('.number-backup').length + 1;
                jQuery('.table > tbody:last').after(
                '<tr>'+
                '<td class="number-backup" onclick="shows(\'' + data.md5_data + '\', this)">' +
                co + 
                '</td>' +
                '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)" style="text-align: left; padding-left: 7px;" >' +
                data.time + 
                '</td>' +
                '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)">' +
                name_backup +
                '</td>' +
                '<td class="pointer" onclick="shows(\'' + data.md5_data + '\',this)">' +
                data.counts +
                '</td>' +
                '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)">' +
                img_table +
                '</td>' +
                '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)">' +
                data.type + ' <?php langWPADM::get('backup')?>' +
                '</td>' +
                '<td class="pointer" onclick="shows(\'' + data.md5_data + '\', this)">' +
                size_backup.toFixed(2) + "<?php langWPADM::get('Mb')?>" +
                '</td>' +
                '<td>' + 
                '<a href="javascript:void(0)" class="button-wpadm" title="<?php langWPADM::get('Restore')?>" onclick="show_recovery_form(\'' + data.type + '\', \'' + data.name + '\')"><span class="pointer dashicons dashicons-backup"></span><?php langWPADM::get('Restore')?></a> &nbsp;' +
                '<a href="javascript:void(0)" class="button-wpadm" title="<?php langWPADM::get('Delete')?>" onclick="delete_backup(\'' + data.name + '\', \'' + data.type + '\')"><span class="pointer dashicons dashicons-trash"></span><?php langWPADM::get('Delete')?></a> &nbsp;' +
                '</td>' +
                '</tr>'+
                '<tr id="' + data.md5_data + '" style="display: none;">'+
                '<td colspan="2">' +
                '</td>' +
                '<td align="center" style="padding: 0px; width: 350px;">' +
                '<div style="overflow: auto; max-height: 150px;">' +
                '<table border="0" align="center" style="width: 100%;" class="info-path">' +
                info +
                '</table>' +
                '</div>' +
                '</td>' +
                '<td colspan="6"></td>' +
                '</tr>')
            }
        }
        var logs = [];
        function processBar()
        {      
            var data_log = {
                'action': 'wpadm_logs',
            };   
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: data_log,
                success: function(response){
                    eval("var data=" + response);
                    for(s in data.log) {
                        if (jQuery.inArray(s , logs) == -1) {
                            l = jQuery("#log-backup").html();
                            l = "<div>" + data.log[s] + "</div>" + l;
                            jQuery("#log-backup").html(l);
                        }
                    }
                    if (process_flag == 1) {
                        setTimeout('processBar()', 3000);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown){
                    processStop();
                    AjaxAlert(jqXHR, textStatus, errorThrown);
                },
            });
        }

        function showTime(t)
        {
            if (process_flag == 1) {
                if ( (typeof t) == 'undefined') {
                    t = 1;
                } else {
                    t = t + 1;
                }
                time = t + " <?php langWPADM::get('sec.'); ?>";
                jQuery("#time_backup").html(time);
                setTimeout(function() { showTime(t) }, 1000); 
            }
        }
        function processStop()
        {
            process_flag = 0;
        }
        function delete_backup(backup, type)
        {
            document.delete_backups.backup_name.value = backup;
            document.delete_backups.backup_type.value = type;
            document.delete_backups.submit();
        }
        function create_backup (type) {
            if (type == 'auth') {
                document.form_auth_backup_create.submit();
            }
        }
        function show_recovery_form(type, name)
        {
            var act = '';
            if (type == 'local') {
                act = 'wpadm_local_restore';
            } else {
                act = 'wpadm_restore_dropbox';
            }
            var data_backup = {
                'action': act,
                'name': name,
            };  
            jQuery("#log-backup").html('');
            jQuery("#action-buttons").css('margin-top', '8px');
            jQuery("#support-button").css('margin-top', '8px');
            jQuery(".title-logs").css('display', 'block');
            jQuery(".title-status").css('display', 'none');
            jQuery("#logs-form").show("slow");
            jQuery("#action-buttons").css('margin-top', '8px');
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: data_backup,
                beforeSend: function(){
                    process_flag = 1
                    processBar();
                    showTime();

                },
                success: function(data){
                    process_flag = 0;
                    if (data.result == 'success') {
                        jQuery('.title-logs').css('display', 'none');
                        jQuery('.title-status').css({'display':'block', 'color':'green'});
                        if (type == 'local') {
                            str = '<?php langWPADM::get('Local Backup (%s) was restored successfully'); ?>';
                            str = str.replace('%s', name);
                            jQuery('.title-status').html(str);
                        } else {
                            str = '<?php langWPADM::get('Dropbox Backup (%s) was restored successfully'); ?>';
                            str = str.replace('%s', name);
                            jQuery('.title-status').html(str);
                        }
                    } else {
                        jQuery('.title-logs').css('display', 'none');
                        jQuery('.title-status').css({'display':'block', 'color':'red'});
                        if (type == 'local') {
                            str = "<?php langWPADM::get('Local Backup (%s) wasn\'t restored'); ?>";
                            str = str.replace("%s", name) ;
                            jQuery('.title-status').html(str);
                        } else {
                            str = "<?php langWPADM::get('Dropbox Backup (%s) wasn\'t restored'); ?>";
                            str = str.replace('%s', name);
                            jQuery('.title-status').html(str);
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    processStop();
                    AjaxAlert(jqXHR, textStatus, errorThrown);
                },
                dataType: 'json'
            });

        }
        function auth_form(t)
        {
            var button = jQuery(t);
            var form = button.closest('form');
            var data = {};

            var reg = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,6})+$/;
            mail = document.auth.username.value;
            send = false;
            if (!reg.test(mail)) {
                document.auth.username.style.border = "2px solid red";
            } else {
                document.auth.username.style.border = "1px solid #5b9dd9";
                if(document.auth.password.value.length == 0) {
                    document.auth.password.style.border = "2px solid red";
                } else {
                    send = true;
                    document.auth.password.style.border = "1px solid #5b9dd9";
                }
            }
            if(send) {
                form.find('#message-form').css('display', 'none');
                data['password'] = document.auth.password.value; 
                data['username'] = document.auth.username.value;
                data['plugin'] = document.auth.plugin.value;
                backup = jQuery("#name_backup_restore").val();
                jQuery.ajax({
                    url: form.attr('action'),
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    success: function(data_res) {
                        if( !data_res){
                            alert('error');
                        } else if(data_res.error) {
                            if(form.find('#message-form').length) {
                                form.find('#message-form').html("");
                                form.find('#message-form').css('display', 'block');
                                form.find('#message-form').css('margin', '0');
                                form.find('#message-form').css('margin-top', '6px');
                                form.find('#message-form').html(data_res.error);
                            }
                        } else if(data_res.url) {

                            jQuery.ajax({
                                url: ajaxurl,
                                data: {'action' : 'set_user_mail', 'email' : document.auth.username.value},
                                type: 'POST',
                                dataType: 'json',
                                success: function(res) {

                                } 
                            });
                            form.attr('action', data_res.url);
                            jQuery(form).submit();  
                            location.reload();  
                        }
                    }, 
                    error: function ( jqXHR, textStatus, errorThrown ) {
                        AjaxAlert(jqXHR, textStatus, errorThrown);
                    }

                });
            }
        } 
        function disconnectDropbox()
        {
            var form = jQuery('form#dropbox_form');
            form.find('#oauth_token_secret').val('');
            form.find('#oauth_token').val('');
            form.find('#uid').val('');
            form.find('#dropbox_uid_text').text('');
            form.find('.disconnect_btn').parents('.form_block_input').removeClass('connected');
        }

        var winParams = "left=0,top=0,height=600,width=1000,menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes,toolbar=no,directories=no"
        //https://www.dropbox.com/1/oauth/authorize?oauth_token=mIF2gsXq2jijPL95&oauth_callback=http%3A%2F%2Fdev.wpadm.com%2Fbackup%2FdropboxConnect%3Fauth_callback%3D1
        var dropboxBut, dropboxWin;
        function connectDropbox(button, href, oauth_token_secret, oauth_token, uid){
            if( button && href ){
                dropboxBut = jQuery(button);
                var form = dropboxBut.parents('form');
                var url = href;

                // if (jQuery.trim(jQuery('#app_key').val()) != '' || jQuery.trim(jQuery('#app_secret').val()) != '') {
                url += '&app_key='+jQuery('#app_key').val();
                url += '&app_secret='+jQuery('#app_secret').val();
                // }

                dropboxWin = window.open(url, "Dropbox", winParams);
                if( dropboxWin ){
                    dropboxWin.focus();
                }else{
                    alert('<?php langWPADM::get('Please, permit the pop-up windows.'); ?>');
                }
            }else{
                var form = dropboxBut.parents('form');
                if( dropboxWin ){
                    dropboxWin.close();
                }
                form.find('#oauth_token_secret').val(oauth_token_secret);
                form.find('#oauth_token').val(oauth_token);
                form.find('#uid').val(uid);
                auth_param = false;
                form.find('#dropbox_uid_text').html('<b>UID:</b>' + uid);
                blick_form = false;
                dropboxBut.parents('.form_block_input').addClass('connected');
            }
        }
        function getHelperDropbox()
        {
            jQuery('#helper-keys').arcticmodal({
                beforeOpen: function(data, el) {
                    jQuery('#helper-keys').css('display','block');
                },
                afterClose: function(data, el) {
                    jQuery('#helper-keys').css('display','none');
                }
            });
        }

        function setReadOnly(id)
        {
            r = jQuery('#' + id).attr('readonly');
            if (r == 'readonly') {
                jQuery('#' + id).prop('readonly', false);
            } else {
                jQuery('#' + id).prop('readonly', true);

            }
        }
        function InludesSetting()
        {  
            disp = jQuery('#inludes-setting').css('display');
            if (disp == 'none') {
                showLoadingImg(true);
                getIncludesData();
                jQuery('#inludes-setting').show("slow");
            } else {
                jQuery('.show-includes').html("");
                jQuery('#inludes-setting').hide("slow");
            }
        }
        var level_tree = {};
        function getIncludesData(type, dir_)
        {
            data = {'action' : 'getDirsIncludes'};
            if (type != 'undefined') {
                data['files'] = type
            }
            if ( ( typeof dir_ ) != 'undefined') {
                data['path'] = dir_.path;
            }
            jQuery.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                dataType: 'json',
                success: function(data_res) {
                    showLoadingImg(false);
                    if ((typeof dir_) != 'undefined') {
                        jQuery('#img_load_' + dir_.cache).css('display', 'none');
                    }
                    if (data.path) {
                        showIncludesData(data_res.dir, dir_.id);
                    } else { 
                        if (data_res.dir) {
                            showIncludesData(data_res.dir);
                        }
                    }
                    level_tree[level_tree.length] = data_res.dir;
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    AjaxAlert(jqXHR, textStatus, errorThrown);
                }
            });
        }
        function loadInludes(path, cache, t, lvl)
        {
            if (t.checked) {
                showLoadingImg(true);
                jQuery('#include_' + cache).html('');
                jQuery('#img_load_' + cache).css({'display':'inline'});
                getIncludesData('undefined', {'path' : path, 'id' : 'include_' + cache, 'cache' : cache });
                jQuery('#include_' + cache).show('slow');
            } else {
                in_id = jQuery(t).attr('id')
                if (jQuery('#include_' + in_id).length > 0) {
                    jQuery('#include_' + in_id).hide('slow');
                }
            }
        }
        function showIncludesData(data, id)
        {
            html = "";
            if ( ( typeof data ) != 'undefined' ) {
                if (data.length > 0) {
                    for(i = 0; i < data.length; i++) {
                        if (data[i].check) {
                            check = 'checked="checked"' ;
                            send_checked[send_checked.length] = data[i].check_folder;
                        } else {
                            check = '';
                        }
                        html += '<div id="inc_' + data[i].cache + '" data-value="' + data[i].cache + '">' +
                        '<input type="checkbox" ' + check + ' class="checkbox-send" value="' + data[i].folder + '" name="folder-include" id="send-to-' + data[i].cache + '" onclick="connectFolder(this)" />' +
                        '<input type="checkbox" class="input-folder" value="/' + data[i].dir + '" id="' + data[i].cache + '" onclick="loadInludes(\'/' + data[i].folder + '\', \'' + data[i].cache +'\', this, \'' + level_tree.length + '\')" />' +
                        '<label for="' + data[i].cache  + '">' + data[i].dir + ' <span style="font-size:10px;">(' + data[i].perm + ')</span>' + '</label>' +
                        '<div id="img_load_' + data[i].cache + '" style="display:none; margin-left:10px;position:relative;">' +
                        '<img style="position:absolute;bottom:0;" src="<?php echo plugins_url('/img/folder-loader.gif', dirname(__FILE__) ); ?>" alt="load" title="load" >' +
                        '</div>'+
                        '<div class="tree-includes" id="include_' + data[i].cache + '">' +
                        '</div>' +
                        '</div>';
                    }
                    if (jQuery("#" + id).length > 0) {
                        jQuery("#" + id).html(html);
                    } else {
                        jQuery('.show-includes').html(html);
                    }
                }
            }
        }

        function saveIncludes()
        {
            data = {'action' : 'saveDirsIncludes', 'save' : 1, 'data' : send_checked}
            if (send_checked.length > 0) {
                showLoadingImg(true);
                jQuery.ajax({
                    url: ajaxurl,
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    success: function(data_res) {
                        showLoadingImg(false);

                    }, 
                    error: function (jqXHR, textStatus, errorThrown) {
                        AjaxAlert(jqXHR, textStatus, errorThrown);
                    }
                });
            }
        }
        function saveSetting()
        {
            is_admin = 0;
            if(document.getElementById('is_admin').checked) {
                is_admin = 1;
            } 

            data = {'action' : 'saveSetting', 'is_admin' : is_admin}
            jQuery.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                dataType: 'json',
                success: function(data_res) {
                }, 
                error: function( jqXHR, textStatus, errorThrown ){ 
                    AjaxAlert(jqXHR, textStatus, errorThrown);
                }
            });
        }
        function showApp()
        {
            disp = jQuery('#dropbox-app-key').css('display');
            if (disp == 'none') {
                jQuery('#dropbox-app-key').show('slow');
                jQuery('#dropbox-app-secret').show('slow');
            } else {
                jQuery('#dropbox-app-key').hide('slow');
                jQuery('#dropbox-app-secret').hide('slow');
            }
        }
        function showFormAjax()
        {
            //form-ajax-ftp-email
            disp = jQuery('#form-ajax-ftp-email').css('display');
            if (disp == 'none') {
                jQuery('#form-ajax-ftp-email').show('slow');
            } else {
                jQuery('#form-ajax-ftp-email').hide('slow');
            }
        }
    </script>
    <?php if (!empty($error)) {
            echo '<div class="error" style="text-align: center; color: red; font-weight:bold;">
            <p style="font-size: 16px;">
            ' . $error . '
            </p></div>'; 
    }?>
    <?php if (!empty($msg)) {
            echo '<div class="updated" style="text-align: center; font-weight:bold;">
            <p style="font-size: 16px;">
            ' . $msg . '
            </p></div>'; 
    }?>
    <div id="is-dropbox-auth" style="display: none; width: 380px; text-align: center; background: #fff; border: 2px solid #dde4ff; border-radius: 5px;">
        <div class="title-description" style="font-size: 20px; text-align: center;padding-top:45px; line-height: 30px;">
            <?php langWPADM::get('Please, add your Dropbox credentials:'); ?><br />
            <strong><?php langWPADM::get('"App key"'); ?></strong> & <strong><?php langWPADM::get('"App secret"'); ?></strong> <br />
            <?php langWPADM::get('in the Setting Form'); ?>
        </div>
        <div class="button-description" style="padding:20px 0;padding-top:45px">
            <input type="button" value="<?php langWPADM::get('OK'); ?>" onclick="jQuery('#is-dropbox-auth').arcticmodal('close');" style="text-align: center; width: 100px;" class="button-wpadm">
        </div>
    </div>
    <?php 
        if (isset($sent_response)) {
        ?>
        <script>
            jQuery(document).ready(function() {
                jQuery('#sent-error-report').arcticmodal({
                    beforeOpen: function(data, el) {
                        jQuery('#sent-error-report').css('display','block');
                    },
                    afterClose: function(data, el) {
                        jQuery('#sent-error-report').css('display','none');
                    }
                });
            })
        </script>
        <div id="sent-error-report" style="display: none;" >
            <div class="text-view">
                <?php echo $sent_response ;?>
            </div>
            <div class="button-sent-report">
                <input type="button" class="button-wpadm" value="<?php langWPADM::get('OK'); ?>" onclick="jQuery('#sent-error-report').arcticmodal('close')" />
            </div>
        </div>
        <?php
        }
    ?>
    <div id="ajax-alert" style="display: none;width: 800px; text-align: center; background: #fff; border: 2px solid #dde4ff; border-radius: 5px;">
        <div id="ajax-message" style="font-size: 15px; margin-top: 10px; margin-bottom: 30px;"></div>
        <div style="font-size: 15px; margin-bottom: 30px;"><?php langWPADM::get('To solve this problem, we need to access the system logs of your hosting/server and/or from your backup, <br />that you tried to create or simply send to us your FTP access data.');?></div>

        <form action="<?php echo admin_url( 'admin-post.php?action=error_logs_check' )?>" method="post" style=" text-align: left; margin-left:110px;margin-bottom:20px;">
            <div style="margin-top: 10px; font-size: 16px; font-weight: bold; margin-bottom: 10px;">
                <input type="checkbox" onclick="showFormAjax();" style="margin: 0;" id="show-form-ajax" /> <label for="show-form-ajax"><?php langWPADM::get('I want to provide your FTP access to resolve this issue quickly:');?></label>
            </div>
            <div id="form-ajax-ftp-email" style="display: none;">
                <div class="form-help-send-error" >
                    <div style="margin-top: 3px;">
                        <div class="label-help" style="">
                            <label for="ftp-host"><?php langWPADM::get('FTP Host'); ?></label>
                        </div>  
                        <div style="float:left; ">
                            <input type="text" id="ftp-host" value="<?php echo str_ireplace(array('http://', 'https://'), '', home_url()) ;?>" name="ftp_host" >
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div style="margin-top: 3px;">
                        <div class="label-help" > 
                            <label for="ftp-user"><?php langWPADM::get('FTP User'); ?></label>
                        </div>
                        <div style="float:left; ">
                            <input type="text" id="ftp-user" value="" name="ftp_user">
                        </div>
                    </div>
                    <div class="clear"></div>
                    <div style="margin-top: 3px;">
                        <div class="label-help" > 
                            <label for="ftp-pass"><?php langWPADM::get('FTP Password'); ?></label>
                        </div>
                        <div style="float:left; ">
                            <input type="text" id="ftp-pass" value="" name="ftp_pass">
                        </div>
                    </div>
                    <div class="clear"></div>

                </div>
                <div class="form-help-mail-response">
                    <div style="padding: 20px; border:1px solid #fff; margin-top: 3px;">
                        <div class="label-help" > 
                            <label for="email-resp"><?php langWPADM::get('Response Email:'); ?></label>
                        </div>
                        <div style=" ">
                            <input type="text" id="email-resp" value="<?php echo get_option('admin_email');?>" style="padding-left:3px;" name="mail_response">
                        </div>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
            <div style="text-align: left; margin-left: 100px; margin-top: 10px;">
                <input value="<?php echo $time_log; ?>" type="hidden" name="time_pars">
                <input value="" type="hidden" name="msg_ajax" id="msg_ajax">
            </div>
            <div class="ajax-button" style="margin-bottom: 10px;">
                <input type="submit" class="button-wpadm"  value="<?php langWPADM::get('SEND TO SUPPORT and close this window'); ?>" />&nbsp;&nbsp;&nbsp;
                <input type="button" class="button-wpadm" onclick="jQuery('#ajax-message').arcticmodal('close')" value="<?php langWPADM::get('Close this window WITHOUT SENDING TO SUPPORT'); ?>" /> 
            </div>
        </form> 
    </div>
    <div id="helper-keys" style="display: none;width: 400px; text-align: center; background: #fff; border: 2px solid #dde4ff; border-radius: 5px;">
        <div class="title-description" style="font-size: 20px; text-align: center;padding-top:20px; line-height: 30px;">
            <?php langWPADM::get('Where can I find my app key and secret?'); ?>
        </div>
        <div class="button-description" style="padding:20px 10px;padding-top:20px; text-align: left;">
            <?php langWPADM::get('You can get an API app key and secret by creating an app on the'); ?> 
            <a href="https://www.dropbox.com/developers/apps/create?app_type_checked=api" target="_blank"><?php langWPADM::get('app creation page'); ?></a>.
            <?php langWPADM::get('Once you have an app created, the app key and secret will be available on the app\'s page on the'); ?>
            <a href="https://www.dropbox.com/developers/apps" target="_blank"><?php langWPADM::get('App Console'); ?></a>
            . <?php langWPADM::get('Note that Drop-ins have app keys but no app secrets.'); ?>
        </div>
        <div class="button-description" style="padding:20px 0;padding-top:10px">
            <input type="button" value="<?php langWPADM::get('OK'); ?>" onclick="jQuery('#helper-keys').arcticmodal('close');" style="text-align: center; width: 100px;" class="button-wpadm">
        </div>
    </div>
    <div class="block-content" style="margin-top:20px;">
        <div style="min-height : 215px; padding: 5px; padding-top: 10px;">
            <div class="log-dropbox" style="background-image: url(<?php echo plugins_url('/img/dropbox.png', dirname(__FILE__));?>);">
            </div>
            <div style="float: bottom; font-size: 40px; font-weight: bold; text-shadow: 1px 2px 2px #666; margin-left: 189px;">
                <?php langWPADM::get('Dropbox Full Backup');?> <span style="font-size: 20px;"><?php langWPADM::get('(files+database)');?></span>
            </div>
            <?php if ($show) {?>
                <div id="container-user" class="cfTabsContainer" style="width: 48%; padding-bottom: 0px; padding-top: 0px; float: left; margin-left: 20px;">
                    <div class="stat-wpadm-info-title" id="title-regisr" style="padding :20px 0px; margin-top:11px; line-height: 25px;">
                        <?php langWPADM::get('Free Sign Up'); ?> <br /><?php langWPADM::get('to backup more than one web page...'); ?>
                    </div>
                    <div id="cf_activate" class="cfContentContainer" style="display: none;">
                        <form method="post" id="dropbox_form" action="<?php echo admin_url( 'admin-post.php?action=wpadm_activate_plugin' )?>" >
                            <div class="stat-wpadm-registr-info" style="margin-bottom: 40px; margin-top: 17px;">
                                <table class="form-table stat-table-registr" style="">
                                    <tbody>
                                        <tr valign="top">
                                            <th scope="row">
                                                <label for="email"><?php langWPADM::get('E-mail'); ?></label>
                                            </th>
                                            <td>
                                                <input id="email" class="" type="text" name="email" value="">
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">
                                                <label for="password"><?php langWPADM::get('Password'); ?></label>
                                            </th>
                                            <td>
                                                <input id="password" class="" type="password" name="password" value="">
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">
                                                <label for="password-confirm"><?php langWPADM::get('Password confirm'); ?></label>
                                            </th>
                                            <td>
                                                <input id="password-confirm" class="" type="password" name="password-confirm" value="">
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row">
                                            </th>
                                            <td>
                                                <input class="button-wpadm" type="submit" value="<?php langWPADM::get('Register & Activate'); ?>" name="send">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="stat-wpadm-info" id="registr-info" style="margin-bottom: 2px; margin-top: 30px;">
                                <span style="font-weight:bold; font-size: 14px;"><?php langWPADM::get('If you are NOT registered at'); ?> <a target="_blank" style="color: #fff" href="<?php echo SERVER_URL_INDEX; ?>"><?php langWPADM::get('WPAdm'); ?></a>,</span> <?php langWPADM::get('enter your email and password to use as your Account Data for authorization on WPAdm.'); ?> <br /><span style="font-weight: bold;font-size: 14px;"><?php langWPADM::get('If you already have an account at'); ?> <a target="_blank" style="color: #fff" href="<?php echo SERVER_URL_INDEX; ?>"><?php langWPADM::get('WPAdm'); ?></a></span> <?php langWPADM::get('and you want to Sign-In, so please, enter your registered credential data (email and password twice).'); ?>
                            </div>
                        </form>
                    </div>
                    <div class="clear"></div> 
                    <div class="block-button-show" style="color: #fff;">
                        <div class="block-click" onclick="showRegistInfo(true);">
                            <span id="registr-show" style="color: #fff;"><?php langWPADM::get('Show'); ?></span>
                            <div id="registr-choice-icon" class="dashicons dashicons-arrow-down" style=""></div>
                        </div>
                    </div>
                </div>
                <?php } else { ?>
                <div id="container-user" class="cfTabsContainer" style="width: 48%; padding-bottom: 0px; padding-top: 0px; float: left; margin-left: 20px;">
                    <div class="stat-wpadm-info-title" id="title-regisr" style="padding :10px 0px; margin-top:11px; line-height: 25px;">
                        <?php langWPADM::get('Sign In to backup more than one web page...'); ?>
                    </div>
                    <div>
                        <form method="post" id="auth" name="auth" action="<?php echo SERVER_URL_INDEX . "login-process" ; ?>" target="_blank">
                            <div>
                                <div id="message-form" style="color: red; float: left;margin: 10px;margin-top: 14px;"></div>
                            </div>
                            <div style="padding: 5px; clear: both;">
                                <div class="form-field">
                                    <input class="input-small" type="text" id="username" value="<?php echo get_option(PREFIX_BACKUP_ . "email");?>" readonly="readonly" required="required" name="username" placeholder="<?php langWPADM::get('Email'); ?>" /> 
                                </div>
                                <div class="form-field">
                                    <input class="input-small" type="password" required="required" name="password" placeholder="<?php langWPADM::get('Password'); ?>" />
                                </div>
                                <div class="form-field">
                                    <input class="button-wpadm" type="button" value="Sign In" onclick="auth_form(this);" />
                                    <input type="hidden" value="<?php echo 'dropbox-backup'?>" name="plugin" />
                                </div>
                            </div>
                            <div style="clear:both; padding: 5px; font-size: 11px; color: #fff;">
                                <div class="form-field" style="margin-bottom: 10px;">
                                    <input type="checkbox" onclick="setReadOnly('username')" style="margin: 0px;"> <?php langWPADM::get('set new mail'); ?> 
                                </div>
                            </div>
                            <div style="clear:both;"></div>
                        </form>
                    </div>
                </div>
                <?php } ?>
            <div class="cfTabsContainer" style="width: 28%; float: left; margin-left: 10px; padding-bottom: 0px; padding-top: 0px;">
                <div class="stat-wpadm-info-title" id="title-setting" style="padding :20px 0px; margin-top:11px; line-height: 50px;">
                    <?php langWPADM::get('Settings'); ?>
                </div>
                <div id="setting_active" class="cfContentContainer" style="display: none;">
                    <form method="post" action="" >
                        <div class="stat-wpadm-registr-info" style="width: auto; margin-bottom: 9px;">
                            <div  style="margin-bottom: 12px; margin-top: 20px; font-size: 15px;">
                                <?php langWPADM::get('Please, add your Dropbox credentials:'); ?>
                            </div>
                            <?php $show_fields =  isset($dropbox_options['app_key']) && !empty($dropbox_options['app_key']) && isset($dropbox_options['app_secret']) && !empty($dropbox_options['app_secret']) && $dropbox_options['app_key'] != WPADM_APP_KEY && $dropbox_options['app_secret'] != WPADM_APP_SECRET ; ?>
                            <div class="setting-checkbox">
                                <input type="checkbox" onclick="showApp();" <?php echo $show_fields ? 'cheched="checked"' : ''?> /><label><?php langWPADM::get('Connection to your dropbox app');?></label>
                            </div>

                            <table class="form-table stat-table-registr" style="margin-top:2px">
                                <tbody>
                                    <tr valign="top" id="dropbox-app-key" style="display: <?php echo $show_fields  ? 'table-row' : 'none'?>;">
                                        <th scope="row">
                                            <label for="email"><?php langWPADM::get('App key'); ?>*</label>
                                        </th>
                                        <td>
                                            <input id="app_key" class="" type="text" name="app_key" value="<?php echo isset($dropbox_options['app_key']) && $dropbox_options['app_key'] != WPADM_APP_KEY ? $dropbox_options['app_key'] : ''?>">
                                        </td>
                                    </tr>
                                    <tr valign="top" id="dropbox-app-secret" style="display: <?php echo $show_fields  ? 'table-row' : 'none'?>;">
                                        <th scope="row">
                                            <label for="password"><?php langWPADM::get('App secret'); ?>*</label>
                                        </th>
                                        <td>
                                            <input id="app_secret" class="" type="text" name="app_secret" value="<?php echo isset($dropbox_options['app_secret']) && $dropbox_options['app_secret'] != WPADM_APP_SECRET ? $dropbox_options['app_secret'] : ''?>">
                                        </td>
                                    </tr>        
                                    <tr valign="top">
                                        <th scope="row">
                                        </th>
                                        <td style="width: 292px;">
                                            <input class="btn-orange" type="button" onclick="connectDropbox(this,'<?php echo admin_url( 'admin-post.php?action=dropboxConnect' )?>')" value="<?php langWPADM::get('Connect'); ?>" name="submit">
                                            <span id="dropbox_uid_text"><?php echo isset($dropbox_options['oauth_token']) && isset($dropbox_options['uid']) ? "UID " . $dropbox_options['uid'] : '';  ?></span>
                                            <div class="desc-wpadm"><?php langWPADM::get('Click to Connect your Dropbox'); ?></div>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <td colspan="2" align="right">
                                            <a class="help-key-secret" href="javascript:getHelperDropbox();" ><?php langWPADM::get('Where to get App key & App secret?'); ?></a><br />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <?php if ( is_super_admin() ) { ?>
                                <div class="setting-checkbox">
                                    <input type="checkbox" <?php echo isset($dropbox_options['is_admin']) && $dropbox_options['is_admin'] == 1 ? 'checked="checked"' : ''; ?> name="is_admin" value="1" id="is_admin" onclick="saveSetting()" /><label for="is_admin" style="font-size: 13px;"><?php langWPADM::get('Appear in menu for admins only'); ?></label>
                                </div>
                                <?php } ?>
                        </div>
                    </form>
                </div>
                <div class="clear"></div> 
                <div class="block-button-show" style="color: #fff;">
                    <div class="block-click" onclick="showSetting(true);">
                        <span id="setting-show" style="color: #fff;"><?php langWPADM::get('Show'); ?></span>
                        <div id="setting-choice-icon" class="dashicons dashicons-arrow-down" style=""></div>
                    </div>
                </div>
            </div> 
        </div> 
    </div>     
    <div style="clear: both;"></div>
    <div class="block-content">
        <div class="" style="margin-top:10px;">
            <div id="logs-form" style="display: none; float:left; width: 70%;">
                <div class="title-logs"><span style="font-size:16px;"><?php langWPADM::get('Please wait...'); ?> <span id="time_backup">0 <?php langWPADM::get('sec.'); ?></span><img style="float: right;" src="<?php echo plugins_url('/img/wpadmload.gif', dirname(__FILE__))?>" alt=""></span></div>
                <div class="title-status" style="font-size:16px; display: none;"></div>
                <div style="border: 1px solid #ddd; text-align: left; background: #fff; padding: 2px;">
                    <div id="log-backup" style="overflow: auto; height: 60px; border: 5px solid #fff; "></div>
                </div>
            </div>

            <div id="reviews-dropbox" class="pointer" onclick="window.open('https://wordpress.org/support/view/plugin-reviews/dropbox-backup?filter=5');">
                <div class="title-reviews"><?php langWPADM::get('++ Review ++'); ?></div>
                <div class="desc-reviews"><?php langWPADM::get('Your review is important for us'); ?></div>
                <img src="<?php echo plugins_url('/img/stars-5.png', dirname(__FILE__));?>" alt=""></a>
            </div>
            <div id="support-button" style="float: right; margin-top: 130px; margin-right: 20px;">  
                <button onclick="window.open('<?php echo SERVER_URL_INDEX . 'support/?pl=dbp'?>')" class="backup_button" style="padding: 5px 10px; margin-top: 10px; font-size: 15px;bottom: 0px;"><?php langWPADM::get('Help'); ?></button> <br />
            </div>
            <div id="action-buttons" style="">
                <div style="float: left;">
                    <button onclick="start_dropbox_backup();" class="backup_button" style=""><?php langWPADM::get('Create Dropbox Backup'); ?></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
                <div style="float: left; margin-top: 2px;">
                    <button onclick="start_local_backup()" class="backup_button" style="padding: 5px 10px; margin-top: 10px; font-size: 15px;bottom: 0px;"><?php langWPADM::get('Create Local Backup'); ?></button> <br />
                </div>
                <!-- <div style="float: left; margin-top: 2px;margin-left: 20px;">
                <button onclick="InludesSetting();" class="backup_button" style="padding: 5px 10px; margin-top: 10px; font-size: 15px;bottom: 0px;"><?php langWPADM::get('Folders & files'); ?></button> <br />
                </div> -->
                <div style="clear: both;"></div>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>
        <div>
            <form action="<?php echo WPADM_URL_BASE;?>wpsite/recovery-backup" method="post" target="_blank" id="form_auth_backup" name="form_auth_backup">
            </form>
            <form action="<?php echo WPADM_URL_BASE;?>backup/tasks" method="post" target="_blank" id="form_auth_backup_create" name="form_auth_backup_create">
                <input type="hidden" name="url_task_create" value="<?php echo get_option('siteurl');?>">
            </form>
            <form action="" method="post" id="form_auth_backup" name="form_auth_backup">
            </form>
            <form action="<?php echo admin_url( 'admin-post.php?action=wpadm_delete_backup' )?>" method="post" id="delete_backups" name="delete_backups">
                <input type="hidden" name="backup-name" id="backup_name" value="" />
                <input type="hidden" name="backup-type" id="backup_type" value="" />
            </form>
            <!-- <div id="inludes-setting" class="" style="display: none;  position: relative; text-align: center; background: #f1ebeb; border: 2px solid #dde4ff; border-radius: 5px;">
            <div>
            <div class="title-description" style="font-size: 20px; text-align: center;padding-top:20px; line-height: 30px;">
            <?php langWPADM::get('Include/Exclude of Files & Folders to backup'); ?>
            <div style="font-size: 14px;">
            <?php langWPADM::get('Database of web page will be included automatically'); ?>
            </div>
            </div>
            <div class="loading-img">
            <img style="display: none; margin: 0 auto;" src="<?php echo plugins_url('/img/wpadmload.gif', dirname(__FILE__) ); ?>"> 
            </div>
            <div class="button-description">
            <!-- <input type="radio" value="folder" id="inc-folder" checked="checked"><label for="inc-folder">View folders</label> &nbsp;&nbsp;&nbsp; <input type="radio" value="files" id="inc-files" ><label for="inc-files">View Folders & Files</label>   
            <div class="show-includes">

            </div>
            </div>
            <div class="clear"></div>
            <div class="button-description" style="padding:20px 0; width: 100%;">
            <input type="button" value="<?php langWPADM::get('OK'); ?>" onclick="saveIncludes();" style="text-align: center; width: 100px;" class="button-wpadm">
            <input type="button" value="<?php langWPADM::get('Cancel'); ?>" onclick="InludesSetting();" style="text-align: center; width: 100px;" class="button-wpadm">
            </div>
            </div>

            </div>   -->

            <table class="table" style="margin-top: 5px; display: <?php echo isset($data['md5']) && ($n = count($data['data'])) && is_array($data['data'][0]) ? 'table' : 'none'?>;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th align="left"><?php langWPADM::get('Create, Date/Time'); ?></th>
                        <th><?php langWPADM::get('Name of Backup'); ?></th>
                        <th><?php langWPADM::get('Archive Parts'); ?></th>
                        <th><?php langWPADM::get('Status'); ?></th>
                        <th><?php langWPADM::get('Type of Backup'); ?></th>
                        <th><?php langWPADM::get('Size'); ?></th>
                        <?php if(is_admin() || is_super_admin()) {?>
                            <th><?php langWPADM::get('Action'); ?></th>
                            <?php
                            }
                        ?> 
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($data['md5']) && ($n = count($data['data'])) && is_array($data['data'][0])) { 
                            for($i = 0; $i < $n; $i++) {
                                $size = $data['data'][$i]['size'] / 1024 / 1024; /// MByte
                                $size = round($size, 2);
                                $files = explode(",", str_replace(array('"', "[", "]"), "", $data['data'][$i]['files'] ) );
                                $f = count($files);
                            ?>
                            <tr>
                                <td class="number-backup"><?php echo ($i + 1);?></td>
                                <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>', this)" class="pointer" style="text-align: left; padding-left: 7px;"><?php echo $data['data'][$i]['dt'];?></td>
                                <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>', this)" class="pointer">
                                    <?php echo ($data['data'][$i]['size'] != 0) ? $data['data'][$i]['name'] : "<strong style=\"color:red;\">". langWPADM::get('Not available', false) . "</strong>&nbsp;&nbsp;(<a style=\"text-decoration:underline;\">".langWPADM::get('Why?', false)."</a>)";?>
                                    <script type="text/javascript">
                                        backup_name = '<?php echo $data['data'][$i]['name']?>';
                                    </script>
                                </td>
                                <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>', this)" class="pointer"><?php echo isset($data['data'][$i]['count']) ? $data['data'][$i]['count'] : $f ;?></td>
                                <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>', this)" class="pointer" style="padding: 0px;">
                                    <?php if ($data['data'][$i]['size'] != 0) { ?>
                                        <img src="<?php echo plugin_dir_url(__FILE__) . "ok.png" ;?>" title="Successful" alt="Successful" style="float: left; width: 20px; height: 20px;" />
                                        <div style="margin-top :1px;float: left;"><?php echo langWPADM::get('Successful', false);?></div>
                                        <?php } else {
                                        ?>
                                        <img src="<?php echo plugin_dir_url(__FILE__) . "not-ok.png" ;?>" title="Successful" alt="Successful" style="float: left; width: 20px; height: 20px;margin-left: 21px;" />
                                        <div style="margin-top :1px;float: left;"><?php echo langWPADM::get('Fail', false);?>&nbsp;&nbsp;(<a style="text-decoration:underline;"><?php echo langWPADM::get('Show Details', false);?></a>)</div>
                                        <?php 
                                    }?>
                                </td>
                                <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>', this)" class="pointer"><?php echo $data['data'][$i]['type'];?> <?php langWPADM::get('backup'); ?></td>
                                <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>', this)" class="pointer"><?php echo $size . langWPADM::get('Mb', false);?></td>
                                <td> 
                                    <?php if(is_admin() || is_super_admin()) {?>
                                        <?php if ($data['data'][$i]['size'] > 0) { ?>
                                            <a class="button-wpadm" href="javascript:void(0)" title="<?php langWPADM::get('Restore'); ?>" onclick="show_recovery_form('<?php echo isset($data['data'][$i]['name']) && $data['data'][$i]['type'] != 'local' ? $data['data'][$i]['name'] : 'local' ?>', '<?php echo $data['data'][$i]['name']?>')" style="color: #fff;"><span class="pointer dashicons dashicons-backup" style="margin-top:3px;"></span><?php langWPADM::get('Restore'); ?></a>&nbsp;
                                            <?php } ?>
                                        <a class="button-wpadm" href="javascript:void(0)" title="<?php langWPADM::get('Delete'); ?>" onclick="delete_backup('<?php echo $data['data'][$i]['name']; ?>', '<?php echo $data['data'][$i]['type'];?>')" style="color: #fff;"><span class="pointer dashicons dashicons-trash" style="margin-top:3px;"></span><?php langWPADM::get('Delete'); ?></a>&nbsp;
                                        <?php
                                        }
                                    ?>
                                </td> 
                            </tr>
                            <tr id="<?php echo md5( print_r($data['data'][$i], 1) );?>" style="display:none; ">
                                <?php if ($data['data'][$i]['size'] != 0) {?>
                                    <td colspan="2">
                                    </td>
                                    <td align="center" style="padding: 0px; width: 350px;">
                                        <div style="overflow: auto; max-height: 150px;">
                                            <?php 
                                                if ($f > 0) {  ?>
                                                <table border="0" align="center" class="info-path"> <?php
                                                        for($j = 0; $j < $f; $j++) {
                                                            if (!empty($files[$j])) {
                                                            ?>
                                                            <tr style="border: 0;">
                                                                <td style="border: 0;">
                                                                    <?php if ($data['data'][$i]['type'] == 'local') {?>
                                                                        <a href="<?php echo content_url(WPADM_DIR_NAME) . "/{$data['data'][$i]['name']}/{$files[$j]}"?>">
                                                                            <?php echo $files[$j]; ?>
                                                                        </a>
                                                                        <?php 
                                                                        } else { 
                                                                            echo $files[$j]; 
                                                                        } 
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                            }
                                                        }
                                                    ?>
                                                </table>
                                                <?php
                                                } 
                                            ?>
                                        </div>
                                    </td>
                                    <td colspan="6"></td> 
                                    <?php 
                                    } else { ?>
                                    <td colspan="2">
                                    </td>
                                    <td colspan="4" style="background: #ddecf9;">
                                        <div style="padding-left: 10px; padding-right: 10px;">
                                            <div style="font-size: 12px; text-align: left;">
                                                <?php 
                                                    $time_log = str_replace(array(':', '-', " "), "_", $data['data'][$i]['dt']); 
                                                    if ( file_exists( $base_path . "/tmp/logs_error_" . $time_log ) ) {
                                                        $log_ = file_get_contents( $base_path . "/tmp/logs_error_" . $time_log );
                                                        $pos = stripos($log_, "error");
                                                        if ($pos !== false) {
                                                            for($p = $pos; ; $p--) {
                                                                if ($log_{$p} == "\n") {
                                                                    $pos_new = $p + 1;
                                                                    break;
                                                                }
                                                            }
                                                            $error =substr($log_, $pos_new);
                                                            echo str_replace("\n", "<br />", $error);
                                                        }
                                                    } else {
                                                        langWPADM::get('Error log wasn\'t Found');
                                                }?> 
                                            </div>

                                            <form action="<?php echo admin_url( 'admin-post.php?action=error_logs_check' )?>" method="post" style=" text-align: left;">
                                                <div style="margin-top: 10px; font-size: 16px; font-weight: bold; margin-bottom: 10px;">
                                                    <?php langWPADM::get('Please, provide your FTP access to resolve this issue quickly:');?>
                                                </div>
                                                <div class="form-help-send-error" >
                                                    <div style="margin-top: 3px;">
                                                        <div class="label-help" style="">
                                                            <label for="ftp-host"><?php langWPADM::get('FTP Host'); ?></label>
                                                        </div>  
                                                        <div style="float:left; ">
                                                            <input type="text" id="ftp-host" value="<?php echo str_ireplace(array('http://', 'https://'), '', home_url()) ;?>" name="ftp_host" >
                                                        </div>
                                                    </div>
                                                    <div class="clear"></div>
                                                    <div style="margin-top: 3px;">
                                                        <div class="label-help" > 
                                                            <label for="ftp-user"><?php langWPADM::get('FTP User'); ?></label>
                                                        </div>
                                                        <div style="float:left; ">
                                                            <input type="text" id="ftp-user" value="" name="ftp_user">
                                                        </div>
                                                    </div>
                                                    <div class="clear"></div>
                                                    <div style="margin-top: 3px;">
                                                        <div class="label-help" > 
                                                            <label for="ftp-pass"><?php langWPADM::get('FTP Password'); ?></label>
                                                        </div>
                                                        <div style="float:left; ">
                                                            <input type="text" id="ftp-pass" value="" name="ftp_pass">
                                                        </div>
                                                    </div>
                                                    <div class="clear"></div>

                                                </div>
                                                <div class="form-help-mail-response">
                                                    <div style="padding: 20px; border:1px solid #fff; margin-top: 3px;">
                                                        <div class="label-help" > 
                                                            <label for="email-resp"><?php langWPADM::get('Response Email:'); ?></label>
                                                        </div>
                                                        <div style=" ">
                                                            <input type="text" id="email-resp" value="<?php echo get_option('admin_email');?>" style="padding-left:3px;" name="mail_response">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="clear"></div>
                                                <div style="text-align: left; margin-left: 100px; margin-top: 10px;">
                                                    <input value="<?php echo $time_log; ?>" type="hidden" name="time_pars">
                                                    <input class="backup_button" style="font-size: 14px;font-weight: normal;padding: 3px;text-shadow: 0px;" type="submit" value="<?php langWPADM::get('Send request to support'); ?>">
                                                </div>
                                            </form>

                                        </div>
                                    </td>
                                    <td colspan="3">
                                    </td>
                                    <?php 
                                    }
                                ?>
                            </tr>
                            <?php 
                        } ?>
                        <?php } ?>
                </tbody>
            </table>

        </div>
    </div>

</div>
