<?php //子テーマ用関数
if (!defined('ABSPATH')) exit;

//子テーマ用のビジュアルエディタースタイルを適用
add_editor_style();

//以下に子テーマ用の関数を書く
// 会員登録画面からユーザー名を取り除く
add_filter( 'wpmem_register_form_rows', function( $rows ) {
    unset( $rows['username'] );
    return $rows;
});
// メールアドレスからユーザー名を作成する
add_filter( 'wpmem_pre_validate_form', function( $fields ) {
    $fields['username'] = $fields['user_email'];
    return $fields;
});

// WP-Members関連のエラーを抑制する関数
function suppress_wpmembers_errors() {
    // エラーハンドラー関数を定義
    function custom_error_handler($errno, $errstr, $errfile) {
        // WP-Membersプラグインのエラーを抑制
        if (strpos($errfile, 'wp-members') !== false || 
            strpos($errfile, 'email-as-username-for-wp-members') !== false) {
            // 特定のエラーメッセージのみを抑制
            if (strpos($errstr, 'Undefined array key') !== false) {
                return true; // エラーを抑制
            }
        }
        // その他のエラーは通常通り処理
        return false;
    }
    
    // エラーハンドラーを設定（警告と通知のみ）
    set_error_handler('custom_error_handler', E_WARNING | E_NOTICE);
}

// フロントエンド表示時のみ実行
if (!is_admin() && !defined('DOING_AJAX')) {
    add_action('init', 'suppress_wpmembers_errors', 1);
}


// タクソノミーの子ターム取得用Ajaxハンドラー
function get_taxonomy_children_ajax() {
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
    
    if (!$parent_id || !$taxonomy) {
        wp_send_json_error('パラメータが不正です');
    }
    
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'parent' => $parent_id,
    ));
    
    if (is_wp_error($terms) || empty($terms)) {
        wp_send_json_error('子タームが見つかりませんでした');
    }
    
    $result = array();
    foreach ($terms as $term) {
        $result[] = array(
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        );
    }
    
    wp_send_json_success($result);
}
add_action('wp_ajax_get_taxonomy_children', 'get_taxonomy_children_ajax');
add_action('wp_ajax_nopriv_get_taxonomy_children', 'get_taxonomy_children_ajax');

// タームリンク取得用Ajaxハンドラー
function get_term_link_ajax() {
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
    
    if (!$term_id || !$taxonomy) {
        wp_send_json_error('パラメータが不正です');
    }
    
    $term = get_term($term_id, $taxonomy);
    
    if (is_wp_error($term) || empty($term)) {
        wp_send_json_error('タームが見つかりませんでした');
    }
    
    $link = get_term_link($term);
    
    if (is_wp_error($link)) {
        wp_send_json_error('リンクの取得に失敗しました');
    }
    
    wp_send_json_success($link);
}
add_action('wp_ajax_get_term_link', 'get_term_link_ajax');
add_action('wp_ajax_nopriv_get_term_link', 'get_term_link_ajax');

// スラッグからタームリンク取得用Ajaxハンドラー
function get_term_link_by_slug_ajax() {
    $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
    
    if (!$slug || !$taxonomy) {
        wp_send_json_error('パラメータが不正です');
    }
    
    $term = get_term_by('slug', $slug, $taxonomy);
    
    if (!$term || is_wp_error($term)) {
        wp_send_json_error('タームが見つかりませんでした');
    }
    
    $link = get_term_link($term);
    
    if (is_wp_error($link)) {
        wp_send_json_error('リンクの取得に失敗しました');
    }
    
    wp_send_json_success($link);
}
add_action('wp_ajax_get_term_link_by_slug', 'get_term_link_by_slug_ajax');
add_action('wp_ajax_nopriv_get_term_link_by_slug', 'get_term_link_by_slug_ajax');


/* ------------------------------------------------------------------------------ 
	親カテゴリー・親タームを選択できないようにする
------------------------------------------------------------------------------ */
require_once(ABSPATH . '/wp-admin/includes/template.php');
class Nocheck_Category_Checklist extends Walker_Category_Checklist {

  function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
    extract($args);
    if ( empty( $taxonomy ) )
      $taxonomy = 'category';

    if ( $taxonomy == 'category' )
      $name = 'post_category';
    else
      $name = 'tax_input['.$taxonomy.']';

    $class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
    $cat_child = get_term_children( $category->term_id, $taxonomy );

    if( !empty( $cat_child ) ) {
      $output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->slug . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->slug, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), true, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
    } else {
      $output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->slug . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->slug, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
    }
  }

}

/**
 * 求人検索のパスURLを処理するための関数
 */

/**
 * カスタムリライトルールを追加
 */
function job_search_rewrite_rules() {
    // 特徴のみのクエリパラメータ対応
    add_rewrite_rule(
        'jobs/features/?$',
        'index.php?post_type=job&job_features_only=1',
        'top'
    );
    
    // /jobs/location/tokyo/ のようなURLルール
    add_rewrite_rule(
        'jobs/location/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]',
        'top'
    );
    
    // /jobs/position/nurse/ のようなURLルール
    add_rewrite_rule(
        'jobs/position/([^/]+)/?$',
        'index.php?post_type=job&job_position=$matches[1]',
        'top'
    );
    
    // /jobs/type/full-time/ のようなURLルール
    add_rewrite_rule(
        'jobs/type/([^/]+)/?$',
        'index.php?post_type=job&job_type=$matches[1]',
        'top'
    );
    
    // /jobs/facility/hospital/ のようなURLルール
    add_rewrite_rule(
        'jobs/facility/([^/]+)/?$',
        'index.php?post_type=job&facility_type=$matches[1]',
        'top'
    );
    
    // /jobs/feature/high-salary/ のようなURLルール
    add_rewrite_rule(
        'jobs/feature/([^/]+)/?$',
        'index.php?post_type=job&job_feature=$matches[1]',
        'top'
    );
    
    // 複合条件のURLルール
    
    // エリア + 職種
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]',
        'top'
    );
    
    // エリア + 雇用形態
    add_rewrite_rule(
        'jobs/location/([^/]+)/type/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_type=$matches[2]',
        'top'
    );
    
    // エリア + 施設形態
    add_rewrite_rule(
        'jobs/location/([^/]+)/facility/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&facility_type=$matches[2]',
        'top'
    );
    
    // エリア + 特徴
    add_rewrite_rule(
        'jobs/location/([^/]+)/feature/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_feature=$matches[2]',
        'top'
    );
    
    // 職種 + 雇用形態
    add_rewrite_rule(
        'jobs/position/([^/]+)/type/([^/]+)/?$',
        'index.php?post_type=job&job_position=$matches[1]&job_type=$matches[2]',
        'top'
    );
    
    // 職種 + 施設形態
    add_rewrite_rule(
        'jobs/position/([^/]+)/facility/([^/]+)/?$',
        'index.php?post_type=job&job_position=$matches[1]&facility_type=$matches[2]',
        'top'
    );
    
    // 職種 + 特徴
    add_rewrite_rule(
        'jobs/position/([^/]+)/feature/([^/]+)/?$',
        'index.php?post_type=job&job_position=$matches[1]&job_feature=$matches[2]',
        'top'
    );
    
    // 三つの条件の組み合わせ
    
    // エリア + 職種 + 雇用形態
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/type/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]&job_type=$matches[3]',
        'top'
    );
    
    // エリア + 職種 + 施設形態
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/facility/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]&facility_type=$matches[3]',
        'top'
    );
    
    // エリア + 職種 + 特徴
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/feature/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]&job_feature=$matches[3]',
        'top'
    );
    
    // 追加: 四つの条件の組み合わせ
    
    // エリア + 職種 + 雇用形態 + 施設形態
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/type/([^/]+)/facility/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]&job_type=$matches[3]&facility_type=$matches[4]',
        'top'
    );
    
    // エリア + 職種 + 雇用形態 + 特徴
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/type/([^/]+)/feature/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]&job_type=$matches[3]&job_feature=$matches[4]',
        'top'
    );
    
    // エリア + 職種 + 施設形態 + 特徴
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/facility/([^/]+)/feature/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]&facility_type=$matches[3]&job_feature=$matches[4]',
        'top'
    );
    
    // エリア + 雇用形態 + 施設形態 + 特徴
    add_rewrite_rule(
        'jobs/location/([^/]+)/type/([^/]+)/facility/([^/]+)/feature/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_type=$matches[2]&facility_type=$matches[3]&job_feature=$matches[4]',
        'top'
    );
    
    // 職種 + 雇用形態 + 施設形態 + 特徴
    add_rewrite_rule(
        'jobs/position/([^/]+)/type/([^/]+)/facility/([^/]+)/feature/([^/]+)/?$',
        'index.php?post_type=job&job_position=$matches[1]&job_type=$matches[2]&facility_type=$matches[3]&job_feature=$matches[4]',
        'top'
    );
    
    // 追加: 五つの条件の組み合わせ（全条件）
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/type/([^/]+)/facility/([^/]+)/feature/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]&job_type=$matches[3]&facility_type=$matches[4]&job_feature=$matches[5]',
        'top'
    );
    
    // ページネーション対応（例：エリア + 職種の場合）
    add_rewrite_rule(
        'jobs/location/([^/]+)/position/([^/]+)/page/([0-9]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]&job_position=$matches[2]&paged=$matches[3]',
        'top'
    );
    
    // 他のページネーションパターンも必要に応じて追加
}
add_action('init', 'job_search_rewrite_rules');

/**
 * クエリ変数を追加
 */
function job_search_query_vars($vars) {
    $vars[] = 'job_location';
    $vars[] = 'job_position';
    $vars[] = 'job_type';
    $vars[] = 'facility_type';
    $vars[] = 'job_feature';
    $vars[] = 'job_features_only'; // 追加: 特徴のみの検索フラグ
    return $vars;
}
add_filter('query_vars', 'job_search_query_vars');

/**
 * URLパスとクエリパラメータを解析してフィルター条件を取得する関数
 */
