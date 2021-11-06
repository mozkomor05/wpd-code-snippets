/* global code_snippets_editor_atts, code_snippets_editor_settings */

import './editor';

(function (Ace, editor_atts, editor_settings) {
    'use strict';

    editor_atts['value'] = [
        'add_filter( \'admin_footer_text\', function ( $text ) {\n',
        '\t$site_name = get_bloginfo( \'name\' );\n',
        '\t$text = "Thank you for visiting $site_name.";\n',
        '\treturn $text;',
        '} );\n',
    ].join('\n');

    const editor = Ace('code_snippets_editor_preview', editor_atts, true);
    window.code_snippets_editor_preview = editor;

    for (const setting of editor_settings) {
        const element = document.querySelector('[name="code_snippets_settings[editor][' + setting.name + ']"]');

        element.addEventListener('change', () => {
            const opt = setting['ace'];
            let value = (() => {
                switch (setting.type) {
                    case 'ace_theme_select':
                        return 'ace/theme/' + element.options[element.selectedIndex].value;
                    case 'checkbox':
                        return element.checked;
                    case 'number':
                        return parseInt(element.value);
                    default:
                        return null;
                }
            })();
            if (null !== value) {
                editor.setOption(opt, value);
            }
        });
    }

}(window.Code_Snippets_Ace, code_snippets_editor_atts, code_snippets_editor_settings));
