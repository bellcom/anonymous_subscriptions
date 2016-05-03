<?php

/**
 * Preprocess the content for the messages template.
 * Ability to modify input here.
 */
function template_preprocess_anonymous_subscriptions_message(&$vars) {
  $vars['title'] = $vars['title'];
  $vars['message'] = $vars['message'];
  $vars['url'] = $vars['url'];
}