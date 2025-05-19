<?php
/**
 * Template Name: マイページ
 *
 * @package WordPress
 * @subpackage Your_Theme_Name
 * @since Your_Theme_Version
 */

// メルマガ設定更新処理
if (isset($_POST['update_mailmagazine_settings']) && is_user_logged_in()) {
    if (!isset($_POST['mailmagazine_nonce_field']) || !wp_verify_nonce($_POST['mailmagazine_nonce_field'], 'mailmagazine_settings_action')) {
        // Nonceエラー処理
        wp_die('セキュリティチェックに失敗しました。');
    } else {
        $current_user_id = get_current_user_id();
        $preference = isset($_POST['mailmagazine_preference']) ? sanitize_text_field($_POST['mailmagazine_preference']) : 'unsubscribe';
        update_user_meta($current_user_id, 'mailmagazine_preference', $preference);
        
        // 更新完了メッセージ表示用フラグ
        $GLOBALS['mailmagazine_updated'] = true;
    }
}

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php if (is_user_logged_in()) : ?>
            <?php
            $current_user = wp_get_current_user();
            $current_user_id = $current_user->ID;
            
            // お名前（oname）を取得
            $user_oname = get_user_meta($current_user_id, 'oname', true);
            $display_name = !empty($user_oname) ? $user_oname : $current_user->display_name;
            ?>
            <div class="mypage-header">
                <h1><?php echo esc_html($display_name); ?>さんのマイページ</h1>
                <p class="welcome-message">ようこそ、<?php echo esc_html($display_name); ?>さん。</p>
            </div>

            <nav class="mypage-navigation">
                <ul>
                    <li class="<?php echo (isset($_GET['_tab']) && $_GET['_tab'] == 'profile-edit') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('_tab', 'profile-edit', get_permalink())); ?>">
                            <span class="nav-icon"><i class="fas fa-user"></i></span>プロフィール編集
                        </a>
                    </li>
                    <li class="<?php echo (isset($_GET['_tab']) && $_GET['_tab'] == 'password-reset') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('_tab', 'password-reset', get_permalink())); ?>">
                            <span class="nav-icon"><i class="fas fa-key"></i></span>パスワード再設定
                        </a>
                    </li>
                    <li class="<?php echo (isset($_GET['_tab']) && $_GET['_tab'] == 'mailmagazine') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('_tab', 'mailmagazine', get_permalink())); ?>">
                            <span class="nav-icon"><i class="fas fa-envelope"></i></span>メルマガ設定
                        </a>
                    </li>
                    <li class="<?php echo (isset($_GET['_tab']) && $_GET['_tab'] == 'withdrawal') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('_tab', 'withdrawal', get_permalink())); ?>">
                            <span class="nav-icon"><i class="fas fa-user-times"></i></span>退会手続き
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="mypage-content">
                <?php
                // 現在のタブを取得
                $current_tab = isset($_GET['_tab']) ? $_GET['_tab'] : '';
                
                // タブごとのコンテンツを表示
                switch ($current_tab) {
                    case 'profile-edit':
                        // プロフィール編集タブ
                        ?>
                        <section id="profile-edit" class="member-section">
                            <div class="section-header">
                                <h2><i class="fas fa-user"></i>プロフィール編集</h2>
                                <p>アカウント情報の変更ができます。</p>
                            </div>
                            
                            <?php
                            // 更新メッセージ
                            if (isset($_GET['updated']) && $_GET['updated'] == 'profile') {
                                echo '<div class="notice notice-success"><p>プロフィール情報を更新しました。</p></div>';
                            }
                            
                            // WP-Membersのユーザー編集フォーム（リダイレクト先をデフォルトタブに設定）
echo do_shortcode('[wpmem_form user_edit redirect_to="' . get_permalink() . '?updated=profile"]');
                            ?>
                        </section>
                        <?php
                        break;
                        
                    case 'password-reset':
                        // パスワード再設定タブ
                        ?>
                        <section id="password-reset" class="member-section">
                            <div class="section-header">
                                <h2><i class="fas fa-key"></i>パスワード再設定</h2>
                                <p>パスワードの再設定を行います。</p>
                            </div>
                            
                            <div class="password-reset-container">
                                <?php
                                // パスワードリセットメール送信処理
                                $message = '';
                                $message_type = '';
                                
                                if (isset($_POST['reset_password_submit']) && isset($_POST['user_login'])) {
                                    $user_login = sanitize_text_field($_POST['user_login']);
                                    
                                    if (empty($user_login)) {
                                        $message = 'メールアドレスを入力してください。';
                                        $message_type = 'error';
                                    } else {
                                        // ユーザー情報チェック
                                        $user_data = get_user_by('email', $user_login);
                                        
                                        if (!$user_data) {
                                            $message = '入力されたメールアドレスのユーザーが見つかりません。';
                                            $message_type = 'error';
                                        } else {
                                            // パスワードリセットメール送信
                                            $result = retrieve_password($user_data->user_login);
                                            
                                            if (is_wp_error($result)) {
                                                $message = $result->get_error_message();
                                                $message_type = 'error';
                                            } else {
                                                $message = 'パスワード再設定用のメールを送信しました。メールに記載されているリンクからパスワードの再設定を行ってください。';
                                                $message_type = 'success';
                                            }
                                        }
                                    }
                                }
                                
                                // メッセージ表示
                                if (!empty($message)) {
                                    echo '<div class="message message-' . $message_type . '">';
                                    echo '<p>' . esc_html($message) . '</p>';
                                    echo '</div>';
                                }
                                ?>
                                
                                <form method="post" action="<?php echo esc_url(add_query_arg('_tab', 'password-reset', get_permalink())); ?>" class="password-reset-form">
                                    <div class="form-group">
                                        <label for="user_login">登録メールアドレス</label>
                                        <input type="email" name="user_login" id="user_login" value="<?php echo esc_attr($current_user->user_email); ?>" class="input" required>
                                        <p class="description">パスワード再設定用のリンクを送信します。</p>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <input type="submit" name="reset_password_submit" value="パスワード再設定メールを送信" class="button">
                                    </div>
                                </form>
                                
                                <div class="password-reset-note">
                                    <h3>パスワード再設定の手順</h3>
                                    <ol>
                                        <li>登録メールアドレスを確認し、送信ボタンをクリックします。</li>
                                        <li>メールアドレス宛にパスワード再設定用のリンクが送信されます。</li>
                                        <li>メール内のリンクをクリックし、新しいパスワードを設定してください。</li>
                                    </ol>
                                </div>
                            </div>
                        </section>
                        <?php
                        break;
                        
                    case 'mailmagazine':
                        // メルマガ設定タブ
                        ?>
                        <section id="mailmagazine-settings" class="member-section">
                            <div class="section-header">
                                <h2><i class="fas fa-envelope"></i>メルマガ設定</h2>
                                <p>メールマガジンの購読設定を変更できます。</p>
                            </div>
                            
                            <?php
                            if (isset($GLOBALS['mailmagazine_updated']) && $GLOBALS['mailmagazine_updated']) {
                               echo '<div class="notice notice-success"><p>メルマガ設定を更新しました。</p></div>';
                            }
                            
                            $mailmagazine_preference = get_user_meta($current_user_id, 'mailmagazine_preference', true);
                            ?>
                            <form method="post" action="<?php echo esc_url(add_query_arg('_tab', 'mailmagazine', get_permalink())); ?>" class="mailmagazine-form">
                                <?php wp_nonce_field('mailmagazine_settings_action', 'mailmagazine_nonce_field'); ?>
                                <div class="radio-group">
                                    <label class="radio-container">
                                        <input type="radio" name="mailmagazine_preference" value="subscribe" <?php checked($mailmagazine_preference, 'subscribe'); ?>>
                                        <span class="radio-text">メルマガを購読する</span>
                                        <span class="radio-description">最新の求人情報やお役立ち情報をお届けします</span>
                                    </label>
                                </div>
                                <div class="radio-group">
                                    <label class="radio-container">
                                        <input type="radio" name="mailmagazine_preference" value="unsubscribe" <?php checked($mailmagazine_preference, 'unsubscribe'); checked(empty($mailmagazine_preference), true); ?>>
                                        <span class="radio-text">メルマガを購読しない</span>
                                        <span class="radio-description">アカウント関連のお知らせメールのみ受信します</span>
                                    </label>
                                </div>
                                <div class="form-actions">
                                    <input type="submit" name="update_mailmagazine_settings" value="設定を保存" class="button">
                                </div>
                            </form>
                        </section>
                        <?php
                        break;
                        
                    case 'withdrawal':
                        // 退会手続きタブ
                        ?>
                        <section id="withdrawal" class="member-section">
                            <div class="section-header">
                                <h2><i class="fas fa-user-times"></i>退会手続き</h2>
                                <p>アカウントを削除する場合は、以下の手順で退会手続きを行ってください。</p>
                            </div>
                            
                            <div class="withdrawal-notice">
                                <p><i class="fas fa-exclamation-triangle"></i> <strong>注意:</strong> 退会されますと、すべてのアカウント情報が完全に削除されます。一度退会すると、データの復旧はできません。</p>
                            </div>
                            
                            <!-- 退会処理フォーム -->
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="delete-account-form">
                                <?php wp_nonce_field('delete_account_action', 'delete_account_nonce'); ?>
                                <input type="hidden" name="action" value="delete_my_account">
                                
                                <div class="confirmation-check">
                                    <label>
                                        <input type="checkbox" name="confirm_deletion" required>
                                        上記内容を確認し、退会することに同意します。
                                    </label>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="button button-danger" onclick="return confirm('本当に退会してもよろしいですか？この操作は取り消すことができません。');">退会する</button>
                                </div>
                            </form>
                            
                            <p class="withdrawal-note">※退会手続き完了後、確認メールが送信されます。</p>
                        </section>
                        <?php
                        break;
                        
                    default:
                        // デフォルトの表示（レコメンド求人など）
                        ?>
                        <section id="recommended-jobs" class="member-section">
    <div class="section-header">
        <h2><i class="fas fa-briefcase"></i>あなたのエリア・職種に合った求人</h2>
    </div>
    
    <?php
    // ユーザーの登録情報からエリアと職種を取得
    $user_prefecture = get_user_meta($current_user_id, 'prefectures', true);
    $user_city = get_user_meta($current_user_id, 'municipalities', true); // 市区町村
    $user_job_type = get_user_meta($current_user_id, 'jobtype', true);

    if ($user_prefecture || $user_job_type) : // どちらか一方でも情報があれば検索を実行
        // 基本的なクエリ引数
        $args = array(
            'post_type'      => 'job', // 正しい投稿タイプ名
            'posts_per_page' => 5,
            'post_status'    => 'publish',
        );
        
        $tax_query = array();
        
        // エリア（都道府県または市区町村）で絞り込み
        if (!empty($user_prefecture)) {
            $location_query = array(
                'taxonomy' => 'job_location', // 正しいタクソノミー名
                'field'    => 'name',
                'terms'    => array($user_prefecture),
            );
            
            // 市区町村があれば、それも条件に含める
            if (!empty($user_city)) {
                $location_query['terms'][] = $user_city;
                // OR条件で検索（都道府県または市区町村に一致）
                $location_query['operator'] = 'IN';
            }
            
            $tax_query[] = $location_query;
        }
        
        // 職種で絞り込み
        if (!empty($user_job_type)) {
            $tax_query[] = array(
                'taxonomy' => 'job_position', // 正しいタクソノミー名
                'field'    => 'name',
                'terms'    => $user_job_type,
            );
        }
        
        // 複数のタクソノミー条件がある場合はANDで結合
        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }
        
        // tax_queryを設定
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        $recommended_jobs_query = new WP_Query($args);

        if ($recommended_jobs_query->have_posts()) :
            ?>
            <div class="job-cards-container">
                <?php while ($recommended_jobs_query->have_posts()) : $recommended_jobs_query->the_post(); 
                    // カスタムフィールドデータの取得
                    $facility_name = get_post_meta(get_the_ID(), 'facility_name', true);
                    $facility_company = get_post_meta(get_the_ID(), 'facility_company', true);
                    $job_content_title = get_post_meta(get_the_ID(), 'job_content_title', true);
                    $salary_range = get_post_meta(get_the_ID(), 'salary_range', true);
                    $salary_type = get_post_meta(get_the_ID(), 'salary_type', true);
                    $facility_address = get_post_meta(get_the_ID(), 'facility_address', true);
                    
                    // タクソノミーの取得
                    $facility_types = get_the_terms(get_the_ID(), 'facility_type');
                    $job_features = get_the_terms(get_the_ID(), 'job_feature');
                    $job_types = get_the_terms(get_the_ID(), 'job_type');
                    $job_positions = get_the_terms(get_the_ID(), 'job_position');
                    
                    // 施設形態のチェック
                    $has_jidou = false;    // 児童発達支援フラグ
                    $has_houkago = false;  // 放課後等デイサービスフラグ

                    if ($facility_types && !is_wp_error($facility_types)) {
                        foreach ($facility_types as $type) {
                            // 組み合わせタイプのチェック
                            if ($type->slug === 'jidou-houkago') {
                                // 児童発達支援・放課後等デイの場合は両方表示
                                $has_jidou = true;
                                $has_houkago = true;
                            } 
                            // 児童発達支援のみのチェック
                            else if ($type->slug === 'jidou') {
                                $has_jidou = true;
                            } 
                            // 放課後等デイサービスのみのチェック
                            else if ($type->slug === 'houkago') {
                                $has_houkago = true;
                            }
                            
                            // 従来の拡張スラッグもサポート
                            else if (in_array($type->slug, ['jidou-hattatsu', 'jidou-hattatsu-shien', 'child-development-support'])) {
                                $has_jidou = true;
                            }
                            else if (in_array($type->slug, ['houkago-day', 'houkago-dayservice', 'after-school-day-service'])) {
                                $has_houkago = true;
                            }
                        }
                    }
                    
                    // 雇用形態に基づくカラークラスを設定
