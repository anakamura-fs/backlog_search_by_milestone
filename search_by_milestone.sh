#!/bin/bash

export SPACE_ID="xxx"
export APIKEY="yyy"
export BACKLOG_DOMAIN="com"
export PROJECT="PPP"
export MILESTONE_NAME="MS"
# export USER_ID="12345"

php get_the_user.php
echo
php search_by_milestone.php

