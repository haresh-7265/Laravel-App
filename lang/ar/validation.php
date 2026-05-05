<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines — Arabic
    |--------------------------------------------------------------------------
    |
    | Arabic translations for the most common validation rules.
    | Rules not listed here will fall back to the English (fallback_locale).
    |
    */

    'required'   => 'حقل :attribute مطلوب.',
    'numeric'    => 'حقل :attribute يجب أن يكون رقمًا.',
    'max'        => [
        'array'   => 'حقل :attribute يجب ألا يحتوي على أكثر من :max عناصر.',
        'file'    => 'حقل :attribute يجب ألا يكون أكبر من :max كيلوبايت.',
        'numeric' => 'حقل :attribute يجب ألا يكون أكبر من :max.',
        'string'  => 'حقل :attribute يجب ألا يكون أكثر من :max حروف.',
    ],
    'unique'     => ':attribute مُستخدم بالفعل.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [],

];
