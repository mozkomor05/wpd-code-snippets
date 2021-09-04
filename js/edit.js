/* global ajaxurl, jQuery */

import './editor';

'use strict'
jQuery(function ($) {
    const outputTextarea = document.getElementById('snippet_output');

    document.getElementById('wpd_execute').addEventListener('click', function () {
        let codeToRun = code_snippets_editor.session.getValue();
        codeToRun = codeToRun.replace(/^\<\?php/, '//').trim();

        $("#snippet_settings_wrapper").children().each(function () {
            codeToRun = codeToRun.replace($(this).find(".replace").val(), $(this).find(".setting_value").val());
        });

        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {action: "wpd_evaluate_code", input: codeToRun},
            success: function (res) {
                const outputHtml = res.data.output.html || '';
                const outputText = res.data.output.text || '';
                let errorStr = '';

                if (!res.success) {
                    errorStr = `<br><span style="color:red">${res.data.error.message}</span>`;
                }

                if (document.getElementById('wpd_execute_render_html').checked) {
                    outputTextarea.innerHTML = outputHtml + errorStr;
                } else {
                    outputTextarea.innerHTML = outputText + errorStr;
                }
            }
        });
    });

    document.getElementById('wpd_execute_clear').addEventListener('click', function () {
        outputTextarea.innerHTML = '';
    });

    $('.editor_section h2').on('click', function () {
        $(this).toggleClass('collapsed');
        $(this).next('.collapsible').slideToggle();
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
                label: $(this).find(".label").val(),
                data_type: $(this).find(".data_type").val(),
                replace: $(this).find(".replace").val(),
                default_value: $(this).find(".default_value").val(),
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


