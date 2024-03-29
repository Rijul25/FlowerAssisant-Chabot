<?php
// Email Notificator
namespace WatsonConv;

class Email_Notificator {
    public function __construct() {
        add_action("init", array(__CLASS__, "run"));
    }

    public static function run() {
        $enabled = get_option('watsonconv_notification_enabled', '') === 'yes';
        if ($enabled) {
            $prev_ts = intval(get_option('watsonconv_notification_summary_prev_ts', 0));
            $dt = time() - $prev_ts;

            $interval = intval(get_option('watsonconv_notification_summary_interval', 0));

            if ($interval > 0 && $dt > $interval) {
                self::send_summary_notification();
                self::reset_summary_prev_ts();
            }
        }
    }

    public static function reset_summary_prev_ts() {
        update_option('watsonconv_notification_summary_prev_ts', time());
    }

    /**
     * @param bool $force_send
     * @return bool
     */
    public static function send_summary_notification($force_send=false, $emails=NULL) {
        $res = false;

        if(empty($emails)) {
            $emails = get_option('watsonconv_notification_email_to', '');
        }

        $emails_array = explode(",", $emails);
        $errors_array = array();
        $siteUrl = get_site_url();
        foreach($emails_array as $email) {
            $email = trim($email);

            $prev_ts = intval(get_option('watsonconv_notification_summary_prev_ts', 0));
            $topic = 'Watson Assistant plug-in for WordPress: ChatBot Usage Summary';
            $headers = "Content-Type: text/html; charset=UTF-8\r\n";
            $count = self::get_session_count_since_last_time($prev_ts);
            if ($count > 0 || $force_send) {
                $message = '<table style="width: 100%;">
                            <tr>
                                <td style="text-align: center;">
                                    <div style="margin-left: 30%; width: 40%; /*border-width: 1px;border-color: dimgray;border-style: solid;*/">
                                        <div style="background: gold; margin: 0 2% 0 2%;">
                                            <h1 style="font-size: 38px; ">ChatBot served</h1>
                                        </div>
                                        <p>
                                            <strong style="font-size: 38px;">' . $count . '</strong>
                                        </p>
                                        <p>
                                            <strong style="opacity: 0.7; font-size: 28px">Session(s)</strong>
                                        </p>
                                        <p>
                                            <strong style="opacity: 0.7; font-size: 28px">at ' . $siteUrl . '</strong>
                                        </p>
                                        <p style="opacity: 0.5">
                                            Since ' . date('r', $prev_ts)
                                        . '</p>
                                            <img style="height: 15%; width: 15%; margin-top: 5%" src="data:image/png;base64,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">
                                        <p>
                                            <strong style="opacity: 0.7">
                                                This email was generated<br>by Watson Assistant plug-in for WordPress.
                                            </strong>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>';
                $res = wp_mail($email, $topic, $message, $headers);

                if(!$res) {
                    array_push($errors_array, $GLOBALS['phpmailer']->ErrorInfo);
                }
            }
        }

        if(count($errors_array) > 0) {
            return $errors_array;
        }
        else {
            return true;
        }
    }

    /**
     * @param integer $since_ts - unix timestamp
     * @return integer
     */
    private static function get_session_count_since_last_time($since_ts) {
        global $wpdb;
        $tname = \WatsonConv\Storage::get_full_table_name('sessions');
        $count = intval($wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.$tname.' WHERE s_created > FROM_UNIXTIME(%d)', $since_ts)));
        return $count;
    }
}

new Email_Notificator();
