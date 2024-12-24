#!/usr/bin/env bash
set -eu

if [ -n "${DEBUG:-}" ]; then
  set -x
fi

CURRENT_MAJOR_ALIAS=${CURRENT_MAJOR_ALIAS:-}

# deployment_branch_name returns the branch name for the current deployment.
deployment_branch_name() {
  date --utc --date="now" +'saas/%Y/%W'
}

# current_major_alias Fetches the latest released version of Cicada 6,
# excluding rc-versions and formats it as a major alias, e.g. `6.6.x-dev`.
#
# Can be overriden by setting the `CURRENT_MAJOR_ALIAS` environment variable.
current_major_alias() {
  if [ -n "${CURRENT_MAJOR_ALIAS}" ]; then
    printf "%s" "${CURRENT_MAJOR_ALIAS}"
    return
  fi

  curl -fsSL "https://releases.cicada.com/changelog/index.json" | jq -r '[.[] | select(test("[a-zA-Z]") | not)] | first | split(".") | [.[0], .[1], "x-dev"] | join(".")'
}

# custom_version_core returns the custom version for the core repositories.
custom_version_core() {
  branch="$(deployment_branch_name)"
  major_alias="$(current_major_alias)"

  printf "cicada-ag/platform:dev-%s as %s;cicada/commercial:dev-%s;swag/saas-rufus:dev-%s" "${branch}" "${major_alias}" "${branch}" "${branch}"
}

# custom_version_extensions returns the custom version for the extension
# repositories.
custom_version_extensions() {
  set -eu
  tmpdir="$(mktemp -d)"

  git clone --depth=1 "https://gitlab-ci-token:${GITLAB_SAAS_TOKEN}@gitlab.cicada.com/cicada/6/product/saas.git" "${tmpdir}"
  composer -d "${tmpdir}" show --locked --outdated --direct --format=json >"${tmpdir}/outdated.json"

  jq -r \
    '[.locked[] | select(.name | test("^(cicada|swag)/")) | select(.latest | test("(^dev-|-dev)") | not) | select(."latest-status" | test("update-possible|semver-safe-update")) | .name + ":" + .latest] | join(";")' \
    "${tmpdir}/outdated.json"
}

commit_date() {
  local repo="${1}"
  local branch="${2}"

  gh api "/repos/cicada/${repo}/branches/${branch}" | jq -r '.commit.commit.committer.date' | xargs -I '{}' date -R --date="{}"
}

gitlab_mr_description() {
  deployment_branch_name="$(deployment_branch_name)"
  deployment_branch_name_url_encoded=$(echo "${deployment_branch_name}" | sed 's/\//%2F/g')

  cat <<EOF | tr -d '\n'
<p>
This PR has been created automatically to facilitate the deployment <em>${deployment_branch_name}</em>.
<br/>
Please review the changes and merge this MR if you are satisfied with the deployment.
</p>
<p>
For the core dependencies, the dates of the latest commits on the branches are as follows, please make sure that all pipelines are green! 👀
<ul>
<li><span>cicada-ag/platform: <b>$(commit_date "cicada-private" "${deployment_branch_name_url_encoded}")</b></span></li>
<li><span>cicada/commercial: <b>$(commit_date "SwagCommercial" "${deployment_branch_name_url_encoded}")</b></span></li>
<li><span>swag/saas-rufus: <b>$(commit_date "Rufus" "${deployment_branch_name_url_encoded}")</b></span></li>
</ul>
</p>
EOF
}

# deployment_env compiles the environment variables for the deployment.
deployment_env() {
  update_extensions="${1:-}"

  deployment_branch_name="$(deployment_branch_name)"
  ci_update_dependency="1"
  gitlab_mr_description="$(gitlab_mr_description)"
  custom_version=""
  gitlab_mr_title="Deployment - ${deployment_branch_name}"
  gitlab_mr_labels="pipeline:autodeploy"
  gitlab_mr_assignees="cicadabot"

  if [ -n "${update_extensions}" ]; then
    custom_version="$(custom_version_core);$(custom_version_extensions)"
  else
    custom_version="$(custom_version_core)"
  fi

  cat <<EOF
DEPLOYMENT_BRANCH_NAME="${deployment_branch_name}"
CI_UPDATE_DEPENDENCY="${ci_update_dependency}"
CUSTOM_VERSION="${custom_version}"
GITLAB_MR_TITLE="${gitlab_mr_title}"
GITLAB_MR_DESCRIPTION_TEXT="${gitlab_mr_description}"
GITLAB_MR_LABELS="${gitlab_mr_labels}"
GITLAB_MR_ASSIGNEES="${gitlab_mr_assignees}"
EOF
}

deployment_env_b64() {
  target_var="${1}"
  update_extensions="${2:-}"

  printf "%s=%s\n" "${target_var}" "$(deployment_env "${update_extensions}" | base64 -w0)"
  printf "CUSTOM_VERSION=1\n"
  printf "CI_UPDATE_DEPENDENCY=1\n"
}

"$@"
