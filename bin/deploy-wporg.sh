#!/usr/bin/env bash
# Deploy the built plugin to WordPress.org SVN in small commits, then tag with a server-side copy.
# One big commit (trunk plus a working-copy tag copy, ~2,800 changes) times out on the SVN server.
set -euo pipefail

: "${VERSION:?Set VERSION}"
SLUG="${SLUG:-biometric-authentication}"
DRY_RUN="${DRY_RUN:-false}"
WORKSPACE="${GITHUB_WORKSPACE:-$(pwd)}"
SVN_URL="https://plugins.svn.wordpress.org/${SLUG}"
# Outside the workspace, or the rsync below would copy the checkout into trunk.
SVN_DIR="${RUNNER_TEMP:-/tmp}/svn-${SLUG}"

if [[ "$DRY_RUN" != true ]]; then
	: "${SVN_USERNAME:?Set the SVN_USERNAME secret}"
	: "${SVN_PASSWORD:?Set the SVN_PASSWORD secret}"
fi

if ! command -v svn >/dev/null; then
	sudo apt-get update -y && sudo apt-get install -y subversion
fi

svn_commit() {
	local message="$1"
	shift
	if [[ "$DRY_RUN" == true ]]; then
		echo "  [dry run] commit $(svn status -q "$@" | wc -l | tr -d ' ') changes: $message"
		# Revert so later batches only count what they would really commit.
		svn revert -R --quiet "$@"
		return
	fi
	svn commit "$@" -m "$message" --no-auth-cache --non-interactive \
		--username "$SVN_USERNAME" --password "$SVN_PASSWORD"
}

echo "➤ Deploying $SLUG $VERSION (dry run: $DRY_RUN)"
rm -rf "$SVN_DIR"
svn checkout --quiet --depth immediates "$SVN_URL" "$SVN_DIR"
cd "$SVN_DIR"
svn update --quiet --set-depth infinity assets trunk
svn update --quiet --set-depth immediates tags

if [[ -d "tags/$VERSION" ]]; then
	echo "ℹ︎ $VERSION is already published"
	exit 0
fi

echo "➤ Syncing files"
rsync -rc --exclude-from="$WORKSPACE/.distignore" "$WORKSPACE/" trunk/ --delete --delete-excluded
MANAGE_ASSETS=false
if [[ -d "$WORKSPACE/.wordpress-org" ]]; then
	MANAGE_ASSETS=true
	rsync -rc "$WORKSPACE/.wordpress-org/" assets/ --delete
fi

svn add . --force --quiet
svn status | sed -n 's/^! *//p' | xargs -r -I% svn rm --quiet %@

if [[ "$MANAGE_ASSETS" == true ]]; then
	shopt -s nullglob
	for image in assets/*.png; do svn propset --quiet svn:mime-type image/png "$image"; done
	for image in assets/*.jpg; do svn propset --quiet svn:mime-type image/jpeg "$image"; done
	for image in assets/*.gif; do svn propset --quiet svn:mime-type image/gif "$image"; done
	for image in assets/*.svg; do svn propset --quiet svn:mime-type image/svg+xml "$image"; done
	shopt -u nullglob
fi

echo "➤ Committing trunk/vendor one directory at a time"
# trunk/vendor/<org> paths (or top-level vendor files) that have changes.
mapfile -t vendor_paths < <(svn status trunk/vendor | cut -c9- | awk -F/ 'NF >= 3 { print $1 "/" $2 "/" $3 }' | sort -u)
for path in "${vendor_paths[@]}"; do
	svn_commit "Update ${path#trunk/} for version $VERSION" "$path"
done

echo "➤ Committing the rest of trunk and assets"
svn_commit "Update to version $VERSION from GitHub" trunk assets

echo "➤ Tagging $VERSION"
if [[ "$DRY_RUN" == true ]]; then
	echo "  [dry run] svn copy $SVN_URL/trunk $SVN_URL/tags/$VERSION"
else
	svn copy "$SVN_URL/trunk" "$SVN_URL/tags/$VERSION" -m "Tagging version $VERSION" \
		--no-auth-cache --non-interactive --username "$SVN_USERNAME" --password "$SVN_PASSWORD"
fi

echo "✓ Deployed $SLUG $VERSION"