function get_job_filters_from_url() {
    $filters = array();
    
    // 特徴のみのフラグをチェック
    $features_only = get_query_var('job_features_only');
    if (!empty($features_only)) {
        $filters['features_only'] = true;
    }
    
    // パス型URLからの条件取得
    $location = get_query_var('job_location');
    if (!empty($location)) {
        $filters['location'] = $location;
    }
    
    $position = get_query_var('job_position');
    if (!empty($position)) {
        $filters['position'] = $position;
    }
    
    $job_type = get_query_var('job_type');
    if (!empty($job_type)) {
        $filters['type'] = $job_type;
    }
    
    $facility_type = get_query_var('facility_type');
    if (!empty($facility_type)) {
        $filters['facility'] = $facility_type;
    }
    
    // 単一の特徴（パス型URL用）
    $job_feature = get_query_var('job_feature');
    if (!empty($job_feature)) {
        $filters['feature'] = $job_feature;
    }
    
    // クエリパラメータからの複数特徴取得
    if (isset($_GET['features']) && is_array($_GET['features'])) {
        $filters['features'] = array_map('sanitize_text_field', $_GET['features']);
    }
    
    return $filters;
}

/**
 * 特定の特徴フィルターのみを削除した場合のURLを生成する関数
 */
function remove_feature_from_url($feature_to_remove) {
    // 現在のクエリ変数を取得
    $location_slug = get_query_var('job_location');
    $position_slug = get_query_var('job_position');
    $job_type_slug = get_query_var('job_type');
    $facility_type_slug = get_query_var('facility_type');
    $job_feature_slug = get_query_var('job_feature');
    
    // URLクエリパラメータから特徴の配列を取得（複数選択の場合）
    $feature_slugs = isset($_GET['features']) ? (array)$_GET['features'] : array();
    
    // 特徴のスラッグが単一で指定されている場合、それも追加
    if (!empty($job_feature_slug) && !in_array($job_feature_slug, $feature_slugs)) {
        $feature_slugs[] = $job_feature_slug;
    }
    
    // 削除する特徴を配列から除外
    if (!empty($feature_slugs)) {
        $feature_slugs = array_values(array_diff($feature_slugs, array($feature_to_remove)));
    }
    
    // 単一特徴のパラメータが一致する場合、それも削除
    if ($job_feature_slug === $feature_to_remove) {
        $job_feature_slug = '';
    }
    
    // 残りのフィルターでURLを構築
    $url_parts = array();
    $query_params = array();
    
    if (!empty($location_slug)) {
        $url_parts[] = 'location/' . $location_slug;
    }
    
    if (!empty($position_slug)) {
        $url_parts[] = 'position/' . $position_slug;
    }
    
    if (!empty($job_type_slug)) {
        $url_parts[] = 'type/' . $job_type_slug;
    }
    
    if (!empty($facility_type_slug)) {
        $url_parts[] = 'facility/' . $facility_type_slug;
    }
    
    if (!empty($job_feature_slug)) {
        $url_parts[] = 'feature/' . $job_feature_slug;
    }
    
    // URLの構築
    $base_url = home_url('/jobs/');
    
    if (!empty($url_parts)) {
        $path = implode('/', $url_parts);
        $base_url .= $path . '/';
    } else if (!empty($feature_slugs)) {
        // 他の条件がなく特徴のみが残っている場合は特徴専用エンドポイントを使う
        $base_url .= 'features/';
    } else {
        // すべての条件が削除された場合は求人一覧ページに戻る
        return home_url('/jobs/');
    }
    
    // 複数特徴はクエリパラメータとして追加
    if (!empty($feature_slugs)) {
        foreach ($feature_slugs as $feature) {
            $query_params[] = 'features[]=' . urlencode($feature);
        }
    }
    
    // クエリパラメータの追加
    if (!empty($query_params)) {
        $base_url .= '?' . implode('&', $query_params);
    }
    
    return $base_url;
}

/**
 * 特定のフィルターを削除した場合のURLを生成する関数
 */
function remove_filter_from_url($filter_to_remove) {
    // 現在のクエリ変数を取得
    $location_slug = get_query_var('job_location');
    $position_slug = get_query_var('job_position');
    $job_type_slug = get_query_var('job_type');
    $facility_type_slug = get_query_var('facility_type');
    $job_feature_slug = get_query_var('job_feature');
    
    // URLクエリパラメータから特徴の配列を取得（複数選択の場合）
    $feature_slugs = isset($_GET['features']) ? (array)$_GET['features'] : array();
    
    // 特徴のスラッグが単一で指定されている場合、それも追加
    if (!empty($job_feature_slug) && !in_array($job_feature_slug, $feature_slugs)) {
        $feature_slugs[] = $job_feature_slug;
    }
    
    // 削除するフィルターを処理 - 指定されたフィルターのみを空にする
    switch ($filter_to_remove) {
        case 'location':
            $location_slug = '';
            break;
        case 'position':
            $position_slug = '';
            break;
        case 'type':
            $job_type_slug = '';
            break;
        case 'facility':
            $facility_type_slug = '';
            break;
        case 'feature':
            // 特徴フィルターのみを削除
            $job_feature_slug = '';
            $feature_slugs = array();
            break;
    }
    
    // 残りのフィルターでURLを構築
    $url_parts = array();
    $query_params = array();
    
    // 各フィルターが空でなければURLパーツに追加
    if (!empty($location_slug)) {
        $url_parts[] = 'location/' . $location_slug;
    }
    
    if (!empty($position_slug)) {
        $url_parts[] = 'position/' . $position_slug;
    }
    
    if (!empty($job_type_slug)) {
        $url_parts[] = 'type/' . $job_type_slug;
    }
    
    if (!empty($facility_type_slug)) {
        $url_parts[] = 'facility/' . $facility_type_slug;
    }
    
    if (!empty($job_feature_slug)) {
        $url_parts[] = 'feature/' . $job_feature_slug;
    }
    
    // URLの構築
    $base_url = home_url('/jobs/');
    
    // パスがある場合はそれを追加
    if (!empty($url_parts)) {
        $path = implode('/', $url_parts);
        $base_url .= $path . '/';
    } else if (!empty($feature_slugs)) {
        // 他の条件がなく特徴のみが残っている場合は特徴専用エンドポイントを使う
        $base_url .= 'features/';
    } else {
        // すべての条件が削除された場合は求人一覧ページに戻る
        return home_url('/jobs/');
    }
    
    // 複数特徴はクエリパラメータとして追加
    if (!empty($feature_slugs) && $filter_to_remove !== 'feature') {
        foreach ($feature_slugs as $feature) {
            $query_params[] = 'features[]=' . urlencode($feature);
        }
    }
    
    // クエリパラメータの追加
    if (!empty($query_params)) {
        $base_url .= '?' . implode('&', $query_params);
    }
    
    return $base_url;
}

/**
 * 求人アーカイブページのメインクエリを変更する
 */
function modify_job_archive_query($query) {
    // メインクエリのみに適用
    if (!is_admin() && $query->is_main_query() && 
        (is_post_type_archive('job') || 
        is_tax('job_location') || 
        is_tax('job_position') || 
        is_tax('job_type') || 
        is_tax('facility_type') || 
        is_tax('job_feature'))) {
        
        // URLクエリパラメータから特徴の配列を取得（複数選択の場合）
        $feature_slugs = isset($_GET['features']) && is_array($_GET['features']) ? $_GET['features'] : array();
        
        // 特徴（job_feature）のパラメータがある場合のみ処理
        if (!empty($feature_slugs)) {
            // 既存のtax_queryを取得（なければ新規作成）
            $tax_query = $query->get('tax_query');
            
            if (!is_array($tax_query)) {
                $tax_query = array();
            }
            
            // 特徴の条件を追加
            $tax_query[] = array(
                'taxonomy' => 'job_feature',
                'field'    => 'slug',
                'terms'    => $feature_slugs,
                'operator' => 'IN',
            );
            
            // 複数の条件がある場合はAND条件で結合
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            
            // 更新したtax_queryを設定
            $query->set('tax_query', $tax_query);
        }
        
        // 特徴のみのフラグがある場合（/jobs/features/ エンドポイント）
        if (get_query_var('job_features_only')) {
            // この場合、クエリパラメータの特徴のみでフィルタリング
            if (!empty($feature_slugs)) {
                $tax_query = array(
                    array(
                        'taxonomy' => 'job_feature',
                        'field'    => 'slug',
                        'terms'    => $feature_slugs,
                        'operator' => 'IN',
                    )
                );
                
                $query->set('tax_query', $tax_query);
            }
        }
    }
}
add_action('pre_get_posts', 'modify_job_archive_query');

/**
 * タクソノミーの子ターム取得用AJAX処理
 */
function get_taxonomy_children_callback() {
    // セキュリティチェック
    if (!isset($_POST['taxonomy']) || !isset($_POST['parent_id'])) {
        wp_send_json_error('Invalid request');
    }
    
    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    $parent_id = intval($_POST['parent_id']);
    
    // 子タームを取得
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'parent' => $parent_id,
    ));
    
    if (is_wp_error($terms)) {
        wp_send_json_error($terms->get_error_message());
    }
    
    // 結果を整形して返送
    $result = array();
    foreach ($terms as $term) {
        $result[] = array(
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        );
    }
    
    wp_send_json_success($result);
}
add_action('wp_ajax_get_taxonomy_children', 'get_taxonomy_children_callback');
add_action('wp_ajax_nopriv_get_taxonomy_children', 'get_taxonomy_children_callback');

/**
 * タームのURLを取得するAJAX処理
 */
function get_term_link_callback() {
    // セキュリティチェック
    if (!isset($_POST['term_id']) || !isset($_POST['taxonomy'])) {
        wp_send_json_error('Invalid request');
    }
    
    $term_id = intval($_POST['term_id']);
    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    
    $term = get_term($term_id, $taxonomy);
    if (is_wp_error($term)) {
        wp_send_json_error($term->get_error_message());
    }
    
    $term_link = get_term_link($term);
    if (is_wp_error($term_link)) {
        wp_send_json_error($term_link->get_error_message());
    }
    
    wp_send_json_success($term_link);
}
add_action('wp_ajax_get_term_link', 'get_term_link_callback');
add_action('wp_ajax_nopriv_get_term_link', 'get_term_link_callback');

/**
 * スラッグからタームリンクを取得するAJAX処理
 */
function get_term_link_by_slug_callback() {
    // セキュリティチェック
    if (!isset($_POST['slug']) || !isset($_POST['taxonomy'])) {
        wp_send_json_error('Invalid request');
    }
    
    $slug = sanitize_text_field($_POST['slug']);
    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    
    $term = get_term_by('slug', $slug, $taxonomy);
    if (!$term || is_wp_error($term)) {
        wp_send_json_error('Term not found');
    }
    
    $term_link = get_term_link($term);
    if (is_wp_error($term_link)) {
        wp_send_json_error($term_link->get_error_message());
    }
    
    wp_send_json_success($term_link);
}
add_action('wp_ajax_get_term_link_by_slug', 'get_term_link_by_slug_callback');
add_action('wp_ajax_nopriv_get_term_link_by_slug', 'get_term_link_by_slug_callback');

