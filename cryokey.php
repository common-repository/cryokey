<?php
/*
Plugin Name: CryoKey
Description: Basic CryoKey Integration
Version: 2.4
Author: Authenticade LLC
License: MIT
*/

$CK_HOME = "https://www.cryokey.com";

/**
 * Check for CryoKey credentials. If available, automatically
 * log in.
 *
 * This function works as a filter for the normal authentication flow.
 * It handles multi-factor duties if necessary. You should apply it
 * LAST, since the multi-factor support will check against the
 * existing authenticated user.
 */
function ck_authenticate($_user, $_username, $_password)
{
  $amf = ("1" === get_option('ck_allow_multifactor')) ? true : false;
  $fmf = ("1" === get_option('ck_force_multifactor')) ? true : false;
  $tid = @$_REQUEST['cktid'];
  if (isset($tid))
  {
    // Try CryoKey login first.
    $ckuser = ck_login($tid);
    if (is_a($ckuser, 'WP_User'))
    {
      // Advance to multi-factor if necessary.
      $ckmulti = ("1" === get_user_meta($ckuser->ID, 'ckmulti', TRUE)) ? true : false;
      if ($amf && ($ckmulti || $fmf))
      {
        // If the user wants multi-factor authentication, check the
        // authenticated user here.
        if (!is_a($_user, 'WP_User') || ($ckuser->ID !== $_user->ID))
        {
          // Main factor check failed; halt further authentication.
          return new WP_Error('failed_multifactor', sprintf(__('<strong>ERROR</strong>: Multi-Factor authentication required, and the username/password combination failed. <a href="%1$s" title="Password Lost and Found">Lost your password</a>?'), wp_lostpassword_url()));
        }
      }

      // Successful CryoKey authentication.
      return $ckuser;
    }

    return new WP_Error('need_cryokey', __('<strong>ERROR</strong>: CryoKey user not found.'));
  }
  else
  {
    // Validate regular WordPress authentication flow.
    if (is_a($_user, 'WP_User'))
    {
      $ckmulti = ("1" === get_user_meta($_user->ID, 'ckmulti', TRUE)) ? true : false;
      if ($amf && ($ckmulti || $fmf))
      {
        // We can't multi-factor authenticate without CryoKey.
        return new WP_Error('need_cryokey', __('<strong>ERROR</strong>: Multi-Factor authentication required, and you did not present matching CryoKey credentials.'));
      }
    }

    return $_user;
  }
}

/**
 * Show a button for CryoKey logins (usually in login form).
 */
function ck_button()
{
  //$js = plugins_url('cryokey.js', __FILE__);
  $icon = plugins_url('ckicon.png', __FILE__);
  $service = wp_login_url();
  $ip = ck_ip();
  $msgmulti = __("Log In Using CryoKey");

  //echo "<p><script type='text/javascript' src='{$js}'></script>";
  if ("1" === get_option('ck_allow_multifactor'))
  {
    echo "<a href='javascript:ck_multi(\"{$service}\", \"{$ip}\");'><img src='{$icon}' width='16' height='16' alt='{$msgmulti}'/> {$msgmulti}</a>";
  }
  else
  {
    echo "<a href='javascript:ck_initiate(\"{$service}\", \"{$ip}\", \"{$service}\");'><img src='{$icon}' width='16' height='16' alt='{$msgmulti}'/> {$msgmulti}</a>";
  }
  echo "</p>";
}

/**
 * Try to auto-fill the E-Mail address field (document ID
 * 'user_email').
 */
function ck_fill()
{
  // Note that this will override the POST value during registration.
  if (isset($_REQUEST['ckemail']))
    echo "<script type='text/javascript'>document.getElementById('user_email').value = '{$_REQUEST['ckemail']}';</script>";
}

/**
 * Attach the CryoKey Lite JavaScript files.
 */
function ck_scripts()
{
  global $CK_HOME;

  wp_enqueue_script('ckauth', "{$CK_HOME}/cryokey.js");
  wp_enqueue_script('cryokey', plugins_url('cryokey.js', __FILE__), array('ckauth'));
}

/**
 * Determine the current user based on certificate authentication.
 */
