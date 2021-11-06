import './autocompletions/mode-php';

'use strict'
window.Code_Snippets_Ace = (function (editor_id, editor_atts, preview) {
    let beautifying = false;
    const editor = ace.edit(editor_id);
    const prependPhp = (value) => {
        value = value || editor.session.getValue();
        editor.session.setValue(`<?php\n` + value.replace(/^\s+/g, ''));
    };
    const beautiful = ace.require("ace/ext/beautify");
    const textarea = preview ? false : document.getElementById('snippet_code_real');

    editor.commands.addCommand({
        name: "beautify",
        bindKey: {win: "Ctrl-Alt-f", mac: "Cmd-Alt-f"},
        exec: function (editor) {
            beautifying = true;
            beautiful.beautify(editor.session);
            beautifying = false;
        }
    });

    if (!preview) {
        editor.commands.addCommand({
            name: "saveSnippet",
            bindKey: {win: "Ctrl-s", mac: "Cmd-s"},
            exec: function (editor) {
                document.getElementById('save_snippet_extra').click();
            }
        });
    }

    ace.require('ace/ext/language_tools');
    editor.setTheme('ace/theme/iplastic');
    editor.session.setMode('ace/mode/php');

    const defaultOptions = {
        enableLiveAutocompletion: true,
    };

    editor_atts['theme'] = 'ace/theme/' + editor_atts['theme'];

    editor.setOptions(Object.assign(defaultOptions, editor_atts));
    editor.container.style.lineHeight = 1.6;
    editor.renderer.updateFontSize();
    editor.setShowPrintMargin(false);

    const whitelistCommands = [
        'golineup',
        'gotoright',
        'golinedown',
        'gotoleft',
    ];

    prependPhp();

    editor.commands.on('exec', function (e) {
        const commandName = e.command.name;
        const rowCol = editor.selection.getCursor();

        if (rowCol.row === 0 && !whitelistCommands.includes(commandName)) {
            e.preventDefault();
            e.stopPropagation();

            if (e.args === '\n' || e.args === '\r\n') {
                editor.navigateDown(1);
            }
        }
    });

    editor.session.on('change', () => {
        if (beautifying)
            return;

        let value = editor.session.getValue();

        if (!value.startsWith('<?php')) {
            prependPhp(value);
        } else if (editor.session.getLength() === 1) {
            let value = '';
            if (!editor.session.getValue()) {
                value = `<?php\n`;
            }

            editor.session.setValue(value);
        }

        if (textarea)
            textarea.value = value;
    });

    editor.session.selection.on('changeSelection', function (e) {
        const range = editor.session.selection.getRange();

        if (range.start.row === 0) {
            range.start.row = 1;
            editor.session.selection.setSelectionRange(range);
        }
    });

    if (textarea)
        textarea.value = editor.session.getValue();
    editor.focus();
    editor.gotoLine(2);

    return editor;
});