$employment_color_class = 'other'; // デフォルトはその他
if ($job_types && !is_wp_error($job_types)) {
    // スラッグによる判定
    $job_type_slug = $job_types[0]->slug;
    $job_type_name = $job_types[0]->name;
    
    // スラッグベースでの判定
    switch($job_type_slug) {
        case 'full-time':
        case 'seishain': // 正社員
            $employment_color_class = 'full-time';
            break;
        case 'part-time':
        case 'part':
        case 'arubaito': // パート・アルバイト
            $employment_color_class = 'part-time';
            break;
        default:
            // スラッグで判定できない場合は名前で判定
            if ($job_type_name === '正社員') {
                $employment_color_class = 'full-time';
            } else if ($job_type_name === 'パート・アルバイト' || 
                      strpos($job_type_name, 'パート') !== false || 
                      strpos($job_type_name, 'アルバイト') !== false) {
                $employment_color_class = 'part-time';
            } else {
                $employment_color_class = 'other';
            }
            break;
    }
}
                ?>
                
                <div class="job-card">
                    <!-- 上部コンテンツ：左右に分割 -->
                    <div class="job-content">
                        <!-- 左側：サムネイル画像、施設形態アイコン、特徴タグ -->
                        <div class="left-content">
                            <!-- サムネイル画像 -->
                            <div class="job-image">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/300x200" alt="<?php echo esc_attr($facility_name); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <!-- 施設形態を画像アイコン -->
                            <div class="facility-icons">
                                <?php if ($has_houkago): ?>
                                <!-- 放デイアイコン -->
                                <div class="facility-icon">
                                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/day.png" alt="放デイ">
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($has_jidou): ?>
                                <!-- 児発支援アイコン -->
                                <div class="facility-icon red-icon">
                                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/img/support.png" alt="児発支援">
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 特徴タクソノミータグ - 3つまで表示 -->
                            <?php if ($job_features && !is_wp_error($job_features)): ?>
                            <div class="tags-container">
                                <?php 
                                $features_count = 0;
                                foreach ($job_features as $feature):
                                    if ($features_count < 3):
                                        // プレミアム特徴の判定（例：高収入求人など）
                                        $premium_class = (in_array($feature->slug, ['high-salary', 'bonus-available'])) ? 'premium' : '';
                                ?>
                                    <span class="tag <?php echo $premium_class; ?>"><?php echo esc_html($feature->name); ?></span>
                                <?php
                                        $features_count++;
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 右側：運営会社名、施設名、本文詳細 -->
                        <div class="right-content">
                            <!-- 会社名と雇用形態を横に並べる -->
                            <div class="company-section">
                                <span class="company-name"><?php echo esc_html($facility_company); ?></span>
                                <?php if ($job_types && !is_wp_error($job_types)): ?>
                                <div class="employment-type <?php echo $employment_color_class; ?>">
                                    <?php echo esc_html($job_types[0]->name); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- 施設名を会社名の下に配置 -->
                            <h1 class="job-title"><?php echo esc_html($facility_name); ?></h1>
                            
                            <h2 class="job-subtitle"><?php echo esc_html($job_content_title); ?></h2>
                            
                            <p class="job-description">
                                <?php echo wp_trim_words(get_the_content(), 40, '...'); ?>
                            </p>
                            
                            <!-- 本文の下に区切り線を追加 -->
                            <div class="divider"></div>
                            
                            <!-- 職種、給料、住所情報 -->
                            <div class="job-info">
                                <?php if ($job_positions && !is_wp_error($job_positions)): ?>
                                <div class="info-item">
                                    <span class="info-icon"><i class="fa-solid fa-user"></i></span>
                                    <span><?php echo esc_html($job_positions[0]->name); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <span class="info-icon"><i class="fa-solid fa-money-bill-wave"></i></span>
                                    <span>
                                        <?php 
                                            // 賃金形態の表示（月給/時給）
                                            if ($salary_type === 'MONTH') {
                                                echo '月給 ';
                                            } elseif ($salary_type === 'HOUR') {
                                                echo '時給 ';
                                            }
                                            
                                            echo esc_html($salary_range);
                                            
                                            // 円表示がなければ追加
                                            if (mb_strpos($salary_range, '円') === false) {
                                                echo '円';
                                            }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-icon"><i class="fa-solid fa-location-dot"></i></span>
                                    <span><?php echo esc_html($facility_address); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 区切り線 -->
                    <div class="divider"></div>
                    
                    <!-- ボタンエリア -->
                    <div class="buttons-container">
                        <?php if (is_user_logged_in()): 
                            // お気に入り状態の確認
                            $user_id = get_current_user_id();
                            $favorites = get_user_meta($user_id, 'user_favorites', true); 
                            $is_favorite = is_array($favorites) && in_array(get_the_ID(), $favorites);
                        ?>
                            <button class="keep-button <?php echo $is_favorite ? 'kept' : ''; ?>" data-job-id="<?php echo get_the_ID(); ?>">
                                <span class="star"><i class="fa-solid fa-star"></i></span>
                                <?php echo $is_favorite ? 'キープ済み' : 'キープ'; ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="keep-button">
                                <span class="star"><i class="fa-solid fa-star"></i></span>キープ
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php the_permalink(); ?>" class="detail-view-button">詳細をみる</a>
                    </div>
                </div>
                <?php endwhile; ?>
                
                <div class="more-link">
                    <a href="<?php echo esc_url(home_url('/jobs/')); ?>" class="button">すべての求人を見る</a>
                </div>
            </div>
            <?php
            wp_reset_postdata();
        else :
            echo '<div class="no-jobs"><p>現在、あなたの希望条件に合致する求人は見つかりませんでした。</p></div>';
        endif; // $recommended_jobs_query->have_posts()
    else :
        echo '<div class="no-profile-info">';
        echo '<p>希望エリアと職種の登録がありません。プロフィール編集から設定してください。</p>';
        echo '</div>';
    endif; // $user_prefecture || $user_job_type
    ?>