function ck_login($_tid)
{
  $auto = ("1" == get_option('ck_auto_register')) ? true : false;
  $email = ck_redeem($_tid, wp_login_url());
  if (empty($email))
    return new WP_Error('authentication_failed', __("Couldn't verify credentials."));

  // If the user requested registration, add the user.
  if (false == get_user_by('email', $email))
  {
    if ($auto)
    {
      $name = ck_scramble(8);
      $registration = array(
        'user_login' => "CK{$email}",
        'user_pass' => wp_generate_password(),
        'user_email' => $email,
        'user_nicename' => $name,
        'display_name' => "CK{$name}",
        'nickname' => $name,
        'description' => "CryoKey Generated Registration"
      );

      //wp_create_user("CK{$email}", wp_generate_password(), $email);
      wp_insert_user($registration);
    }
  }

  // Check for existing user.
  $user = get_user_by('email', $email);
  if ($user)
  {
    // Change the current user.
    return $user;
  }

  // Unknown user, and didn't create.
  return new WP_Error('authentication_failed', __("No matching user."));
}

/**
 * This function will replace the login/logout link to display
 * a log in link. We log in using the CryoKey Lite mechanism.
 *
 * The input is the HTML link that WordPress calculates. We use
 * it to carry over redirections.
 */
function ck_link($_input)
{
  // If the login/logout has a redirect, then extract it.
  $redirect = (preg_match('/[\?&]redirect_to=([^&"\']*)/', $_input, $matches) ? $matches[1] : $_SERVER['PHP_SELF']);
  $url = wp_login_url($redirect);
  $out = wp_logout_url($redirect);

  // Calculate CryoKey login parameters. The service must match the
  // one you use when redeeming the ticket.
  $service = wp_login_url();
  $ip = ck_ip();

  // Text Labels
  $msglogout = __("Log Out");
  $msglogin = __("Log In");

  // Show either a login or logout link. Note that the login link
  // is a direct initiation (single-factor). If it fails, you end
  // up in the regular authentication page.
  if (is_user_logged_in())
    return "<a href='javascript:ck_logout(\"{$out}\");'>{$msglogout}</a>";
  else
    return "<a href='javascript:ck_initiate(\"{$service}\", \"{$ip}\", \"{$url}\");'>{$msglogin}</a>";
}

/**
 * Generate a random string of a given length.
 */
function ck_scramble($_length)
{
  $symbols = "abcdefghijklmnopqrstuvwxyz1234567890";
  $result = "";
  $available = strlen($symbols) - 1;
  for ($index = 0; $index < $_length; $index++)
  {
    $code = rand(0, $available);
    $result .= $symbols[$code];
  }
  return $result;
}
/**
 * Try to redeem a ticket. We return an E-Mail address on success,
 * or NULL on failure.
 */
function ck_redeem($_tid, $_service)
{
  global $CK_HOME;

  $ip = ck_ip();
  $response = json_decode(file_get_contents($CK_HOME . "/public/redeem.php?tid=" . urlencode($_tid) . "&from=" . urlencode($ip) . "&to=" . urlencode($_service)));
  if (($response->ckfrom == $ip) || ($response->ckfrom == $response->ckto))
    return $response->ckname;
  else
    return NULL;
}
/**
 * Try to determine the client's IP address.
 */
function ck_ip()
{
  if (isset($_SERVER['HTTP_CLIENT_IP']))
    return $_SERVER['HTTP_CLIENT_IP'];
  if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    return $_SERVER['HTTP_X_FORWARDED_FOR'];
  return $_SERVER['REMOTE_ADDR'];
}

// If multi-factor is necessary, then fail regular authentication.
add_filter('authenticate', 'ck_authenticate', 50, 3);

// Include CryoKey JavaScript.
add_action('wp_enqueue_scripts', 'ck_scripts');
add_action('login_enqueue_scripts', 'ck_scripts');
//add_action('admin_enqueue_scripts', 'ck_scripts');

// Remove these if the user logged in.
add_action('login_form', 'ck_button');
add_action('register_form', 'ck_fill');
add_filter('loginout', 'ck_link');

// User Profile Settings (for per-user multi-factor)
add_action('show_user_profile', 'ck_show_user_options');
add_action('personal_options_update', 'ck_save_user_options');

// Registration Form Updates
add_action('register_form', 'ck_register_form');
add_action('user_register', 'ck_user_register');

if (is_admin())
{
  // Add CryoKey options panel.
  add_action('admin_init', 'ck_admin');
  add_action('admin_menu', 'ck_menu');
}

///////////////////////////////
// CRYOKEY USER REGISTRATION //
///////////////////////////////
function ck_register_form()
{
  $amf = ("1" === get_option('ck_allow_multifactor')) ? true : false;
  $fmf = ("1" === get_option('ck_force_multifactor')) ? true : false;
  if ($amf)
  {
    if(!$fmf)
    {
      if (isset($_POST['ckmulti']))
        $ckmulti = "<input type='checkbox' name='ckmulti' id='ckmulti' value='1' checked='checked'>" . __("Enabled") . "</input>";
      else
        $ckmulti = "<input type='checkbox' name='ckmulti' id='ckmulti' value='1'>" . __("Enabled") . "</input>";
?>
  <p>
  <label for="ckmulti"><?php _e("Multi-Factor"); ?></label>: <?php echo $ckmulti; ?>
  </p>
<?php
    }
  }
}
function ck_user_register($_uid)
{
  if (isset($_POST['ckmulti']))
    update_user_meta($_uid, 'ckmulti', $_POST['ckmulti']);
}

