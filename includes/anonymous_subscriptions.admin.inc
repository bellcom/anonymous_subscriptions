<?php

/**
 * Implements hook_settings().
 *
 * General Settings for Anonymous Subscriptions
 */
function anonymous_subscriptions_settings() {
  $form = array();

  $form['subscription_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Subscription settings'),
  );

  $form['subscription_fieldset']['anonymous_subscriptions_verify'] = array(
    '#type' => 'checkbox',
    '#title' => t('Emails must be verified'),
    '#default_value' => variable_get('anonymous_subscriptions_verify', FALSE),
  );

  $form['subscription_fieldset']['anonymous_subscriptions_send_default'] = array(
    '#type' => 'checkbox',
    '#title' => t('Send by default'),
    '#default_value' => variable_get('anonymous_subscriptions_send_default', FALSE),
  );

  $form['subscription_fieldset']['anonymous_subscriptions_max_to_send'] = array(
    '#type' => 'select',
    '#title' => t('Maximum time to spend processing e-mails during cron.'),
    '#default_value' => variable_get('anonymous_subscriptions_max_to_send', 60),
    '#options' => array(30 => '30 seconds', 60 => '1 minute', 120 => '2 minutes'),
  );

  $form['subscription_fieldset']['anonymous_subscriptions_limit_window'] = array(
    '#type' => 'select',
    '#title' => t('How long to block flood attempts.'),
    '#default_value' => variable_get('anonymous_subscriptions_limit_window', 300),
    '#options' => array(60 => '1 minute', 300 => '5 minutes', 600 => '10 minutes'),
  );

  $form['subscription_fieldset']['anonymous_subscriptions_ip_limit'] = array(
    '#type' => 'select',
    '#title' => t('How many attempts before blocking a user from a single IP.'),
    '#default_value' => variable_get('anonymous_subscriptions_ip_limit', 20),
    '#options' => array(5 => '5 attempts', 10 => '10 attempts', 20 => '20 attempts'),
  );

  $form['email_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Email settings'),
  );

  $form['email_fieldset']['anonymous_subscriptions_site_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Site URL'),
    '#description' => t('The URL you want to use as the base URL sent in e-mails. Tokens available'),
    '#default_value' => variable_get('anonymous_subscriptions_site_url', '[site:url]'),
  );

  $form['email_fieldset']['anonymous_subscriptions_subject_text'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject text'),
    '#description' => 'Tokens are available',
    '#default_value' => variable_get('anonymous_subscriptions_subject_text', 'New content added to [site:name]'),
  );

  $form['email_fieldset']['anonymous_subscriptions_body_text'] = array(
    '#type' => 'textarea',
    '#title' => t('Body text'),
    '#description' => 'Tokens are available',
    '#default_value' => variable_get('anonymous_subscriptions_body_text', "A new post has been made to [site:name].\nPlease visit [node:url] to see this new post!"),
  );

  if (module_exists('token')) {
    $form['email_fieldset']['token_help'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => array('site', 'node'),
    );
  }

  $form['anonymous_subscriptions_node_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Node types enabled for subscription notifications'),
    '#options' => node_type_get_names(),
    '#default_value' => variable_get('anonymous_subscriptions_node_types', array()),
  );

  $form['array_filter'] = array(
    '#type' => 'value',
    '#value' => TRUE,
  );

  return system_settings_form($form);
}

/**
 * Implements hook_settings_validate().
 */
function anonymous_subscriptions_settings_validate($form, $form_state) {
  $url = $form['email_fieldset']['anonymous_subscriptions_site_url']['#value'];
  $url = token_replace($url);
  $url = parse_url($url);
  if(!isset($url) || empty($url) || !isset($url['scheme']) || empty($url['scheme']) ) {
    form_set_error('anonymous_subscriptions_site_url', t('Please insert a valid URL or Token.'));
  }
}

/**
 *
 * Import Settings for Anonymous Subscriptions
 */
function anonymous_subscriptions_import() {
    $form = array();

    $form['import_fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Import e-mails'),
    );

    $form['import_fieldset']['anonymous_subscriptions_verify_import'] = array(
        '#type' => 'checkbox',
        '#title' => t('Emails must be verified'),
        '#default_value' => variable_get('anonymous_subscriptions_verify', FALSE),
    );

    $form['import_fieldset']['anonymous_subscriptions_emails'] = array(
        '#type' => 'textarea',
        '#title' => t('List of e-mail addresses'),
        '#description' => 'Place e-mails on individual lines e.g. <br/>email@email.com<br/>email2@email.com<br/>email3@email.com',
        '#default_value' => '',
    );

    $form['#validate'][] = 'anonymous_subscriptions_import_validate';
    $form['#submit'][] = 'anonymous_subscriptions_import_submit';


    return system_settings_form($form);
}

/**
 * Handle the validation of the import emails form
 */
