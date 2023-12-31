#!/usr/bin/env bash
#
#   Copyright (c) 2021: Jacob.Lundqvist@gmail.com
#   License: MIT
#
#   Part of https://github.com/jaclu/tmux-menus
#
#   Version: 1.0.1 2021-11-11
#

CURRENT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

MENUS_DIR="$CURRENT_DIR/items"
SCRIPTS_DIR="$CURRENT_DIR/scripts"

source "$SCRIPTS_DIR/utils.sh"

trigger_key=$(get_tmux_option "@menus_trigger" "\\")

tmux bind -N "Displays tmux-menus" F12 run-shell $MENUS_DIR/main.sh