/**
 * URLが変更されたときにリライトルールをフラッシュする
 */
function flush_rewrite_rules_on_theme_activation() {
    if (get_option('job_search_rewrite_rules_flushed') != '1') {
        flush_rewrite_rules();
        update_option('job_search_rewrite_rules_flushed', '1');
    }
}
add_action('after_switch_theme', 'flush_rewrite_rules_on_theme_activation');

// リライトルールの強制フラッシュと再登録
function force_rewrite_rules_refresh() {
    // 初回読み込み時にのみ実行
    if (!get_option('force_rewrite_refresh_done')) {
        // リライトルールを追加
        job_search_rewrite_rules();
        
        // リライトルールをフラッシュ
        flush_rewrite_rules();
        
        // 実行済みフラグを設定
        update_option('force_rewrite_refresh_done', '1');
    }
}
add_action('init', 'force_rewrite_rules_refresh', 99);

// 特徴のみのリライトルールを追加した後にフラッシュする
function flush_features_rewrite_rules() {
    if (!get_option('job_features_rewrite_flushed')) {
        flush_rewrite_rules();
        update_option('job_features_rewrite_flushed', true);
    }
}
add_action('init', 'flush_features_rewrite_rules', 999);

// リライトルールのデバッグ（必要に応じて）
function debug_rewrite_rules() {
    if (current_user_can('manage_options') && isset($_GET['debug_rewrite'])) {
        global $wp_rewrite;
        echo '<pre>';
        print_r($wp_rewrite->rules);
        echo '</pre>';
        exit;
    }
}
add_action('init', 'debug_rewrite_rules', 100);

// 以下のコードがfunctions.phpに追加されているか確認してください
function job_path_query_vars($vars) {
    $vars[] = 'job_path';
    return $vars;
}
add_filter('query_vars', 'job_path_query_vars');

// 求人ステータス変更・削除用のアクション処理
add_action('admin_post_draft_job', 'set_job_to_draft');
add_action('admin_post_publish_job', 'set_job_to_publish');
add_action('admin_post_delete_job', 'delete_job_post');

/**
 * 求人を下書きに変更
 */
function set_job_to_draft() {
    if (!isset($_GET['job_id']) || !isset($_GET['_wpnonce'])) {
        wp_die('無効なリクエストです。');
    }
    
    $job_id = intval($_GET['job_id']);
    
    // ナンス検証
    if (!wp_verify_nonce($_GET['_wpnonce'], 'draft_job_' . $job_id)) {
        wp_die('セキュリティチェックに失敗しました。');
    }
    
    // 権限チェック
    $job_post = get_post($job_id);
    if (!$job_post || $job_post->post_type !== 'job' || 
        ($job_post->post_author != get_current_user_id() && !current_user_can('administrator'))) {
        wp_die('この求人を編集する権限がありません。');
    }
    
    // 下書きに変更
    wp_update_post(array(
        'ID' => $job_id,
        'post_status' => 'draft'
    ));
    
    // リダイレクト
    wp_redirect(home_url('/job-list/?status=drafted'));
    exit;
}

/**
 * 求人を公開に変更
 */
function set_job_to_publish() {
    if (!isset($_GET['job_id']) || !isset($_GET['_wpnonce'])) {
        wp_die('無効なリクエストです。');
    }
    
    $job_id = intval($_GET['job_id']);
    
    // ナンス検証
    if (!wp_verify_nonce($_GET['_wpnonce'], 'publish_job_' . $job_id)) {
        wp_die('セキュリティチェックに失敗しました。');
    }
    
    // 権限チェック
    $job_post = get_post($job_id);
    if (!$job_post || $job_post->post_type !== 'job' || 
        ($job_post->post_author != get_current_user_id() && !current_user_can('administrator'))) {
        wp_die('この求人を編集する権限がありません。');
    }
    
    // 公開に変更
    wp_update_post(array(
        'ID' => $job_id,
        'post_status' => 'publish'
    ));
    
    // リダイレクト
    wp_redirect(home_url('/job-list/?status=published'));
    exit;
}

/**
 * 求人を削除
 */
function delete_job_post() {
    if (!isset($_GET['job_id']) || !isset($_GET['_wpnonce'])) {
        wp_die('無効なリクエストです。');
    }
    
    $job_id = intval($_GET['job_id']);
    
    // ナンス検証
    if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_job_' . $job_id)) {
        wp_die('セキュリティチェックに失敗しました。');
    }
    
    // 権限チェック
    $job_post = get_post($job_id);
    if (!$job_post || $job_post->post_type !== 'job' || 
        ($job_post->post_author != get_current_user_id() && !current_user_can('administrator'))) {
        wp_die('この求人を削除する権限がありません。');
    }
    
    // 削除
    wp_trash_post($job_id);
    
    // リダイレクト
    wp_redirect(home_url('/job-list/?status=deleted'));
    exit;
}



/**
 * 求人用カスタムフィールドとメタボックスの設定
 */

/**
 * 求人投稿のメタボックスを追加
 */