</section>

<!-- 求人カード用のスタイルをページに追加 -->
<style>
/* 雇用形態バッジの基本スタイル */
.employment-type {
    padding: 6px 15px;
    border-radius: 30px;
    font-size: 14px;
    margin-left: auto;
    display: inline-block;
    font-weight: 500;
    text-align: center;
}

/* フルタイム */
.employment-type.full-time {
    background-color: #C5E5FF;
    color: #5599FF;
}

/* パートタイム */
.employment-type.part-time {
    background-color: #FFD5F9;
    color: #F58284;
}

/* その他の雇用形態 */
.employment-type.other {
    background-color: #D5FFEC;
    color: #0FB96A;
}


/* 施設アイコン関連スタイル */
.facility-icons {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    margin-bottom: 10px;
}
.facility-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    background-color: #fff;
}
.facility-icon.red-icon {
    border-color: #FF5252;
}
.facility-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
/* 施設アイコンのテキストを非表示 */
.facility-icon span {
    display: none;
}

/* 求人カードのスタイル */
.job-card {
    background-color: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 1000px;
    overflow: hidden;
    padding: 20px;
    margin-bottom: 30px;
}

.job-content {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

/* 左側のスタイル */
.left-content {
    width: 30%;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.job-image {
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
}

.job-image img {
    width: 100%;
    height: auto;
    object-fit: cover;
}

.tags-container {
    display: flex;
    flex-wrap: nowrap;
    gap: 5px;
    justify-content: flex-start;
    width: 100%;
}

.tag {
    background-color: #fff;
    border: 1px solid #FFB74D;
    color: #FF9800;
    padding: 3px 5px;
    border-radius: 20px;
    font-size: 10px;
    white-space: nowrap;
    flex: 1;
    text-align: center;
}

.tag.premium {
    background-color: #fff;
    border: 1px solid #FFA000;
    color: #FFA000;
}

/* 右側のスタイル */
.right-content {
    width: 70%;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.company-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.company-name {
    color: #666;
    font-size: 14px;
    text-align: left;
    margin-left: 0;
    padding-left: 0;
}

.job-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 10px;
}

.job-subtitle {
    font-size: 16px;
    margin-bottom: 10px;
}

.job-description {
    font-size: 14px;
    color: #333;
    line-height: 1.6;
}

/* 区切り線 */
.divider {
    height: 1px;
    background-color: #eee;
    margin: 15px 0;
}

.job-info {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-icon {
    width: 20px;
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ボタンエリア */
.buttons-container {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.keep-button {
    background-color: #fff;
    border: 1px solid #FFB74D;
    color: #FF9800;
    padding: 15px 20px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    width: 45%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
}

.keep-button .star {
    color: #FFB74D;
    margin-right: 10px;
}

.keep-button.kept {
    background-color: #FFF8E1;
}

.detail-view-button {
    background-color: #26A69A;
    border: none;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    width: 45%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .job-content {
        flex-direction: column;
    }
    
    .left-content, .right-content {
        width: 100%;
    }
    
    .buttons-container {
        flex-direction: column;
        gap: 10px;
    }
    
    .keep-button, .detail-view-button {
        width: 100%;
    }
}

/* マイページでの追加スタイル */
.more-link {
    text-align: center;
    margin-top: 20px;
    margin-bottom: 10px;
}

.no-jobs, .no-profile-info {
    background-color: #f8f9fa;
    padding: 15px;
    text-align: center;
    border-radius: 5px;
    color: #666;
}
</style>

<!-- キープボタン用JavaScript -->
<script>
jQuery(document).ready(function($) {
    // キープボタン機能
    $('.keep-button').on('click', function() {
        // リンクでない場合のみ処理（ログイン済みユーザー用）
        if (!$(this).attr('href')) {
            var jobId = $(this).data('job-id');
            var $button = $(this);
            
            // AJAXでキープ状態を切り替え
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'toggle_job_favorite',
                    job_id: jobId,
                    nonce: '<?php echo wp_create_nonce('job_favorite_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.status === 'added') {
                            $button.addClass('kept');
                            $button.html('<span class="star"><i class="fa-solid fa-star"></i></span> キープ済み');
                        } else {
                            $button.removeClass('kept');
                            $button.html('<span class="star"><i class="fa-solid fa-star"></i></span> キープ');
                        }
                    }
                }
            });
        }
    });
});
</script>
                        <?php
                        break;
                }
                ?>
            </div>
            
            <!-- ログアウトボタン（マイページの下部に配置） -->
            <div class="logout-section">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-button">
                    <i class="fas fa-sign-out-alt"></i> ログアウト
                </a>
            </div>

        <?php else : ?>
            <h1>ログイン</h1>
            <p>マイページをご利用いただくにはログインが必要です。</p>
            <?php echo do_shortcode('[wpmem_form login]'); ?>
            <p><a href="<?php echo esc_url(home_url('/password-reset/')); ?>">パスワードをお忘れですか？</a></p>
            <p>アカウントをお持ちでない方は<a href="<?php echo esc_url(home_url('/register/')); ?>">こちらで新規登録</a></p>
        <?php endif; ?>

    </main>
