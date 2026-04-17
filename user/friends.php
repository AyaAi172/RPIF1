<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/RPIF1/admin/includes/CommonCode.php";
requireLogin();
$title = "Friends";

$msg = "";
$currentUserId = (int)$_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  checkCsrf();
  $action = $_POST["action"] ?? "";

  if ($action === "send_request") {
    $username = trim($_POST["username"] ?? "");

    if ($username === "") {
      $msg = "Please enter a username.";
    } else {
      $stmt = mysqli_prepare($conn, "SELECT user_id, username FROM users WHERE username = ?");
      mysqli_stmt_bind_param($stmt, "s", $username);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);

      if (!($target = mysqli_fetch_assoc($res))) {
        $msg = "User not found.";
      } else {
        $targetId = (int)$target["user_id"];

        if ($targetId === $currentUserId) {
          $msg = "You cannot send a friend request to yourself.";
        } else {
          $stmt = mysqli_prepare($conn, "SELECT 1 FROM friendships WHERE user_id = ? AND friend_user_id = ?");
          mysqli_stmt_bind_param($stmt, "ii", $currentUserId, $targetId);
          mysqli_stmt_execute($stmt);
          $res = mysqli_stmt_get_result($stmt);

          if (mysqli_fetch_assoc($res)) {
            $msg = "You are already friends with this user.";
          } else {
            $stmt = mysqli_prepare($conn, "
              SELECT status
              FROM friend_requests
              WHERE
                (sender_user_id = ? AND receiver_user_id = ? AND status = 'pending')
                OR
                (sender_user_id = ? AND receiver_user_id = ? AND status = 'pending')
              LIMIT 1
            ");
            mysqli_stmt_bind_param($stmt, "iiii", $currentUserId, $targetId, $targetId, $currentUserId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if (mysqli_fetch_assoc($res)) {
              $msg = "A friend request is already pending between you and this user.";
            } else {
              $stmt = mysqli_prepare($conn, "
                INSERT INTO friend_requests (sender_user_id, receiver_user_id, status)
                VALUES (?, ?, 'pending')
              ");
              mysqli_stmt_bind_param($stmt, "ii", $currentUserId, $targetId);

              if (mysqli_stmt_execute($stmt)) {
                $msg = "Friend request sent to " . $target["username"] . ".";
              } else {
                $msg = "Could not send the friend request.";
              }
            }
          }
        }
      }
    }
  }

  if ($action === "accept_request") {
    $requestId = (int)($_POST["request_id"] ?? 0);

    $stmt = mysqli_prepare($conn, "
      SELECT request_id, sender_user_id, receiver_user_id
      FROM friend_requests
      WHERE request_id = ? AND receiver_user_id = ? AND status = 'pending'
      LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "ii", $requestId, $currentUserId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($request = mysqli_fetch_assoc($res)) {
      $senderId = (int)$request["sender_user_id"];

      mysqli_begin_transaction($conn);

      try {
        $stmt = mysqli_prepare($conn, "
          UPDATE friend_requests
          SET status = 'accepted', responded_at = NOW()
          WHERE request_id = ? AND status = 'pending'
        ");
        mysqli_stmt_bind_param($stmt, "i", $requestId);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) !== 1) {
          throw new Exception("The request could not be updated.");
        }

        $stmt = mysqli_prepare($conn, "
          INSERT INTO friendships (user_id, friend_user_id)
          VALUES (?, ?), (?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iiii", $currentUserId, $senderId, $senderId, $currentUserId);
        mysqli_stmt_execute($stmt);

        mysqli_commit($conn);
        $msg = "Friend request accepted.";
      } catch (Throwable $e) {
        mysqli_rollback($conn);
        $msg = "Could not accept the friend request.";
      }
    } else {
      $msg = "Friend request not found.";
    }
  }

  if ($action === "reject_request") {
    $requestId = (int)($_POST["request_id"] ?? 0);

    $stmt = mysqli_prepare($conn, "
      UPDATE friend_requests
      SET status = 'rejected', responded_at = NOW()
      WHERE request_id = ? AND receiver_user_id = ? AND status = 'pending'
    ");
    mysqli_stmt_bind_param($stmt, "ii", $requestId, $currentUserId);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) === 1) {
      $msg = "Friend request rejected.";
    } else {
      $msg = "Friend request not found.";
    }
  }

  if ($action === "remove_friend") {
    $friendId = (int)($_POST["friend_user_id"] ?? 0);

    mysqli_begin_transaction($conn);

    try {
      $stmt = mysqli_prepare($conn, "
        DELETE FROM friendships
        WHERE (user_id = ? AND friend_user_id = ?)
           OR (user_id = ? AND friend_user_id = ?)
      ");
      mysqli_stmt_bind_param($stmt, "iiii", $currentUserId, $friendId, $friendId, $currentUserId);
      mysqli_stmt_execute($stmt);

      $stmt = mysqli_prepare($conn, "
        UPDATE friend_requests
        SET status = 'rejected', responded_at = NOW()
        WHERE
          (
            sender_user_id = ? AND receiver_user_id = ?
            AND status = 'pending'
          )
          OR
          (
            sender_user_id = ? AND receiver_user_id = ?
            AND status = 'pending'
          )
      ");
      mysqli_stmt_bind_param($stmt, "iiii", $currentUserId, $friendId, $friendId, $currentUserId);
      mysqli_stmt_execute($stmt);

      mysqli_commit($conn);
      $msg = "Friendship ended.";
    } catch (Throwable $e) {
      mysqli_rollback($conn);
      $msg = "Could not end the friendship.";
    }
  }
}

$incomingRequests = [];
$stmt = mysqli_prepare($conn, "
  SELECT fr.request_id, fr.created_at, u.user_id, u.username
  FROM friend_requests fr
  INNER JOIN users u ON u.user_id = fr.sender_user_id
  WHERE fr.receiver_user_id = ? AND fr.status = 'pending'
  ORDER BY fr.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $incomingRequests[] = $row;

$outgoingRequests = [];
$stmt = mysqli_prepare($conn, "
  SELECT fr.request_id, fr.created_at, u.user_id, u.username
  FROM friend_requests fr
  INNER JOIN users u ON u.user_id = fr.receiver_user_id
  WHERE fr.sender_user_id = ? AND fr.status = 'pending'
  ORDER BY fr.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $outgoingRequests[] = $row;

$requestUpdates = [];
$stmt = mysqli_prepare($conn, "
  SELECT fr.request_id, fr.status, fr.responded_at, u.username
  FROM friend_requests fr
  INNER JOIN users u ON u.user_id = fr.receiver_user_id
  WHERE fr.sender_user_id = ? AND fr.status IN ('accepted', 'rejected')
  ORDER BY fr.responded_at DESC, fr.request_id DESC
  LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $requestUpdates[] = $row;

$friends = [];
$stmt = mysqli_prepare($conn, "
  SELECT u.user_id, u.username
  FROM friendships f
  INNER JOIN users u ON u.user_id = f.friend_user_id
  WHERE f.user_id = ?
  ORDER BY u.username
");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $friends[] = $row;

$incomingCount = count($incomingRequests);
$outgoingCount = count($outgoingRequests);
$updatesCount = count($requestUpdates);
$friendsCount = count($friends);

require_once PIF_ROOT . "/includes/header.php";
?>

<section class="soft-panel p-4 p-lg-5 mb-4">
  <div class="row align-items-center g-4">
    <div class="col-lg-7">
      <p class="text-uppercase fw-bold text-secondary small mb-2">Community</p>
      <h1 class="display-6 fw-bold mb-2">Friends</h1>
      <p class="text-muted mb-0">Send requests, respond quickly, and keep track of your network from one place.</p>
    </div>
    <div class="col-lg-5">
      <div class="row g-3">
        <div class="col-6">
          <div class="metric-card">
            <div class="metric-label">Incoming</div>
            <div class="metric-value"><?= $incomingCount ?></div>
            <div class="metric-note">Requests waiting for you</div>
          </div>
        </div>
        <div class="col-6">
          <div class="metric-card">
            <div class="metric-label">Friends</div>
            <div class="metric-value"><?= $friendsCount ?></div>
            <div class="metric-note">Accepted friendships</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($msg !== ""): ?>
  <div class="alert alert-info border-0 shadow-sm"><?= esc($msg) ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-xl-4">
    <div class="card p-4">
      <div class="card-header-inline">
        <div>
          <h2 class="section-title">Send friend request</h2>
          <p class="section-kicker">Invite someone by username.</p>
        </div>
        <span class="badge text-bg-dark">New</span>
      </div>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
        <input type="hidden" name="action" value="send_request">

        <label class="form-label">Username</label>
        <input class="form-control form-control-lg mb-3" name="username" placeholder="Enter username" required>

        <p class="text-muted small mb-3">The user will see your request and can accept or reject it.</p>
        <button class="btn btn-dark btn-lg w-100">Send request</button>
      </form>
    </div>
  </div>

  <div class="col-xl-8 friends-stack">
    <div class="card p-4">
      <div class="card-header-inline">
        <div>
          <h2 class="section-title">Incoming requests</h2>
          <p class="section-kicker">People waiting for your response.</p>
        </div>
        <span class="badge rounded-pill text-bg-primary"><?= $incomingCount ?></span>
      </div>

      <?php if ($incomingCount === 0): ?>
        <p class="empty-state">No incoming requests.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Username</th>
                <th>Sent</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($incomingRequests as $request): ?>
                <tr>
                  <td class="fw-semibold"><?= esc($request["username"]) ?></td>
                  <td><?= esc($request["created_at"]) ?></td>
                  <td>
                    <div class="action-group">
                      <form method="post">
                        <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                        <input type="hidden" name="action" value="accept_request">
                        <input type="hidden" name="request_id" value="<?= (int)$request["request_id"] ?>">
                        <button class="btn btn-sm btn-success">Accept</button>
                      </form>

                      <form method="post">
                        <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                        <input type="hidden" name="action" value="reject_request">
                        <input type="hidden" name="request_id" value="<?= (int)$request["request_id"] ?>">
                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card p-4">
      <div class="card-header-inline">
        <div>
          <h2 class="section-title">Outgoing requests</h2>
          <p class="section-kicker">Requests you have already sent.</p>
        </div>
        <span class="badge rounded-pill text-bg-secondary"><?= $outgoingCount ?></span>
      </div>

      <?php if ($outgoingCount === 0): ?>
        <p class="empty-state">No pending requests sent by you.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Username</th>
                <th>Sent</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($outgoingRequests as $request): ?>
                <tr>
                  <td class="fw-semibold"><?= esc($request["username"]) ?></td>
                  <td><?= esc($request["created_at"]) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card p-4">
      <div class="card-header-inline">
        <div>
          <h2 class="section-title">Request updates</h2>
          <p class="section-kicker">Latest answers to the requests you sent.</p>
        </div>
        <span class="badge rounded-pill text-bg-light"><?= $updatesCount ?></span>
      </div>

      <?php if ($updatesCount === 0): ?>
        <p class="empty-state">No updates yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Username</th>
                <th>Status</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($requestUpdates as $update): ?>
                <tr>
                  <td class="fw-semibold"><?= esc($update["username"]) ?></td>
                  <td>
                    <?php if ($update["status"] === "accepted"): ?>
                      <span class="badge bg-success">Accepted</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Rejected</span>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($update["responded_at"] ?? "-") ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card p-4">
      <div class="card-header-inline">
        <div>
          <h2 class="section-title">My friends</h2>
          <p class="section-kicker">Users you are already connected with.</p>
        </div>
        <span class="badge rounded-pill text-bg-success"><?= $friendsCount ?></span>
      </div>

      <?php if ($friendsCount === 0): ?>
        <p class="empty-state">No friends yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Username</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($friends as $friend): ?>
                <tr>
                  <td class="fw-semibold"><?= esc($friend["username"]) ?></td>
                  <td class="text-end">
                    <form method="post" onsubmit="return confirm('End this friendship?');">
                      <input type="hidden" name="csrf" value="<?= esc(csrfToken()) ?>">
                      <input type="hidden" name="action" value="remove_friend">
                      <input type="hidden" name="friend_user_id" value="<?= (int)$friend["user_id"] ?>">
                      <button class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once PIF_ROOT . "/includes/footer.php";
?>