function add_job_meta_boxes() {
    add_meta_box(
        'job_details',
        '求人詳細情報',
        'render_job_details_meta_box',
        'job',
        'normal',
        'high'
    );
    
    add_meta_box(
        'facility_details',
        '施設情報',
        'render_facility_details_meta_box',
        'job',
        'normal',
        'high'
    );
    
    add_meta_box(
        'workplace_environment',
        '職場環境',
        'render_workplace_environment_meta_box',
        'job',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_job_meta_boxes');

/**
 * 求人詳細情報のメタボックスをレンダリング
 */
function render_job_details_meta_box($post) {
    // nonce フィールドを作成
    wp_nonce_field('save_job_details', 'job_details_nonce');
    
    // 現在のカスタムフィールド値を取得
    $salary_range = get_post_meta($post->ID, 'salary_range', true);
    $working_hours = get_post_meta($post->ID, 'working_hours', true);
    $holidays = get_post_meta($post->ID, 'holidays', true);
    $benefits = get_post_meta($post->ID, 'benefits', true);
    $requirements = get_post_meta($post->ID, 'requirements', true);
    $application_process = get_post_meta($post->ID, 'application_process', true);
    $contact_info = get_post_meta($post->ID, 'contact_info', true);
    $bonus_raise = get_post_meta($post->ID, 'bonus_raise', true);
    
    // フォームを表示
    ?>
    <style>
        .job-form-row {
            margin-bottom: 15px;
        }
        .job-form-row label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .job-form-row input[type="text"],
        .job-form-row textarea {
            width: 100%;
        }
        .required {
            color: #f00;
        }
    </style>
    
    <div class="job-form-row">
        <label for="salary_range">給与範囲 <span class="required">*</span></label>
        <input type="text" id="salary_range" name="salary_range" value="<?php echo esc_attr($salary_range); ?>" required>
        <p class="description">例: 月給180,000円〜250,000円</p>
    </div>
    
    <div class="job-form-row">
        <label for="working_hours">勤務時間 <span class="required">*</span></label>
        <input type="text" id="working_hours" name="working_hours" value="<?php echo esc_attr($working_hours); ?>" required>
        <p class="description">例: 9:00〜18:00（休憩60分）</p>
    </div>
    
    <div class="job-form-row">
        <label for="holidays">休日・休暇 <span class="required">*</span></label>
        <input type="text" id="holidays" name="holidays" value="<?php echo esc_attr($holidays); ?>" required>
        <p class="description">例: 土日祝、年末年始、有給休暇あり</p>
    </div>
    
    <div class="job-form-row">
        <label for="benefits">福利厚生</label>
        <textarea id="benefits" name="benefits" rows="4"><?php echo esc_textarea($benefits); ?></textarea>
        <p class="description">社会保険、交通費支給、各種手当など</p>
    </div>
    
    <div class="job-form-row">
        <label for="bonus_raise">昇給・賞与</label>
        <textarea id="bonus_raise" name="bonus_raise" rows="4"><?php echo esc_textarea($bonus_raise); ?></textarea>
        <p class="description">昇給制度や賞与の詳細など</p>
    </div>
    
    <div class="job-form-row">
        <label for="requirements">応募要件</label>
        <textarea id="requirements" name="requirements" rows="4"><?php echo esc_textarea($requirements); ?></textarea>
        <p class="description">必要な資格や経験など</p>
    </div>
    
    <div class="job-form-row">
        <label for="application_process">選考プロセス</label>
        <textarea id="application_process" name="application_process" rows="4"><?php echo esc_textarea($application_process); ?></textarea>
        <p class="description">書類選考、面接回数など</p>
    </div>
    
    <div class="job-form-row">
        <label for="contact_info">応募方法・連絡先 <span class="required">*</span></label>
        <textarea id="contact_info" name="contact_info" rows="4" required><?php echo esc_textarea($contact_info); ?></textarea>
        <p class="description">電話番号、メールアドレス、応募フォームURLなど</p>
    </div>
    <?php
}

/**
 * 施設情報のメタボックスをレンダリング
 */
function render_facility_details_meta_box($post) {
    // nonce フィールドを作成
    wp_nonce_field('save_facility_details', 'facility_details_nonce');
    
    // 現在のカスタムフィールド値を取得
    $facility_name = get_post_meta($post->ID, 'facility_name', true);
    $facility_address = get_post_meta($post->ID, 'facility_address', true);
    $facility_tel = get_post_meta($post->ID, 'facility_tel', true);
    $facility_hours = get_post_meta($post->ID, 'facility_hours', true);
    $facility_url = get_post_meta($post->ID, 'facility_url', true);
    $facility_company = get_post_meta($post->ID, 'facility_company', true);
    $capacity = get_post_meta($post->ID, 'capacity', true);
    $staff_composition = get_post_meta($post->ID, 'staff_composition', true);
    
    // フォームを表示
    ?>
    <div class="job-form-row">
        <label for="facility_name">施設名 <span class="required">*</span></label>
        <input type="text" id="facility_name" name="facility_name" value="<?php echo esc_attr($facility_name); ?>" required>
    </div>
    
    <div class="job-form-row">
        <label for="facility_company">運営会社名</label>
        <input type="text" id="facility_company" name="facility_company" value="<?php echo esc_attr($facility_company); ?>">
    </div>
    
    <div class="job-form-row">
        <label for="facility_address">施設住所 <span class="required">*</span></label>
        <input type="text" id="facility_address" name="facility_address" value="<?php echo esc_attr($facility_address); ?>" required>
        <p class="description">例: 〒123-4567 神奈川県横浜市○○区△△町1-2-3</p>
    </div>
    
    <div class="job-form-row">
        <label for="capacity">利用者定員数</label>
        <input type="text" id="capacity" name="capacity" value="<?php echo esc_attr($capacity); ?>">
        <p class="description">例: 60名（0〜5歳児）</p>
    </div>
    
    <div class="job-form-row">
        <label for="staff_composition">スタッフ構成</label>
        <textarea id="staff_composition" name="staff_composition" rows="4"><?php echo esc_textarea($staff_composition); ?></textarea>
        <p class="description">例: 園長1名、主任保育士2名、保育士12名、栄養士2名、調理員3名、事務員1名</p>
    </div>
    
    <div class="job-form-row">
        <label for="facility_tel">施設電話番号</label>
        <input type="text" id="facility_tel" name="facility_tel" value="<?php echo esc_attr($facility_tel); ?>">
    </div>
    
    <div class="job-form-row">
        <label for="facility_hours">施設営業時間</label>
        <input type="text" id="facility_hours" name="facility_hours" value="<?php echo esc_attr($facility_hours); ?>">
    </div>
    
    <div class="job-form-row">
        <label for="facility_url">施設WebサイトURL</label>
        <input type="url" id="facility_url" name="facility_url" value="<?php echo esc_url($facility_url); ?>">
    </div>
    <?php
}

/**
 * 職場環境のメタボックスをレンダリング
 */
function render_workplace_environment_meta_box($post) {
    // nonce フィールドを作成
    wp_nonce_field('save_workplace_environment', 'workplace_environment_nonce');
    
    // 現在のカスタムフィールド値を取得
    $daily_schedule = get_post_meta($post->ID, 'daily_schedule', true);
    $staff_voices = get_post_meta($post->ID, 'staff_voices', true);
    
    // フォームを表示
    ?>
    <div class="job-form-row">
        <label for="daily_schedule">仕事の一日の流れ</label>
        <textarea id="daily_schedule" name="daily_schedule" rows="8"><?php echo esc_textarea($daily_schedule); ?></textarea>
        <p class="description">例：9:00 出勤・朝礼、9:30 午前の業務開始、12:00 お昼休憩 など時間ごとの業務内容</p>
    </div>
    
    <div class="job-form-row">
        <label for="staff_voices">職員の声</label>
        <textarea id="staff_voices" name="staff_voices" rows="8"><?php echo esc_textarea($staff_voices); ?></textarea>
        <p class="description">実際に働いているスタッフの声を入力（職種、勤続年数、コメントなど）</p>
    </div>
    <?php
}

/**
 * カスタムフィールドのデータを保存
 */
function save_job_meta_data($post_id) {
    // 自動保存の場合は何もしない
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 権限チェック
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // 求人詳細情報の保存
    if (isset($_POST['job_details_nonce']) && wp_verify_nonce($_POST['job_details_nonce'], 'save_job_details')) {
        if (isset($_POST['salary_range'])) {
            update_post_meta($post_id, 'salary_range', sanitize_text_field($_POST['salary_range']));
        }
        
        if (isset($_POST['working_hours'])) {
            update_post_meta($post_id, 'working_hours', sanitize_text_field($_POST['working_hours']));
        }
        
        if (isset($_POST['holidays'])) {
            update_post_meta($post_id, 'holidays', sanitize_text_field($_POST['holidays']));
        }
        
        if (isset($_POST['benefits'])) {
            update_post_meta($post_id, 'benefits', wp_kses_post($_POST['benefits']));
        }
        
        if (isset($_POST['bonus_raise'])) {
            update_post_meta($post_id, 'bonus_raise', wp_kses_post($_POST['bonus_raise']));
        }
        
        if (isset($_POST['requirements'])) {
            update_post_meta($post_id, 'requirements', wp_kses_post($_POST['requirements']));
        }
        
        if (isset($_POST['application_process'])) {
            update_post_meta($post_id, 'application_process', wp_kses_post($_POST['application_process']));
        }
        
        if (isset($_POST['contact_info'])) {
            update_post_meta($post_id, 'contact_info', wp_kses_post($_POST['contact_info']));
        }
    }
    
    // 施設情報の保存
    if (isset($_POST['facility_details_nonce']) && wp_verify_nonce($_POST['facility_details_nonce'], 'save_facility_details')) {
        if (isset($_POST['facility_name'])) {
            update_post_meta($post_id, 'facility_name', sanitize_text_field($_POST['facility_name']));
        }
        
        if (isset($_POST['facility_company'])) {
            update_post_meta($post_id, 'facility_company', sanitize_text_field($_POST['facility_company']));
        }
        
        if (isset($_POST['facility_address'])) {
            update_post_meta($post_id, 'facility_address', sanitize_text_field($_POST['facility_address']));
        }
        
        if (isset($_POST['capacity'])) {
            update_post_meta($post_id, 'capacity', sanitize_text_field($_POST['capacity']));
        }
        
        if (isset($_POST['staff_composition'])) {
            update_post_meta($post_id, 'staff_composition', wp_kses_post($_POST['staff_composition']));
        }
        
        if (isset($_POST['facility_tel'])) {
            update_post_meta($post_id, 'facility_tel', sanitize_text_field($_POST['facility_tel']));
        }
        
        if (isset($_POST['facility_hours'])) {
            update_post_meta($post_id, 'facility_hours', sanitize_text_field($_POST['facility_hours']));
        }
        
        if (isset($_POST['facility_url'])) {
            update_post_meta($post_id, 'facility_url', esc_url_raw($_POST['facility_url']));
        }
    }
    
    // 職場環境の保存
    if (isset($_POST['workplace_environment_nonce']) && wp_verify_nonce($_POST['workplace_environment_nonce'], 'save_workplace_environment')) {
        if (isset($_POST['daily_schedule'])) {
            update_post_meta($post_id, 'daily_schedule', wp_kses_post($_POST['daily_schedule']));
        }
        
        if (isset($_POST['staff_voices'])) {
            update_post_meta($post_id, 'staff_voices', wp_kses_post($_POST['staff_voices']));
        }
    }
}
add_action('save_post_job', 'save_job_meta_data');

// 追加のカスタムフィールドを設定
function add_additional_job_fields($post_id) {
    // 本文タイトル
    if (isset($_POST['job_content_title'])) {
        update_post_meta($post_id, 'job_content_title', sanitize_text_field($_POST['job_content_title']));
    }
    
    // GoogleMap埋め込みコード
    if (isset($_POST['facility_map'])) {
        update_post_meta($post_id, 'facility_map', wp_kses($_POST['facility_map'], array(
            'iframe' => array(
                'src' => array(),
                'width' => array(),
                'height' => array(),
                'frameborder' => array(),
                'style' => array(),
                'allowfullscreen' => array()
            )
        )));
    }
    
    // 仕事の一日の流れ（配列形式）
    if (isset($_POST['daily_schedule_time']) && is_array($_POST['daily_schedule_time'])) {
        $schedule_items = array();
        $count = count($_POST['daily_schedule_time']);
        
        for ($i = 0; $i < $count; $i++) {
            if (!empty($_POST['daily_schedule_time'][$i])) {
                $schedule_items[] = array(
                    'time' => sanitize_text_field($_POST['daily_schedule_time'][$i]),
                    'title' => sanitize_text_field($_POST['daily_schedule_title'][$i]),
                    'description' => wp_kses_post($_POST['daily_schedule_description'][$i])
                );
            }
        }
        
        update_post_meta($post_id, 'daily_schedule_items', $schedule_items);
    }
    
    // 職員の声（配列形式）
    if (isset($_POST['staff_voice_role']) && is_array($_POST['staff_voice_role'])) {
        $voice_items = array();
        $count = count($_POST['staff_voice_role']);
        
        for ($i = 0; $i < $count; $i++) {
            if (!empty($_POST['staff_voice_role'][$i])) {
                $voice_items[] = array(
                    'image_id' => intval($_POST['staff_voice_image'][$i]),
                    'role' => sanitize_text_field($_POST['staff_voice_role'][$i]),
                    'years' => sanitize_text_field($_POST['staff_voice_years'][$i]),
                    'comment' => wp_kses_post($_POST['staff_voice_comment'][$i])
                );
            }
        }
        
        update_post_meta($post_id, 'staff_voice_items', $voice_items);
    }
}

// 求人投稿保存時にカスタムフィールドを処理
add_action('save_post_job', function($post_id) {
    // 自動保存の場合は何もしない
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 権限チェック
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // 追加フィールドを保存
    add_additional_job_fields($post_id);
}, 15);


// JavaScriptとCSSを登録・読み込むための関数
function register_job_search_scripts() {
    // URLパラメータを追加して、キャッシュを防止
    $version = '1.0.0';
    
    // スタイルシートの登録（必要に応じて）
    wp_register_style('job-search-style', get_stylesheet_directory_uri() . '/css/job-search.css', array(), $version);
    wp_enqueue_style('job-search-style');
    
    // JavaScriptの登録
    wp_register_script('job-search', get_stylesheet_directory_uri() . '/js/job-search.js', array('jquery'), $version, true);
    
    // JavaScriptにパラメータを渡す
    wp_localize_script('job-search', 'job_search_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'site_url' => home_url(),
        'nonce' => wp_create_nonce('job_search_nonce')
    ));
    
    // JavaScriptを読み込む
    wp_enqueue_script('job-search');
}
add_action('wp_enqueue_scripts', 'register_job_search_scripts');



/**
 * 退会処理の実装
 */

// 退会処理のアクションフックを追加
add_action('admin_post_delete_my_account', 'handle_delete_account');

/**
 * ユーザーアカウント削除処理
 */
function handle_delete_account() {
    // ログインチェック
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url());
        exit;
    }
    
    // nonceチェック
    if (!isset($_POST['delete_account_nonce']) || !wp_verify_nonce($_POST['delete_account_nonce'], 'delete_account_action')) {
        wp_die('セキュリティチェックに失敗しました。');
    }
    
    // 退会確認チェックボックスが選択されているか確認
    if (!isset($_POST['confirm_deletion'])) {
        wp_redirect(add_query_arg('error', 'no_confirmation', home_url('/withdrawal/')));
        exit;
    }
    
    // 現在のユーザー情報を取得
    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;
    $user_name = $current_user->display_name;
    $user_id = $current_user->ID;
    
    // 退会完了メールを送信
    send_account_deletion_email($user_email, $user_name);
    
    // ユーザーをログアウト
    wp_logout();
    
    // ユーザーアカウントを削除
    // WP-Membersのユーザー削除APIがあれば使用する
    if (function_exists('wpmem_delete_user')) {
        wpmem_delete_user($user_id);
    } else {
        // WP標準のユーザー削除機能を使用
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);
    }
    
    // 退会完了ページへリダイレクト
    wp_redirect(home_url('/?account_deleted=true'));
    exit;
}