</div>

<style>
/* マイページ全体のスタイル */
.site-main {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

/* ヘッダー部分 */
.mypage-header {
    text-align: center;
    margin: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.mypage-header h1 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: #333;
}

.welcome-message {
    font-size: 1rem;
    color: #666;
}

/* ナビゲーション */
.mypage-navigation {
    margin-bottom: 30px;
}

.mypage-navigation ul {
    display: flex;
    justify-content: center;
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
    gap: 5px;
}

.mypage-navigation li {
    margin: 0;
    flex: 1;
    min-width: 200px;
    max-width: 200px;
}

.mypage-navigation a {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 15px;
    text-decoration: none;
    color: #555;
    background-color: #f5f5f5;
    border-radius: 5px;
    transition: all 0.3s ease;
    height: 100%;
    text-align: center;
}

#wpmem_login fieldset,
#wpmem_reg fieldset {
    margin: 0  auto!important;
}


	
.mypage-navigation li.active a {
    background-color: #1DD1B0;
    color: white;
}

.mypage-navigation a:hover {
    background-color: #e9e9e9;
}

.mypage-navigation li.active a:hover {
    background-color: #1DD1B0;
}

.nav-icon {
    margin-right: 8px;
    display: inline-block;
}

/* コンテンツ部分 */
.mypage-content {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 25px;
    margin-bottom: 30px;
}

