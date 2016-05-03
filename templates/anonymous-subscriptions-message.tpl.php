<?php
/**
 * @file
 * Template to control the messages.
 */
?>
<div class="anonymous-subscription-messages clearfix">
  <?php if (isset($title)): ?>
    <h2><?php print $title; ?></h2>
  <?php endif; ?>
  <?php if (isset($message)): ?>
    <div class="anonymous-subscription-message">
      <?php print $message; ?>
    </div>
  <?php endif; ?>
  <?php if (isset($url)): ?>
    <div class="anonymous-subscription-url">
      <a href="<?php print $url; ?>">Click here to return to homepage</a>
    </div>
  <?php endif; ?>
</div>
