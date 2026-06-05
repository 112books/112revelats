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
  print "Publicant a GitHub Pages..."
  git subtree push --prefix $BUILD_DIR $REMOTE gh-pages 2>/dev/null
  if [[ $? -ne 0 ]]; then
    # If subtree push fails (e.g. first time), force split and push
    git push $REMOTE `git subtree split --prefix $BUILD_DIR $BRANCH`:gh-pages --force
  fi
  ok "Deploy fet → https://112books.github.io/$REPO_NAME/"
}

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
