<?php

/**
 * @file
 * email_list view definition.
 */

// --- Paste exported view below ---

$view = new view();
$view->name = 'anonymous_subscriptions_emails';
$view->description = 'Display a list subscribers.';
$view->tag = 'default';
$view->base_table = 'anonymous_subscriptions_emails';
$view->human_name = 'Anonymous Subscriptions Emails';
$view->core = 0;
$view->api_version = '3.0';
$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

/* Display: Defaults */
$handler = $view->new_display('default', 'Defaults', 'default');
$handler->display->display_options['title'] = 'Anonymous Subscription Emails';
$handler->display->display_options['items_per_page'] = 0;
$handler->display->display_options['use_more_always'] = FALSE;
$handler->display->display_options['access']['type'] = 'perm';
$handler->display->display_options['access']['perm'] = 'administer anonymous_subscriptions';
$handler->display->display_options['cache']['type'] = 'none';
$handler->display->display_options['query']['type'] = 'views_query';
$handler->display->display_options['query']['options']['query_comment'] = FALSE;
$handler->display->display_options['exposed_form']['type'] = 'basic';
$handler->display->display_options['exposed_form']['options']['reset_button'] = TRUE;
$handler->display->display_options['pager']['type'] = 'full';
$handler->display->display_options['pager']['options']['items_per_page'] = '50';
$handler->display->display_options['pager']['options']['offset'] = '0';
$handler->display->display_options['pager']['options']['id'] = '0';
$handler->display->display_options['style_plugin'] = 'table';
$handler->display->display_options['style_options']['columns'] = array(
  'eid' => 'eid',
  'email' => 'email',
  'verified' => 'verified',
  'code' => 'code',
  'nothing' => 'nothing',
);
$handler->display->display_options['style_options']['default'] = 'eid';
$handler->display->display_options['style_options']['info'] = array(
  'eid' => array(
    'sortable' => 1,
    'default_sort_order' => 'desc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'email' => array(
    'sortable' => 1,
    'default_sort_order' => 'asc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'verified' => array(
    'sortable' => 1,
    'default_sort_order' => 'asc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'code' => array(
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'nothing' => array(
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
);
$handler->display->display_options['style_options']['sticky'] = TRUE;
$handler->display->display_options['style_options']['empty_table'] = TRUE;
/* No results behavior: Global: Text area */
$handler->display->display_options['empty']['area']['id'] = 'area';
$handler->display->display_options['empty']['area']['table'] = 'views';
$handler->display->display_options['empty']['area']['field'] = 'area';
$handler->display->display_options['empty']['area']['empty'] = TRUE;
$handler->display->display_options['empty']['area']['content'] = 'No subscribers found.';
$handler->display->display_options['empty']['area']['format'] = 'plain_text';
/* Field: Anonymous Subscriptions: Email ID */
$handler->display->display_options['fields']['eid']['id'] = 'eid';
$handler->display->display_options['fields']['eid']['table'] = 'anonymous_subscriptions_emails';
$handler->display->display_options['fields']['eid']['field'] = 'eid';
$handler->display->display_options['fields']['eid']['hide_empty'] = TRUE;
/* Field: Anonymous Subscriptions: Email */
$handler->display->display_options['fields']['email']['id'] = 'email';
$handler->display->display_options['fields']['email']['table'] = 'anonymous_subscriptions_emails';
$handler->display->display_options['fields']['email']['field'] = 'email';
/* Field: Anonymous Subscriptions: Are they verified */
$handler->display->display_options['fields']['verified']['id'] = 'verified';
$handler->display->display_options['fields']['verified']['table'] = 'anonymous_subscriptions_emails';
$handler->display->display_options['fields']['verified']['field'] = 'verified';
$handler->display->display_options['fields']['verified']['label'] = 'Verified';
/* Field: Anonymous Subscriptions: Verification Code */
$handler->display->display_options['fields']['code']['id'] = 'code';
$handler->display->display_options['fields']['code']['table'] = 'anonymous_subscriptions_emails';
$handler->display->display_options['fields']['code']['field'] = 'code';
$handler->display->display_options['fields']['code']['label'] = 'Code';
/* Field: Global: Custom text */
$handler->display->display_options['fields']['nothing']['id'] = 'nothing';
$handler->display->display_options['fields']['nothing']['table'] = 'views';
$handler->display->display_options['fields']['nothing']['field'] = 'nothing';
$handler->display->display_options['fields']['nothing']['label'] = 'Operations';
$handler->display->display_options['fields']['nothing']['alter']['text'] = '<a href="/subscription/[email]/[code]/verify">Verify</a> | <a href="/subscription/[email]/[code]/unsubscribe">Unsubscribe</a>';
$handler->display->display_options['fields']['nothing']['hide_empty'] = TRUE;
$handler->display->display_options['fields']['nothing']['hide_alter_empty'] = TRUE;

/* Display: Page */
$handler = $view->new_display('page', 'Page', 'page_1');
$handler->display->display_options['path'] = 'admin/reports/anonymous_subscriptions_emails';
$handler->display->display_options['menu']['type'] = 'normal';
$handler->display->display_options['menu']['title'] = 'Anonymous Subscriptions Email List';
$handler->display->display_options['menu']['weight'] = '0';
$handler->display->display_options['menu']['name'] = 'management';
$handler->display->display_options['menu']['context'] = 0;
$handler->display->display_options['menu']['context_only_inline'] = 0;


// --- Paste exported view above ---

$views[$view->name] = $view;
