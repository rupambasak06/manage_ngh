<?php
/**
 * Bengali UI Language Strings
 * Edit any text here to customize the entire interface without touching code.
 */

declare(strict_types=1);

return [
    // App & Branding
    'app_title'             => 'হোটেল রুম ডেইলি চেকলিস্ট',
    'app_subtitle'          => 'দৈনিক চেকলিস্ট ও ইনভেন্টরি সিস্টেম',
    'loading'               => 'লোড হচ্ছে...',
    'saving'                => 'সেভ হচ্ছে...',
    'saved'                 => 'সংরক্ষিত',
    'save_error'            => 'সেভ করতে সমস্যা হয়েছে! ইন্টারনেট চেক করুন।',
    'sync_live'             => 'লাইভ আপডেট চালু',
    'offline_warning'       => 'সার্ভারের সাথে সংযোগ বিচ্ছিন্ন হয়েছে',

    // Navigation
    'nav_checklist'         => 'চেকলিস্ট',
    'nav_summary'           => 'সারাংশ',
    'nav_reports'           => 'রিপোর্ট',
    'nav_admin'             => 'সেটিংস',
    'nav_logout'            => 'লগআউট',
    'nav_dark_mode'         => 'ডার্ক মোড',
    'nav_light_mode'        => 'লাইট মোড',

    // Roles
    'role_owner'            => 'মালিক (Owner)',
    'role_manager'          => 'ম্যানেজার (Manager)',
    'role_staff'            => 'স্টাফ (Staff)',

    // Auth / Login
    'login_title'           => 'লগইন করুন',
    'login_subtitle'        => 'আপনার ইমেইল ও পাসওয়ার্ড দিয়ে প্রবেশ করুন',
    'email'                 => 'ইমেইল',
    'password'              => 'পাসওয়ার্ড',
    'login_button'          => 'প্রবেশ করুন',
    'login_failed'          => 'ইমেইল বা পাসওয়ার্ড সঠিক নয়।',
    'login_disabled'        => 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে। মালিকের সাথে যোগাযোগ করুন।',
    'login_rate_limited'    => 'অনেকবার ভুল চেষ্টা করা হয়েছে। ১৫ মিনিট পর আবার চেষ্টা করুন।',
    'csrf_invalid'          => 'সিকিউরিটি টোকেন মেয়াদোত্তীর্ণ হয়েছে। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।',
    'unauthorized'          => 'এই পেজটি দেখার অনুমতি আপনার নেই।',
    'logged_out'            => 'সফলভাবে লগআউট হয়েছে।',

    // Date Bar & Checklist Header
    'today'                 => 'আজ',
    'prev_day'              => 'আগের দিন',
    'next_day'              => 'পরের দিন',
    'jump_today'            => 'আজকের তারিখে ফিরুন',
    'viewing_history'       => 'পুরানো তারিখের রেকর্ড দেখছেন',
    'all_floors'            => 'সব ফ্লোর',
    'room_prefix'           => 'রুম',
    'totals_strip_title'    => 'আজকের মোট হিসাব (সব রুম)',
    'totals_toggle'         => 'মোট হিসাব',
    'checked_rooms_count'   => 'চেক হয়েছে',
    'checkout_done_count'   => 'চেকআউট সম্পূর্ণ',

    // Room Card
    'checkout_complete'     => 'চেকআউট সম্পূর্ণ',
    'checkout_pending'      => 'চেকআউট বাকি',
    'extras_section_title'  => 'এক্সট্রা যা যা দেওয়া হয়েছে',
    'notes_section_title'   => 'মন্তব্য',
    'notes_placeholder'     => 'রুমের কোনো সমস্যা বা মন্তব্য লিখুন...',
    'expected_short'        => 'ডিফল্ট',
    'mismatch_warning'      => 'ডিফল্ট সংখ্যার সাথে মিলছে না',
    'last_updated_by'       => 'সর্বশেষ আপডেট',
    'no_rooms_found'        => 'কোনো সক্রিয় রুম পাওয়া যায়নি। সেটিংস থেকে ফ্লোর ও রুম যোগ করুন।',

    // Summary & Reports
    'summary_title'         => 'আজকের সারাংশ',
    'summary_extras_title'  => '১. আজ দেওয়া এক্সট্রা সামগ্রী',
    'summary_missing_title' => '২. কম থাকা / বাকি আইটেম (চেক করা রুম)',
    'summary_unchecked_title' => 'এখনও চেক করা হয়নি',
    'summary_notes_title'   => '৩. দিনের সমস্ত মন্তব্য',
    'grand_total'           => 'সর্বমোট',
    'no_extras_today'       => 'কোনো এক্সট্রা সামগ্রী দেওয়া হয়নি।',
    'no_missing_items'      => 'চেক করা রুমগুলোতে কোনো আইটেম কম নেই।',
    'all_rooms_checked'     => 'সব রুম চেক করা হয়েছে!',
    'no_notes_today'        => 'কোনো মন্তব্য নেই।',
    'less_suffix'           => 'কম',
    'not_ticked'            => 'টিক দেওয়া হয়নি',

    // Reports Page
    'reports_title'         => 'রিপোর্ট ও এক্সপোর্ট',
    'date_from'             => 'শুরুর তারিখ',
    'date_to'               => 'শেষের তারিখ',
    'show_report'           => 'রিপোর্ট দেখুন',
    'export_csv'            => 'CSV ডাউনলোড',
    'print_report'          => 'প্রিন্ট করুন',
    'report_date_range'     => 'তারিখের সীমা',
    'report_total_days'     => 'মোট দিন',
    'report_daily_breakdown'=> 'তারিখ অনুযায়ী বিস্তারিত',
    'print_back'            => 'ফিরে যান',
    'print_now'             => 'প্রিন্ট / PDF সেভ করুন',
    'printed_at'            => 'প্রিন্টের সময়',

    // Admin Navigation
    'admin_title'           => 'অ্যাডমিন সেটিংস',
    'admin_floors_rooms'    => 'ফ্লোর ও রুম',
    'admin_sections'        => 'সেকশন',
    'admin_options'         => 'অপশন / আইটেম',
    'admin_extras'          => 'এক্সট্রা তালিকা',
    'admin_defaults'        => 'রুম ডিফল্ট',
    'admin_users'           => 'ব্যবহারকারী',
    'admin_audit'           => 'অডিট লগ',

    // Common CRUD
    'add_new'               => 'নতুন যোগ করুন',
    'edit'                  => 'এডিট',
    'save'                  => 'সংরক্ষণ করুন',
    'cancel'                => 'বাতিল',
    'delete'                => 'মুছুন',
    'hide'                  => 'লুকান',
    'show'                  => 'দেখান',
    'active'                => 'সক্রিয়',
    'inactive'              => 'নিষ্ক্রিয়',
    'status'                => 'অবস্থা',
    'actions'               => 'অ্যাকশন',
    'order'                 => 'ক্রম',
    'move_up'               => 'উপরে নিন',
    'move_down'             => 'নিচে নিন',
    'confirm_delete'        => 'আপনি কি নিশ্চিতভাবে এটি মুছতে চান?',
    'saved_success'         => 'সফলভাবে সংরক্ষিত হয়েছে।',
    'deleted_success'       => 'সফলভাবে মুছে ফেলা হয়েছে।',
    'error_generic'         => 'একটি ত্রুটি ঘটেছে। অনুগ্রহ করে আবার চেষ্টা করুন।',
    'error_required_fields' => 'অনুগ্রহ করে সমস্ত প্রয়োজনীয় ঘর পূরণ করুন।',

    // Admin: Floors & Rooms
    'add_floor'             => 'নতুন ফ্লোর যোগ করুন',
    'floor_name'            => 'ফ্লোরের নাম (যেমন: ৭ম তলা)',
    'add_room'              => 'নতুন রুম যোগ করুন',
    'room_number'           => 'রুম নম্বর (যেমন: 400)',
    'select_floor'          => 'ফ্লোর নির্বাচন করুন',
    'room_exists_error'     => 'এই রুম নম্বরটি ইতিমধ্যেই আছে।',
    'floor_has_rooms_error' => 'এই ফ্লোরে রুম রয়েছে। আগে রুমগুলো সরান বা মুছুন।',

    // Admin: Sections
    'add_section'           => 'নতুন সেকশন যোগ করুন',
    'section_title'         => 'সেকশনের নাম',
    'counts_checkout_label' => 'চেকআউট সম্পূর্ণ হওয়ার জন্য গণ্য হবে',
    'yes'                   => 'হ্যাঁ',
    'no'                    => 'না',

    // Admin: Options
    'add_option'            => 'নতুন অপশন যোগ করুন',
    'option_label'          => 'অপশনের নাম',
    'option_section'        => 'সেকশন',
    'option_type'           => 'ধরন',
    'type_checkbox'         => 'চেকবক্স (হ্যাঁ/না)',
    'type_counter'          => 'কাউন্টার (সংখ্যা)',
    'max_count'             => 'সর্বোচ্চ সংখ্যা',
    'applies_to'            => 'কোন রুমে প্রযোজ্য',
    'applies_all'           => 'সব রুম',
    'applies_selected'      => 'নির্বাচিত রুম',
    'select_rooms'          => 'রুম নির্বাচন করুন',

    // Admin: Extras
    'add_extra'             => 'নতুন এক্সট্রা আইটেম যোগ করুন',
    'extra_label'           => 'এক্সট্রা আইটেমের নাম',

    // Admin: Room Defaults
    'defaults_title'        => 'রুম ডিফল্ট সেটিংস',
    'defaults_help'         => 'ডিফল্ট পরিবর্তন আজকের তারিখ থেকে কার্যকর হবে। পুরানো তারিখের হিসাব কখনো পরিবর্তন হবে না।',
    'select_room'           => 'রুম নির্বাচন করুন',
    'default_qty'           => 'ডিফল্ট সংখ্যা',
    'include_in_default'    => 'এই রুমে দেখান',
    'copy_to_all_rooms'     => 'এই রুমের ডিফল্ট সব রুমে কপি করুন',
    'reset_all_defaults'    => 'সব ডিফল্ট রিসেট করুন',
    'confirm_copy_defaults' => 'এই রুমের ডিফল্ট সেটিংস কি আজ থেকে সব সক্রিয় রুমে কপি করবেন? (প্রতিটি রুমের প্রযোজ্য অপশন অনুযায়ী)',
    'confirm_reset_defaults'=> 'সব রুমের ডিফল্ট রিসেট করবেন? রিসেট করলে আজ থেকে প্রতিটি রুমে সব প্রযোজ্য অপশন দেখাবে।',
    'effective_from_today'  => 'কার্যকর তারিখ: আজ থেকে',

    // Admin: Users
    'add_user'              => 'নতুন ব্যবহারকারী তৈরি করুন',
    'user_name'             => 'নাম',
    'user_role'             => 'ভূমিকা (Role)',
    'new_password'          => 'নতুন পাসওয়ার্ড',
    'reset_password'        => 'পাসওয়ার্ড রিসেট',
    'password_min_error'    => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
    'email_exists_error'    => 'এই ইমেইলটি ইতিমধ্যেই ব্যবহৃত হচ্ছে।',
    'cannot_disable_self'   => 'আপনি নিজের অ্যাকাউন্ট নিষ্ক্রিয় করতে পারবেন না।',
    'last_login'            => 'সর্বশেষ লগইন',
    'never'                 => 'কখনো নয়',

    // Admin: Audit Log
    'audit_title'           => 'অডিট লগ (পরিবর্তনের ইতিহাস)',
    'audit_time'            => 'সময়',
    'audit_user'            => 'ব্যবহারকারী',
    'audit_room'            => 'রুম',
    'audit_item'            => 'আইটেম / অ্যাকশন',
    'audit_change'          => 'পরিবর্তন (আগে → পরে)',
    'audit_empty'           => 'এখনও কোনো পরিবর্তনের রেকর্ড নেই।',
    'filter'                => 'ফিল্টার',
    'clear_filter'          => 'ফিল্টার মুছুন',

    // Bengali Months & Weekdays
    'months' => [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
    ],
    'weekdays' => [
        0 => 'রবিবার', 1 => 'সোমবার', 2 => 'মঙ্গলবার', 3 => 'বুধবার',
        4 => 'বৃহস্পতিবার', 5 => 'শুক্রবার', 6 => 'শনিবার',
    ],
];
