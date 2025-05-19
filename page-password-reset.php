<?php
/**
 * パスワード再設定ページのテンプレート
 * Template Name: パスワード再設定ページ
 */

// メール送信処理
$message = '';
$message_type = '';

if (isset($_POST['user_login']) && !empty($_POST['user_login'])) {
    $user_login = sanitize_text_field($_POST['user_login']);
    
    if (is_email($user_login)) {
        // メールアドレスの場合
        $user_data = get_user_by('email', $user_login);
        if (!$user_data) {
            $message = '入力されたメールアドレスのユーザーが見つかりません。';
            $message_type = 'error';
        }
    } else {
        // ユーザー名の場合
        $user_data = get_user_by('login', $user_login);
        if (!$user_data) {
            $message = '入力されたユーザー名のユーザーが見つかりません。';
            $message_type = 'error';
        }
    }
    
    // ユーザーが見つかった場合はパスワードリセットメールを送信
    if ($user_data) {
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

// パスワードリセットメールの内容をカスタマイズするためのフィルター
if (!has_filter('retrieve_password_message')) {
    add_filter('retrieve_password_message', 'custom_password_reset_mail', 10, 4);
    add_filter('retrieve_password_title', 'custom_password_reset_mail_title', 10, 1);

    function custom_password_reset_mail_title($title) {
        $site_name = get_bloginfo('name');
        return '[' . $site_name . '] パスワード再設定のご案内';
    }

    function custom_password_reset_mail($message, $key, $user_login, $user_data) {
        $site_name = get_bloginfo('name');
        
        // 標準のリセットURL
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
        
        // メール本文の作成
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
}

// パスワード再設定ページのスタイルをカスタマイズするためのアクション
if (!has_action('login_enqueue_scripts', 'custom_password_reset_styles')) {
    add_action('login_enqueue_scripts', 'custom_password_reset_styles');

    function custom_password_reset_styles() {
        // パスワードリセット画面でのみスタイルを適用
        if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], array('rp', 'resetpass'))) {
            ?>
            <style type="text/css">
                body.login {
                    background-color: #f8f9fa;
                    font-family: 'Helvetica Neue', Arial, sans-serif;
                }
                
                #login h1 a {
                    display: none; /* WordPressロゴを非表示 */
                }
                
                #login {
                    width: 400px;
                    padding: 8% 0 0;
                    margin: auto;
                }
                
                .login form {
                    margin-top: 20px;
                    padding: 26px 24px 34px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    background: #fff;
                }
                
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
                }
                
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
                }
                
                .login .button-primary:hover {
                    background-color: #005f8a;
                    border-color: #005f8a;
                }
                
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
                
                /* カスタムコンテンツ追加 */
                #login:before {
                    content: "パスワード再設定";
                    display: block;
                    font-size: 24px;
                    font-weight: bold;
                    text-align: center;
                    color: #333;
                    margin-bottom: 20px;
                }
                
                .login form:after {
                    content: "※このフォームで新しいパスワードを設定してください。";
                    display: block;
                    font-size: 13px;
                    text-align: center;
                    color: #666;
                    margin-top: 15px;
                }
                
                /* エラーメッセージのスタイル */
                .login #login_error {
                    background-color: #f8d7da;
                    border-left-color: #f5c6cb;
                    color: #721c24;
                    border-radius: 4px;
                }
                
                /* 成功メッセージのスタイル */
                .login .message {
                    background-color: #d4edda;
                    border-left-color: #c3e6cb;
                    color: #155724;
                    border-radius: 4px;
                }
            </style>
            
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    // タイトルの変更
                    document.title = '<?php echo esc_js(get_bloginfo('name')); ?> - パスワード再設定';
                    
                    // パスワード強度メーターを追加
                    var pass1Field = document.getElementById('pass1');
                    if (pass1Field) {
                        var meterContainer = document.createElement('div');
                        meterContainer.className = 'password-strength-meter';
                        meterContainer.innerHTML = '<div class="meter"><div class="meter-bar"></div></div><span id="password-strength">弱い</span>';
                        
                        pass1Field.parentNode.insertBefore(meterContainer, pass1Field.nextSibling);
                        
                        // メーターのスタイル
                        var style = document.createElement('style');
                        style.textContent = `
                            .password-strength-meter {
                                margin: 5px 0 15px;
                            }
                            .meter {
                                height: 4px;
                                background-color: #f1f1f1;
                                margin-bottom: 5px;
                            }
                            .meter-bar {
                                height: 100%;
                                width: 0;
                                background-color: #dc3545;
                                transition: width 0.3s, background-color 0.3s;
                            }
                            #password-strength {
                                font-size: 12px;
                                color: #666;
                            }
                        `;
                        document.head.appendChild(style);
                        
                        // パスワード強度の評価
                        pass1Field.addEventListener('input', function() {
                            var value = this.value;
                            var strength = 0;
                            var meterBar = document.querySelector('.meter-bar');
                            var strengthText = document.getElementById('password-strength');
                            
                            // 長さのチェック
                            if (value.length >= 8) strength += 1;
                            
                            // 文字種のチェック
                            if (value.match(/[a-z]/)) strength += 1;
                            if (value.match(/[A-Z]/)) strength += 1;
                            if (value.match(/[0-9]/)) strength += 1;
                            if (value.match(/[^a-zA-Z0-9]/)) strength += 1;
                            
                            // 強度に応じてメーターを更新
                            var strengthPercent = (strength / 5) * 100;
                            meterBar.style.width = strengthPercent + '%';
                            
                            // 強度に応じて色を変更
                            if (strength < 2) {
                                meterBar.style.backgroundColor = '#dc3545'; // 赤
                                strengthText.textContent = '弱い';
                                strengthText.style.color = '#dc3545';
                            } else if (strength < 4) {
                                meterBar.style.backgroundColor = '#ffc107'; // 黄色
                                strengthText.textContent = '普通';
                                strengthText.style.color = '#ffc107';
                            } else {
                                meterBar.style.backgroundColor = '#28a745'; // 緑
                                strengthText.textContent = '強い';
                                strengthText.style.color = '#28a745';
                            }
                        });
                    }
                    
                    // フォームの見出しをカスタマイズ
                    var formTitle = document.querySelector('.login form p:first-child');
                    if (formTitle && formTitle.textContent.includes('Enter your new password below')) {
                        formTitle.innerHTML = '<strong>新しいパスワードを設定してください</strong>';
                    }
                    
                    // パスワードのラベルをカスタマイズ
                    var labels = document.querySelectorAll('.login label');
                    for (var i = 0; i < labels.length; i++) {
                        if (labels[i].innerHTML.includes('New password')) {
                            labels[i].innerHTML = '新しいパスワード';
                        } else if (labels[i].innerHTML.includes('Confirm new password')) {
                            labels[i].innerHTML = '新しいパスワード（確認）';
                        }
                    }
                    
                    // ボタンのテキストをカスタマイズ
                    var submitButton = document.querySelector('.login .button-primary');
                    if (submitButton && submitButton.value === 'Reset Password') {
                        submitButton.value = 'パスワードを変更する';
                    }
                    
                    // ナビゲーションリンクのカスタマイズ
                    var navLinks = document.querySelectorAll('#nav a, #backtoblog a');
                    for (var i = 0; i < navLinks.length; i++) {
                        if (navLinks[i].textContent.includes('Log in')) {
                            navLinks[i].textContent = 'ログインページに戻る';
                        } else if (navLinks[i].textContent.includes('Back to')) {
                            navLinks[i].textContent = 'サイトトップに戻る';
                        }
                    }
                });
            </script>
            <?php
        }
    }
}

