#!/bin/bash

# ライフライン設備の各ビューファイルにモーダル修正を追加するスクリプト

# 各設備のモーダルID
declare -A MODALS
MODALS[gas]="gas-documents-modal"
MODALS[water]="water-documents-modal"
MODALS[elevator]="elevator-documents-modal"

# 各設備のファイルパス
declare -A FILES
FILES[gas]="resources/views/facilities/lifeline-equipment/gas.blade.php"
FILES[water]="resources/views/facilities/lifeline-equipment/water.blade.php"
FILES[elevator]="resources/views/facilities/lifeline-equipment/elevator.blade.php"

# 各設備の表示名
declare -A NAMES
NAMES[gas]="ガス設備"
NAMES[water]="水道設備"
NAMES[elevator]="エレベーター設備"

echo "ライフライン設備のモーダル修正を追加します..."

for equipment in gas water elevator; do
    modal_id="${MODALS[$equipment]}"
    file_path="${FILES[$equipment]}"
    display_name="${NAMES[$equipment]}"
    
    echo "処理中: $display_name ($modal_id)"
    
    # モーダルが存在するか確認
    if grep -q "$modal_id" "$file_path"; then
        echo "  ✓ モーダルが見つかりました"
        
        # 既に修正が適用されているか確認
        if grep -q "Hoisting modal to body" "$file_path"; then
            echo "  ⚠ 既に修正が適用されています。スキップします。"
        else
            echo "  → 修正を適用します..."
            # ここで実際の修正を適用（手動で行う必要があります）
            echo "  ℹ 手動で修正を適用してください"
        fi
    else
        echo "  ✗ モーダルが見つかりません"
    fi
    
    echo ""
done

echo "完了"