function anonymous_subscriptions_import_validate($form, &$form_state) {
    $emails = $form_state['values']['anonymous_subscriptions_emails'];
    if(empty(trim($emails))) {
        form_set_error('form', t('No e-mails added to list.'));
    } else {
        $email_array = explode("\r\n",$emails);
        $bad_emails = array();
        $valid = 0;
        foreach ($email_array as $email) {
            if (!empty(trim($email)) && !valid_email_address($email)) {
                $bad_emails[] = $email;
            } else {
                $valid++;
            }
        }

        if (sizeof($bad_emails) > 0) {
            form_set_error('form', t('The following are not valid e-mails: !emails', ['!emails' => implode(', ', $bad_emails)]));
        } elseif ($valid == 0) {
            form_set_error('form', t('No e-mails added to list.'));
        }
    }
}

/**
 * Handle the submission of the import emails form
 */
function anonymous_subscriptions_import_submit($form, &$form_state) {
    $emails = $form_state['values']['anonymous_subscriptions_emails'];
    $verification_required = $form_state['values']['anonymous_subscriptions_verify_import'];
    $emails = explode("\r\n", $emails);
    $batch = array(
        'title' => t('Importing subscriptions'),
        'operations' => array(),
        'finished' => '_anonymous_subscriptions_batch_import_finished',
        'init_message' => t('Initializing...'),
        'error_message' => t('Importing of subscriptions encountered an error.'),
        'file' => drupal_get_path('module', 'anonymous_subscriptions') . '/includes/anonymous_subscriptions.admin.inc',
    );
    foreach($emails as $email) {
        if (!empty(trim($email))) {
            $batch['operations'][] = array('_anonymous_subscriptions_batch_admin_import_users', array($email, $verification_required));
        }
    }
    batch_set($batch);
}

/**
 * Import individual users as part of the Batch API process
 *
 * @param $email
 * @param $verification_required
 * @param $context
 */
function _anonymous_subscriptions_batch_admin_import_users($email, $verification_required, &$context) {
    global $base_url;
    $original_base_url = $base_url;
    $site_url = variable_get('anonymous_subscriptions_site_url', '[site:url]');
    $base_url = token_replace($site_url);
    $email = trim($email);
    $code = substr(md5(uniqid(mt_rand(), true)), 0, 8);

    try {
        db_insert(ANONYMOUS_SUBSCRIPTIONS_TABLE)
            ->fields(array(
                'email' => $email,
                'verified' => (1 - $verification_required),
                'code' => $code,
            ))
            ->execute();
    } catch (PDOException $e) {
        //User already exists, so don't add this one.
        $context['results']['failed'][] = $email;
        return;
    }



    if ($verification_required == 1) {
        $subject = variable_get('site_name', '[site:name]') . ' - subscription';
        $text = t('To confirm your subscription please visit the following url !url', ['!url' => url("subscription/$code/$email/verify", ['absolute' => TRUE])]);
        $body = array($text);
        $params = array(
            'email' => $email,
            'subject' => $subject,
            'body' => $body
        );
        drupal_mail('anonymous_subscriptions', 'anonymous_subscriptions_key', $email, language_default(), $params);
    } else {
        $subject = variable_get('site_name', '[site:name]') . ' - subscription';
        $text = t("You are now subscribed to receive updates from !base_url.  To unsubscribe please visit !url", ['!base_url' => url($base_url), '!url' => url("subscription/$code/$email/unsubscribe", ['absolute' => TRUE])]);
        $body = array($text);
        $params = array(
            'email' => $email,
            'subject' => $subject,
            'body' => $body
        );
        drupal_mail('anonymous_subscriptions', 'anonymous_subscriptions_key', $email, language_default(), $params);
    }

    $context['results']['rows_imported']++;
    $context['message'] = t('Imported !c email subscriptions.', array( '!c' => $context['results']['rows_imported'] ));

    // Setting base URL back to the original as to ensure we get the correct url (after token replacing etc)
    // for our $site_url variable, e.g. if node:url is used we want the correct base_url
    $base_url = $original_base_url;
}

/**
 * Batch API when finished import of subscriptions run this
 *
 * @param $success
 * @param $results
 * @param $operations
 */
function _anonymous_subscriptions_batch_import_finished($success, $results, $operations) {
    if ($success) {
        $message = t('@count subscriptions successfully imported', array('@count' => $results['rows_imported']));
    }
    else {
        $message = t('Finished with error.');
    }

    if(isset($context['results']['failed']) && sizeof($context['results']['failed']) > 0) {
        drupal_set_message(t('The following e-mails already exist: !emails', ['!emails' => implode(', ', $context['results']['failed'])]), 'warning');
    }

    drupal_set_message($message);
}

/**
 * Helper function to determine if a string starts with a
 * certain string
 *
 * @param $haystack
 * @param $needle
 * @return bool
 */
function _startsWith($haystack, $needle) {
  // search backwards starting from haystack length characters from the end
  return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

/**
 * Helper function to determine if a string ends with a
 * certain string
 *
 * @param $haystack
 * @param $needle
 * @return bool
 */
function _endsWith($haystack, $needle) {
  // search forward starting from end minus needle length characters
  return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}


