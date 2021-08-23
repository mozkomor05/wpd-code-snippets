/* global ajaxurl, jQuery */
'use strict'
jQuery(function ($) {
    $("#run_code_wpd").on('click', function () {
        let codeToRun = code_snippets_editor.getValue();
        $("#snippet_settings_wrapper").children().each(function () {
            codeToRun = codeToRun.replace($(this).find(".replace").val(), $(this).find(".setting_value").val());
        });
        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {"action": "evaluatewpd", "input": btoa(encodeURIComponent(codeToRun))},
            success: function (msg) {
                $("#snippet_output").html(msg.output ? msg.output : msg.data.output);
            },
            error: function (msg) {
                $("#snippet_output").html(msg.data.output);
            }
        });
    });

    function insertTextAtCursor(editor, text) {
        const doc = editor.getDoc();
        const cursor = doc.getCursor();
        doc.replaceRange(text, cursor);
    }

    $("#snippet_template").change(function () {
        var id = $(this).children(":selected").val();
        var settings = window.codeSnippetTemplateSettings[parseInt(id)];
        settings.forEach(function (item, index) {
            $("#snippet_template_settings_wrapper").append(`
            <div id="snippet_template_setting_${index}"> 
          <label class="label" assignedTo="${index}" >${item['label']}</label>
          <input type="text" class="setting_value" value="${item['default_value']}">
        </div>        
`);
        });
        $("#snippet_template_settings").slideDown();


    });

    $("#execute_template").click(function () {
        var id = $("#snippet_template").children(":selected").val();
        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {"action": "getsnippetcontent", "id": id},
            success: function (msg) {
                $("#snippet_template_settings_wrapper").children().each(function (index) {
                    msg.code = msg.code.replace(window.codeSnippetTemplateSettings[id][index]["replace"], $(this).find(".setting_value").val());
                });
                insertTextAtCursor(window.code_snippets_editor, msg.code);
                $("#snippet_template").prop("selectedIndex", 0);
                $("#snippet_template_settings_wrapper").html("");
                $("#snippet_template_settings").hide();
            }
        });
    });

    let advanced_wpd_enabled = false;

    function advanced_wpd_enable() {
        $("#snippet_values_wrapper").find("*").prop('disabled', true);
        $("#snippet_settings_wrapper").slideDown();
        $("#snippet_advanced_view").slideDown();
        $("#snippet_setting_buttons").slideDown();
    }

    function advanced_wpd_disable() {
        $("#snippet_values_wrapper").find("*").prop('disabled', false);
        $("#snippet_settings_wrapper").slideUp();
        $("#snippet_advanced_view").slideUp();
        $("#snippet_setting_buttons").slideUp();
    }

    $("#advanced_view_wpd").click(function () {
        if (advanced_wpd_enabled) {
            advanced_wpd_disable();
        } else {
            advanced_wpd_enable();
        }
        advanced_wpd_enabled = !advanced_wpd_enabled;
    });

    $("#add_variable_wpd").click(function () {
        $("#snippet_settings_wrapper").append(`
            <div id="snippet_setting_` + jQuery("#snippet_settings_wrapper").children().length + `">
                <div class="editor_section">
                    <label>Label for snippet setting:</label>
                    <input type="text" class="label"><br>
                </div>
                <div class="editor_section">
                    <label>String to replace in snippet:</label>
                    <input type="text" class="replace"><br>
                </div>
                <div class="editor_section">
                    <label>Default value:</label>
                    <input type="text" class="default_value"><br>
                </div>
                <div class="editor_section">
                    <label>Value:</label>
                    <input type="text" class="setting_value"><br>
                </div>
            </div>
        `);

        //jQuery("#snippet_setting_" + (jQuery("#snippet_settings_wrapper").children().length - 1))
    });
    const testikfunkce = function () {
        const objekticek = [];
        $("#snippet_settings_wrapper").children().each(function () {
            if ($(this).is(":hidden"))
                return;

            objekticek.push({
                label: jQuery(this).find(".label").val(),
                data_type: jQuery(this).find(".data_type").val(),
                replace: jQuery(this).find(".replace").val(),
                default_value: jQuery(this).find(".default_value").val(),
            });
        });
        return JSON.stringify(objekticek);
    };

    var returnforvalues = function () {
        const objekticek = {};
        $("#snippet_values_wrapper").children().each(function () {
            if ($(this).find("input").is(":disabled")) return;
            objekticek[$(this).find(".label").attr("assignedTo")] = $(this).find(".setting_value").val();
        });
        if (JSON.stringify(objekticek) === "{}") {
            $("#snippet_settings_wrapper").children().each(function () {
                objekticek[$(this).find(".replace").val()] = $(this).find(".setting_value").val();
            });
        }
        return JSON.stringify(objekticek);
    }
    const snippetForm = $('#snippet-form');

    snippetForm.submit(function () {
        const snippetSettings = $('#snippet_snippet_settings');
        const snippetValues = jQuery('#snippet_snippet_values');
        snippetSettings.val(testikfunkce());

        let empty = 0;
        if (snippetSettings.val() === "[]") {
            snippetSettings.prop('disabled', true);
            empty++;
        }

        snippetValues.val(returnforvalues());
        if (snippetValues.val() === "{}") {
            snippetValues.prop('disabled', true);
            empty++;
        }
        if (empty === 2) {
            snippetForm.append('<input type="hidden" id="has_no_settings" name="has_no_settings" value="true">');
        }
    });

    $("#remove_variable_wpd").on('click', function () {
        $('#snippet_settings_wrapper').children().last().remove();
    });
});