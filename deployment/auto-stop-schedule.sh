#!/bin/bash

# EC2インスタンス自動停止スケジュール
# 夜間・週末の自動停止でコスト削減

INSTANCE_ID="i-1234567890abcdef0"  # 実際のインスタンスIDに変更

# 平日夜間停止（22時）
if [ $(date +%u) -le 5 ] && [ $(date +%H) -eq 22 ]; then
    echo "平日夜間停止を実行します"
    aws ec2 stop-instances --instance-ids $INSTANCE_ID
fi

# 週末停止（金曜22時）
if [ $(date +%u) -eq 5 ] && [ $(date +%H) -eq 22 ]; then
    echo "週末停止を実行します"
    aws ec2 stop-instances --instance-ids $INSTANCE_ID
fi

# 平日朝開始（8時）
if [ $(date +%u) -le 5 ] && [ $(date +%H) -eq 8 ]; then
    echo "平日朝開始を実行します"
    aws ec2 start-instances --instance-ids $INSTANCE_ID
fi