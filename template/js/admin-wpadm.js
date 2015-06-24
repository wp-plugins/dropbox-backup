function showTab (elem, type) 
{
    jQuery('.cfTab').removeClass('selected');
    jQuery('.cfContentContainer').hide();
    jQuery(elem).addClass('selected');
    jQuery('#cf_' + type).fadeIn();
}

var shows_id = ""
var shows_t = ""
function shows(id, t)
{
    if(document.getElementById(id).style.display == "none") {
        document.getElementById(id).style.display = "table-row";
        jQuery(t).parent("tr").addClass('border-shadow-bottom');
        if (shows_id == "") {
            shows_id = id;
            shows_t = t;
        } else {
            if(shows_id != id) {
                document.getElementById(shows_id).style.display = "none";
                jQuery(shows_t).parent("tr").removeClass('border-shadow-bottom');
            }
            shows_id = id;
            shows_t  = t;
        }
    } else if(document.getElementById(id).style.display == "table-row") {
        document.getElementById(id).style.display = "none";
        jQuery(t).parent("tr").removeClass('border-shadow-bottom');
    }
}
var bl = false;
function show_form_auth(file_val)
{
    if (file_val == 'registr') {
        showRegistInfo(false);
        if (bl === false) {
            blick('container-user');
            bl = true;
        }
    } else {
        html = '<input type="hidden" value="' + file_val +'" name="internal_identifier">';
        jQuery('#form_auth_backup').html(html);
        document.form_auth_backup.submit();
    }
}
var blick_form = true;
function blick(id, border_)
{
    if (border_ == 'undefined') {
        border_ = 10;
    }
    jQuery('#' + id).css({
        outline: "0px solid #cd433d",
        border: "0px"
    }).animate({
        outlineWidth: border_ + 'px',
        outlineColor: '#cd433d'
    }, 400).animate({outlineWidth: '0px',outlineColor: '#cd433d' } , 400);
    if (blick_form) {
        setTimeout('blick("' + id + '", ' + border_ + ')', 800);
    }
}

var send_checked = [];
function connectFolder(t)
{
    folder = jQuery(t).val();
    send_checked = unique(send_checked);
    k = jQuery.inArray( folder, send_checked );
    if ( k >= 0) {
        if (!t.checked) {
            send_checked.splice(k,1);
        }
    } else {
        if (t.checked) {
            send_checked[send_checked.length] = folder;
        } 
    }
    divs = jQuery(t).parents('div[id^="inc_"]');
    set = true;
    if (divs.length > 0) {
        for(i = 0; i < divs.length; i ++) {
            if(i == 1) {
                check = jQuery(divs[i]).find('.checkbox-send:checked');
                if(check.length > 1) {
                    set = false;
                }
            }
            id = jQuery(divs[i]).attr('data-value');
            if (set) {
                if (t.checked) {
                    jQuery("#send-to-" + id).attr('checked', true);
                } else {
                    jQuery("#send-to-" + id).attr('checked', false);
                }  
            }
        }
    }
}
function showLoadingImg(show)
{
    img = jQuery('.loading-img').find('img');
    dips = jQuery(img).css('display');
    if (dips == 'none') {
        if (show) {
            jQuery(img).css('display', 'block');
        }
    } else {
        if (!show) {
            jQuery(img).css('display', 'none');
        }
    }
}
function unique(arr)
{
    arr = arr.filter(function (e, i, arr) {
        return arr.lastIndexOf(e) === i;
    });
    return arr;
}
