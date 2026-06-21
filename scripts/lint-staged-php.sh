#!/usr/bin/env bash
# lint-staged helper: format staged PHP source files (skip generated block manifests).
set -euo pipefail

files=()
for file in "$@"; do
	if [[ "${file}" == *index.asset.php ]] || [[ "${file}" == *settings.asset.php ]]; then
		continue
	fi
	files+=("${file}")
done

if ((${#files[@]} == 0)); then
	exit 0
fi

vendor/bin/php-cs-fixer fix \
	--config=.php-cs-fixer.dist.php \
	--allow-unsupported-php-version=yes \
	-- "${files[@]}"