// リダイレクト先のカスタマイズ
if (!has_action('login_form_resetpass', 'redirect_after_password_reset')) {
    // パスワード変更完了後にリダイレクト先をカスタマイズ
    add_action('login_form_resetpass', 'redirect_after_password_reset');

    function redirect_after_password_reset() {
        // フォーム送信時
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            add_filter('wp_redirect', 'custom_resetpass_redirect', 10, 2);
        }
    }

    function custom_resetpass_redirect($location, $status) {
        // デフォルトのリダイレクト先チェック
        if (strpos($location, 'password=changed') !== false) {
            // パスワード変更成功後、ログインページにリダイレクト
            return home_url('/login/?reset=success');
        }
        
        return $location;
    }
}

get_header();
?>

<main class="password-reset-page">
    <div class="content">
        <div class="auth-container">
            <div class="auth-header">
                <h1 class="auth-title">パスワード再設定</h1>
                <p class="auth-description">登録済みのメールアドレスを入力してください。パスワード再設定用のリンクをメールでお送りします。</p>
            </div>
            
            <div class="auth-form">
                <?php if (!empty($message)) : ?>
                    <div class="message message-<?php echo $message_type; ?>">
                        <p><?php echo esc_html($message); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (is_user_logged_in()) : ?>
                    <?php 
                    $current_user = wp_get_current_user();
                    ?>
                    <div class="already-logged-in">
                        <p>こんにちは、<strong><?php echo esc_html($current_user->display_name); ?></strong> さん</p>
                        <p>現在ログイン中です。パスワードを変更するには、マイページの「パスワード変更」から行ってください。</p>
                        <p>
                            <a href="<?php echo esc_url(home_url('/members/?_tab=password-change')); ?>" class="button">パスワード変更へ</a>
                            <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="button button-secondary">ログアウト</a>
                        </p>
                    </div>
                <?php else : ?>
                    <!-- パスワードリセットフォーム -->
                    <form method="post" action="<?php echo esc_url(get_permalink()); ?>" id="lostpasswordform">
                        <div class="form-group">
                            <label for="user_login">メールアドレス</label>
                            <input type="email" name="user_login" id="user_login" class="input" value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>" size="20" autocapitalize="off" autocomplete="username" required />
                        </div>
                        
                        <?php do_action('lostpassword_form'); ?>
                        
                        <p class="submit">
                            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="パスワード再設定メールを送信" />
                        </p>
                    </form>
                    
                    <div class="notification">
                        <p><strong>※ パスワード再設定の手順</strong></p>
                        <ol>
                            <li>登録済みのメールアドレスを入力し、送信ボタンをクリックします。</li>
                            <li>入力したメールアドレス宛にパスワード再設定用のリンクが送信されます。</li>
                            <li>メール内のリンクをクリックし、新しいパスワードを設定してください。</li>
                        </ol>
                    </div>
                    
                    <div class="troubleshooting">
                        <h3>メールが届かない場合</h3>
                        <ul>
                            <li>迷惑メールフォルダをご確認ください。</li>
                            <li>登録時とは異なるメールアドレスを入力している可能性があります。</li>
                            <li>それでも解決しない場合は、<a href="<?php echo esc_url(home_url('/contact/')); ?>">お問い合わせ</a>ください。</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="auth-footer">
                <p><a href="<?php echo esc_url(home_url('/login/')); ?>" class="login-link">ログインページに戻る</a></p>
                <p>アカウントをお持ちでない方は<a href="<?php echo esc_url(home_url('/register/')); ?>" class="register-link">こちらで新規登録</a></p>
            </div>
        </div>
    </div>
