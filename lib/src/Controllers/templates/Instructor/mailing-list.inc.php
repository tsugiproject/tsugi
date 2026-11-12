<?php
/**
 * Context mailing-list UI.
 *
 * Expected: $context_id, $context_title, $days, $include_opted_out, $premium_only,
 * $rows, $membership_url, $form_url
 */
use Tsugi\Util\U;
use Tsugi\Controllers\Tool;
?>
<h2>Mailing List for: <?= htmlentities($context_title) ?></h2>
<p>
  <a href="<?= htmlspecialchars($membership_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default">Back to Membership</a>
</p>

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Generate Mailing List</h3>
  </div>
  <div class="panel-body">
    <p>Generate a mailing list of users who have logged in within a specified number of days.</p>
    <form method="post" action="<?= htmlspecialchars($form_url, ENT_QUOTES, 'UTF-8') ?>">
      <?= Tool::csrfField() ?>
      <input type="hidden" name="context_id" value="<?= htmlentities($context_id) ?>">
      <div class="form-group" style="margin-bottom: 15px;">
        <label for="days">Users who logged in within the last:</label>
        <input type="number" class="form-control" id="days" name="days" value="<?= htmlentities($days !== null ? $days : 30) ?>" min="1" max="365" style="width: 80px; margin: 0 10px; display: inline-block;">
        <label for="days">days</label>
      </div>
      <div class="form-group" style="margin-bottom: 15px;">
        <label>
          <input type="checkbox" name="include_opted_out" value="1" <?= $include_opted_out ? 'checked' : '' ?>>
          Include all users including those that have opted out of all email
        </label>
      </div>
      <div class="form-group" style="margin-bottom: 15px;">
        <label>
          <input type="checkbox" name="premium_only" value="1" <?= $premium_only ? 'checked' : '' ?>>
          Supporters / premium users only
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Generate Mailing List</button>
    </form>
  </div>
</div>

<?php if ( $days !== null ): ?>
  <p>Users who logged in within the last <?= htmlentities($days) ?> days<?= $include_opted_out ? '' : ' (excluding users who opted out of email)' ?><?= $premium_only ? ' — supporters / premium only' : '' ?></p>

  <?php if ( count($rows) == 0 ): ?>
  <div class="alert alert-info">
    <p>No users found matching the criteria.</p>
  </div>
<?php else:
  $emails = array();
  foreach ( $rows as $row ) {
      if ( !empty($row['email']) ) {
          $emails[] = trim($row['email']);
      }
  }
?>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title" style="display: inline-block;">Comma-separated list (<?= count($rows) ?> total)</h3>
      <button type="button" class="btn btn-sm btn-default" onclick="toggleSection('comma-list-body', this)" style="margin-left: 10px;">Hide</button>
    </div>
    <div class="panel-body" id="comma-list-body">
      <textarea class="form-control" rows="5" readonly style="font-family: monospace;"><?php
        echo htmlentities(implode(', ', $emails));
      ?></textarea>
    </div>
  </div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title" style="display: inline-block;">Semicolon-separated list (<?= count($rows) ?> total)</h3>
      <button type="button" class="btn btn-sm btn-default" onclick="toggleSection('semicolon-list-body', this)" style="margin-left: 10px;">Show</button>
    </div>
    <div class="panel-body" id="semicolon-list-body" style="display: none;">
      <textarea class="form-control" rows="5" readonly style="font-family: monospace;"><?php
        echo htmlentities(implode('; ', $emails));
      ?></textarea>
    </div>
  </div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title" style="display: inline-block;">One per line (<?= count($rows) ?> total)</h3>
      <button type="button" class="btn btn-sm btn-default" onclick="toggleSection('line-list-body', this)" style="margin-left: 10px;">Show</button>
    </div>
    <div class="panel-body" id="line-list-body" style="display: none;">
      <textarea class="form-control" rows="10" readonly style="font-family: monospace;"><?php
        echo htmlentities(implode("\n", $emails));
      ?></textarea>
    </div>
  </div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title" style="display: inline-block;">Detailed List</h3>
      <button type="button" class="btn btn-sm btn-default" onclick="toggleSection('detailed-list-body', this)" style="margin-left: 10px;">Show</button>
    </div>
    <div class="panel-body" id="detailed-list-body" style="display: none;">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Email</th>
            <th>Display Name</th>
            <th>Premium</th>
            <th>Last Login</th>
            <th>Days Since Login</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $now = new DateTime();
          foreach ( $rows as $row ):
            $days_since_login = 'N/A';
            if ( $row['login_at'] ) {
              try {
                $login_date = new DateTime($row['login_at']);
                $diff = $now->diff($login_date);
                $days_since_login = $diff->days;
              } catch (Exception $e) {
                $days_since_login = 'N/A';
              }
            }
          ?>
            <tr>
              <td><?= htmlentities($row['email']) ?></td>
              <td><?= htmlentities($row['displayname'] ? $row['displayname'] : 'N/A') ?></td>
              <td><?= (int) U::get($row, 'premium', 0) > 0 ? 'yes' : 'no' ?></td>
              <td><?= htmlentities($row['login_at'] ? $row['login_at'] : 'Never') ?></td>
              <td><?= htmlentities((string)$days_since_login) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php endif; ?>

<script>
function toggleSection(sectionId, button) {
    var section = document.getElementById(sectionId);
    if (section.style.display === 'none' || section.style.display === '') {
        section.style.display = 'block';
        button.textContent = 'Hide';
    } else {
        section.style.display = 'none';
        button.textContent = 'Show';
    }
}
</script>
