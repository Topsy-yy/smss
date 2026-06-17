<?php
session_start();
require '../config.php';
require '../backend/security.php';
require_login(2);

function formatOpportunityType($typeCode) {
    $map = array(
        'merit_based' => 'Merit Based',
        'means_based' => 'Means Based',
        'cultural_talent' => 'Cultural / Arts',
        'visual_art' => 'Visual Art',
        'sports_talent' => 'Sports Talent',
        'science_maths_based' => 'Science & Maths',
        'technology_based' => 'Technology Based'
    );

    return isset($map[$typeCode]) ? $map[$typeCode] : 'Scholarship';
}

function formatTimeAgo($datetime) {
    $ts = strtotime((string) $datetime);
    if (!$ts) {
        return 'Recently';
    }

    $diff = time() - $ts;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' mins ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    }
    return floor($diff / 86400) . ' days ago';
}

$conn = getDbConnection();
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$stats = array(
    'total_students' => 0,
    'total_opps' => 0,
    'verified' => 0,
    'pending' => 0
);

$countQueries = array(
    'total_students' => "SELECT COUNT(*) AS total FROM student",
    'total_opps' => "SELECT COUNT(*) AS total FROM scholarship",
    'verified' => "SELECT COUNT(*) AS total FROM scholarship WHERE adminapproval = 'Approved'",
    'pending' => "SELECT COUNT(*) AS total FROM scholarship WHERE adminapproval = 'Pending'"
);

foreach ($countQueries as $key => $query) {
    $res = $conn->query($query);
    if ($res && ($row = $res->fetch_assoc())) {
        $stats[$key] = (int) $row['total'];
    }
}

$opportunities = array();
$oppSql = "
    SELECT
        S.scholarshipID AS id,
        S.sigID,
        S.schname AS title,
        COALESCE(NULLIF(SIG.`organization/university`, ''), TRIM(CONCAT(COALESCE(SIG.firstName, ''), ' ', COALESCE(SIG.lastName, ''))), 'Unknown Organization') AS org,
        S.sch AS type_code,
        S.appDeadline AS deadline,
        S.adminapproval AS status,
        S.schstatus,
        COUNT(A.applicationID) AS apps
    FROM scholarship S
    LEFT JOIN signatory SIG ON SIG.sigID = S.sigID
    LEFT JOIN application A ON A.scholarshipID = S.scholarshipID
    GROUP BY S.scholarshipID, S.sigID, S.schname, org, S.sch, S.appDeadline, S.adminapproval, S.schstatus
    ORDER BY S.appDeadline ASC, S.scholarshipID DESC
";
$oppRes = $conn->query($oppSql);
if ($oppRes) {
    while ($row = $oppRes->fetch_assoc()) {
        $daysLeft = (int) floor((strtotime($row['deadline']) - strtotime(date('Y-m-d'))) / 86400);
        $row['days_left'] = $daysLeft;
        $row['type'] = formatOpportunityType($row['type_code']);
        $opportunities[] = $row;
    }
}

$activities = array();

$appActivitySql = "
    SELECT A.appDate, ST.firstName, ST.lastName, S.schname
    FROM application A
    JOIN student ST ON ST.studentID = A.studentID
    JOIN scholarship S ON S.scholarshipID = A.scholarshipID
    ORDER BY A.appDate DESC
    LIMIT 4
";
$appActRes = $conn->query($appActivitySql);
if ($appActRes) {
    while ($row = $appActRes->fetch_assoc()) {
        $studentName = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
        if ($studentName === '') {
            $studentName = 'A student';
        }

        $activities[] = array(
            'type' => 'app',
            'desc' => $studentName . ' submitted an application for ' . ($row['schname'] ?: 'a scholarship') . '.',
            'time' => formatTimeAgo($row['appDate'])
        );
    }
}

$recentOppSql = "
    SELECT scholarshipID, schname, adminapproval
    FROM scholarship
    ORDER BY scholarshipID DESC
    LIMIT 3
