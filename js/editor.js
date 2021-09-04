import './autocompletions/mode-php';

window.code_snippets_editor = (function (editor_atts) {
    const editor = ace.edit('snippet_code');
    const prependPhp = (value) => {
        value = value || editor.session.getValue();
        editor.session.setValue(`<?php\n` + value.replace(/^\s+/g, ''));
    };
    const textarea = document.getElementById('snippet_code_real');

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
        let value = editor.session.getValue();

        if (!value.startsWith('<?php')) {
            prependPhp(value);
        }

        if ( editor.session.getLength() === 1 ) {
            let value = '';
            if ( ! editor.session.getValue() ) {
                value = `<?php\n`;
            }

            editor.session.setValue( value );
        }

        textarea.value = value;
    });

    textarea.value = editor.session.getValue();
    editor.focus();
    editor.gotoLine(2);

    return editor;
})(code_snippets_editor_atts);