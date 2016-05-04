<?php

/**
 * Implements hook_block_info().
 */
function anonymous_subscriptions_block_info() {
  $blocks['anonymous_subscriptions_block'] = array(
    'info' => t('Anonymous subscription email block'),
    'cache' => DRUPAL_CACHE_PER_ROLE,
  );
  return $blocks;
}

/**
 * Implements hook_block_view().
 */
function anonymous_subscriptions_block_view($delta = '') {
  switch ($delta) {
    case 'anonymous_subscriptions_block':
      $block['subject'] = t('Subscribe');
      if (user_access('access content')) {
        // Retrieve and process data here.
        $the_form = drupal_get_form('anonymous_subscriptions_form');
        $block['content'] = drupal_render($the_form);

      }

      return $block;
  }
}

/**
 * Implements hook_form().
 *
 * Block used to display form to register for subscription
 */
function anonymous_subscriptions_form() {
  $form['email'] = array(
    '#type' => 'textfield',
    '#title' => t('Email'),
    '#default_value' => '',
    '#required' => TRUE,
    '#size' => 15,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Subscribe'),
  );
  $form['#validate'][] = 'anonymous_subscriptions_form_validate';
  $form['#submit'][] = 'anonymous_subscriptions_form_submit';
  return $form;
}

/**
 * Implements hook_form_validate().
 *
 * Validates the subscription form.
 */
function anonymous_subscriptions_form_validate($form, &$form_state) {
  $window = variable_get('anonymous_subscriptions_limit_window', 300);
  $limit = variable_get('anonymous_subscriptions_ip_limit', 20);
  if(flood_is_allowed('failed_subscribe_attempt_ip', $limit, $window)) {
    flood_register_event('failed_subscribe_attempt_ip', $window);
    $email = $form_state['values']['email'];
    if ($email && !valid_email_address($email)) {
      form_set_error('email', 'You must enter a valid email address.');
    } else {
      $result = db_select(ANONYMOUS_SUBSCRIPTIONS_TABLE, 's')
        ->fields('s')
        ->condition('email', trim($email), '=')
        ->execute()
        ->fetchAssoc();
      if ($result) {
        form_set_error('email', 'You are already subscribed.');
      }
    }
  } else {
    form_set_error('email', 'You have made too many attempts to subscribe.');
  }
}

/**
 * Implements hook_form_submit().
 *
 * Handles the registration form submission generating a code and
 * storing that in the database table as well as the default verification
 * status.
 */
function anonymous_subscriptions_form_submit($form, &$form_state) {
  global $base_url;
  $original_base_url = $base_url;
  $site_url = variable_get('anonymous_subscriptions_site_url', '[site:url]');
  $base_url = token_replace($site_url);
  $verification_required = variable_get('anonymous_subscriptions_verify', TRUE);
  $verify_int = (int)(!$verification_required);
  $email = $form['email']['#value'];
  $email = trim($email);
  $code = substr(md5(uniqid(mt_rand(), true)), 0, 8);;

  db_insert(ANONYMOUS_SUBSCRIPTIONS_TABLE)
    ->fields(array(
      'email' => $email,
      'verified' => $verify_int,
      'code' => $code,
    ))
    ->execute();


  if ($verification_required) {
    drupal_set_message('Thanks for subscribing, in order to receive updates, you will need to verify your email address by clicking on a link in an email we just sent you.');
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
    drupal_set_message('You are now subscribed to updates.');
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

  // Setting base URL back to the original as to ensure we get the correct url (after token replacing etc)
  // for our $site_url variable, e.g. if node:url is used we want the correct base_url
  $base_url = $original_base_url;
}