/**
 * 退会完了メールを送信する
 *
 * @param string $user_email 退会するユーザーのメールアドレス
 * @param string $user_name  退会するユーザーの表示名
 */
function send_account_deletion_email($user_email, $user_name) {
    $site_name = get_bloginfo('name');
    $admin_email = get_option('admin_email');
    
    // メールの件名
    $subject = sprintf('[%s] 退会手続き完了のお知らせ', $site_name);
    
    // メールの本文
    $message = sprintf(
        '%s 様
        
退会手続きが完了しました。

%s をご利用いただき、誠にありがとうございました。
アカウント情報および関連データはすべて削除されました。

またのご利用をお待ちしております。

------------------------------
%s
%s',
        $user_name,
        $site_name,
        $site_name,
        home_url()
    );
    
    // メールヘッダー
    $headers = array(
        'From: ' . $site_name . ' <' . $admin_email . '>',
        'Content-Type: text/plain; charset=UTF-8'
    );
    
    // メール送信
    wp_mail($user_email, $subject, $message, $headers);
    
    // 管理者にも通知
    $admin_subject = sprintf('[%s] ユーザー退会通知', $site_name);
    $admin_message = sprintf(
        '以下のユーザーが退会しました:
        
ユーザー名: %s
メールアドレス: %s
退会日時: %s',
        $user_name,
        $user_email,
        current_time('Y-m-d H:i:s')
    );
    
    wp_mail($admin_email, $admin_subject, $admin_message, $headers);
}

/**
 * トップページに退会完了メッセージを表示
 */
function show_account_deleted_message() {
    if (isset($_GET['account_deleted']) && $_GET['account_deleted'] === 'true') {
        echo '<div class="account-deleted-message">';
        echo '<p><strong>退会手続きが完了しました。ご利用ありがとうございました。</strong></p>';
        echo '</div>';
        
        // スタイルを追加
        echo '<style>
        .account-deleted-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #28a745;
        }
        </style>';
    }
}
add_action('wp_body_open', 'show_account_deleted_message');




/**
 * WordPressログイン画面とパスワードリセット画面のカスタマイズ
 */

// ログイン画面に独自のスタイルを適用
add_action('login_enqueue_scripts', 'custom_login_styles');

function custom_login_styles() {
    ?>
    <style type="text/css">
        /* 全体のスタイル */
        body.login {
            background-color: #f8f9fa;
        }
        
        /* WordPressロゴを非表示 */
        #login h1 a {
            display: none;
        }
        
        /* フォーム全体の調整 */
        #login {
            width: 400px;
            padding: 5% 0 0;
        }
        
        /* 見出しを追加 */
        #login:before {
            content: "パスワード再設定";
            display: block;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        
        /* フォームのスタイル */
        .login form {
            margin-top: 20px;
            padding: 26px 24px 34px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* ラベルとフォーム要素 */
        .login label {
            font-size: 14px;
            color: #333;
            font-weight: bold;
        }
        
        .login form .input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            margin: 5px 0 15px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        /* ボタンスタイル */
        .login .button-primary {
            background-color: #0073aa;
            border-color: #0073aa;
            color: white;
            width: 100%;
            padding: 10px;
            text-shadow: none;
            box-shadow: none;
            border-radius: 4px;
            font-size: 16px;
            height: auto;
            line-height: normal;
            text-transform: none;
        }
        
        .login .button-primary:hover {
            background-color: #005f8a;
            border-color: #005f8a;
        }
        
        /* リンクのスタイル */
        #nav, #backtoblog {
            text-align: center;
            margin: 16px 0 0;
            font-size: 14px;
        }
        
        #nav a, #backtoblog a {
            color: #0073aa;
            text-decoration: none;
        }
        
        #nav a:hover, #backtoblog a:hover {
            color: #005f8a;
            text-decoration: underline;
        }
        
        /* メッセージスタイル */
        .login .message,
        .login #login_error {
            border-radius: 4px;
        }
        
        /* 余計な要素を非表示 */
        .login .privacy-policy-page-link {
            display: none;
        }
        
        /* パスワード強度インジケータを非表示 */
        .pw-weak {
            display: none !important;
        }
        
        /* パスワードリセット画面専用のスタイル */
        body.login-action-rp form p:first-child,
        body.login-action-resetpass form p:first-child {
            font-size: 14px;
            color: #333;
        }
        
        /* 文言を日本語化（CSSのcontentで置き換え） */
        body.login-action-lostpassword form p:first-child {
            display: none;  /* 元のテキストを非表示 */
        }
        
        body.login-action-lostpassword form:before {
            content: "メールアドレスを入力してください。パスワードリセット用のリンクをメールでお送りします。";
            display: block;
            margin-bottom: 15px;
            font-size: 14px;
            color: #333;
        }
        
        body.login-action-rp form:before,
        body.login-action-resetpass form:before {
            content: "新しいパスワードを設定してください。";
            display: block;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
    </style>
    <?php
}

// ログイン画面のタイトルを変更
add_filter('login_title', 'custom_login_title', 10, 2);

function custom_login_title($title, $url) {
    if (isset($_GET['action']) && $_GET['action'] == 'lostpassword') {
        return 'パスワード再設定 | ' . get_bloginfo('name');
    } elseif (isset($_GET['action']) && ($_GET['action'] == 'rp' || $_GET['action'] == 'resetpass')) {
        return '新しいパスワードの設定 | ' . get_bloginfo('name');
    }
    return $title;
}

// ログイン画面のテキストを日本語化
add_filter('gettext', 'custom_login_text', 20, 3);

function custom_login_text($translated_text, $text, $domain) {
    if ($domain == 'default') {
        switch ($text) {
            // パスワードリセット関連
            case 'Enter your username or email address and you will receive a link to create a new password via email.':
                $translated_text = 'メールアドレスを入力してください。パスワードリセット用のリンクをメールでお送りします。';
                break;
            case 'Username or Email Address':
                $translated_text = 'メールアドレス';
                break;
            case 'Get New Password':
                $translated_text = 'パスワード再設定メールを送信';
                break;
            case 'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.':
                $translated_text = 'パスワード再設定用のメールを送信しました。メールが届くまで数分かかる場合があります。10分以上経ってもメールが届かない場合は、再度試してください。';
                break;
            case 'There is no account with that username or email address.':
                $translated_text = '入力されたメールアドレスのアカウントが見つかりません。';
                break;
            
            // パスワード設定画面関連
            case 'Enter your new password below or generate one.':
            case 'Enter your new password below.':
                $translated_text = '新しいパスワードを入力してください。';
                break;
            case 'New password':
                $translated_text = '新しいパスワード';
                break;
            case 'Confirm new password':
                $translated_text = '新しいパスワード（確認）';
                break;
            case 'Reset Password':
                $translated_text = 'パスワードを変更';
                break;
            case 'Your password has been reset. <a href="%s">Log in</a>':
                $translated_text = 'パスワードが変更されました。<a href="%s">ログイン</a>してください。';
                break;
            
            // その他のリンク
            case 'Log in':
                $translated_text = 'ログイン';
                break;
            case '&larr; Back to %s':
                $translated_text = 'トップページに戻る';
                break;
        }
    }
    return $translated_text;
}

// パスワードリセットメールのカスタマイズ
add_filter('retrieve_password_message', 'custom_password_reset_email', 10, 4);
add_filter('retrieve_password_title', 'custom_password_reset_email_title', 10, 1);

function custom_password_reset_email_title($title) {
    $site_name = get_bloginfo('name');
    return '[' . $site_name . '] パスワード再設定のご案内';
}

function custom_password_reset_email($message, $key, $user_login, $user_data) {
    $site_name = get_bloginfo('name');
    
    // リセットURL
    $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
    
    // メール本文
    $message = $user_data->display_name . " 様\r\n\r\n";
    $message .= "パスワード再設定のリクエストを受け付けました。\r\n\r\n";
    $message .= "以下のリンクをクリックして、新しいパスワードを設定してください：\r\n";
    $message .= $reset_url . "\r\n\r\n";
    $message .= "このリンクは24時間のみ有効です。\r\n\r\n";
    $message .= "リクエストに心当たりがない場合は、このメールを無視してください。\r\n\r\n";
    $message .= "------------------------------\r\n";
    $message .= $site_name . "\r\n";
    
    return $message;
}

// パスワード変更後のリダイレクト先を変更
add_action('login_form_resetpass', 'redirect_after_password_reset');

function redirect_after_password_reset() {
    if ('POST' === $_SERVER['REQUEST_METHOD']) {
        add_filter('login_redirect', 'custom_password_reset_redirect', 10, 3);
    }
}

function custom_password_reset_redirect($redirect_to, $requested_redirect_to, $user) {
    return home_url('/login/?password-reset=success');
}

// functions.php に追加
function custom_job_post_link($permalink, $post) {
    if ($post->post_type !== 'job') {
        return $permalink;
    }
    
    // 地域と職種のタクソノミーを取得
    $location_terms = get_the_terms($post->ID, 'job_location');
    $position_terms = get_the_terms($post->ID, 'job_position');
    
    $location_slug = $location_terms && !is_wp_error($location_terms) ? $location_terms[0]->slug : 'area';
    $position_slug = $position_terms && !is_wp_error($position_terms) ? $position_terms[0]->slug : 'position';
    
    // 新しいURLパターンを構築
    $permalink = home_url('/jobs/' . $location_slug . '/' . $position_slug . '/' . $post->ID . '/');
    
    return $permalink;
}
add_filter('post_type_link', 'custom_job_post_link', 10, 2);

