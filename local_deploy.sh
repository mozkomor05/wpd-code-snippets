#!/bin/sh

npm run build
npm run package
sudo unzip wpd-code-snippets.*.zip -d /srv/http/wp-content/plugins/
rm wpd-code-snippets.*.zip
