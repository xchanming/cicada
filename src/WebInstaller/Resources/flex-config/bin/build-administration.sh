#!/usr/bin/env bash

CWD="$(cd -P -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"

export PROJECT_ROOT="${PROJECT_ROOT:-"$(dirname "$CWD")"}"
export ENV_FILE=${ENV_FILE:-"${PROJECT_ROOT}/.env"}

# shellcheck source=functions.sh
source "${PROJECT_ROOT}/bin/functions.sh"

curenv=$(declare -p -x)

load_dotenv "$ENV_FILE"

# Restore environment variables set globally
set -o allexport
eval "$curenv"
set +o allexport

set -euo pipefail

export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
export DISABLE_ADMIN_COMPILATION_TYPECHECK=true
export PROJECT_ROOT="${PROJECT_ROOT:-"$(dirname "$CWD")"}"
ADMIN_ROOT="${ADMIN_ROOT:-"${PROJECT_ROOT}/vendor/cicada/administration"}"

BIN_TOOL="${CWD}/console"

if [[ ${CI-""} ]]; then
    BIN_TOOL="${CWD}/ci"

    if [[ ! -x "$BIN_TOOL" ]]; then
        chmod +x "$BIN_TOOL"
    fi
fi

# build admin
[[ ${CICADA_SKIP_BUNDLE_DUMP-""} ]] || "${BIN_TOOL}" bundle:dump
"${BIN_TOOL}" feature:dump || true

if [[ $(command -v jq) ]]; then
    OLDPWD=$(pwd)
    cd "$PROJECT_ROOT" || exit

    jq -c '.[]' "var/plugins.json" | while read -r config; do
        srcPath=$(echo "$config" | jq -r '(.basePath + .administration.path)')

        # the package.json files are always one upper
        path=$(dirname "$srcPath")
        name=$(echo "$config" | jq -r '.technicalName' )

        if [[ -f "$path/package.json" && ! -d "$path/node_modules" && $name != "administration" ]]; then
            echo "=> Installing npm dependencies for ${name}"

            npm install --prefix "$path"
        fi
    done
    cd "$OLDPWD" || exit
else
    echo "Cannot check extensions for required npm installations as jq is not installed"
fi

# Dump entity schema
if [[ -z "${CICADA_SKIP_ENTITY_SCHEMA_DUMP-""}" ]] && [[ -f "${ADMIN_ROOT}"/Resources/app/administration/scripts/entitySchemaConverter/entity-schema-converter.ts ]]; then
  mkdir -p "${ADMIN_ROOT}"/Resources/app/administration/test/_mocks_
  "${BIN_TOOL}" -e prod framework:schema -s 'entity-schema' "${ADMIN_ROOT}"/Resources/app/administration/test/_mocks_/entity-schema.json
  (cd "${ADMIN_ROOT}"/Resources/app/administration && npm run convert-entity-schema)
fi

(cd "${ADMIN_ROOT}"/Resources/app/administration && npm install && npm run build)
[[ ${CICADA_SKIP_ASSET_COPY-""} ]] ||"${BIN_TOOL}" assets:install
