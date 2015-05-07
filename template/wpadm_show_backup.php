<div class="wrap">
    <script src="<?php echo plugin_dir_url(__FILE__) . 'js/jquery.arcticmodal-0.3.min.js'?>" type="text/javascript"></script>
    <link rel='stylesheet'  href='<?php echo plugin_dir_url(__FILE__) . 'js/jquery.arcticmodal-0.3.css'?>' type='text/css' media='all' />
    <a href="<?php echo admin_url( 'admin-post.php?action=wpadm_delete_pub_key' )?>">delete WPAdm key</a><br />
    <h2>DropBox Full Backup (files+database) bei WPAdm</h2>
    <?php if (!empty($error)) {
            echo '<div class="error" style="text-align: center; color: red; font-weight:bold;">
            <p style="font-size: 16px;">
            ' . $error . '
            </p></div>'; 
    }?>
    <?php if (!empty($msg)) {
            echo '<div class="updated" style="text-align: center; color: red; font-weight:bold;">
            <p style="font-size: 16px;">
            ' . $msg . '
            </p></div>'; 
    }?>
    <script>
        var global={};
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
                jQuery('#registr-show').html("Hide");
                jQuery('#title-regisr').css("padding" , "0px 0px");
                jQuery('#registr-choice-icon').removeClass("dashicons-arrow-down").addClass('dashicons-arrow-up');
            } else {
                if (show) {
                    jQuery('#cf_activate').hide('slow');
                    jQuery('#registr-show').html("Show");
                    jQuery('#title-regisr').css("padding" , "20px 0px");
                    jQuery('#registr-choice-icon').removeClass("dashicons-arrow-up").addClass('dashicons-arrow-down');
                }
            }
        }
        process_flag = 0;
        function start_local_backup()
        {
            var data_backup = {
                'action': 'wpadm_local_backup',
            };  
            jQuery("#logs-form").show("slow");
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
                        jQuery('.title-status').html('Local Backup was created successfully');
                    } else {
                        jQuery('.title-logs').css('display', 'none');
                        jQuery('.title-status').css({'display':'block', 'color':'red'});
                        jQuery('.title-status').html('Local Backup wasn\'t created');
                    }
                    showData(data);
                    jQuery('.table').css('display', 'table');

                },
                error: function(){
                    processStop();
                },
                dataType: 'json'
            });
        }
        function showData(data)
        {
            size_backup = data.size / 1024 / 1024;
            info = "";
            for(i = 0; i < data.data.length; i++) {
                e = data.data[i].split('/');
                info += '<tr style="border: 0;">' +
                '<td style="border: 0;padding: 0px;"><a href="<?php echo get_option('siteurl') . "/wpadm_backups/"?>' + data.name + '/' + e[e.length - 1] + '">' + e[e.length - 1] + '</td>' +
                '</tr>' ;
            }
            count = jQuery('.number-backup').length + 1;
            jQuery('.table > tbody:last').after(
            '<tr>'+
            '<td class="pointer" onclick="shows(\'' + data.md5_data + '\')">' +
            count + 
            '</td>' +
            '<td class="pointer" onclick="shows(\'' + data.md5_data + '\')" style="text-align: left; padding-left: 7px;" >' +
            data.time + 
            '</td>' +
            '<td class="pointer" onclick="shows(\'' + data.md5_data + '\')">' +
            data.name +
            '</td>' +
            '<td class="pointer" onclick="shows(\'' + data.md5_data + '\')">' +
            data.counts +
            '</td>' +
            '<td class="pointer" onclick="shows(\'' + data.md5_data + '\')">' +
            '<img src="<?php echo plugin_dir_url(__FILE__) . "/ok.png" ;?>" title="Successful" alt="Successful" style="float: left;" />'+
            '<div style="margin-top :7px;float: left;"><?php echo 'Successful';?></div>' +
            '</td>' +
            '<td class="pointer" onclick="shows(\'' + data.md5_data + '\')">' +
            data.type + ' backup' +
            '</td>' +
            '<td class="pointer" onclick="shows(\'' + data.md5_data + '\')">' +
            size_backup.toFixed(2) + "Mb" +
            '</td>' +
            '<td>' +
            '<a href="javascript:void(0)" class="button-wpadm" title="Restore" onclick="show_recovery_form(\'local\', \'' + data.name + '\')"><span class="pointer dashicons dashicons-backup"></span>Restore</a> &nbsp;' +
            '<a href="javascript:void(0)" class="button-wpadm" title="Delete" onclick="delete_backup(\'' + data.name + '\', \'' + data.type + '\')"><span class="pointer dashicons dashicons-trash"></span>Delete</a> &nbsp;' +
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
                error: function(){
                    processStop();
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
                time = t + " sec.";
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
            if (type == 'registr') {
                showRegistInfo(false);
                if (bl === false) {
                    blick('container-user');
                    bl = true;
                }
            } else if (type == 'auth') {
                document.form_auth_backup_create.submit();
            }
        }
        function show_recovery_form(type, name)
        {
            if (type == 'local') {
                var data_backup = {
                    'action': 'wpadm_local_restore',
                    'name': name,
                };  
                jQuery("#log-backup").html('');
                jQuery(".title-logs").css('display', 'block');
                jQuery(".title-status").css('display', 'none');
                jQuery("#logs-form").show("slow");
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
                            jQuery('.title-status').html('Local Backup(' + name + ') was restore is successfully');
                        } else {
                            jQuery('.title-logs').css('display', 'none');
                            jQuery('.title-status').css({'display':'block', 'color':'red'});
                            jQuery('.title-status').html('Local Backup(' + name + ') wasn\'t restore');
                        }
                    },
                    error: function(){
                        processStop();
                    },
                    dataType: 'json'
                });
            } else {
                jQuery('#recovery-backup').arcticmodal({
                    beforeOpen: function(data, el) {
                        jQuery("#recovery-backup").css('display', 'block');
                        jQuery('#wpadminbar').css('z-index', 1);
                        jQuery('.title-recover').find('span').html(type);
                        jQuery("#name_backup_restore").val(type);
                    },
                    afterClose: function(data, el) {
                        jQuery("#recovery-backup").css('display', 'none');
                        jQuery('#wpadminbar').css('z-index', 99999);
                    }
                });
            }
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
                backup = jQuery("#name_backup_restore").val();
                jQuery.ajax({
                    url: form.attr('action'),
                    data: data,
                    type: 'POST',
                    dataType: 'json',
                    success: function(data) {
                        if( !data){
                            alert('error');
                        } else if(data.error) {
                            if(form.find('#message-form').length) {
                                form.find('#message-form').html("");
                                form.find('#message-form').css('display', 'block');
                                form.find('#message-form').html(data.error);
                            }
                        } else if(data.url) {
                            form.attr('action', data.url);
                            jQuery('#name_backups_restore_files').val(jQuery.param( global[backup] ))
                            jQuery.arcticmodal('close');
                            jQuery(form).submit();   
                        }
                    }

                });
            }
        } 

    </script>
    <div id="recovery-backup" style="width: 500px; display: none;">
        <div style="background: #fff; border-radius: 10px; height: 200px; border: 2px solid #0096d6;">
            <form method="post" id="auth" name="auth" action="<?php echo SERVER_URL_INDEX . "login-process/" ; ?>" target="_blank">
                <div class="title-recover" style="font-size: 16px; text-align: center;margin-bottom: 10px; margin-top: 15px;">
                    Backup Restore: <span style="font-size: 14px;"></span>
                </div>
                <div style="margin: 15px;">
                    <div id="message-form" style="color: red; float: left;"></div>
                </div>
                <div style="padding: 5px; height: 70px; clear: both;">
                    <div style="padding-top: 0px;margin-bottom: 5px; float: left;">
                        <div style="margin-top: 10px;">
                            <input class="input-small" type="email" required="required" name="username" placeholder="Email" /> 
                        </div>
                        <div style="margin-top: 10px;">
                            <input class="input-small" type="password" required="required" name="password" placeholder="Password" />
                        </div>

                    </div>
                    <div style="height: 40px; float: left; padding-top: 0px;margin-top:10px; margin-left: 10px; color: #939393; width: 55%;">
                        Enter for Restore your backup. It is necessary for the proper functionality of your site.
                    </div>
                </div>
                <div style="margin-top: 20px; clear: both; text-align: center;">
                    <input type="hidden" id="name_backup_restore" value="">
                    <input type="hidden" id="name_backups_restore_files" name="name_backups_restore_files" value="">
                    <input type="hidden" id="url" name="<?php echo get_option('siteurl')?>" value="">
                    <input class="button-wpadm" type="button" value="Next" onclick="auth_form(this);" />&nbsp;&nbsp;&nbsp;&nbsp;
                    <input class="button-wpadm wpadm-red" type="button" value="Cancel" onclick="jQuery.arcticmodal('close')" />
                </div>
            </form>
        </div>
    </div>

    <div id="container-user" class="cfTabsContainer" style="width: 96%; padding-bottom: 0px; padding-top: 0px;">
        <?php if ($show) {?>
            <div class="stat-wpadm-info-title" id="title-regisr" style="padding :20px 0px; margin-top:11px;">
                Free Sign Up to use more functionality...
            </div>
            <div id="cf_activate" class="cfContentContainer" style="display: none;">
                <form method="post" action="<?php echo admin_url( 'admin-post.php?action=wpadm_activate_plugin' )?>" >
                    <div class="stat-wpadm-registr-info" style="">
                        <table class="form-table stat-table-registr" style="">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row">
                                        <label for="email">E-mail</label>
                                    </th>
                                    <td>
                                        <input id="email" class="" type="text" name="email" value="">
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <label for="password">Password</label>
                                    </th>
                                    <td>
                                        <input id="password" class="" type="password" name="password" value="">
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <label for="password-confirm">Password confirm</label>
                                    </th>
                                    <td>
                                        <input id="password-confirm" class="" type="password" name="password-confirm" value="">
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                    </th>
                                    <td>
                                        <input class="button-wpadm" type="submit" value="Register & Activate" name="submit">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="stat-wpadm-info" id="registr-info" style="margin-bottom: 2px;">
                        <span style="font-weight:bold; font-size: 14px;">If you are NOT registered at WPAdm,</span> enter your email and password to use as your Account Data for authorization on WPAdm. <br /><span style="font-weight: bold;font-size: 14px;">If you already have an account at WPAdm</span> and you want to Sign-In, so please, enter your registered credential data (email and password twice).
                    </div>
                </form>


            </div>
            <div class="clear"></div> 
            <div class="block-button-show" style="color: #fff;">
                <div class="block-click" onclick="showRegistInfo(true);">
                    <span id="registr-show" style="color: #fff;">Show</span>
                    <div id="registr-choice-icon" class="dashicons dashicons-arrow-down" style=""></div>
                </div>
            </div>

            <?php } ?>
    </div>
    <div id="logs-form" style="display: none; padding: 10px; background: #ffffc8; border: 1px solid #0096d6; position: relative; height: 70px; text-align: center;">
        <div class="title-logs"><span style="font-size:16px;">Please wait... <span id="time_backup">0 sec.</span></span></div>
        <div class="title-status" style="font-size:16px; display: none;"></div>
        <div id="log-backup" style="overflow: auto; height: 50px; text-align: left; background: #fff;"></div>
    </div>
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
        <div style="margin-top: 30px;">
            <a href="javascript:start_local_backup();" class="button-wpadm" style="color: #fff;">Create Local Backup</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="javascript:void(0);" class="button-wpadm" onclick="create_backup('<?php echo ($show) ? 'registr' : 'auth';?>')" style="color: #fff;">Create Dropbox Backup</a><br />
        </div>
        <style>
            .pointer {
                cursor: pointer;
            }
        </style>
        <table class="table" style="margin-top: 5px; display: <?php echo isset($data['md5']) && ($n = count($data['data'])) && is_array($data['data'][0]) ? 'table' : 'none'?>;">
            <thead>
                <tr>
                    <th>#</th>
                    <th align="left">Created Date/Time</th>
                    <th>Name of Backup</th>
                    <th>Arhive Parts</th>
                    <th>Status</th>
                    <th>Type of Backup</th>
                    <th>Size</th>
                    <?php if(is_admin() || is_super_admin()) {?>
                        <th>Action</th>
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
                        ?>
                        <tr>
                            <td class="number-backup"><?php echo ($i + 1);?></td>
                            <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>')" class="pointer" style="text-align: left; padding-left: 7px;"><?php echo $data['data'][$i]['dt'];?></td>
                            <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>')" class="pointer">
                                <?php echo $data['data'][$i]['name'];?>
                                <script type="text/javascript">
                                    backup_name = '<?php echo $data['data'][$i]['name']?>';
                                    global[backup_name] = {};
                                </script>
                            </td>
                            <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>')" class="pointer"><?php echo $data['data'][$i]['count'];?></td>
                            <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>')" class="pointer" style="padding: 0px;">
                                <img src="<?php echo plugin_dir_url(__FILE__) . "/ok.png" ;?>" title="Successful" alt="Successful" style="float: left;" />
                                <div style="margin-top :7px;float: left;"><?php echo 'Successful';?></div>
                            </td>
                            <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>')" class="pointer"><?php echo $data['data'][$i]['type'];?> backup</td>
                            <td onclick="shows('<?php echo md5( print_r($data['data'][$i], 1) );?>')" class="pointer"><?php echo $size . "Mb";?></td>
                            <?php if(is_admin() || is_super_admin()) {?>
                                <td>
                                    <a class="button-wpadm" href="javascript:void(0)" title="Restore" onclick="show_recovery_form('<?php echo isset($data['data'][$i]['name']) && $data['data'][$i]['type'] != 'local' ? $data['data'][$i]['name'] : 'local' ?>', '<?php echo $data['data'][$i]['name']?>')" style="color: #fff;"><span class="pointer dashicons dashicons-backup" style="margin-top:3px;"></span>Restore</a>&nbsp;
                                    <a class="button-wpadm" href="javascript:void(0)" title="Delete" onclick="delete_backup('<?php echo $data['data'][$i]['name']; ?>', '<?php echo $data['data'][$i]['type'];?>')" style="color: #fff;"><span class="pointer dashicons dashicons-trash" style="margin-top:3px;"></span>Delete</a>&nbsp;
                                </td>
                                <?php
                                }
                            ?>
                        </tr>
                        <tr id="<?php echo md5( print_r($data['data'][$i], 1) );?>" style="display:none; ">
                            <td colspan="2">
                            </td>
                            <td align="center" style="padding: 0px; width: 350px;">
                                <div style="overflow: auto; max-height: 150px;">
                                    <?php 
                                        $files = explode(",", str_replace(array('"', "[", "]"), "", $data['data'][$i]['files'] ) );
                                        $f = count($files);
                                        if ($f > 0) {  ?>
                                        <table border="0" align="center" class="info-path"> <?php
                                                for($j = 0; $j < $f; $j++) {
                                                    if (!empty($files[$j])) {
                                                    ?>
                                                    <tr style="border: 0;">
                                                        <td style="border: 0;"><a href="<?php echo get_option('siteurl') . "/wpadm_backups/{$data['data'][$i]['name']}/{$files[$j]}"?>"><?php echo $files[$j]; ?></a>

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
                        </tr>
                        <?php 
                    } ?>

                    <?php } ?>
            </tbody>
        </table>

    </div>

</div>
