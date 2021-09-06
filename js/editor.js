import './autocompletions/mode-php';

window.code_snippets_editor = (function (editor_atts) {
    let beautifying = false;
    const editor = ace.edit('snippet_code');
    const prependPhp = (value) => {
        value = value || editor.session.getValue();
        editor.session.setValue(`<?php\n` + value.replace(/^\s+/g, ''));
    };
    const textarea = document.getElementById('snippet_code_real');
    const beautiful = ace.require("ace/ext/beautify");

    editor.commands.addCommand({
        name: "beautify",
        bindKey: {win: "Shift-Alt-f", mac: "Shift-Alt-f"},
        exec: function (editor) {
            beautifying = true;
            beautiful.beautify(editor.session);
            beautifying = false;
        }
    });

    editor.commands.addCommand({
        name: "saveSnippet",
        bindKey: {win: "Ctrl-s", mac: "Cmd-s"},
        exec: function (editor) {
            document.getElementById('save_snippet_extra').click();
        }
    });

    ace.require('ace/ext/language_tools');
    editor.setTheme('ace/theme/iplastic');
    editor.session.setMode('ace/mode/php');

    const defaultOptions = {
        enableLiveAutocompletion: true,
    };

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

        textarea.value = value;
    });

    editor.session.selection.on('changeSelection', function (e) {
        const range = editor.session.selection.getRange();

        if (range.start.row === 0) {
            range.start.row = 1;
            editor.session.selection.setSelectionRange(range);
        }
    });

    textarea.value = editor.session.getValue();
    editor.focus();
    editor.gotoLine(2);

    return editor;
})(code_snippets_editor_atts);