// functions.php に追加
function add_custom_job_rewrite_rules() {
    add_rewrite_rule(
        'jobs/([^/]+)/([^/]+)/([0-9]+)/?$',
        'index.php?post_type=job&p=$matches[3]',
        'top'
    );
    
    // 地域別一覧ページ
    add_rewrite_rule(
        'jobs/([^/]+)/?$',
        'index.php?post_type=job&job_location=$matches[1]',
        'top'
    );
    
    // 職種別一覧ページ
    add_rewrite_rule(
        'jobs/position/([^/]+)/?$',
        'index.php?post_type=job&job_position=$matches[1]',
        'top'
    );
	
    // 基本の求人アーカイブページ用のルール
    add_rewrite_rule(
        'jobs/?$',
        'index.php?post_type=job',
        'top'
    );
	
}
add_action('init', 'add_custom_job_rewrite_rules');


function breadcrumb() {
    echo '<div class="breadcrumb">';
    echo '<a href="'.home_url().'">ホーム</a> &gt; ';
    
    if (is_single()) {
        $categories = get_the_category();
        if ($categories) {
            echo '<a href="'.get_category_link($categories[0]->term_id).'">'.$categories[0]->name.'</a> &gt; ';
        }
        echo get_the_title();
    } elseif (is_page()) {
        echo get_the_title();
    } elseif (is_category()) {
        echo single_cat_title('', false);
    }
    
    echo '</div>';
}

/**
 * お気に入り求人の追加・削除処理（修正版）
 */
add_action('wp_ajax_toggle_job_favorite', 'handle_toggle_job_favorite');
add_action('wp_ajax_nopriv_toggle_job_favorite', 'handle_toggle_job_favorite');

function handle_toggle_job_favorite() {
    // ナンス検証（複数のnonceに対応）
    $nonce_keys = array('job_favorite_nonce', 'favorites_nonce');
    $nonce_valid = false;
    
    foreach ($nonce_keys as $key) {
        if (wp_verify_nonce($_POST['nonce'], $key)) {
            $nonce_valid = true;
            break;
        }
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました。'));
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'ログインが必要です。'));
    }
    
    $user_id = get_current_user_id();
    $job_id = intval($_POST['job_id']);
    
    if (!$job_id) {
        wp_send_json_error(array('message' => '無効な求人IDです。'));
    }
    
    // 現在のお気に入りリストを取得（キーを'user_favorites'に統一）
    $favorites = get_user_meta($user_id, 'user_favorites', true);
    if (!is_array($favorites)) {
        $favorites = array();
    }
    
    // お気に入りの状態を切り替え
    if (in_array($job_id, $favorites)) {
        // お気に入りから削除
        $favorites = array_filter($favorites, function($id) use ($job_id) {
            return $id != $job_id;
        });
        $favorited = false;
        $status = 'removed';
    } else {
        // お気に入りに追加
        $favorites[] = $job_id;
        $favorited = true;
        $status = 'added';
    }
    
    // ユーザーメタデータを更新
    update_user_meta($user_id, 'user_favorites', array_values($favorites));
    
    wp_send_json_success(array(
        'favorited' => $favorited,
        'status' => $status,
        'message' => $favorited ? 'お気に入りに追加しました。' : 'お気に入りから削除しました。'
    ));
}

/**
 * 検索結果ページにおいて、カスタム投稿タイプ「job」のみを表示する
 */
function job_custom_search_filter($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_search) {
        // フロントエンドの検索結果ページでのみ実行
        $query->set('post_type', 'job');
    }
    return $query;
}
add_filter('pre_get_posts', 'job_custom_search_filter');

/**
 * キーワード検索を拡張して、カスタムフィールドも検索対象に含める
 */
function job_custom_search_where($where, $query) {
    global $wpdb;
    
    if (!is_admin() && $query->is_main_query() && $query->is_search) {
        $search_term = get_search_query();
        
        if (!empty($search_term)) {
            // オリジナルの検索条件を保持
            $original_where = $where;
            
            // カスタムフィールドを検索対象に追加
            $custom_fields = array(
                'facility_name',
                'facility_company',
                'facility_address',
                'job_content_title',
                'salary_range',
                'requirements',
                'benefits'
            );
            
            $meta_query = array();
            foreach ($custom_fields as $field) {
                $meta_query[] = $wpdb->prepare("(pm.meta_key = %s AND pm.meta_value LIKE %s)", $field, '%' . $wpdb->esc_like($search_term) . '%');
            }
            
            // メタデータとのJOINを確実にするためにクエリを調整
            // 注意：このアプローチは複雑なため、実際の環境でよく確認してください
            if (!empty($meta_query)) {
                $meta_where = ' OR (' . implode(' OR ', $meta_query) . ')';
                
                // 基本的な検索句の正規表現を使用して置換
                $pattern = '/([\(])\s*' . $wpdb->posts . '\.post_title\s+LIKE\s*(\'[^\']*\')\s*\)/';
                if (preg_match($pattern, $where, $matches)) {
                    $where = str_replace($matches[0], $matches[0] . $meta_where, $where);
                }
            }
        }
    }
    
    return $where;
}
add_filter('posts_where', 'job_custom_search_where', 10, 2);

/**
 * カスタムフィールド検索のためのJOINを追加
 */
function job_custom_search_join($join, $query) {
    global $wpdb;
    
    if (!is_admin() && $query->is_main_query() && $query->is_search) {
        $search_term = get_search_query();
        
        if (!empty($search_term)) {
            $join .= " LEFT JOIN $wpdb->postmeta pm ON ($wpdb->posts.ID = pm.post_id) ";
        }
    }
    
    return $join;
}
add_filter('posts_join', 'job_custom_search_join', 10, 2);

/**
 * 検索結果が重複しないようにする
 */
function job_custom_search_distinct($distinct, $query) {
    if (!is_admin() && $query->is_main_query() && $query->is_search) {
        return "DISTINCT";
    }
    
    return $distinct;
}
add_filter('posts_distinct', 'job_custom_search_distinct', 10, 2);


// スライダーカスタム投稿タイプの登録
function register_slider_post_type() {
    $labels = array(
        'name'                  => 'スライダー',
        'singular_name'         => 'スライド',
        'menu_name'             => 'スライダー',
        'name_admin_bar'        => 'スライド',
        'archives'              => 'スライドアーカイブ',
        'attributes'            => 'スライド属性',
        'all_items'             => 'すべてのスライド',
        'add_new_item'          => '新しいスライドを追加',
        'add_new'               => '新規追加',
        'new_item'              => '新しいスライド',
        'edit_item'             => 'スライドを編集',
        'update_item'           => 'スライドを更新',
        'view_item'             => 'スライドを表示',
        'view_items'            => 'スライドを表示',
        'search_items'          => 'スライドを検索',
    );
    
    $args = array(
        'label'                 => 'スライド',
        'labels'                => $labels,
        'supports'              => array('title'),  // タイトルのみサポート
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-images-alt2',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );
    
    register_post_type('slide', $args);
}
add_action('init', 'register_slider_post_type');

// スライド用のカスタムフィールドを追加
function slider_custom_meta_boxes() {
    add_meta_box(
        'slider_settings',
        'スライド設定',
        'slider_settings_callback',
        'slide',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'slider_custom_meta_boxes');

// スライド設定のコールバック関数
function slider_settings_callback($post) {
    wp_nonce_field(basename(__FILE__), 'slider_nonce');
    
    // 保存された値を取得
    $slide_image_id = get_post_meta($post->ID, 'slide_image_id', true);
    $slide_image_url = wp_get_attachment_image_url($slide_image_id, 'full');
    $slide_link = get_post_meta($post->ID, 'slide_link', true);
    
    ?>
    <div class="slider-settings-container" style="margin-bottom: 20px;">
        <p>
            <label for="slide_image"><strong>スライド画像：</strong></label><br>
            <input type="hidden" name="slide_image_id" id="slide_image_id" value="<?php echo esc_attr($slide_image_id); ?>" />
            <button type="button" class="button" id="slide_image_button">画像を選択</button>
            <button type="button" class="button" id="slide_image_remove" style="<?php echo empty($slide_image_id) ? 'display:none;' : ''; ?>">画像を削除</button>
            
            <div id="slide_image_preview" style="margin-top: 10px; <?php echo empty($slide_image_url) ? 'display:none;' : ''; ?>">
                <img src="<?php echo esc_url($slide_image_url); ?>" alt="スライド画像" style="max-width: 300px; height: auto;" />
            </div>
        </p>
        
        <p>
            <label for="slide_link"><strong>スライドリンク：</strong></label><br>
            <input type="url" name="slide_link" id="slide_link" value="<?php echo esc_url($slide_link); ?>" style="width: 100%;" />
            <span class="description">スライドをクリックした時に移動するURLを入力してください。空白の場合はリンクしません。</span>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // 画像選択ボタンのクリックイベント
        $('#slide_image_button').click(function(e) {
            e.preventDefault();
            
            var image_frame;
            
            // MediaUploader インスタンスが既に存在する場合は再利用
            if (image_frame) {
                image_frame.open();
                return;
            }
            
            // MediaUploader の設定と作成
            image_frame = wp.media({
                title: 'スライド画像を選択',
                button: {
                    text: '画像を使用'
                },
                multiple: false
            });
            
            // 画像が選択されたときの処理
            image_frame.on('select', function() {
                var attachment = image_frame.state().get('selection').first().toJSON();
                $('#slide_image_id').val(attachment.id);
                
                // プレビュー更新
                $('#slide_image_preview img').attr('src', attachment.url);
                $('#slide_image_preview').show();
                $('#slide_image_remove').show();
            });
            
            // MediaUploader を開く
            image_frame.open();
        });
        
        // 画像削除ボタンのクリックイベント
        $('#slide_image_remove').click(function(e) {
            e.preventDefault();
            $('#slide_image_id').val('');
            $('#slide_image_preview').hide();
            $(this).hide();
        });
    });
    </script>
    <?php
}

// スライド設定を保存
function save_slider_meta($post_id) {
    // 自動保存の場合は処理しない
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    // nonce を確認
    if (!isset($_POST['slider_nonce']) || !wp_verify_nonce($_POST['slider_nonce'], basename(__FILE__))) return;
    
    // 権限を確認
    if (!current_user_can('edit_post', $post_id)) return;
    
    // スライド画像IDを保存
    if (isset($_POST['slide_image_id'])) {
        update_post_meta($post_id, 'slide_image_id', sanitize_text_field($_POST['slide_image_id']));
    }
    
    // スライドリンクを保存
    if (isset($_POST['slide_link'])) {
        update_post_meta($post_id, 'slide_link', esc_url_raw($_POST['slide_link']));
    }
}
add_action('save_post_slide', 'save_slider_meta');

