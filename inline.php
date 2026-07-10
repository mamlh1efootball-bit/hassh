<?php

if (defined('GIFT_INLINE_V5_INCLUDED')) {
    return;
}
define('GIFT_INLINE_V5_INCLUDED', true);

/* ===================== CONFIG ===================== */

if (!defined('GIFT_INLINE_BOT_USERNAME')) {
    $gift_inline_bot_username = isset($GLOBALS['usernamebot']) ? (string)$GLOBALS['usernamebot'] : 'KajBot';
    $gift_inline_bot_username = ltrim($gift_inline_bot_username, '@');
    define('GIFT_INLINE_BOT_USERNAME', $gift_inline_bot_username !== '' ? $gift_inline_bot_username : 'KajBot');
}

if (!defined('GIFT_API_ENDPOINT')) {
    $gift_inline_api_base = isset($GLOBALS['api']) ? rtrim((string)$GLOBALS['api'], '/') : '';
    define('GIFT_API_ENDPOINT', $gift_inline_api_base !== '' ? $gift_inline_api_base . '/gifts/v2' : '');
}

if (!defined('GIFT_INLINE_CACHE_FILE')) {
    define('GIFT_INLINE_CACHE_FILE', __DIR__ . '/.gift_inline_cache_final_v5.json');
}
if (!defined('GIFT_INLINE_CACHE_TTL')) {
    define('GIFT_INLINE_CACHE_TTL', 60);
}
if (!defined('GIFT_INLINE_PAGE_SIZE')) {
    define('GIFT_INLINE_PAGE_SIZE', 30); // Telegram max: 50
}
if (!defined('GIFT_INLINE_TIMEOUT')) {
    define('GIFT_INLINE_TIMEOUT', 9);
}
if (!defined('GIFT_INLINE_DEBUG_LOG')) {
    define('GIFT_INLINE_DEBUG_LOG', __DIR__ . '/inline_final_v5.log');
}

/* ===================== ENTRYPOINT ===================== */

gift_inline_v5_boot_log();

$__gift_inline_update = gift_inline_v5_update_array(isset($GLOBALS['update']) ? $GLOBALS['update'] : null);
gift_inline_v5_log('UPDATE_CHECK type=' . gift_inline_v5_type(isset($GLOBALS['update']) ? $GLOBALS['update'] : null) . ' keys=' . gift_inline_v5_keys($__gift_inline_update) . ' inline_present=' . (isset($__gift_inline_update['inline_query']) ? 'yes' : 'no'));

if (isset($__gift_inline_update['inline_query']) && is_array($__gift_inline_update['inline_query'])) {
    gift_inline_v5_log('INLINE_QUERY raw=' . gift_inline_v5_json_short($__gift_inline_update['inline_query'], 3000));
    gift_inline_v5_handle($__gift_inline_update['inline_query']);
    exit;
}

gift_inline_v5_log('NO_INLINE_QUERY_CONTINUE normal bot flow continues. If you typed @bot in a chat and this never logs inline_present=yes, webhook/BotFather inline mode is the problem.');
unset($__gift_inline_update);

/* ===================== HANDLER ===================== */

