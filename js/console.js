jQuery("#run_code_wpd").click(function(){
    var codeToRun = code_snippets_editor.getValue();
    jQuery("#snippet_settings_wrapper").children().each(function(){
        codeToRun = codeToRun.replace(jQuery(this).find(".replace").val(), jQuery(this).find(".setting_value").val());
    });
    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: wpdajax.ajax_url,
        data: {"action": "evaluatewpd", "input": btoa(codeToRun)},
        success: function(msg){
            jQuery("#snippet_output").html(msg.output);
        }
    });
});

function insertTextAtCursor(editor, text) {
    var doc = editor.getDoc();
    var cursor = doc.getCursor();
    doc.replaceRange(text, cursor);
}

jQuery("#snippet_template").change(function(){
    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: wpdajax.ajax_url,
        data: {"action": "getsnippetcontent", "id": jQuery(this).children(":selected").val()},
        success: function(msg){
            insertTextAtCursor(window.code_snippets_editor, msg.code);
            jQuery("#snippet_template").prop("selectedIndex", 0);
        }
    });
    
});

var advanced_wpd_enabled = false;
function advanced_wpd_enable(){
    jQuery("#snippet_values_wrapper").find("*").prop('disabled', true);
    jQuery("#snippet_settings_wrapper").show();
}
function advanced_wpd_disable(){
    jQuery("#snippet_values_wrapper").find("*").prop('disabled', false);
    jQuery("#snippet_settings_wrapper").hide();
}
jQuery("#advanced_view_wpd").click(function(){
    if(advanced_wpd_enabled){
        advanced_wpd_disable();
    } else {
        advanced_wpd_enable();
    }
    advanced_wpd_enabled = !advanced_wpd_enabled;
});

jQuery("#add_variable_wpd").click(function(){
    jQuery("#snippet_settings_wrapper").append(`<div id="snippet_setting_` + jQuery("#snippet_settings_wrapper").children().length + `">
    <input type="text" class="label">
    <select class="data_type">
          <option value="string">String</option>
          <option value="number">Number</option>
          <option value="boolean">Boolean</option>
    </select>
    <input type="text" class="replace">
    <input type="text" class="default_value">
    <input type="text" class="setting_value">
    </div>`);

    //jQuery("#snippet_setting_" + (jQuery("#snippet_settings_wrapper").children().length - 1))
});
var testikfunkce = function(){
    var objekticek = [];
    jQuery("#snippet_settings_wrapper").children().each(function(){
        if(jQuery(this).is(":hidden")) return;
        objekticek.push({
            label: jQuery(this).find(".label").val(),
            data_type: jQuery(this).find(".data_type").val(), 
            replace: jQuery(this).find(".replace").val(), 
            default_value: jQuery(this).find(".default_value").val(), 
        });
    });
    return JSON.stringify(objekticek);
}

var returnforvalues = function(){
    var objekticek = {};
    jQuery("#snippet_values_wrapper").children().each(function(){
        if(jQuery(this).find("input").is(":disabled")) return;
        objekticek[jQuery(this).find(".label").attr("assignedTo")] = jQuery(this).find(".setting_value").val();
    });
    if(JSON.stringify(objekticek) == "{}"){
        jQuery("#snippet_settings_wrapper").children().each(function(){
            objekticek[jQuery(this).find(".replace").val()] = jQuery(this).find(".setting_value").val();
        });
    }
    return JSON.stringify(objekticek);
}
const form = document.getElementById('snippet-form');
form.addEventListener('submit', function(event){ 
    jQuery('#snippet_snippet_settings').val(testikfunkce());
    if(jQuery('#snippet_snippet_settings').val() == "[]"){
        jQuery("#snippet_snippet_settings").prop('disabled', true);
    }
    jQuery('#snippet_snippet_values').val(returnforvalues());
    if(jQuery('#snippet_snippet_values').val() == "{}"){
        jQuery("#snippet_snippet_values").prop('disabled', true);
    }
});
jQuery("#remove_variable_wpd").click(function(){
    jQuery('#snippet_settings_wrapper').children().last().remove();
});
