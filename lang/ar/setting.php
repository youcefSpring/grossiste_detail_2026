<?php

return [
    'saved' => 'تم حفظ الإعدادات.',
    'shop' => 'معلومات المحل',
    'selling' => 'إعدادات البيع',
    'advanced' => 'خيارات متقدمة',
    'advanced_hint' => 'لا تفعّلها إلا إذا كنت تحتاجها فعلا؛ تفعيلها يزيد الحقول في الشاشات.',
    'max_discount_hint' => 'أقصى تخفيض يمنحه البائع دون إذن.',
    'prefix_hint' => 'يظهر في رقم الفاتورة، مثل INV-2026-00001.',
    'payment_methods_hint' => 'ما لا تختاره لن يظهر للبائع.',

    'fields' => [
        'shop_name' => 'اسم المحل',
        'shop_phone' => 'الهاتف',
        'shop_address' => 'العنوان',
        'logo' => 'الشعار (يظهر في الفاتورة)',
        'default_type' => 'نوع البيع الافتراضي',
        'max_discount' => 'حد التخفيض للبائع',
        'payment_methods' => 'طرق الدفع المتاحة',
        'currency_symbol' => 'رمز العملة',
        'invoice_prefix' => 'بادئة رقم الفاتورة',
        'default_locale' => 'اللغة الافتراضية',
    ],

    'toggles' => [
        'tax_enabled' => 'تفعيل الرسم على القيمة المضافة',
        'variants_enabled' => 'تفعيل متغيرات المنتج (مقاس، لون...)',
        'multi_warehouse_enabled' => 'تفعيل تعدد المخازن',
        'allow_negative_stock' => 'السماح بالبيع دون توفر مخزون',
    ],

    'toggle_hints' => [
        'tax_enabled' => 'يضيف حقل الرسم للمنتجات والفواتير.',
        'variants_enabled' => 'يسمح بمنتج واحد بعدة مقاسات أو ألوان.',
        'multi_warehouse_enabled' => 'يظهر اختيار المخزن في كل عملية.',
        'allow_negative_stock' => 'خطير: يسمح بالبيع حتى لو كان المخزون صفرا.',
    ],
];