// MediaUploader のスクリプトを読み込む
function slider_admin_scripts() {
    global $post_type;
    if ('slide' === $post_type) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'slider_admin_scripts');




// functions.phpに追加
function custom_wpmem_login_redirect($redirect_to, $user) {
    // 特定のページからのログインかどうかをチェック
    if (isset($_POST['is_franchise_login']) && $_POST['is_franchise_login'] === '1') {
        return 'https://testjc-fc.kphd-portal.net/job-list/';
    }
    return $redirect_to;
}
add_filter('wpmem_login_redirect', 'custom_wpmem_login_redirect', 10, 2);

/**
 * メルマガ関連機能の実装
 */

// メルマガ購読者一覧ページを管理メニューに追加
function add_mailmagazine_subscribers_menu() {
    add_menu_page(
        'メルマガ購読者一覧', // ページタイトル
        'メルマガリスト', // メニュータイトル
        'manage_options', // 権限
        'mailmagazine-subscribers', // メニュースラッグ
        'display_mailmagazine_subscribers', // 表示用の関数
        'dashicons-email-alt', // アイコン
        26 // 位置
    );
}
add_action('admin_menu', 'add_mailmagazine_subscribers_menu');

// メルマガ購読者一覧ページの表示
function display_mailmagazine_subscribers() {
    // 管理者権限チェック
    if (!current_user_can('manage_options')) {
        wp_die('アクセス権限がありません。');
    }
    
    // CSVエクスポート処理
if (isset($_POST['export_csv']) && isset($_POST['mailmagazine_export_nonce']) && 
    wp_verify_nonce($_POST['mailmagazine_export_nonce'], 'mailmagazine_export_action')) {
    
    // 出力バッファリングを無効化（既に開始されている場合は終了）
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // CSVのヘッダー設定
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mailmagazine_subscribers_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // BOMを出力（Excelでの文字化け対策）
    fputs($output, "\xEF\xBB\xBF");
    
    // ヘッダー行 - 都道府県と希望職種を含める
    fputcsv($output, array(
        '登録日', 
        '名前', 
        'メールアドレス',
        '都道府県',
        '希望職種'
    ));
    
    // 購読者を取得
    $subscribers = get_mailmagazine_subscribers();
    
    foreach ($subscribers as $user) {
        // 追加のユーザーメタデータを取得
        $prefecture = get_user_meta($user->ID, 'prefectures', true);
        $job_type = get_user_meta($user->ID, 'jobtype', true);
        
        fputcsv($output, array(
            date('Y/m/d', strtotime($user->user_registered)),
            $user->display_name,
            $user->user_email,
            $prefecture,
            $job_type
        ));
    }
    
    fclose($output);
    exit;
}
    
    // 購読者を取得
    $subscribers = get_mailmagazine_subscribers();
    $total_subscribers = count($subscribers);
    
    // 管理画面の表示
    ?>
    <div class="wrap">
        <h1>メルマガ購読者一覧</h1>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="post">
                    <?php wp_nonce_field('mailmagazine_export_action', 'mailmagazine_export_nonce'); ?>
                    <input type="submit" name="export_csv" class="button action" value="CSVでエクスポート">
                </form>
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo $total_subscribers; ?> 件の購読者</span>
            </div>
            <br class="clear">
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-registered">登録日</th>
                    <th scope="col" class="manage-column column-name">名前</th>
                    <th scope="col" class="manage-column column-email">メールアドレス</th>
                    <th scope="col" class="manage-column column-prefecture">都道府県</th>
                    <th scope="col" class="manage-column column-jobtype">希望職種</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($subscribers)) {
                    echo '<tr><td colspan="5">購読者はいません。</td></tr>';
                } else {
                    foreach ($subscribers as $user) {
                        $prefecture = get_user_meta($user->ID, 'prefectures', true);
                        $job_type = get_user_meta($user->ID, 'jobtype', true);
                        ?>
                        <tr>
                            <td><?php echo date('Y/m/d', strtotime($user->user_registered)); ?></td>
                            <td>
                                <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>">
                                    <?php echo $user->display_name; ?>
                                </a>
                            </td>
                            <td><?php echo $user->user_email; ?></td>
                            <td><?php echo esc_html($prefecture); ?></td>
                            <td><?php echo esc_html($job_type); ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-registered">登録日</th>
                    <th scope="col" class="manage-column column-name">名前</th>
                    <th scope="col" class="manage-column column-email">メールアドレス</th>
                    <th scope="col" class="manage-column column-prefecture">都道府県</th>
                    <th scope="col" class="manage-column column-jobtype">希望職種</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}

/**
 * 別の方法でCSVをダウンロードする専用のアクション
 */
function mailmagazine_download_csv_action() {
    // 管理者権限チェック
    if (!current_user_can('manage_options')) {
        wp_die('アクセス権限がありません。');
    }
    
    // nonceチェック
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'download_mailmagazine_csv')) {
        wp_die('セキュリティチェックに失敗しました。');
    }
    
    // 出力バッファリングを無効化
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // CSVのヘッダー設定
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mailmagazine_subscribers_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // BOMを出力（Excelでの文字化け対策）
    fputs($output, "\xEF\xBB\xBF");
    
    // ヘッダー行 - 都道府県と希望職種を含める
    fputcsv($output, array(
        '登録日', 
        '名前', 
        'メールアドレス',
        '都道府県',
        '希望職種'
    ));
    
    // 購読者を取得
    $subscribers = get_mailmagazine_subscribers();
    
    foreach ($subscribers as $user) {
        // 追加のユーザーメタデータを取得
        $prefecture = get_user_meta($user->ID, 'prefectures', true);
        $job_type = get_user_meta($user->ID, 'jobtype', true);
        
        fputcsv($output, array(
            date('Y/m/d', strtotime($user->user_registered)),
            $user->display_name,
            $user->user_email,
            $prefecture,
            $job_type
        ));
    }
    
    fclose($output);
    exit;
}
    
add_action('admin_post_download_mailmagazine_csv', 'mailmagazine_download_csv_action');

/**
 * メルマガを購読しているユーザーを取得する関数
 */
function get_mailmagazine_subscribers() {
    // ユーザークエリパラメータ
    $args = array(
        'meta_key'     => 'mailmagazine_preference',
        'meta_value'   => 'subscribe',
        'fields'       => array('ID', 'user_email', 'display_name', 'user_registered')
    );
    
    // クエリ実行
    $subscribers = get_users($args);
    
    return $subscribers;
}

/**
 * 新規ユーザー登録時にメルマガ設定のデフォルト値を設定
 */
function set_default_mailmagazine_preference($user_id) {
    // ユーザーロールを取得
    $user = get_userdata($user_id);
    
    if ($user && in_array('subscriber', (array) $user->roles)) {
        // subscriberロールのユーザーは「購読する」をデフォルトに設定
        add_user_meta($user_id, 'mailmagazine_preference', 'subscribe', true);
    } else {
        // その他のロールのユーザーは「購読しない」をデフォルトに設定
        add_user_meta($user_id, 'mailmagazine_preference', 'unsubscribe', true);
    }
}
add_action('user_register', 'set_default_mailmagazine_preference');

/**
 * ユーザープロフィール画面にメルマガ設定フィールドを追加
 */
function add_mailmagazine_preference_field($user) {
    // 現在の設定を取得
    $mailmagazine_preference = get_user_meta($user->ID, 'mailmagazine_preference', true);
    if (empty($mailmagazine_preference)) {
        $mailmagazine_preference = 'unsubscribe'; // デフォルト値
    }
    ?>
    <h3>メルマガ設定</h3>
    <table class="form-table">
        <tr>
            <th><label for="mailmagazine_preference">メルマガ購読</label></th>
            <td>
                <select name="mailmagazine_preference" id="mailmagazine_preference">
                    <option value="subscribe" <?php selected($mailmagazine_preference, 'subscribe'); ?>>購読する</option>
                    <option value="unsubscribe" <?php selected($mailmagazine_preference, 'unsubscribe'); ?>>購読しない</option>
                </select>
                <p class="description">メールマガジンの購読設定を選択してください。</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_mailmagazine_preference_field');
add_action('edit_user_profile', 'add_mailmagazine_preference_field');

/**
 * ユーザープロフィール更新時にメルマガ設定を保存
 */
function save_mailmagazine_preference_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    if (isset($_POST['mailmagazine_preference'])) {
        update_user_meta($user_id, 'mailmagazine_preference', sanitize_text_field($_POST['mailmagazine_preference']));
    }
}
add_action('personal_options_update', 'save_mailmagazine_preference_field');
add_action('edit_user_profile_update', 'save_mailmagazine_preference_field');

/**
 * ユーザーが加盟教室(agency)グループに所属しているかチェックする関数
 */
function is_agency_user() {
    // ユーザーがログインしているか確認
    if (!is_user_logged_in()) {
        return false;
    }
    
    // 現在のユーザー情報を取得
    $user = wp_get_current_user();
    
    // WordPress標準のロールで'agency'を持っているか確認
    return in_array('agency', (array) $user->roles);
}

/**
 * ヘッダーナビゲーションとページアクセスのリダイレクト処理
 */
function agency_user_redirect() {
    // agencyユーザーかどうかをチェック
    if (is_agency_user()) {
        global $wp;
        $current_url = home_url(add_query_arg(array(), $wp->request));
        
        // お気に入りページや会員ページへのアクセスを/job-list/にリダイレクト
        if (strpos($current_url, '/favorites') !== false || 
            strpos($current_url, '/members') !== false) {
            wp_redirect(home_url('/job-list/'));
            exit;
        }
    }
}
add_action('template_redirect', 'agency_user_redirect');

/**
 * ヘッダーリンク修正用のJavaScript
 */
function modify_header_links_for_agency() {
    if (is_agency_user()) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // お気に入りとマイページのリンクを/job-list/に変更
            $('.user-nav a[href*="/favorites"]').attr('href', '<?php echo home_url("/job-list/"); ?>');
            $('.user-nav a[href*="/members"]').attr('href', '<?php echo home_url("/job-list/"); ?>');
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'modify_header_links_for_agency');

/**
 * 特定のユーザーロールの管理画面アクセスを制限する
 */
