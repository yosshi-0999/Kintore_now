<?php

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーションの言語ファイル
    |--------------------------------------------------------------------------
    |
    | キーは Laravel 本体の lang/en/validation.php と同じ。
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeには有効なURLを指定してください。',
    'after' => ':attributeには:dateより後の日時を指定してください。',
    'after_or_equal' => ':attributeには:date以降の日時を指定してください。',
    'alpha' => ':attributeには英字のみ使用できます。',
    'alpha_dash' => ':attributeには英数字・ハイフン・アンダースコアのみ使用できます。',
    'alpha_num' => ':attributeには英数字のみ使用できます。',
    'any_of' => ':attributeが正しくありません。',
    'array' => ':attributeは配列で指定してください。',
    'ascii' => ':attributeには半角の英数字と記号のみ使用できます。',
    'before' => ':attributeには:dateより前の日時を指定してください。',
    'before_or_equal' => ':attributeには:date以前の日時を指定してください。',
    'between' => [
        'array' => ':attributeは:min〜:max個で指定してください。',
        'file' => ':attributeは:min〜:maxKBのファイルを指定してください。',
        'numeric' => ':attributeは:min〜:maxの範囲で指定してください。',
        'string' => ':attributeは:min〜:max文字で入力してください。',
    ],
    'boolean' => ':attributeにはtrueかfalseを指定してください。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attributeが確認用と一致しません。',
    'contains' => ':attributeに必要な値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeには正しい日時を指定してください。',
    'date_equals' => ':attributeには:dateと同じ日時を指定してください。',
    'date_format' => ':attributeは:formatの形式で指定してください。',
    'decimal' => ':attributeは小数点以下:decimal桁で指定してください。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherには異なる値を指定してください。',
    'digits' => ':attributeは:digits桁で指定してください。',
    'digits_between' => ':attributeは:min〜:max桁で指定してください。',
    'dimensions' => ':attributeの画像サイズが正しくありません。',
    'distinct' => ':attributeの値が重複しています。',
    'doesnt_contain' => ':attributeに次の値を含めることはできません：:values',
    'doesnt_end_with' => ':attributeの末尾に次の値は使用できません：:values',
    'doesnt_start_with' => ':attributeの先頭に次の値は使用できません：:values',
    'email' => ':attributeには正しいメールアドレスを指定してください。',
    'encoding' => ':attributeは:encodingでエンコードしてください。',
    'ends_with' => ':attributeは次のいずれかで終わる必要があります：:values',
    'enum' => '選択された:attributeは正しくありません。',
    'exists' => '選択された:attributeは正しくありません。',
    'extensions' => ':attributeには次の拡張子のファイルを指定してください：:values',
    'file' => ':attributeにはファイルを指定してください。',
    'filled' => ':attributeを入力してください。',
    'gt' => [
        'array' => ':attributeは:value個より多く指定してください。',
        'file' => ':attributeは:valueKBより大きいファイルを指定してください。',
        'numeric' => ':attributeには:valueより大きい値を指定してください。',
        'string' => ':attributeは:value文字より多く入力してください。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上指定してください。',
        'file' => ':attributeは:valueKB以上のファイルを指定してください。',
        'numeric' => ':attributeには:value以上の値を指定してください。',
        'string' => ':attributeは:value文字以上で入力してください。',
    ],
    'hex_color' => ':attributeには正しい16進数のカラーコードを指定してください。',
    'image' => ':attributeには画像を指定してください。',
    'in' => '選択された:attributeは正しくありません。',
    'in_array' => ':attributeは:otherに含まれている必要があります。',
    'in_array_keys' => ':attributeには次のキーのいずれかを含めてください：:values',
    'integer' => ':attributeには整数を指定してください。',
    'ip' => ':attributeには正しいIPアドレスを指定してください。',
    'ipv4' => ':attributeには正しいIPv4アドレスを指定してください。',
    'ipv6' => ':attributeには正しいIPv6アドレスを指定してください。',
    'json' => ':attributeには正しいJSON文字列を指定してください。',
    'list' => ':attributeはリスト形式で指定してください。',
    'lowercase' => ':attributeは小文字で入力してください。',
    'lt' => [
        'array' => ':attributeは:value個より少なく指定してください。',
        'file' => ':attributeは:valueKBより小さいファイルを指定してください。',
        'numeric' => ':attributeには:valueより小さい値を指定してください。',
        'string' => ':attributeは:value文字より少なく入力してください。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下で指定してください。',
        'file' => ':attributeは:valueKB以下のファイルを指定してください。',
        'numeric' => ':attributeには:value以下の値を指定してください。',
        'string' => ':attributeは:value文字以下で入力してください。',
    ],
    'mac_address' => ':attributeには正しいMACアドレスを指定してください。',
    'max' => [
        'array' => ':attributeは:max個以下で指定してください。',
        'file' => ':attributeは:maxKB以下のファイルを指定してください。',
        'numeric' => ':attributeには:max以下の値を指定してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'max_digits' => ':attributeは:max桁以下で指定してください。',
    'mimes' => ':attributeには次の形式のファイルを指定してください：:values',
    'mimetypes' => ':attributeには次の形式のファイルを指定してください：:values',
    'min' => [
        'array' => ':attributeは:min個以上指定してください。',
        'file' => ':attributeは:minKB以上のファイルを指定してください。',
        'numeric' => ':attributeには:min以上の値を指定してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'min_digits' => ':attributeは:min桁以上で指定してください。',
    'missing' => ':attributeは含めないでください。',
    'missing_if' => ':otherが:valueの場合、:attributeは含めないでください。',
    'missing_unless' => ':otherが:valueでない場合、:attributeは含めないでください。',
    'missing_with' => ':valuesがある場合、:attributeは含めないでください。',
    'missing_with_all' => ':valuesがすべてある場合、:attributeは含めないでください。',
    'multiple_of' => ':attributeには:valueの倍数を指定してください。',
    'not_in' => '選択された:attributeは正しくありません。',
    'not_regex' => ':attributeの形式が正しくありません。',
    'numeric' => ':attributeには数値を指定してください。',
    'password' => [
        'letters' => ':attributeには英字を1文字以上含めてください。',
        'mixed' => ':attributeには大文字と小文字をそれぞれ1文字以上含めてください。',
        'numbers' => ':attributeには数字を1文字以上含めてください。',
        'symbols' => ':attributeには記号を1文字以上含めてください。',
        'uncompromised' => 'この:attributeは過去に漏えいしたことがあります。別の:attributeを指定してください。',
    ],
    'present' => ':attributeが存在している必要があります。',
    'present_if' => ':otherが:valueの場合、:attributeが存在している必要があります。',
    'present_unless' => ':otherが:valueでない場合、:attributeが存在している必要があります。',
    'present_with' => ':valuesがある場合、:attributeが存在している必要があります。',
    'present_with_all' => ':valuesがすべてある場合、:attributeが存在している必要があります。',
    'prohibited' => ':attributeは入力できません。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは入力できません。',
    'prohibited_if_accepted' => ':otherを承認した場合、:attributeは入力できません。',
    'prohibited_if_declined' => ':otherを拒否した場合、:attributeは入力できません。',
    'prohibited_unless' => ':otherが:valuesに含まれない場合、:attributeは入力できません。',
    'prohibits' => ':attributeを入力した場合、:otherは入力できません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeを入力してください。',
    'required_array_keys' => ':attributeには次の項目を含めてください：:values',
    'required_if' => ':otherが:valueの場合、:attributeを入力してください。',
    'required_if_accepted' => ':otherを承認した場合、:attributeを入力してください。',
    'required_if_declined' => ':otherを拒否した場合、:attributeを入力してください。',
    'required_unless' => ':otherが:valuesに含まれない場合、:attributeを入力してください。',
    'required_with' => ':valuesがある場合、:attributeを入力してください。',
    'required_with_all' => ':valuesがすべてある場合、:attributeを入力してください。',
    'required_without' => ':valuesがない場合、:attributeを入力してください。',
    'required_without_all' => ':valuesがすべてない場合、:attributeを入力してください。',
    'same' => ':attributeと:otherが一致しません。',
    'size' => [
        'array' => ':attributeは:size個で指定してください。',
        'file' => ':attributeは:sizeKBのファイルを指定してください。',
        'numeric' => ':attributeには:sizeを指定してください。',
        'string' => ':attributeは:size文字で入力してください。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まる必要があります：:values',
    'string' => ':attributeには文字列を指定してください。',
    'timezone' => ':attributeには正しいタイムゾーンを指定してください。',
    'unique' => 'この:attributeはすでに使われています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字で入力してください。',
    'url' => ':attributeには正しいURLを指定してください。',
    'ulid' => ':attributeには正しいULIDを指定してください。',
    'uuid' => ':attributeには正しいUUIDを指定してください。',

    /*
    |--------------------------------------------------------------------------
    | 項目ごとのメッセージ
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 項目名
    |--------------------------------------------------------------------------
    |
    | スターターキットの認証・設定画面で使う項目名。
    | 筋トレ機能の画面は、各コンポーネントの validationAttributes() で指定している。
    |
    */

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認用）',
        'current_password' => '現在のパスワード',
    ],

];
