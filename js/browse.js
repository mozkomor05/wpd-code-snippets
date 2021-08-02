/* globals ajaxurl  */
'use strict';
(function () {
    const nonce = document.getElementById('code_snippets_ajax_nonce').value;
    const strings = window.code_snippets_browse_i18n;

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
     * @param {string}   endpoint
     * @param {function} [success_callback]
     */
    function install_snippet(endpoint, success_callback) {
        const query_string = 'action=browse_code_snippet&_ajax_nonce=' + nonce + '&field=install&url=' + endpoint;

        const request = new XMLHttpRequest();
        request.open('POST', ajaxurl, true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        request.onload = () => {
            if (request.status < 200 || request.status >= 400) return;

            if (success_callback !== undefined)
                success_callback(JSON.parse(request.responseText));
        };

        request.send(query_string);
    }

    /**
     * Activate an inactive snippet, or deactivate an active snippet
     * @param {Event} e
     */
    function install_click(e) {

        const endpoint = this.getAttribute("data-endpoint");

        if (!endpoint || !this.classList.contains("snippet-install-button"))
            return;

        e.preventDefault();

        this.textContent = strings['waiting'];
        this.classList.remove("snippet-install-button");

        install_snippet(endpoint, (response) => {
            if (response.success) {
                this.classList.add("snippet-installed-button");
                this.textContent = strings["installed"];
            } else {
                this.classList.add("snippet-error-button");
                this.textContent = strings['install_error'];
            }
        });
    }

    foreach(document.getElementsByClassName('snippet-install-button'), (link, i) => {
        link.addEventListener('click', install_click);
    });
})();