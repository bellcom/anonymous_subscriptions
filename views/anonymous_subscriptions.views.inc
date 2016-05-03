<?php

/**
 * Implementation of hook_views_data()
 */
function anonymous_subscriptions_views_data() {
  $data = array();
  // Views Data for Anonymous Subscriptions Emails
  $data['anonymous_subscriptions_emails']['table'] = array(
    'group' => t("Anonymous Subscriptions"),
    'title' => t("Anonymous Subscriptions Emails"),
    'help' => t("Shows a table of currently subscribed users and their status."),
  );
  $data['anonymous_subscriptions_emails']['table']['base'] = array(
    'field' => 'eid',
    'title' => t('Subscription Emails'),
    'help' => t("Subscription emails"),
    'weight' => -50,
    //'access query tag' => 'node_access',
    'defaults' => array(
      'field' => 'eid',
    ),
  );
  $data['anonymous_subscriptions_emails']['eid'] = array(
    'field' => array(
      'title' => t('Email ID'),
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
      'help' => t("The unique email ID."),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
  );
  $data['anonymous_subscriptions_emails']['email'] = array(
    'field' => array(
      'title' => t('Email'),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
      'help' => t("The email."),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );
  $data['anonymous_subscriptions_emails']['verified'] = array(
    'field' => array(
      'title' => t('Are they verified'),
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
      'help' => t("Are they verified"),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
  );
  $data['anonymous_subscriptions_emails']['code'] = array(
    'field' => array(
      'title' => t('Verification Code'),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
      'help' => t("The verification code."),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );



  // Views Data for Queued Emails
  $data['queue']['table'] = array(
    'group' => t("Anonymous Subscriptions"),
    'title' => t("Anonymous Subscriptions Queue"),
    'help' => t("Shows a table of pending emails."),
  );
  $data['queue']['table']['base'] = array(
    'field' => 'item_id',
    'title' => t('Pending Emails'),
    'weight' => -50,
    'defaults' => array(
      'field' => 'item_id',
    ),
  );
  $data['queue']['item_id'] = array(
    'field' => array(
      'title' => t('Item ID'),
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
      'help' => t("The Item ID."),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
  );
  $data['queue']['name'] = array(
    'field' => array(
      'title' => t('Name'),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
      'help' => t("The name of the queue."),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );
  $data['queue']['data'] = array(
    'field' => array(
      'title' => t('Data to be sent'),
      'handler' => 'views_handler_field',
      'click sortable' => FALSE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter',
    ),
  );
  $data['queue']['created'] = array(
    'field' => array(
      'title' => t('Creation Date'),
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
      'help' => t("The Creation Date."),
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );


  return $data;
}