function restrict_admin_access() {
    // 現在のユーザー情報を取得
    $user = wp_get_current_user();
    
    // agencyまたはsubscriberロールを持つユーザーの管理画面アクセスを制限
    if (
        !empty($user->ID) && 
        (in_array('agency', (array) $user->roles) || in_array('subscriber', (array) $user->roles))
    ) {
        // 現在のURLが管理画面かどうかを確認
        $screen = get_current_screen();
        
        // プロフィール編集画面は許可（オプション）
        if (is_admin() && (!isset($screen) || $screen->id !== 'profile')) {
            // agencyユーザーはジョブリストページへ、subscriberユーザーはホームページへリダイレクト
            if (in_array('agency', (array) $user->roles)) {
                wp_redirect(home_url('/job-list/'));
            } else {
                wp_redirect(home_url());
            }
            exit;
        }
    }
}
add_action('admin_init', 'restrict_admin_access');

/**
 * 管理バーを非表示にする
 */
function remove_admin_bar_for_specific_roles() {
    if (
        current_user_can('agency') || 
        current_user_can('subscriber')
    ) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'remove_admin_bar_for_specific_roles');

/**
 * ログイン時のリダイレクト処理
 */
function custom_login_redirect($redirect_to, $request, $user) {
    // ユーザーオブジェクトが有効かチェック
    if (isset($user->roles) && is_array($user->roles)) {
        // agencyユーザーはジョブリストページへリダイレクト
        if (in_array('agency', $user->roles)) {
            return home_url('/job-list/');
        }
        // subscriberユーザーはホームページへリダイレクト
        elseif (in_array('subscriber', $user->roles)) {
            return home_url();
        }
    }
    
    // その他のユーザーは通常のリダイレクト先へ
    return $redirect_to;
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);

/**
 * AJAX リクエストのアクセス制限を行わない（フロントエンドの機能を維持するため）
 */
function allow_ajax_requests_for_all_users() {
    // 現在のリクエストがAJAXリクエストの場合は制限をバイパス
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    // メディアアップロードなどの特定のリクエストも許可
    $allowed_actions = array(
        'upload-attachment',
        'async-upload',
    );
    
    if (isset($_GET['action']) && in_array($_GET['action'], $allowed_actions)) {
        return;
    }
    
    // 通常の管理画面アクセス制限を適用
    restrict_admin_access();
}
add_action('admin_init', 'allow_ajax_requests_for_all_users', 0);  // 優先度0で先に実行


/**
 * 加盟教室ユーザーのアカウント確認要件をバイパスする
 */
function bypass_account_confirmation_for_agency() {
    // WP-Membersが有効かつ関数が存在するか確認
    if (!function_exists('wpmem_is_user_confirmed')) {
        return;
    }
    
    // ログインフォームが送信された場合
    if (isset($_POST['log']) && isset($_POST['pwd'])) {
        // ユーザー名（メールアドレス）を取得
        $username = sanitize_user($_POST['log']);
        
        // ユーザー情報取得
        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }
        
        // ユーザーが存在し、agencyロールを持っている場合
        if ($user && in_array('agency', (array) $user->roles)) {
            // アカウント確認済みフラグを設定
            $confirmed_key = '_wpmem_user_confirmed';
            $is_confirmed = get_user_meta($user->ID, $confirmed_key, true);
            
            // まだ確認されていない場合は確認済みにする
            if (!$is_confirmed) {
                update_user_meta($user->ID, $confirmed_key, time());
            }
        }
    }
}
add_action('init', 'bypass_account_confirmation_for_agency', 5); // 優先度5で早めに実行

/**
 * エラーメッセージを変更またはフィルタリングする
 */
function filter_wpmem_login_errors($error) {
    // アカウント未確認エラーの場合
    if (strpos($error, 'Account not confirmed') !== false) {
        // 現在のページURLを取得
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        // 加盟教室ログインページかどうかを確認
        if (isset($_POST['is_franchise_login']) && $_POST['is_franchise_login'] === '1') {
            // エラーをキャンセルして空文字列を返す
            return '';
        }
    }
    
    return $error;
}
add_filter('wpmem_login_failed', 'filter_wpmem_login_errors', 10, 1);

/**
 * 加盟教室用ログインフォームにカスタムフィールドを追加
 */
function add_franchise_login_field() {
    // 加盟教室ログインページでのみ実行
    if (is_page('franchise-login') || strpos($_SERVER['REQUEST_URI'], '/franchise-login') !== false) {
        echo '<input type="hidden" name="is_franchise_login" value="1" />';
    }
}
add_action('wpmem_login_form_before_submit', 'add_franchise_login_field');

/**
 * 自動的にユーザーアカウントを確認済みに設定する（加盟教室のみ）
 */
function auto_confirm_agency_users($user_id) {
    // ユーザーが作成された直後に実行
    $user = get_userdata($user_id);
    
    // agencyロールのユーザーの場合
    if ($user && in_array('agency', (array) $user->roles)) {
        // アカウントを自動的に確認済みにする
        update_user_meta($user_id, '_wpmem_user_confirmed', time());
    }
}
add_action('user_register', 'auto_confirm_agency_users', 20); // 優先度20で他のフックの後に実行

/**
 * 加盟教室ユーザー（agencyロール）向けのパスワードリセットメール送信を抑制する
 */
function disable_password_reset_email_for_agency($allow, $user_id) {
    // ユーザー情報を取得
    $user = get_userdata($user_id);
    
    // agencyロールを持つユーザーの場合はメール送信を抑制
    if ($user && in_array('agency', (array) $user->roles)) {
        return false; // メール送信を許可しない
    }
    
    return $allow; // その他のユーザーは通常通り
}
add_filter('allow_password_reset', 'disable_password_reset_email_for_agency', 10, 2);

/**
 * 加盟教室ユーザー（agencyロール）のパスワードリセットページへのアクセスを制限
 */
function redirect_agency_password_reset() {
    // パスワードリセット関連のアクションをチェック
    if (isset($_GET['action']) && 
        ($_GET['action'] == 'lostpassword' || $_GET['action'] == 'rp' || $_GET['action'] == 'resetpass')) {
        
        // ログインしている場合はユーザー情報を確認
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            
            // agencyロールのユーザーはジョブリストページにリダイレクト
            if (in_array('agency', (array) $current_user->roles)) {
                wp_redirect(home_url('/job-list/'));
                exit;
            }
        } 
        // ログインしていない場合でも、URLからユーザー情報を確認
        else if (isset($_GET['login'])) {
            $user = get_user_by('login', urldecode($_GET['login']));
            if (!$user) {
                $user = get_user_by('email', urldecode($_GET['login']));
            }
            
            // agencyロールのユーザーの場合はフランチャイズログインページにリダイレクト
            if ($user && in_array('agency', (array) $user->roles)) {
                wp_redirect(home_url('/franchise-login/'));
                exit;
            }
        }
    }
}
add_action('login_init', 'redirect_agency_password_reset', 1);

/**
 * WP-Membersのパスワードリセットプロセスをカスタマイズ
 */
function custom_wpmem_pwd_reset_request($args) {
    // ユーザー名またはメールアドレスからユーザーを特定
    $user = (isset($args['user'])) ? get_user_by('login', $args['user']) : false;
    if (!$user) {
        $user = (isset($args['user'])) ? get_user_by('email', $args['user']) : false;
    }
    
    // ユーザーが存在し、agencyロールを持つ場合
    if ($user && in_array('agency', (array) $user->roles)) {
        // リクエストを中止し、フランチャイズログインページにリダイレクト
        wp_redirect(home_url('/franchise-login/?reset=blocked'));
        exit;
    }
    
    return $args; // それ以外のユーザーは通常通り
}
add_filter('wpmem_pwdreset_args', 'custom_wpmem_pwd_reset_request', 10, 1);

/**
 * WP-Membersのメール送信をフックで直接抑制する
 */
function block_wpmem_emails_for_agency($args) {
    // ユーザー名またはメールアドレスからユーザーを特定
    $user = false;
    
    // 新しいユーザー登録の場合
    if (isset($args['user_id'])) {
        $user = get_user_by('id', $args['user_id']);
    } 
    // パスワードリセットなどの場合
    elseif (isset($args['user_login'])) {
        $user = get_user_by('login', $args['user_login']);
    }
    elseif (isset($args['user'])) {
        $user = get_user_by('login', $args['user']);
        if (!$user) {
            $user = get_user_by('email', $args['user']);
        }
    }
    
    // メールアドレスで直接検索
    if (!$user && isset($args['user_email'])) {
        $user = get_user_by('email', $args['user_email']);
    }
    
    // ユーザーが特定できてagencyロールを持つ場合
    if ($user && in_array('agency', (array) $user->roles)) {
        // メール送信を中止
        add_filter('wp_mail', function() {
            return false;
        }, 999);
        
        // デバッグログに記録（必要に応じて）
        error_log('WP-Members email blocked for agency user: ' . $user->user_email);
        
        return false;
    }
    
    return $args;
}

// WP-Membersの各種フィルターにフックを追加
add_filter('wpmem_email_filter', 'block_wpmem_emails_for_agency', 1, 3);
add_filter('wpmem_email_args', 'block_wpmem_emails_for_agency', 1);
add_filter('wpmem_pwdreset_args', 'block_wpmem_emails_for_agency', 1);
add_filter('wpmem_notify_args', 'block_wpmem_emails_for_agency', 1);

/**
 * PHPMailerレベルでのフィルタリング
 */
function filter_phpmailer_for_agency($phpmailer) {
    // 送信先メールアドレスを取得
    $to = $phpmailer->getToAddresses();
    
    if (!empty($to)) {
        $to_email = $to[0][0]; // 最初の送信先アドレス
        
        // メールアドレスからユーザーを特定
        $user = get_user_by('email', $to_email);
        
        // agencyロールのユーザーの場合でパスワード関連のメールの場合
        if ($user && in_array('agency', (array) $user->roles)) {
            $subject = $phpmailer->Subject;
            
            // パスワードリセット関連の件名かチェック
            if (
                strpos($subject, 'パスワード') !== false ||
                strpos($subject, 'Password') !== false ||
                strpos($subject, 'リセット') !== false ||
                strpos($subject, 'Reset') !== false ||
                strpos($subject, '再設定') !== false
            ) {
                // メール送信を無効化
                $phpmailer->clearAllRecipients();
                error_log('PHPMailer: Password reset email blocked for agency user: ' . $to_email);
            }
        }
    }
}
add_action('phpmailer_init', 'filter_phpmailer_for_agency', 999);