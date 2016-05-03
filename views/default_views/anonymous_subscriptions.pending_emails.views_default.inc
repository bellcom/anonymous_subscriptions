<?php

/**
 * @file
 * pending_emails view definition.
 */

// --- Paste exported view below ---
$view = new view();
$view->name = 'anonymous_subscriptions_pending_emails';
$view->description = 'Display a list of pending emails.';
$view->tag = 'default';
$view->base_table = 'queue';
$view->human_name = 'Anonymous Subscriptions Pending Emails';
$view->core = 0;
$view->api_version = '3.0';
$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

/* Display: Defaults */
$handler = $view->new_display('default', 'Defaults', 'default');
$handler->display->display_options['title'] = 'Anonymous Subscription Pending Emails';
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
  'item_id' => 'item_id',
  'name' => 'name',
  'data' => 'data',
  'created' => 'created',
  'nothing' => 'nothing',
);
$handler->display->display_options['style_options']['default'] = 'item_id';
$handler->display->display_options['style_options']['info'] = array(
  'item_id' => array(
    'sortable' => 1,
    'default_sort_order' => 'desc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'name' => array(
    'sortable' => 1,
    'default_sort_order' => 'asc',
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'data' => array(
    'align' => '',
    'separator' => '',
    'empty_column' => 0,
  ),
  'created' => array(
    'sortable' => 1,
    'default_sort_order' => 'asc',
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
$handler->display->display_options['empty']['area']['content'] = 'No emails pending.';
$handler->display->display_options['empty']['area']['format'] = 'plain_text';
/* Field: Anonymous Subscriptions: Item ID */
$handler->display->display_options['fields']['item_id']['id'] = 'item_id';
$handler->display->display_options['fields']['item_id']['table'] = 'queue';
$handler->display->display_options['fields']['item_id']['field'] = 'item_id';
$handler->display->display_options['fields']['item_id']['hide_empty'] = TRUE;
/* Field: Anonymous Subscriptions: Name */
$handler->display->display_options['fields']['name']['id'] = 'name';
$handler->display->display_options['fields']['name']['table'] = 'queue';
$handler->display->display_options['fields']['name']['field'] = 'name';
/* Field: Anonymous Subscriptions: Data to be sent */
$handler->display->display_options['fields']['data']['id'] = 'data';
$handler->display->display_options['fields']['data']['table'] = 'queue';
$handler->display->display_options['fields']['data']['field'] = 'data';
$handler->display->display_options['fields']['data']['label'] = 'Data';
/* Field: Anonymous Subscriptions: Creation Date */
$handler->display->display_options['fields']['created']['id'] = 'created';
$handler->display->display_options['fields']['created']['table'] = 'queue';
$handler->display->display_options['fields']['created']['field'] = 'created';
$handler->display->display_options['fields']['created']['label'] = 'Created Date';
/* Field: Anonymous Subscriptions: Item ID */
$handler->display->display_options['fields']['item_id_1']['id'] = 'item_id_1';
$handler->display->display_options['fields']['item_id_1']['table'] = 'queue';
$handler->display->display_options['fields']['item_id_1']['field'] = 'item_id';
$handler->display->display_options['fields']['item_id_1']['label'] = '';
$handler->display->display_options['fields']['item_id_1']['exclude'] = TRUE;
$handler->display->display_options['fields']['item_id_1']['element_label_colon'] = FALSE;
$handler->display->display_options['fields']['item_id_1']['separator'] = '';
/* Field: Global: Custom text */
$handler->display->display_options['fields']['nothing']['id'] = 'nothing';
$handler->display->display_options['fields']['nothing']['table'] = 'views';
$handler->display->display_options['fields']['nothing']['field'] = 'nothing';
$handler->display->display_options['fields']['nothing']['label'] = 'Operations';
$handler->display->display_options['fields']['nothing']['alter']['text'] = '<a href="/subscription/[item_id_1]/removeitem">Remove</a>';
$handler->display->display_options['fields']['nothing']['hide_empty'] = TRUE;
$handler->display->display_options['fields']['nothing']['hide_alter_empty'] = TRUE;
/* Filter criterion: Anonymous Subscriptions: Anonymous Subscriptions Queue */
$handler->display->display_options['filters']['name']['id'] = 'name';
$handler->display->display_options['filters']['name']['table'] = 'queue';
$handler->display->display_options['filters']['name']['field'] = 'name';
$handler->display->display_options['filters']['name']['value'] = 'anonymous_subscriptions_queue';
$handler->display->display_options['filters']['name']['group'] = 1;

/* Display: Page */
$handler = $view->new_display('page', 'Page', 'page_2');
$handler->display->display_options['path'] = 'admin/reports/anonymous_subscriptions_pending_emails';
$handler->display->display_options['menu']['type'] = 'normal';
$handler->display->display_options['menu']['title'] = 'Anonymous Subscriptions Pending Email List';
$handler->display->display_options['menu']['weight'] = '0';
$handler->display->display_options['menu']['name'] = 'management';
$handler->display->display_options['menu']['context'] = 0;
$handler->display->display_options['menu']['context_only_inline'] = 0;



// --- Paste exported view above ---

$views[$view->name] = $view;