</main>

<style>
/* パスワードリセットページのスタイル */
.password-reset-page {
    padding: 2rem 0;
}

.content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

.auth-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    background-color: #fff;
}

.auth-header {
    margin-bottom: 2rem;
    text-align: center;
}

.auth-title {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.auth-description {
    color: #666;
}

.auth-form {
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.auth-form input[type="text"],
.auth-form input[type="email"],
.auth-form input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.auth-form input[type="submit"],
.button {
    display: inline-block;
    padding: 10px 20px;
    background-color: #0073aa;
    color: white !important;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    text-decoration: none !important;
    transition: background-color 0.2s;
}

.auth-form input[type="submit"]:hover,
.button:hover {
    background-color: #005f8a;
    color: white !important;
}

.button-secondary {
    background-color: #f7f7f7 !important;
    color: #555 !important;
    border: 1px solid #ccc;
}

.button-secondary:hover {
    background-color: #eaeaea !important;
    color: #333 !important;
}

.button-primary {
    width: 100%;
}

.auth-footer {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
    text-align: center;
}

.auth-footer a {
    color: #0073aa;
    text-decoration: none;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.already-logged-in {
    text-align: center;
    padding: 20px;
    background-color: #f7f7f7;
    border-radius: 4px;
    margin-bottom: 20px;
}

.notification {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-left: 4px solid #0073aa;
}

.troubleshooting {
    margin-top: 20px;
    padding: 15px;
    background-color: #fff8e1;
    border-left: 4px solid #ffb300;
}

.troubleshooting h3 {
    margin-top: 0;
    font-size: 1.1rem;
    color: #775500;
}

.notification ol,
.troubleshooting ul {
    margin-left: 20px;
    padding-left: 0;
}

.notification li,
.troubleshooting li {
    margin-bottom: 8px;
}

/* メッセージスタイル */
.message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.message-error {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #f5c6cb;
}

.message-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #c3e6cb;
}

/* レスポンシブデザイン */
@media (max-width: 768px) {
    .auth-container {
        padding: 1.5rem;
        margin: 1rem;
    }
}
</style>

<?php get_footer(); ?>