.member-section {
    margin-bottom: 20px;
}

.section-header {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.section-header h2 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
}

.section-header h2 i {
    margin-right: 10px;
    color: #0073aa;
}

.section-header p {
    color: #666;
    margin: 0;
}

/* フォーム要素 */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
}

.input, input[type="text"], input[type="email"], input[type="password"], textarea, select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.description {
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

.form-actions {
    margin-top: 20px;
}

/* ボタンスタイル */
.button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    text-decoration: none;
    transition: background-color 0.2s;
}

.button:hover {
    background-color: #005f8a;
    color: white;
}

.button-secondary {
    background-color: #f7f7f7;
    color: #555;
    border: 1px solid #ccc;
}

.button-secondary:hover {
    background-color: #eaeaea;
    color: #333;
}

.button-danger {
    background-color: #dc3545;
}

.button-danger:hover {
    background-color: #c82333;
}

/* ログアウトボタン */
.logout-section {
    text-align: center;
    margin-top: 20px;
    margin-bottom: 40px;
}

.logout-button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #f7f7f7;
    color: #555;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.logout-button:hover {
    background-color: #eaeaea;
    color: #333;
}

/* メッセージスタイル */
.notice, .message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.notice-success, .message-success {
    background-color: #d4edda;
    border-left: 4px solid #28a745;
    color: #155724;
}

