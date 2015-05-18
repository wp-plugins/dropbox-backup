function showTab (elem, type) 
{
    jQuery('.cfTab').removeClass('selected');
    jQuery('.cfContentContainer').hide();
    jQuery(elem).addClass('selected');
    jQuery('#cf_' + type).fadeIn();
}

var shows_id = ""
function shows(id)
{
    if(document.getElementById(id).style.display == "none") {
        document.getElementById(id).style.display = "table-row";
        if (shows_id == "") {
            shows_id = id;
        } else {
            if(shows_id != id) {
                document.getElementById(shows_id).style.display = "none";
            }
            shows_id = id;
        }
    } else if(document.getElementById(id).style.display == "table-row") {
        document.getElementById(id).style.display = "none";
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
function blick(id)
{
    jQuery('#' + id).css({
        outline: "0px solid #ffba00",
        border: "0px"
    }).animate({
        outlineWidth: '10px',
        outlineColor: '#ffba00'
    }, 500).animate({outlineWidth: '0px',outlineColor: '#0096d6' } , 500);

    setTimeout('blick("' + id + '")', 2000);
}
