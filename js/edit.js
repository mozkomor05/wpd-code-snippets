/* global ajaxurl, jQuery */

import './editor';

'use strict'
jQuery(document).ready(function ($) {
    const outputTextarea = document.getElementById('snippet_output');
    const macrosTbody = $('#snippet_macros tbody');
    const snippetMacrosInput = $('#snippet_macros_input');
    const macroList = snippetMacrosInput.val() ? JSON.parse(snippetMacrosInput.val()) : {};

    $('#wpd_execute').on('click', function () {
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

    $('#wpd_execute_clear').on('click', function () {
        outputTextarea.innerHTML = '';
    });

    $('.editor_section h2').on('click', function () {
        $(this).toggleClass('collapsed');
        $(this).next('.collapsible').stop().slideToggle();
    });

    let macrosUpdateTimer = null;
    window.code_snippets_editor.session.on('change', () => {
        clearTimeout(macrosUpdateTimer);
        macrosUpdateTimer = setTimeout(updateMacros, 2000);
    });

    $(document.body).on('change', '#snippet_macros .type_select', function () {
        const $this = $(this);
        const value = $this.val();
        const $tr = $this.closest('tr');
        const macro = $tr.attr('data-macro');
        const $valueTd = $tr.find('.value');

        macroList[macro].type = value;

        switch (value) {
            case 'string':
                const input = $('<input>', {
                    type: 'text',
                    value: macroList[macro].value
                });
                $valueTd.append(input);
                break;

            default:
                $valueTd.empty();
        }
    });

    $(document.body).on('change', '#snippet_macros .value input', function () {
        const $this = $(this);
        const $tr = $this.closest('tr');
        const macro = $tr.attr('data-macro');

        macroList[macro].value = $this.val();
    });

    const updateMacros = function () {
        const code = window.code_snippets_editor.session.getValue();
        const matches = code.matchAll(/\${{([a-zA-Z_0-9]+)}}/gm);

        for (const [key, val] of Object.entries(macroList)) {
            if (val.type === 'unused') {
                delete macroList[key];
            }
        }

        macrosTbody.empty();

        for (const match of matches) {
            const macro = match[1];

            if (!(macro in macroList)) {
                macroList[macro] = {
                    type: 'unused',
                    value: ''
                };
            }

            const macroType = macroList[macro].type;

            const node = $(`
                <tr data-macro="${macro}">
                    <td class="name">${macro}</td>
                    <td class="type">
                        <select class="type_select">
                            <option value="unused">Unused (ignored)</option>
                            <option value="string">String</option>
                        </select>
                    </td>
                    <td class="value"></td>
                </tr>
            `);

            node.find('.type_select').val(macroType);

            macrosTbody.prepend(node);
        }

        $('#snippet_macros .type_select').trigger('change');
    };

    const getMacrosArr = function () {
        return Object.keys(macroList).reduce((newObj, macro) => {
            if (macroList[macro].type !== 'unused' && $('#snippet_macros [data-macro="' + macro + '"]').length)
                newObj[macro] = macroList[macro];

            return newObj;
        }, {});
    };

    $('#snippet-form').submit(function () {
        snippetMacrosInput.val(JSON.stringify(getMacrosArr()));
    });

    updateMacros();
});


