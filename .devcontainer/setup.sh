#! /bin/bash -i

if [[ ! -z "$CODESPACE_NAME" ]]
then
	SITE_HOST="https://${CODESPACE_NAME}-9999.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
else
	SITE_HOST="http://localhost:9999"
fi

exec 3>&1 4>&2
trap 'exec 2>&4 1>&3' 0 1 2 3
exec 1>setup.log 2>&1

# source ~/.bashrc

# Install dependencies
composer install && npm ci && npm run build

# Install WordPress and activate the plugin/theme.
cd /var/www/html/
echo "Setting up WordPress at $SITE_HOST"
wp db reset --yes
wp core install --url="$SITE_HOST" --title="WordPress Trunk" --admin_user="admin" --admin_email="admin@example.com" --admin_password="password" --skip-email

wp plugin activate wp-passkey