////////////////////////////////////////
// CRYOKEY USER-SPECIFIC OPTIONS MENU //
////////////////////////////////////////
function ck_show_user_options($_user)
{
  $amf = ("1" === get_option('ck_allow_multifactor')) ? true : false;
  $fmf = ("1" === get_option('ck_force_multifactor')) ? true : false;
  if ($amf)
  {
    if(!$fmf)
    {
      if ("1" == get_user_meta($_user->ID, 'ckmulti', TRUE))
        $ckmulti = "<input type='checkbox' name='ckmulti' id='ckmulti' value='1' checked='checked'>" . __("Enabled") . "</input>";
      else
        $ckmulti = "<input type='checkbox' name='ckmulti' id='ckmulti' value='1'>" . __("Enabled") . "</input>";

?>
  <h3><?php _e("CryoKey Options"); ?></h3>
  <table class='form-table'>
    <tbody>
      <tr>
        <th><?php _e("Multi Factor Authentication"); ?></th>
        <td><?php echo $ckmulti; ?></td>
      </tr>
    </tbody>
  </table>
<?php
    }
  }
}
function ck_save_user_options($_uid)
{
  if (current_user_can('edit_user', $_uid))
  {
    delete_user_meta($_uid, 'ckmulti');
    if (isset($_POST['ckmulti']))
      add_user_meta($_uid, 'ckmulti', $_POST['ckmulti']);
  }
}

////////////////////////////////
// CRYOKEY OPTIONS MENU SETUP //
////////////////////////////////
function ck_menu()
{
  add_options_page('CryoKey Options', 'CryoKey', 'manage_options', __FILE__, 'ck_options');
}
function ck_admin()
{
  register_setting('ck_options_group', 'ck_auto_register');
  add_settings_section('ck_main', __('CryoKey Main Options'), 'ck_show_main', __FILE__);
  add_settings_field('ck_main_ar', __('Auto Register New Users'), 'ck_show_main_ar', __FILE__, 'ck_main');
  add_settings_field('ck_main_amf', __('Allow Multifactor Authentication (not compatible with Android)'), 'ck_show_main_amf', __FILE__, 'ck_main');
  add_settings_field('ck_main_fmf', __('Force Multifactor Authentication'), 'ck_show_main_fmf', __FILE__, 'ck_main');
}
function ck_show_main()
{
  _e("Tune your CryoKey integration.");
}
function ck_show_main_ar($_arguments)
{
  if ("1" == get_option('ck_auto_register'))
    echo "<input type='checkbox' name='ck_auto_register' id='ck_main_ar' value='1' checked='checked'>" . __("Enabled") . "</input>";
  else
    echo "<input type='checkbox' name='ck_auto_register' id='ck_main_ar' value='1'>" . __("Enabled") . "</input>";
}
function ck_show_main_amf($_arguments)
{
  if ("1" == get_option('ck_allow_multifactor'))
    echo "<input type='checkbox' name='ck_allow_multifactor' id='ck_main_amf' value='1' checked='checked'>" . __("Enabled") . "</input>";
  else
    echo "<input type='checkbox' name='ck_allow_multifactor' id='ck_main_amf' value='1'>" . __("Enabled") . "</input>";
}
function ck_show_main_fmf($_arguments)
{
  if ("1" == get_option('ck_force_multifactor'))
    echo "<input type='checkbox' name='ck_force_multifactor' id='ck_main_fmf' value='1' checked='checked'>" . __("Enabled") . "</input>";
  else
    echo "<input type='checkbox' name='ck_force_multifactor' id='ck_main_fmf' value='1'>" . __("Enabled") . "</input>";
}
function ck_options()
{
  if (!current_user_can('manage_options'))
    wp_die(__('You do not have sufficient permissions to access this page.'));

?>
  <div class="wrap">
  <?php screen_icon(); ?>
  <h2>CryoKey Options</h2>
  <form method="post" action="options.php">
  <?php settings_fields('ck_options_group'); ?>
  <?php do_settings_sections(__FILE__); ?>
  <?php submit_button(); ?>
  </form>
  </div>
<?php
}

?>
