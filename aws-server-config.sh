#!/bin/bash

# AWS Server Configuration
# Generated automatically from existing settings

export SERVER_HOST="35.75.1.64"
export SERVER_USER="ec2-user"
export KEY_PATH="$HOME/Shise-Cal-test-key.pem"
export PROJECT_PATH="/home/ec2-user/shicecal"
export BRANCH="production"

echo "AWS Server Configuration loaded:"
echo "  Server: ${SERVER_USER}@${SERVER_HOST}"
echo "  Key: ${KEY_PATH}"
echo "  Path: ${PROJECT_PATH}"
echo "  Branch: ${BRANCH}"