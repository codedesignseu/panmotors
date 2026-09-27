#!/bin/bash
# Builds the production theme zip: only what the live site needs, from the committed files.
# Usage: bash dev/build-zip.sh [output.zip]   (default: dev/.cache/dist/panmotors-theme.zip)
#
# Starts from `git archive HEAD` (so uncommitted work and git-ignored files never ship), then drops
# everything listed in .distignore: _design/, dev/, docs/, composer files, caches, the task notes.
# acf-json/ stays: ACF loads the field groups from it on the live site.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/dev/.cache/dist/panmotors-theme.zip}"
case "$OUT" in /*) ;; *) OUT="$PWD/$OUT" ;; esac
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

if [ -n "$(git -C "$ROOT" status --porcelain)" ]; then
	echo "Note: uncommitted changes are not in the zip (it is built from HEAD)." >&2
fi

mkdir -p "$TMP/src" "$TMP/panmotors" "$(dirname "$OUT")"
git -C "$ROOT" archive HEAD | tar -x -C "$TMP/src"
rsync -a --exclude-from="$ROOT/.distignore" "$TMP/src/" "$TMP/panmotors/"

rm -f "$OUT"
(cd "$TMP" && zip -qrX "$OUT" panmotors)

for required in style.css functions.php acf-json theme.json blocks assets/css/main.css; do
	[ -e "$TMP/panmotors/$required" ] || { echo "Missing from the zip: $required" >&2; exit 1; }
done
for banned in _design dev docs .git composer.json TASKS.md CLAUDE.md; do
	[ ! -e "$TMP/panmotors/$banned" ] || { echo "Should not be in the zip: $banned" >&2; exit 1; }
done

echo "$OUT"
echo "$(find "$TMP/panmotors" -type f | wc -l | tr -d ' ') files, $(du -h "$OUT" | cut -f1) zipped ($(du -sh "$TMP/panmotors" | cut -f1) unpacked), commit $(git -C "$ROOT" rev-parse --short HEAD)"
