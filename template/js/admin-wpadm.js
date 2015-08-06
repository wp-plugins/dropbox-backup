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