function gift_inline_v5_update_array($update)
{
    if (is_array($update)) {
        return $update;
    }
    if (is_object($update)) {
        $json = json_encode($update, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : array();
    }
    return array();
}

function gift_inline_v5_handle($inline)
{
    $inline_id = gift_inline_v5_get($inline, 'id', '');
    $query = gift_inline_v5_clean(gift_inline_v5_get($inline, 'query', ''));
    $offset = (int)gift_inline_v5_get($inline, 'offset', 0);
    gift_inline_v5_log('HANDLE_START inline_id=' . gift_inline_v5_cut((string)$inline_id, 40) . ' from=' . gift_inline_v5_json_short(gift_inline_v5_get($inline, 'from', array()), 700) . ' query=' . json_encode($query, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' offset=' . $offset);
    if ($offset < 0) $offset = 0;

    if ($inline_id === '') {
        gift_inline_v5_log('NO_INLINE_ID');
        return;
    }

    try {
        if ($query === '') {
            gift_inline_v5_answer($inline_id, array(gift_inline_v5_help_result()), '', 5);
            return;
        }

        $parsed = gift_inline_v5_parse($query);
        gift_inline_v5_log('PARSED ' . gift_inline_v5_json_short($parsed, 1000));

        if ($parsed['root'] !== 'gifts') {
            gift_inline_v5_answer($inline_id, array(gift_inline_v5_start_result($query)), '', 5);
            return;
        }

        if ($parsed['mode'] === 'help') {
            gift_inline_v5_answer($inline_id, array(gift_inline_v5_help_result()), '', 5);
            return;
        }

        if ($parsed['mode'] === 'collections') {
            $api = gift_inline_v5_api(array('collections' => 1));
            if (empty($api['ok'])) {
                gift_inline_v5_answer($inline_id, array(gift_inline_v5_api_error_result($api)), '', 1);
                return;
            }

            $rows = gift_inline_v5_extract_collections($api, $parsed['search']);
            gift_inline_v5_log('COLLECTIONS_EXTRACTED count=' . count($rows) . ' search=' . json_encode($parsed['search'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $paged = gift_inline_v5_collection_results($rows, $parsed['search'], $offset);
            gift_inline_v5_answer($inline_id, $paged['results'], $paged['next_offset'], 20);
            return;
        }

        if (in_array($parsed['mode'], array('models', 'backdrops', 'symbols'), true)) {
            if ($parsed['collection'] === '') {
                gift_inline_v5_answer($inline_id, array(gift_inline_v5_missing_collection_result($parsed['mode'])), '', 5);
                return;
            }

            // V5: split commands like:
            // gifts models Chill Flame
            // gifts models Chill Flame Los Angeles
            // into collection=Chill Flame and search=Los Angeles.
            $split = gift_inline_v5_split_collection_search($parsed['collection']);
            $collection = $split['collection'];
            $attribute_search = gift_inline_v5_clean($split['search'] !== '' ? $split['search'] : $parsed['search']);
            gift_inline_v5_log('COLLECTION_SPLIT typed=' . json_encode($parsed['collection'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' collection=' . json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' attr_search=' . json_encode($attribute_search, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            if ($collection === '') {
                $suggest = gift_inline_v5_collection_suggestions($parsed['mode'], $parsed['collection'], $offset);
                gift_inline_v5_answer($inline_id, $suggest['results'], $suggest['next_offset'], 10);
                return;
            }

            $params = array('collection' => $collection, $parsed['mode'] => 1);
            $api = gift_inline_v5_api($params);
            if (empty($api['ok'])) {
                gift_inline_v5_answer($inline_id, array(gift_inline_v5_api_error_result($api)), '', 1);
                return;
            }

            $rows = gift_inline_v5_extract_attributes($api, $parsed['mode'], $attribute_search);
            gift_inline_v5_log('ATTRIBUTES_EXTRACTED mode=' . $parsed['mode'] . ' collection=' . json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' search=' . json_encode($attribute_search, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' count=' . count($rows) . ' api_counts=' . gift_inline_v5_api_counts($api));
            $paged = gift_inline_v5_attribute_results($collection, $parsed['mode'], $rows, $attribute_search, $offset);
            gift_inline_v5_answer($inline_id, $paged['results'], $paged['next_offset'], 20);
            return;
        }

        gift_inline_v5_answer($inline_id, array(gift_inline_v5_help_result()), '', 5);
    } catch (Exception $e) {
        gift_inline_v5_log('EXCEPTION ' . $e->getMessage());
        gift_inline_v5_answer($inline_id, array(gift_inline_v5_article(array(
            'id' => 'gift_exception_' . substr(sha1($e->getMessage()), 0, 10),
            'title' => '⚠️ خطا در اینلاین گیفت‌ها',
            'description' => 'inline_debug.log را چک کن.',
            'message_text' => "⚠️ <b>خطا در اینلاین گیفت‌ها</b>\n<code>" . gift_inline_v5_h($e->getMessage()) . "</code>",
        ))), '', 1);
    }
}

function gift_inline_v5_parse($query)
{
    $query = gift_inline_v5_clean($query);
    $parts = preg_split('/\s+/u', $query, 3, PREG_SPLIT_NO_EMPTY);

    $root = gift_inline_v5_lower(isset($parts[0]) ? $parts[0] : '');
    if ($root !== 'gifts' && $root !== 'gift') {
        return array('root' => '', 'mode' => 'help', 'collection' => '', 'search' => '');
    }

    $second = gift_inline_v5_lower(isset($parts[1]) ? $parts[1] : '');
    $rest = trim(isset($parts[2]) ? $parts[2] : '');

    if ($second === '') {
        return array('root' => 'gifts', 'mode' => 'collections', 'collection' => '', 'search' => '');
    }

    $map = array(
        'model' => 'models',
        'models' => 'models',
        'مدل' => 'models',
        'مدلها' => 'models',
        'مدل‌ها' => 'models',
        'backdrop' => 'backdrops',
        'backdrops' => 'backdrops',
        'background' => 'backdrops',
        'backgrounds' => 'backdrops',
        'back' => 'backdrops',
        'بک' => 'backdrops',
        'بکدراپ' => 'backdrops',
        'بک‌دراپ' => 'backdrops',
        'symbol' => 'symbols',
        'symbols' => 'symbols',
        'simbol' => 'symbols',
        'سیمبل' => 'symbols',
        'سمبل' => 'symbols',
        'help' => 'help',
        'راهنما' => 'help',
    );

    if (isset($map[$second])) {
        return array('root' => 'gifts', 'mode' => $map[$second], 'collection' => $rest, 'search' => '');
    }

    // gifts art => search collections containing "art"
    $search = trim(substr($query, strlen(isset($parts[0]) ? $parts[0] : '')));
    return array('root' => 'gifts', 'mode' => 'collections', 'collection' => '', 'search' => $search);
}

/* ===================== RESULTS ===================== */

function gift_inline_v5_help_result()
{
    $text = "✨ <b>راهنمای جستجوی گیفت‌های تلگرام</b>\n\n";
    $text .= "برای کار با گیفت‌ها در حالت اینلاین:\n\n";
    $text .= "• لیست همه کالکشن‌ها:\n<code>gifts</code>\n\n";
    $text .= "• جستجو در اسم کالکشن‌ها:\n<code>gifts art</code>\n\n";
    $text .= "• مدل‌های یک کالکشن:\n<code>gifts models Artisan Brick</code>\n\n";
    $text .= "• بک‌دراپ‌های یک کالکشن:\n<code>gifts backdrops Artisan Brick</code>\n\n";
    $text .= "• سیمبل‌های یک کالکشن:\n<code>gifts symbols Artisan Brick</code>\n\n";
    $text .= "ساده‌ترین حالت این است که فقط <code>gifts</code> را بنویسی و از دکمه‌های زیر پیام استفاده کنی.";

    return gift_inline_v5_article(array(
        'id' => 'gift_help',
        'title' => '🔎 جستجوی گیفت‌های تلگرام',
        'description' => 'راهنما، لیست کالکشن‌ها، مدل‌ها، بک‌دراپ‌ها و سیمبل‌ها',
        'message_text' => $text,
        'reply_markup' => gift_inline_v5_help_keyboard(),
    ));
}

function gift_inline_v5_start_result($query)
{
    return gift_inline_v5_article(array(
        'id' => 'gift_start_' . substr(sha1($query), 0, 12),
        'title' => '🎁 جستجوی گیفت‌های تلگرام',
        'description' => 'بنویس: gifts یا gifts art یا gifts models Artisan Brick',
        'message_text' => "🎁 برای استفاده از جستجوی گیفت‌ها، در حالت اینلاین بنویس:\n\n<code>@" . gift_inline_v5_h(GIFT_INLINE_BOT_USERNAME) . " gifts</code>",
        'reply_markup' => gift_inline_v5_help_keyboard(),
    ));
}

function gift_inline_v5_missing_collection_result($mode)
{
    $label = gift_inline_v5_mode_label($mode);
    return gift_inline_v5_article(array(
        'id' => 'gift_missing_' . $mode,
        'title' => 'اسم کالکشن را هم بنویس',
        'description' => "مثال: gifts {$mode} Artisan Brick",
        'message_text' => "⚠️ برای دیدن {$label} باید اسم کالکشن را هم بنویسی.\n\nمثال:\n<code>gifts {$mode} Artisan Brick</code>",
        'reply_markup' => gift_inline_v5_help_keyboard(),
    ));
}

function gift_inline_v5_api_error_result($api)
{
    $err = gift_inline_v5_deep($api, array('error', 'message'), gift_inline_v5_get($api, 'message', 'Gift API response is not ok.'));
    $type = gift_inline_v5_deep($api, array('error', 'type'), gift_inline_v5_get($api, 'code', 'ApiError'));
    return gift_inline_v5_article(array(
        'id' => 'gift_api_error_' . substr(sha1($type . $err), 0, 12),
        'title' => '⚠️ API گیفت جواب درست نداد',
        'description' => gift_inline_v5_cut((string)$err, 90),
        'message_text' => "⚠️ <b>API گیفت جواب درست نداد</b>\n\n<b>Type:</b> <code>" . gift_inline_v5_h((string)$type) . "</code>\n<b>Error:</b> <code>" . gift_inline_v5_h((string)$err) . "</code>",
        'reply_markup' => gift_inline_v5_help_keyboard(),
    ));
}

function gift_inline_v5_not_found_result($title, $description)
{
    return gift_inline_v5_article(array(
        'id' => 'gift_not_found_' . substr(sha1($title . $description . microtime(true)), 0, 16),
        'title' => '❌ ' . $title,
        'description' => $description,
        'message_text' => "❌ <b>" . gift_inline_v5_h($title) . "</b>\n" . gift_inline_v5_h($description),
        'reply_markup' => gift_inline_v5_help_keyboard(),
    ));
}

function gift_inline_v5_collection_results($collections, $search, $offset)
{
    if (!$collections) {
        return array(
            'results' => array(gift_inline_v5_not_found_result('کالکشنی پیدا نشد', $search !== '' ? 'برای لیست کامل فقط gifts را بنویس.' : 'فعلاً خروجی API خالی است.')),
            'next_offset' => '',
        );
    }

    $page_size = min(50, max(1, (int)GIFT_INLINE_PAGE_SIZE));
    $slice = array_slice($collections, $offset, $page_size);
    $results = array();

    foreach ($slice as $row) {
        $name = gift_inline_v5_str(gift_inline_v5_get($row, 'name', ''));
        if ($name === '') continue;

        $floor = gift_inline_v5_floor(gift_inline_v5_first_value($row, array('floor_price', 'floor', 'floorPrice', 'min_price', 'price')));
        $logo = gift_inline_v5_first_url(array(
            gift_inline_v5_get($row, 'logo_url', null),
            gift_inline_v5_get($row, 'photo_url', null),
            gift_inline_v5_get($row, 'image_url', null),
            gift_inline_v5_get($row, 'preview_url', null),
            gift_inline_v5_deep($row, array('links', 'logo'), null),
        ));

        $message = "🎁 <b>" . gift_inline_v5_h($name) . "</b>\n";
        $message .= "💎 <b>Floor:</b> " . gift_inline_v5_h($floor) . "\n\n";
        $message .= "برای دیدن مدل‌ها، بک‌دراپ‌ها و سیمبل‌ها از دکمه‌های زیر استفاده کن.";

        $results[] = gift_inline_v5_article(array(
            'id' => 'gift_col_' . substr(sha1($name), 0, 20),
            'title' => '🎁 ' . $name,
            'description' => '💎 Floor: ' . $floor,
            'thumbnail_url' => $logo,
            'message_text' => $message,
            'reply_markup' => gift_inline_v5_collection_keyboard($name),
        ));
    }

    if (!$results) {
        $results[] = gift_inline_v5_not_found_result('نتیجه‌ای برای نمایش نیست', 'دوباره با gifts تست کن.');
    }

    $next = ($offset + $page_size < count($collections)) ? (string)($offset + $page_size) : '';
    return array('results' => $results, 'next_offset' => $next);
}

function gift_inline_v5_attribute_results($collection, $mode, $items, $search, $offset)
{
    if (!$items) {
        $label = gift_inline_v5_mode_label($mode);
        return array(
            'results' => array(gift_inline_v5_not_found_result($label . ' پیدا نشد', 'کالکشن: ' . $collection)),
            'next_offset' => '',
        );
    }

    $page_size = min(50, max(1, (int)GIFT_INLINE_PAGE_SIZE));
    $slice = array_slice($items, $offset, $page_size);
    $results = array();

    foreach ($slice as $item) {
        $name = gift_inline_v5_str(gift_inline_v5_get($item, 'name', ''));
        if ($name === '') continue;

        $floor = gift_inline_v5_floor(gift_inline_v5_first_value($item, array('floor_price', 'price', 'floor', 'floorPrice', 'min_price')));
        $rarity = gift_inline_v5_rarity(gift_inline_v5_first_value($item, array('rarity_percent', 'rarity', 'readable_rarity', 'percent')));
        $count = gift_inline_v5_count(gift_inline_v5_first_value($item, array('count', 'total', 'supply')));
        $listed = gift_inline_v5_count(gift_inline_v5_first_value($item, array('listed_count', 'listed', 'listings_count')));

        $image = gift_inline_v5_first_url(array(
            gift_inline_v5_get($item, 'image_url', null),
            gift_inline_v5_deep($item, array('asset_urls', 'png'), null),
            gift_inline_v5_deep($item, array('asset_urls', 'webp'), null),
            gift_inline_v5_get($item, 'preview_url', null),
        ));

        $emoji = gift_inline_v5_mode_emoji($mode);
        $label = gift_inline_v5_single_label($mode);

        $desc = array('💎 ' . $floor);
        if ($rarity !== '-') $desc[] = '📊 Rarity: ' . $rarity;
        if ($listed !== '-') $desc[] = '🛒 Listed: ' . $listed;

        $message = "🎁 <b>" . gift_inline_v5_h($collection) . "</b>\n";
        $message .= $emoji . " <b>" . gift_inline_v5_h($label) . ":</b> " . gift_inline_v5_h($name) . "\n";
        $message .= "💎 <b>Floor:</b> " . gift_inline_v5_h($floor) . "\n";
        if ($rarity !== '-') $message .= "📊 <b>Rarity:</b> " . gift_inline_v5_h($rarity) . "\n";
        if ($count !== '-') $message .= "📦 <b>Total:</b> " . gift_inline_v5_h($count) . "\n";
        if ($listed !== '-') $message .= "🛒 <b>Listed:</b> " . gift_inline_v5_h($listed) . "\n";

        $results[] = gift_inline_v5_article(array(
            'id' => 'gift_attr_' . $mode . '_' . substr(sha1($collection . '|' . $name), 0, 20),
            'title' => $emoji . ' ' . $name,
            'description' => implode('  •  ', $desc),
            'thumbnail_url' => $image,
            'message_text' => $message,
            'reply_markup' => gift_inline_v5_collection_keyboard($collection),
        ));
    }

    if (!$results) {
        $results[] = gift_inline_v5_not_found_result('نتیجه‌ای برای نمایش نیست', 'دوباره تست کن.');
    }

    $next = ($offset + $page_size < count($items)) ? (string)($offset + $page_size) : '';
    return array('results' => $results, 'next_offset' => $next);
}

function gift_inline_v5_collection_suggestions($mode, $typed, $offset)
{
    $api = gift_inline_v5_api(array('collections' => 1));
    if (empty($api['ok'])) {
        return array('results' => array(gift_inline_v5_api_error_result($api)), 'next_offset' => '');
    }

    $matches = gift_inline_v5_extract_collections($api, $typed);
    if (!$matches) {
        return array('results' => array(gift_inline_v5_not_found_result('کالکشن پیدا نشد', 'اسم کالکشن را دقیق‌تر بنویس.')), 'next_offset' => '');
    }

    $page_size = min(50, max(1, (int)GIFT_INLINE_PAGE_SIZE));
    $slice = array_slice($matches, $offset, $page_size);
    $results = array();
    $label = gift_inline_v5_mode_label($mode);

    foreach ($slice as $row) {
        $name = gift_inline_v5_str(gift_inline_v5_get($row, 'name', ''));
        if ($name === '') continue;
        $logo = gift_inline_v5_first_url(array(gift_inline_v5_get($row, 'logo_url', null), gift_inline_v5_get($row, 'photo_url', null), gift_inline_v5_get($row, 'image_url', null)));
        $results[] = gift_inline_v5_article(array(
            'id' => 'gift_suggest_' . $mode . '_' . substr(sha1($name), 0, 18),
            'title' => gift_inline_v5_mode_emoji($mode) . ' ' . $label . ' ' . $name,
            'description' => 'برای باز کردن بنویس: gifts ' . $mode . ' ' . $name,
            'thumbnail_url' => $logo,
            'message_text' => "برای دیدن {$label} این کالکشن، از دکمه زیر استفاده کن:\n\n🎁 <b>" . gift_inline_v5_h($name) . "</b>",
            'reply_markup' => array('inline_keyboard' => array(
                array(array('text' => gift_inline_v5_mode_emoji($mode) . ' دیدن ' . $label, 'switch_inline_query_current_chat' => 'gifts ' . $mode . ' ' . $name)),
                array(array('text' => '🎁 سایر کالکشن‌ها', 'switch_inline_query_current_chat' => 'gifts')),
            )),
        ));
    }

    $next = ($offset + $page_size < count($matches)) ? (string)($offset + $page_size) : '';
    return array('results' => $results, 'next_offset' => $next);
}

function gift_inline_v5_article($o)
{
    $result = array(
        'type' => 'article',
        'id' => (string)gift_inline_v5_get($o, 'id', substr(sha1(microtime(true)), 0, 12)),
        'title' => (string)gift_inline_v5_get($o, 'title', 'Gift'),
        'description' => (string)gift_inline_v5_get($o, 'description', ''),
        'input_message_content' => array(
            'message_text' => (string)gift_inline_v5_get($o, 'message_text', ''),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ),
    );

    $reply_markup = gift_inline_v5_get($o, 'reply_markup', null);
    if (is_array($reply_markup)) {
        $result['reply_markup'] = $reply_markup;
    }

    $thumb = gift_inline_v5_get($o, 'thumbnail_url', '');
    if ($thumb !== '' && gift_inline_v5_is_url($thumb)) {
        // New + old fields, to be compatible with older Telegram clients/libs.
        $result['thumbnail_url'] = $thumb;
        // Do not send deprecated thumb_url here; some Bot API versions reject duplicate/deprecated fields.
        $result['thumbnail_width'] = 96;
        $result['thumbnail_height'] = 96;
    }

    return $result;
}

/* ===================== KEYBOARDS ===================== */

function gift_inline_v5_help_keyboard()
{
    return array('inline_keyboard' => array(
        array(
            array('text' => '🎁 لیست کالکشن‌ها', 'switch_inline_query_current_chat' => 'gifts'),
            array('text' => '🔎 جستجو', 'switch_inline_query_current_chat' => 'gifts art'),
        ),
        array(
            array('text' => '🧩 نمونه مدل‌ها', 'switch_inline_query_current_chat' => 'gifts models Artisan Brick'),
            array('text' => '🎨 نمونه بک‌دراپ‌ها', 'switch_inline_query_current_chat' => 'gifts backdrops Artisan Brick'),
        ),
    ));
}

function gift_inline_v5_collection_keyboard($collection)
{
    return array('inline_keyboard' => array(
        array(
            array('text' => '🧩 مدل‌ها', 'switch_inline_query_current_chat' => 'gifts models ' . $collection),
            array('text' => '🎨 بک‌دراپ‌ها', 'switch_inline_query_current_chat' => 'gifts backdrops ' . $collection),
        ),
        array(
            array('text' => '✨ سیمبل‌ها', 'switch_inline_query_current_chat' => 'gifts symbols ' . $collection),
            array('text' => '🎁 سایر کالکشن‌ها', 'switch_inline_query_current_chat' => 'gifts'),
        ),
    ));
}

/* ===================== API + DATA ===================== */

function gift_inline_v5_api($params)
{
    $endpoint = trim((string)GIFT_API_ENDPOINT);
    if ($endpoint === '' || strpos($endpoint, 'YOUR-DOMAIN') !== false) {
        gift_inline_v5_log('API_MISSING_ENDPOINT endpoint=' . $endpoint . ' global_api=' . gift_inline_v5_cut((string)(isset($GLOBALS['api']) ? $GLOBALS['api'] : ''), 300));
        return array('ok' => false, 'error' => array('type' => 'MissingEndpoint', 'message' => 'GIFT_API_ENDPOINT خالی است. مقدار $api در config.php یا مسیر /gifts/v2 را چک کن.'));
    }

    ksort($params);
    $url = $endpoint . (strpos($endpoint, '?') === false ? '?' : '&') . http_build_query($params);
    $cache_key = sha1($url);
    gift_inline_v5_log('API_REQUEST params=' . gift_inline_v5_json_short($params, 1000) . ' url=' . $url . ' cache_key=' . $cache_key);

    $cached = gift_inline_v5_cache_get($cache_key);
    if (is_array($cached)) {
        gift_inline_v5_log('API_CACHE_HIT key=' . $cache_key . ' keys=' . gift_inline_v5_keys($cached));
        return $cached;
    }

    $meta = array();
    $raw = gift_inline_v5_http_get($url, $meta);
    gift_inline_v5_log('API_RESPONSE http_code=' . gift_inline_v5_get($meta, 'http_code', '-') . ' curl_errno=' . gift_inline_v5_get($meta, 'curl_errno', '-') . ' curl_error=' . gift_inline_v5_get($meta, 'curl_error', '-') . ' raw_len=' . strlen((string)$raw) . ' raw_head=' . gift_inline_v5_cut((string)$raw, 1200));

    $json = json_decode($raw ? $raw : '', true);
    if (!is_array($json)) {
        $json = array('ok' => false, 'error' => array(
            'type' => 'InvalidJson',
            'message' => 'Gift API خروجی JSON درست نداد یا خالی بود. json_error=' . json_last_error_msg(),
            'url' => $url,
            'raw' => gift_inline_v5_cut((string)$raw, 700),
        ));
    }

    gift_inline_v5_log('API_JSON keys=' . gift_inline_v5_keys($json) . ' ok=' . (!empty($json['ok']) ? 'true' : 'false') . ' counts=' . gift_inline_v5_api_counts($json));

    if (!empty($json['ok'])) {
        gift_inline_v5_cache_set($cache_key, $json);
    } else {
        gift_inline_v5_log('API_NOT_OK url=' . $url . ' json=' . gift_inline_v5_json_short($json, 1800));
    }

    return $json;
}

function gift_inline_v5_http_get($url, &$meta = null)
{
    if (!is_array($meta)) $meta = array();
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => (int)GIFT_INLINE_TIMEOUT,
            CURLOPT_TIMEOUT => (int)GIFT_INLINE_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'KajBot-GiftsInline-Final/5.0',
            CURLOPT_HEADER => false,
        ));
        $raw = curl_exec($ch);
        $meta['http_code'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $meta['curl_errno'] = (int)curl_errno($ch);
        $meta['curl_error'] = curl_error($ch);
        $meta['total_time'] = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        if ($raw === false) gift_inline_v5_log('CURL_ERROR url=' . $url . ' errno=' . $meta['curl_errno'] . ' error=' . $meta['curl_error']);
        curl_close($ch);
        return is_string($raw) ? $raw : '';
    }

    $ctx = stream_context_create(array(
        'http' => array('timeout' => (int)GIFT_INLINE_TIMEOUT, 'header' => "User-Agent: KajBot-GiftsInline-Final/5.0\r\n"),
        'ssl' => array('verify_peer' => false, 'verify_peer_name' => false),
    ));
    $raw = @file_get_contents($url, false, $ctx);
    $meta['http_code'] = 0;
    $meta['curl_errno'] = 'no_curl';
    $meta['curl_error'] = '';
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $h, $m)) {
                $meta['http_code'] = (int)$m[1];
                break;
            }
        }
    }
    return is_string($raw) ? $raw : '';
}

function gift_inline_v5_extract_collections($data, $search)
{
    $rows = array();
    if (isset($data['collections']) && is_array($data['collections'])) $rows = $data['collections'];
    elseif (isset($data['items']) && is_array($data['items'])) $rows = $data['items'];
    elseif (isset($data['results']) && is_array($data['results'])) $rows = $data['results'];
    elseif (isset($data['data']) && is_array($data['data'])) $rows = $data['data'];

    $rows = gift_inline_v5_rows_from_any($rows);
    $search_n = gift_inline_v5_norm($search);

    if ($search_n !== '') {
        $out = array();
        foreach ($rows as $row) {
            $name = gift_inline_v5_norm(gift_inline_v5_get($row, 'name', ''));
            if ($name !== '' && strpos($name, $search_n) !== false) $out[] = $row;
        }
        $rows = $out;
    }

    usort($rows, 'gift_inline_v5_sort_by_floor_name');
    return $rows;
}

function gift_inline_v5_extract_attributes($data, $mode, $search)
{
    $rows = array();
    if (isset($data['attributes'][$mode]) && is_array($data['attributes'][$mode])) $rows = $data['attributes'][$mode];
    elseif (isset($data[$mode]) && is_array($data[$mode])) $rows = $data[$mode];
    elseif (isset($data['items']) && is_array($data['items'])) $rows = $data['items'];
    elseif (isset($data['results']) && is_array($data['results'])) $rows = $data['results'];

    $rows = gift_inline_v5_rows_from_any($rows);
    $search_n = gift_inline_v5_norm($search);

    if ($search_n !== '') {
        $out = array();
        foreach ($rows as $row) {
            $name = gift_inline_v5_norm(gift_inline_v5_get($row, 'name', ''));
            if ($name !== '' && strpos($name, $search_n) !== false) $out[] = $row;
        }
        $rows = $out;
    }

    usort($rows, 'gift_inline_v5_sort_by_floor_name');
    return $rows;
}

function gift_inline_v5_rows_from_any($rows)
{
    if (!is_array($rows)) return array();
    $out = array();
    $is_list = array_keys($rows) === range(0, count($rows) - 1);
    if ($is_list) {
        foreach ($rows as $row) {
            if (is_array($row)) $out[] = $row;
        }
        return $out;
    }

    // Accept map shapes too: {"Mimic Chest":{"floor_price":97,"rarity":2}}
    foreach ($rows as $name => $value) {
        if (is_array($value)) {
            if (!isset($value['name'])) $value['name'] = (string)$name;
            $out[] = $value;
        } else {
            $out[] = array('name' => (string)$name, 'floor_price' => $value);
        }
    }
    return $out;
}


function gift_inline_v5_split_collection_search($typed)
{
    $typed = gift_inline_v5_clean($typed);
    if ($typed === '') return array('collection' => '', 'search' => '');

    $api = gift_inline_v5_api(array('collections' => 1));
    if (empty($api['ok'])) {
        $fallback = gift_inline_v5_resolve_collection($typed);
        return array('collection' => $fallback, 'search' => '');
    }

    $rows = gift_inline_v5_extract_collections($api, '');
    $target = gift_inline_v5_norm($typed);
    if ($target === '') return array('collection' => '', 'search' => '');

    // 1) Exact match: "Chill Flame" => collection only.
    foreach ($rows as $row) {
        $name = gift_inline_v5_str(gift_inline_v5_get($row, 'name', ''));
        if ($name !== '' && gift_inline_v5_norm($name) === $target) {
            return array('collection' => $name, 'search' => '');
        }
    }

    // 2) Longest prefix match: "Chill Flame los" => collection=Chill Flame, search=los.
    $best = null;
    foreach ($rows as $row) {
        $name = gift_inline_v5_str(gift_inline_v5_get($row, 'name', ''));
        if ($name === '') continue;
        $name_n = gift_inline_v5_norm($name);
        if ($name_n === '') continue;
        if (strpos($target, $name_n . ' ') === 0) {
            $len = strlen($name_n);
            if ($best === null || $len > $best['len']) {
                $search = trim(substr($target, $len));
                $best = array('collection' => $name, 'search' => $search, 'len' => $len);
            }
        }
    }
    if ($best !== null) {
        return array('collection' => $best['collection'], 'search' => $best['search']);
    }

    // 3) Unique contains match: "artisan" => Artisan Brick, no attribute search.
    $matches = array();
    foreach ($rows as $row) {
        $name = gift_inline_v5_str(gift_inline_v5_get($row, 'name', ''));
        if ($name !== '' && strpos(gift_inline_v5_norm($name), $target) !== false) {
            $matches[] = $name;
        }
    }
    if (count($matches) === 1) {
        return array('collection' => $matches[0], 'search' => '');
    }

    // 4) Unknown/ambiguous: return empty so suggestions are shown instead of sending a bad API request.
    return array('collection' => '', 'search' => '');
}

function gift_inline_v5_resolve_collection($typed)
{
    $typed = trim((string)$typed);
    if ($typed === '') return '';

    $api = gift_inline_v5_api(array('collections' => 1));
    if (empty($api['ok'])) return $typed;

    $rows = gift_inline_v5_extract_collections($api, '');
    $target = gift_inline_v5_norm($typed);

    foreach ($rows as $row) {
        $name = gift_inline_v5_get($row, 'name', '');
        if ($name !== '' && gift_inline_v5_norm($name) === $target) return $name;
    }

    $matches = array();
    foreach ($rows as $row) {
        $name = gift_inline_v5_get($row, 'name', '');
        if ($name !== '' && strpos(gift_inline_v5_norm($name), $target) !== false) $matches[] = $name;
    }

    if (count($matches) === 1) return $matches[0];
    return strlen($typed) >= 3 ? $typed : '';
}

/* ===================== TELEGRAM ===================== */

function gift_inline_v5_answer($inline_id, $results, $next_offset, $cache_time)
{
    if (count($results) > 50) $results = array_slice($results, 0, 50);

    $json_results = json_encode(array_values($results), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json_results === false) {
        gift_inline_v5_log('RESULTS_JSON_ERROR ' . json_last_error_msg());
        $json_results = '[]';
    }

    $first_title = isset($results[0]['title']) ? $results[0]['title'] : '';
    gift_inline_v5_log('ANSWER_PREP inline_id=' . gift_inline_v5_cut((string)$inline_id, 40) . ' results_count=' . count($results) . ' next_offset=' . $next_offset . ' cache_time=' . $cache_time . ' json_bytes=' . strlen($json_results) . ' first_title=' . json_encode($first_title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' first_result=' . gift_inline_v5_json_short(isset($results[0]) ? $results[0] : array(), 1600));

    $payload = array(
        'inline_query_id' => $inline_id,
        'results' => $json_results,
        'cache_time' => (int)$cache_time,
        'is_personal' => true,
        'next_offset' => (string)$next_offset,
    );

    $res = gift_inline_v5_bot('answerInlineQuery', $payload);
    gift_inline_v5_log('ANSWER_RESULT ' . gift_inline_v5_json_short($res, 2000));
}

function gift_inline_v5_bot($method, $data)
{
    $token = '';
    if (defined('API_KEY')) $token = API_KEY;
    elseif (defined('BOT_TOKEN')) $token = BOT_TOKEN;
    elseif (defined('TOKEN')) $token = TOKEN;

    if ($token === '') {
        gift_inline_v5_log('NO_BOT_TOKEN constants API_KEY=' . (defined('API_KEY') ? 'defined' : 'no') . ' BOT_TOKEN=' . (defined('BOT_TOKEN') ? 'defined' : 'no') . ' TOKEN=' . (defined('TOKEN') ? 'defined' : 'no'));
        if (function_exists('bot')) {
            gift_inline_v5_log('TG_FALLBACK_TO_EXISTING_BOT_NO_TOKEN method=' . $method);
            return bot($method, $data);
        }
        return null;
    }

    $url = 'https://api.telegram.org/bot' . $token . '/' . $method;
    gift_inline_v5_log('TG_REQUEST method=' . $method . ' token=' . gift_inline_v5_mask_token($token) . ' data_keys=' . gift_inline_v5_keys($data) . ' results_len=' . strlen(isset($data['results']) ? (string)$data['results'] : ''));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ));
        $res = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = (int)curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);
        gift_inline_v5_log('TG_RESPONSE http_code=' . $http . ' curl_errno=' . $errno . ' curl_error=' . $err . ' raw=' . gift_inline_v5_cut((string)$res, 2000));
        $json = json_decode((string)$res, true);
        return is_array($json) ? $json : $res;
    }

    $ctx = stream_context_create(array('http' => array(
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($data),
        'timeout' => 15,
    )));
    $res = @file_get_contents($url, false, $ctx);
    gift_inline_v5_log('TG_RESPONSE_NOCURL raw=' . gift_inline_v5_cut((string)$res, 2000));
    $json = json_decode((string)$res, true);
    return is_array($json) ? $json : $res;
}

/* ===================== FORMAT + UTILS ===================== */

function gift_inline_v5_mode_label($mode)
{
    if ($mode === 'models') return 'مدل‌ها';
    if ($mode === 'backdrops') return 'بک‌دراپ‌ها';
    if ($mode === 'symbols') return 'سیمبل‌ها';
    return 'آیتم‌ها';
}

function gift_inline_v5_single_label($mode)
{
    if ($mode === 'models') return 'Model';
    if ($mode === 'backdrops') return 'Backdrop';
    if ($mode === 'symbols') return 'Symbol';
    return 'Item';
}

function gift_inline_v5_mode_emoji($mode)
{
    if ($mode === 'models') return '🧩';
    if ($mode === 'backdrops') return '🎨';
    if ($mode === 'symbols') return '✨';
    return '🎁';
}

function gift_inline_v5_floor($v)
{
    $n = gift_inline_v5_num($v);
    if ($n === null) return '-';
    return number_format($n, 4, '.', '') . ' 💎';
}

function gift_inline_v5_rarity($v)
{
    if ($v === null || $v === '') return '-';
    if (is_string($v)) {
        $v = trim($v);
        if ($v === '') return '-';
        return strpos($v, '%') !== false ? $v : $v . '%';
    }
    if (is_numeric($v)) {
        $s = rtrim(rtrim(number_format((float)$v, 4, '.', ''), '0'), '.');
        return $s . '%';
    }
    return '-';
}

function gift_inline_v5_count($v)
{
    if ($v === null || $v === '') return '-';
    if (!is_numeric($v)) return (string)$v;
    return number_format((float)$v, 0, '.', ',');
}

function gift_inline_v5_num($v)
{
    if ($v === null || $v === '') return null;
    if (is_numeric($v)) return (float)$v;
    if (is_string($v)) {
        $clean = preg_replace('/[^0-9.\-]/', '', $v);
        if ($clean !== '' && is_numeric($clean)) return (float)$clean;
    }
    return null;
}

function gift_inline_v5_sort_by_floor_name($a, $b)
{
    $af = gift_inline_v5_num(gift_inline_v5_first_value($a, array('floor_price', 'price', 'floor', 'floorPrice', 'min_price')));
    $bf = gift_inline_v5_num(gift_inline_v5_first_value($b, array('floor_price', 'price', 'floor', 'floorPrice', 'min_price')));
    if ($af === null && $bf !== null) return 1;
    if ($af !== null && $bf === null) return -1;
    if ($af !== null && $bf !== null && $af != $bf) return ($af < $bf) ? -1 : 1;
    return strcmp((string)gift_inline_v5_get($a, 'name', ''), (string)gift_inline_v5_get($b, 'name', ''));
}

function gift_inline_v5_first_value($row, $keys)
{
    foreach ($keys as $k) {
        if (is_array($row) && array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') return $row[$k];
    }
    return null;
}

function gift_inline_v5_get($arr, $key, $default)
{
    return (is_array($arr) && array_key_exists($key, $arr)) ? $arr[$key] : $default;
}

function gift_inline_v5_deep($arr, $path, $default)
{
    $cur = $arr;
    foreach ($path as $p) {
        if (!is_array($cur) || !array_key_exists($p, $cur)) return $default;
        $cur = $cur[$p];
    }
    return $cur;
}

function gift_inline_v5_str($v)
{
    return trim((string)$v);
}

function gift_inline_v5_h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gift_inline_v5_clean($s)
{
    $s = trim((string)$s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim((string)$s);
}

function gift_inline_v5_lower($s)
{
    return function_exists('mb_strtolower') ? mb_strtolower((string)$s, 'UTF-8') : strtolower((string)$s);
}

function gift_inline_v5_norm($s)
{
    $s = gift_inline_v5_lower(trim((string)$s));
    $s = str_replace(array('‌', '_', '-', '.', '#', '’', "'", '`', '‘', '“', '”'), ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim((string)$s);
}

function gift_inline_v5_cut($s, $len)
{
    $s = (string)$s;
    if (function_exists('mb_substr')) return mb_substr($s, 0, $len, 'UTF-8');
    return substr($s, 0, $len);
}

function gift_inline_v5_is_url($url)
{
    return (bool)preg_match('~^https?://~i', (string)$url);
}

function gift_inline_v5_first_url($items)
{
    foreach ($items as $url) {
        $url = trim((string)$url);
        if ($url !== '' && gift_inline_v5_is_url($url)) return $url;
    }
    return '';
}

function gift_inline_v5_json_short($value, $len = 700)
{
    if (is_string($value)) return gift_inline_v5_cut($value, $len);
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return gift_inline_v5_cut($json ? $json : '', $len);
}

function gift_inline_v5_log($line)
{
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL;
    $ok = @file_put_contents(GIFT_INLINE_DEBUG_LOG, $entry, FILE_APPEND);
    if ($ok === false) {
        @file_put_contents(sys_get_temp_dir() . '/arz_inline_final_v5.log', $entry, FILE_APPEND);
    }
}

function gift_inline_v5_boot_log()
{
    static $done = false;
    if ($done) return;
    $done = true;
    $raw = '';
    if (function_exists('file_get_contents')) {
        $r = @file_get_contents('php://input');
        if (is_string($r)) $raw = $r;
    }
    gift_inline_v5_log('================ INLINE FINAL V5 BOOT ================');
    gift_inline_v5_log('ENV php=' . PHP_VERSION . ' sapi=' . PHP_SAPI . ' file=' . __FILE__ . ' cwd=' . getcwd() . ' method=' . (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '-') . ' uri=' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-') . ' remote=' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-') . ' content_length=' . (isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : '-') . ' raw_len=' . strlen($raw) . ' raw_head=' . gift_inline_v5_cut($raw, 1500));
    gift_inline_v5_log('CONFIG bot_username=' . (defined('GIFT_INLINE_BOT_USERNAME') ? GIFT_INLINE_BOT_USERNAME : '-') . ' endpoint=' . (defined('GIFT_API_ENDPOINT') ? GIFT_API_ENDPOINT : '-') . ' global_api=' . gift_inline_v5_cut((string)(isset($GLOBALS['api']) ? $GLOBALS['api'] : ''), 500) . ' function_bot=' . (function_exists('bot') ? 'yes' : 'no') . ' token=' . gift_inline_v5_resolve_token_status() . ' log=' . GIFT_INLINE_DEBUG_LOG);
}

function gift_inline_v5_type($v)
{
    if (is_object($v)) return 'object:' . get_class($v);
    if (is_array($v)) return 'array';
    if ($v === null) return 'null';
    return gettype($v);
}

function gift_inline_v5_keys($v)
{
    if (!is_array($v)) return '-';
    return implode(',', array_slice(array_map('strval', array_keys($v)), 0, 30));
}

function gift_inline_v5_api_counts($json)
{
    $parts = array();
    foreach (array('collections', 'items', 'results', 'models', 'backdrops', 'symbols') as $k) {
        if (isset($json[$k]) && is_array($json[$k])) $parts[] = $k . '=' . count($json[$k]);
    }
    if (isset($json['attributes']) && is_array($json['attributes'])) {
        foreach (array('models', 'backdrops', 'symbols') as $k) {
            if (isset($json['attributes'][$k]) && is_array($json['attributes'][$k])) $parts[] = 'attributes.' . $k . '=' . count($json['attributes'][$k]);
        }
    }
    return $parts ? implode(' ', $parts) : '-';
}

function gift_inline_v5_mask_token($token)
{
    $token = (string)$token;
    if ($token === '') return '-';
    return substr($token, 0, 6) . '...' . substr($token, -5);
}

function gift_inline_v5_resolve_token_status()
{
    if (defined('API_KEY')) return 'API_KEY:' . gift_inline_v5_mask_token(API_KEY);
    if (defined('BOT_TOKEN')) return 'BOT_TOKEN:' . gift_inline_v5_mask_token(BOT_TOKEN);
    if (defined('TOKEN')) return 'TOKEN:' . gift_inline_v5_mask_token(TOKEN);
    return 'missing';
}

/* ===================== CACHE ===================== */

function gift_inline_v5_cache_all()
{
    if (!is_file(GIFT_INLINE_CACHE_FILE)) return array();
    $raw = @file_get_contents(GIFT_INLINE_CACHE_FILE);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($json) ? $json : array();
}

function gift_inline_v5_cache_get($key)
{
    $all = gift_inline_v5_cache_all();
    if (!isset($all[$key]) || !is_array($all[$key])) {
        gift_inline_v5_log('CACHE_MISS key=' . $key);
        return null;
    }
    $ts = isset($all[$key]['ts']) ? (int)$all[$key]['ts'] : 0;
    if ($ts <= 0 || time() - $ts > (int)GIFT_INLINE_CACHE_TTL) {
        gift_inline_v5_log('CACHE_EXPIRED key=' . $key . ' ts=' . $ts);
        return null;
    }
    $payload = isset($all[$key]['payload']) ? $all[$key]['payload'] : null;
    return is_array($payload) ? $payload : null;
}

function gift_inline_v5_cache_set($key, $payload)
{
    $all = gift_inline_v5_cache_all();
    $all[$key] = array('ts' => time(), 'payload' => $payload);

    if (count($all) > 300) {
        uasort($all, function ($a, $b) {
            return ((int)(isset($b['ts']) ? $b['ts'] : 0)) - ((int)(isset($a['ts']) ? $a['ts'] : 0));
        });
        $all = array_slice($all, 0, 300, true);
    }

    $tmp = GIFT_INLINE_CACHE_FILE . '.tmp';
    @file_put_contents($tmp, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    @rename($tmp, GIFT_INLINE_CACHE_FILE);
    gift_inline_v5_log('CACHE_SET key=' . $key . ' file=' . GIFT_INLINE_CACHE_FILE);
}
