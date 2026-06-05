#!/bin/bash

# sync-112revelats.sh — Git + Hugo + Deploy (GitHub Pages)

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

BRANCH="main"
REMOTE="origin"
BUILD_DIR="public"
REPO_NAME="112-revelats"

print() { echo -e "${BLUE}[112-revelats]${NC} $1"; }
ok()    { echo -e "${GREEN}[✓]${NC} $1"; }
err()   { echo -e "${RED}[✗]${NC} $1"; }

git rev-parse --git-dir > /dev/null 2>&1 || {
  err "No és un repo git"
  exit 1
}

status() {
  print "Estat del repo"
  git status -s
}

sync() {
  print "Sincronitzant..."
  if [[ -n $(git status -s) ]]; then
    print "Canvis detectats"
    git status -s
    read -p "Missatge del commit: " msg
    git add .
    git commit -m "$msg"
  fi
  git pull $REMOTE $BRANCH --rebase || exit 1
  git push $REMOTE $BRANCH || exit 1
  ok "Sync complet"
}

build() {
  print "Build..."
  hugo --minify || exit 1
  ok "Build correcte"
}

deploy() {
  sync
  build
  deploy_ghpages
}

deploy_ghpages() {
  print "Publicant a GitHub Pages..."
  git checkout --orphan gh-pages-tmp
  git rm -rf . > /dev/null 2>&1 || true
  cp -r $BUILD_DIR/* . 2>/dev/null || true
  touch .nojekyll
  git add -A
  git commit -m "deploy: $(date +%Y-%m-%d_%H:%M)"
  git push $REMOTE gh-pages-tmp:gh-pages --force
  git checkout $BRANCH
  git branch -D gh-pages-tmp 2>/dev/null || true
  ok "Deploy fet → https://112revelats.112books.eu/"
}

deploy_auto() {
  build
  print "Publicant a GitHub Pages..."
  git checkout --orphan gh-pages-tmp 2>/dev/null
  git rm -rf . > /dev/null 2>&1 || true
  cp -r $BUILD_DIR/* . 2>/dev/null || true
  touch .nojekyll
  git add -A 2>/dev/null || true
  git commit -m "deploy: $(date +%Y-%m-%d_%H:%M)" 2>/dev/null || true
  git push $REMOTE gh-pages-tmp:gh-pages --force 2>/dev/null || true
  git checkout $BRANCH 2>/dev/null || true
  git branch -D gh-pages-tmp 2>/dev/null || true
  ok "Deploy fet → https://112revelats.112books.eu/"
}

if [[ "$1" == "deploy" ]]; then
  deploy_auto
  exit 0
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━"
echo " 112 Revelats — Deploy"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1) Status"
echo "2) Sync (git)"
echo "3) Build local"
echo "4) Deploy (GitHub Pages)"
echo "0) Sortir"
echo ""

read -p "Opció: " opt

case $opt in
  1) status ;;
  2) sync ;;
  3) build ;;
  4) deploy ;;
  0) exit ;;
  *) err "Opció no vàlida" ;;
esac
