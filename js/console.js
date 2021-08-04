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
    eval("debugger;");
    jQuery("#snippet_settings_wrapper").children().each(function(){
        objekticek[jQuery(this).find(".replace").val()] = jQuery(this).find(".setting_value").val();
    });
    return JSON.stringify(objekticek);
}
const form = document.getElementById('snippet-form');
form.addEventListener('submit', function(event){
    //event.preventDefault(); 
    jQuery('#snippet_snippet_settings').val(testikfunkce());
    jQuery('#snippet_snippet_values').val(returnforvalues());
    //jQuery(this).unbind('submit').submit();
});
jQuery("#remove_variable_wpd").click(function(){
    jQuery('#snippet_settings_wrapper').children().last().remove();
});
