/* global ajaxurl, pagenow, tb_show */
'use strict';
(function () {
    const nonce = document.getElementById('code_snippets_ajax_nonce').value;
    const network_admin = ('-network' === pagenow.substring(pagenow.length - '-network'.length));
    const strings = window.code_snippets_manage_i18n;

    /**
     * Utility function to loop through a DOM list
     * @param {HTMLCollectionBase} elements
     * @param {function} callback
     */
    function foreach(elements, callback) {
        for (let i = 0; i < elements.length; i++) {
            callback(elements[i], i);
        }
    }

    /**
     * Update the data of a given snippet using AJAX
     * @param {string}   field
     * @param {Element}  row_element
     * @param {object}   snippet
     * @param {function} [success_callback]
     */
    function update_snippet(field, row_element, snippet, success_callback) {
        const id_column = row_element.querySelector('.column-id');

        if (!id_column || !parseInt(id_column.textContent)) {
            return;
        }

        snippet['id'] = parseInt(id_column.textContent);
        snippet['shared_network'] = !!row_element.className.match(/\bshared-network-snippet\b/);
        snippet['network'] = snippet['shared_network'] || network_admin;

        const query_string = 'action=update_code_snippet&_ajax_nonce=' + nonce + '&field=' + field + '&snippet=' + JSON.stringify(snippet);

        const request = new XMLHttpRequest();
        request.open('POST', ajaxurl, true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        request.onload = () => {
            if (request.status < 200 || request.status >= 400) {
                return;
            }

            if (success_callback !== undefined) {
                success_callback(JSON.parse(request.responseText));
            }
        };

        request.send(query_string);
    }

    /* snippet priorities */

    /**
     * Update the priority of a snippet
     */
    function update_snippet_priority() {
        const row = this.parentElement.parentElement;
        const snippet = {'priority': this.value};
        update_snippet('priority', row, snippet);
    }

    foreach(document.getElementsByClassName('snippet-priority'), (field, i) => {
        field.addEventListener('input', update_snippet_priority);
        field.disabled = false;
    });

    /* activate/deactivate links */

    /**
     * Update the snippet count of a specific view
     * @param {HTMLElement} view_count
     * @param {boolean}     increment
     */
    function update_view_count(view_count, increment) {
        let n = parseInt(view_count.textContent.replace(/\((\d+)\)/, '$1'));
        increment ? n++ : n--;
        view_count.textContent = '(' + n.toString() + ')';
    }

    /**
     * Activate an inactive snippet, or deactivate an active snippet
     * @param {Event} e
     */
    function toggle_snippet_active(e) {

        const row = this.parentElement.parentElement; // switch < cell < row
        const match = row.className.match(/\b(?:in)?active-snippet\b/);
        if (!match) {
            return;
        }

        e.preventDefault();

        const activating = 'inactive-snippet' === match[0];
        const snippet = {'active': activating};

        update_snippet('active', row, snippet, (response) => {
            const button = row.querySelector('.snippet-activation-switch');
            if (response.success) {
                row.className = (activating) ?
                    row.className.replace(/\binactive-snippet\b/, 'active-snippet') :
                    row.className.replace(/\bactive-snippet\b/, 'inactive-snippet');

                const views = document.querySelector('.subsubsub');
                update_view_count(views.querySelector('.active .count'), activating);
                update_view_count(views.querySelector('.inactive .count'), activating);

                button.title = activating ? strings['deactivate'] : strings['activate'];
            } else {
                row.className += ' erroneous-snippet';
                button.title = strings['activation_error'];
            }
        });
    }

    function open_push_thickbox(e) {
        e.preventDefault();
        const snippetID = parseInt(this.closest('tr').querySelector('.column-id').textContent);
        document.getElementById('push-snippet-id').value = snippetID;

        const query_string = 'action=get_snippet_fields&_ajax_nonce=' + nonce + '&id=' + snippetID;
        const request = new XMLHttpRequest();

        request.responseType = 'json';
        request.open('POST', ajaxurl, true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        request.onload = () => {
            if (request.status < 200 || request.status >= 400) {
                return;
            }

            document.getElementById('push-snippet-name').value = request.response.data.name;
            document.getElementById('push-snippet-desc').value = request.response.data.desc;

            tb_show(this.title, "#TB_inline?&width=600&height=550&inlineId=push-snippet-thickbox", false);
        };

        request.send(query_string);
    }

    function push_snippet(e) {
        this.disabled = true;
        const form = document.getElementById('push-snippet-form');
        let snippetRow = null;

        const snippet = {};
        const formData = new FormData(form);

        for (const [key, val] of formData) {
            if (key === 'id') {
                snippetRow = Array.from(document.querySelectorAll('#the-list > tr')).find(el =>
                    el.querySelector('.column-id').textContent === val
                );
            } else {
                snippet[key] = val;
            }
        }

        update_snippet('all', snippetRow, snippet, (response) => {
            if (response.success) {
                window.location.href = snippetRow.querySelector('.snippet-push-action').href;
            }
        });
    }

    foreach(document.getElementsByClassName('snippet-activation-switch'), (link, i) => {
        link.addEventListener('click', toggle_snippet_active);
    });

    foreach(document.getElementsByClassName('snippet-push-action'), (link, i) => {
        link.addEventListener('click', open_push_thickbox);
    })

    document.getElementById('push-snippet-btn').addEventListener('click', push_snippet);

})();
