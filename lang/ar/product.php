<?php

return [
    'add' => 'إضافة منتج',
    'edit' => 'تعديل المنتج',
    'none' => 'لا توجد منتجات. أضف أول منتج.',
    'advanced' => 'إعدادات متقدمة (اختيارية)',
    'status' => 'الحالة',
    'all_categories' => 'كل الفئات',
    'search_placeholder' => 'ابحث بالاسم أو الباركود...',
    'scan_hint' => 'امسح أو اكتب الرقم',
    'stock_edit_hint' => 'تعديل الكمية يسجَّل في حركة المخزون.',
    'min_stock_hint' => 'ينبّهك النظام عند بلوغ هذه الكمية.',
    'min_price_hint' => 'أقل سعر مسموح بالبيع به.',
    'opening_stock' => 'كمية البداية',
    'stock_edited' => 'تعديل يدوي للكمية',
    'created' => 'تمت إضافة :name.',
    'updated' => 'تم تعديل :name.',
    'deleted' => 'تم حذف :name.',
    'delete_confirm' => 'هل تريد حذف هذا المنتج؟',

    'fields' => [
        'name' => 'اسم المنتج',
        'category_id' => 'الفئة',
        'barcode' => 'الباركود',
        'sku' => 'الرمز الداخلي',
        'unit' => 'الوحدة',
        'cost_price' => 'سعر الشراء',
        'retail_price' => 'سعر التجزئة',
        'wholesale_price' => 'سعر الجملة',
        'min_price' => 'أدنى سعر',
        'stock' => 'الكمية',
        'min_stock' => 'حد التنبيه',
        'image' => 'الصورة',
        'note' => 'ملاحظة',
        'is_active' => 'منتج مفعّل',
    ],

    'stock_status' => [
        'ok' => 'متوفر',
        'low' => 'على وشك النفاد',
        'out' => 'نفد',
    ],

    'units' => [
        'piece' => 'وحدة',
        'kg' => 'كغ',
        'litre' => 'لتر',
        'metre' => 'متر',
        'box' => 'علبة',
        'carton' => 'كرطونة',
        'pack' => 'حزمة',
    ],
];
