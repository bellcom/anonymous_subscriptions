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
      $block['subject'] = t('Email');
      if (user_access('access content')) {
        // Retrieve and process data here.
        $the_form = drupal_get_form('anonymous_subscriptions_form');
        $block['content'] = drupal_render($the_form);

      }

      return $block;
  }
}