";
$recentOppRes = $conn->query($recentOppSql);
if ($recentOppRes) {
    while ($row = $recentOppRes->fetch_assoc()) {
        $activities[] = array(
            'type' => 'opp',
            'desc' => 'New listing: ' . ($row['schname'] ?: ('Scholarship #' . $row['scholarshipID'])) . ' (' . ($row['adminapproval'] ?: 'Pending') . ').',
            'time' => 'Recently'
        );
    }
}

$recentStudentSql = "
    SELECT studentID, firstName, lastName
    FROM student
    ORDER BY studentID DESC
    LIMIT 3
";
$recentStudentRes = $conn->query($recentStudentSql);
if ($recentStudentRes) {
    while ($row = $recentStudentRes->fetch_assoc()) {
        $name = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
        if ($name === '') {
            $name = 'Student #' . $row['studentID'];
        }
        $activities[] = array(
            'type' => 'user',
            'desc' => $name . ' joined the platform.',
            'time' => 'Recently'
        );
    }
}

$activities = array_slice($activities, 0, 8);

$expiredOpps = array();
$expiredSql = "
    SELECT
        S.scholarshipID,
        S.schname,
        COALESCE(NULLIF(SIG.`organization/university`, ''), TRIM(CONCAT(COALESCE(SIG.firstName, ''), ' ', COALESCE(SIG.lastName, ''))), 'Unknown Organization') AS org,
        DATEDIFF(CURDATE(), S.appDeadline) AS expired_days
    FROM scholarship S
    LEFT JOIN signatory SIG ON SIG.sigID = S.sigID
    WHERE S.appDeadline < CURDATE()
    ORDER BY S.appDeadline DESC
    LIMIT 8
";
$expiredRes = $conn->query($expiredSql);
if ($expiredRes) {
    while ($row = $expiredRes->fetch_assoc()) {
        $expiredOpps[] = $row;
    }
}

$activeSignatories = array();
$sigSql = "
  SELECT sigID, firstName, lastName, `organization/university` AS organization
  FROM signatory
  WHERE status = 'active'
  ORDER BY sigID ASC
";
$sigRes = $conn->query($sigSql);
if ($sigRes) {
  while ($row = $sigRes->fetch_assoc()) {
    $displayName = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
    if ($displayName === '') {
      $displayName = 'Signatory #' . $row['sigID'];
    }
    $activeSignatories[] = array(
      'sigID' => (int) $row['sigID'],
      'name' => $displayName,
      'organization' => (string) ($row['organization'] ?? '')
    );
  }
}

$conn->close();