.message-error {
    background-color: #f8d7da;
    border-left: 4px solid #f5c6cb;
    color: #721c24;
}

/* ラジオボタングループ */
.radio-group {
    margin-bottom: 15px;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.radio-group:hover {
    background-color: #f9f9f9;
}

.radio-container {
    display: flex;
    cursor: pointer;
}

.radio-text {
    font-weight: bold;
    margin-left: 5px;
}

.radio-description {
    margin-left: 25px;
    font-size: 0.9rem;
    color: #666;
    margin-top: 5px;
}

/* パスワードリセット */
.password-reset-container {
    max-width: 500px;
    margin: 0 auto;
}

.password-reset-form {
    margin-bottom: 20px;
}

.password-reset-note {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #0073aa;
    margin-top: 20px;
}

.password-reset-note h3 {
    font-size: 1.1rem;
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.password-reset-note ol {
    margin-left: 20px;
    padding-left: 0;
}

.password-reset-note li {
    margin-bottom: 8px;
}

/* 退会関連 */
.withdrawal-notice {
    background-color: #fff4f4;
    border-left: 4px solid #dc3545;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.confirmation-check {
    margin: 20px 0;
}

.withdrawal-note {
    font-size: 0.9rem;
    color: #666;
    margin-top: 15px;
    text-align: center;
}

/* 求人リスト */
.job-list-container {
    margin-top: 20px;
}

.job-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.job-item {
    margin-bottom: 15px;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.job-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.job-link {
    display: block;
    padding: 15px;
    text-decoration: none;
    color: #333;
    background-color: #fff;
    border-left: 4px solid #0073aa;
}

.job-title {
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 1.1rem;
}

.job-meta {
    font-size: 0.9rem;
    color: #666;
}

.location {
    display: inline-flex;
    align-items: center;
}

.location i {
    margin-right: 5px;
    color: #0073aa;
}

.more-link {
    text-align: center;
    margin-top: 20px;
    margin-bottom: 10px;
}

.no-jobs, .no-profile-info {
    background-color: #f8f9fa;
    padding: 15px;
    text-align: center;
    border-radius: 5px;
    color: #666;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .mypage-navigation ul {
        flex-direction: column;
    }
    
    .mypage-navigation li {
        width: 100%;
        max-width: 100%;
    }
    
    .form-actions {
        display: flex;
        flex-direction: column;
    }
    
    .form-actions .button {
        width: 100%;
        margin-bottom: 10px;
        text-align: center;
    }
}
</style>

<?php 
// Font Awesome を読み込む
if (!wp_script_is('font-awesome', 'enqueued')) {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
}
get_sidebar(); ?>
<?php get_footer(); ?>