$pageTitle = 'Admin Dashboard';
$assetPrefix = '../';
$roleStyles = array('css/admin.css');
$pageStyles = array('css/pages/admin-dashboard.css');
require __DIR__ . '/../includes/head-dashboard.php';
?>
<body class="app-shell">
  <div class="app-page">

    <?php require __DIR__ . '/../includes/nav-admin.php'; ?>

    <div class="admin-container">

      <header class="admin-header">
        <div>
          <h1>Admin Dashboard</h1>
          <p>Manage opportunities and verify listings</p>
        </div>
        <button id="btnOpenModal" class="btn-primary" type="button">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add Opportunity
        </button>
      </header>

      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Students</h3>
          <div class="value"><?php echo number_format($stats['total_students']); ?></div>
          <div class="trend-neutral">Registered student accounts</div>
        </div>
        <div class="stat-card">
          <h3>Total Opportunities</h3>
          <div class="value"><?php echo number_format($stats['total_opps']); ?></div>
          <div class="trend-neutral">All scholarship listings</div>
        </div>
        <div class="stat-card">
          <h3>Verified</h3>
          <div class="value" style="color: var(--success);"><?php echo number_format($stats['verified']); ?></div>
          <div class="trend-neutral">Approved and visible</div>
        </div>
        <div class="stat-card" style="<?php if ($stats['pending'] > 0) echo 'border-color: var(--warning); background: #fffbeb;'; ?>">
          <h3 style="<?php if ($stats['pending'] > 0) echo 'color: #b45309;'; ?>">Pending Verification</h3>
          <div class="value" style="color: var(--warning);"><?php echo number_format($stats['pending']); ?></div>
          <div class="trend-neutral">Awaiting admin review</div>
        </div>
      </div>

      <?php if ($stats['pending'] > 0): ?>
      <div class="alert-banner">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <div>
          <strong>Attention Required:</strong> You have <?php echo $stats['pending']; ?> opportunities awaiting verification.
        </div>
      </div>
      <?php endif; ?>

      <section class="table-section">
        <div class="table-header-bar">
          <h2 style="font-size: 1.25rem;">Opportunity Management</h2>
          <div class="filter-group">
            <button class="filter-btn active" data-filter="all" type="button">All Opportunities</button>
            <button class="filter-btn" data-filter="verified" type="button">Verified</button>
            <button class="filter-btn" data-filter="pending" type="button">Pending</button>
          </div>
        </div>

        <table class="admin-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Organization</th>
              <th>Type</th>
              <th>Deadline</th>
              <th>Status</th>
              <th>Applications</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($opportunities as $opp): ?>
              <?php
                $isVerified = ($opp['status'] === 'Approved');
                $statusFilter = $isVerified ? 'verified' : 'pending';
              ?>
              <tr class="opp-row" data-status="<?php echo $statusFilter; ?>">
                <td style="font-weight: 600; color: var(--admin-primary);"><?php echo htmlspecialchars($opp['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($opp['org'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="badge badge-type"><?php echo htmlspecialchars($opp['type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td class="<?php echo ((int)$opp['days_left'] < 7) ? 'text-danger' : ''; ?>">
                  <?php echo date('M d, Y', strtotime($opp['deadline'])); ?><br>
                  <span style="font-size: 0.75rem; font-weight: normal;">
                    <?php if ((int)$opp['days_left'] >= 0): ?>
                      <?php echo (int)$opp['days_left']; ?> days left
                    <?php else: ?>
                      Expired <?php echo abs((int)$opp['days_left']); ?> days ago
                    <?php endif; ?>
                  </span>
                </td>
                <td class="status-cell">
                  <?php if ($opp['status'] === 'Approved'): ?>
                    <span class="badge badge-verified">Verified</span>
                  <?php elseif ($opp['status'] === 'Pending'): ?>
                    <span class="badge badge-pending">Pending</span>
                  <?php else: ?>
                    <span class="badge badge-pending"><?php echo htmlspecialchars($opp['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php endif; ?>
                </td>
                <td><strong style="font-size: 1.1rem;"><?php echo (int)$opp['apps']; ?></strong></td>
                <td>
                  <div class="action-btns">
                    <?php if ($opp['status'] === 'Pending'): ?>
                      <form action="../backend/adminAcceptReject.php" method="post" style="display:inline-block;">
                        <input type="hidden" name="schID" value="<?php echo (int)$opp['id']; ?>">
                        <button class="btn-verify" type="submit" name="accrej" value="Accept">Verify</button>
                      </form>
                    <?php endif; ?>

                    <form action="tempSchView.php" method="post" style="display:inline-block;">
                      <input type="hidden" name="schname" value="<?php echo htmlspecialchars($opp['title'], ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="sigID" value="<?php echo (int)$opp['sigID']; ?>">
                      <input type="hidden" name="schID" value="<?php echo (int)$opp['id']; ?>">
                      <button class="icon-btn" type="submit" name="view" value="View" title="View">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      </button>
                    </form>

                    <form action="../backend/adminDeleteScholarship.php" method="post" style="display:inline-block;" onsubmit="return confirm('Delete this scholarship permanently? This will remove related applications too.');">
                      <input type="hidden" name="schID" value="<?php echo (int)$opp['id']; ?>">
                      <button class="icon-btn delete" type="submit" title="Delete scholarship">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <div class="bottom-grid">
        <section class="panel">
          <h2 class="panel-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--admin-primary)" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            Recent Activity
          </h2>
          <div class="activity-feed">
            <?php foreach ($activities as $act): ?>
              <div class="activity-item">
                <div class="activity-icon icon-<?php echo htmlspecialchars($act['type'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php if ($act['type'] === 'app'): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  <?php elseif ($act['type'] === 'opp'): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                  <?php else: ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                  <?php endif; ?>
                </div>
                <div class="activity-content">
                  <p><?php echo htmlspecialchars($act['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <span class="activity-time"><?php echo htmlspecialchars($act['time'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="panel">
          <h2 class="panel-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            Expired Opportunities
          </h2>

          <div id="emptyExpiredState" class="empty-state" style="display: <?php echo empty($expiredOpps) ? 'block' : 'none'; ?>;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <p>No expired opportunities.</p>
          </div>

          <ul class="expired-list">
            <?php foreach ($expiredOpps as $exp): ?>
              <li class="expired-item">
                <div class="expired-info">
                  <h4><?php echo htmlspecialchars($exp['schname'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <p>Expired <?php echo (int)$exp['expired_days']; ?> days ago</p>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>
    </div>

    <div id="addOppModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Create New Opportunity</h2>
          <button class="btn-close btn-close-modal" type="button">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>

        <div class="modal-body">
          <form id="addOppForm" action="../backend/adminCreateOpportunity.php" method="post">
            <div class="form-grid">
              <div class="form-group full">
                <label>Opportunity Title</label>
                <input type="text" class="form-control" name="schname" placeholder="e.g. Master's IT Scholarship 2026" required>
              </div>
              <div class="form-group">
                <label>Signatory Owner</label>
                <select class="form-control" name="sigID" required>
                  <option value="">Select signatory</option>
                  <?php foreach ($activeSignatories as $sig): ?>
                    <option value="<?php echo (int)$sig['sigID']; ?>"><?php echo htmlspecialchars($sig['name'] . ' (ID:' . $sig['sigID'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Category</label>
                <select class="form-control" name="scholarship" required>
                  <option value="merit_based">Merit Based</option>
                  <option value="means_based">Means Based</option>
                  <option value="cultural_talent">Cultural / Arts</option>
                  <option value="visual_art">Visual Art</option>
                  <option value="sports_talent">Sports Talent</option>
                  <option value="science_maths_based">Science & Maths</option>
                  <option value="technology_based">Technology Based</option>
                </select>
              </div>
              <div class="form-group">
                <label>Education Level</label>
                <select class="form-control" name="degree" required>
                  <option value="high school">High School</option>
                  <option value="diploma">Diploma</option>
                  <option value="undergraduate">Undergraduate</option>
                  <option value="postgraduate">Postgraduate</option>
                  <option value="phd">PhD</option>
                </select>
              </div>
              <div class="form-group">
                <label>Gender</label>
                <select class="form-control" name="gender" required>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="male+female">Open To All</option>
                </select>
              </div>
              <div class="form-group">
                <label>Target Financial Need</label>
                <select class="form-control" name="target_financial_need" required>
                  <option value="Any">Any</option>
                  <option value="Low">Low</option>
                  <option value="Medium">Medium</option>
                  <option value="High">High</option>
                  <option value="Critical">Critical</option>
                </select>
              </div>
              <div class="form-group">
                <label>Deadline</label>
                <input type="date" class="form-control" name="appdeadline" required>
              </div>
              <div class="form-group">
                <label>Funding</label>
                <input type="text" class="form-control" name="funding" placeholder="e.g. KES 100,000">
              </div>
              <div class="form-group full">
                <label>Location</label>
                <input type="text" class="form-control" name="schlocation" placeholder="e.g. Nairobi or Remote">
              </div>
              <div class="form-group full">
                <label>Description</label>
                <textarea class="form-control" rows="3" name="description" placeholder="Brief overview..." required></textarea>
              </div>
              <div class="form-group full">
                <label>Eligibility Requirements</label>
                <textarea class="form-control" rows="3" name="eligibility" placeholder="Who can apply..." required></textarea>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-secondary btn-close-modal">Cancel</button>
          <button type="submit" form="addOppForm" class="btn-primary">Create Opportunity</button>
        </div>
      </div>
    </div>

    <?php
    $ctaTitle = 'Ready to keep the platform <strong>running smoothly</strong>?';
    $ctaText = 'Continue managing approvals, users, and scholarships with confidence.';
    require __DIR__ . '/../includes/footer-dashboard.php';
    ?>

  </div>

  <script src="../js/admin-dashboard.js"></script>
<?php require __DIR__ . '/../includes/scripts-dashboard.php'; ?>
