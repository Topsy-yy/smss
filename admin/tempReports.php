<?php
session_start();
require '../config.php';
require '../backend/security.php';
require_login(2);

$period = isset($_GET['period']) ? strtolower(trim((string) $_GET['period'])) : 'weekly';
if ($period !== 'monthly') {
    $period = 'weekly';
}

$periodDays = ($period === 'monthly') ? 30 : 7;
$periodLabel = ($period === 'monthly') ? 'Last 30 Days' : 'Last 7 Days';
$rangeStart = date('Y-m-d 00:00:00', strtotime('-' . ($periodDays - 1) . ' days'));
$rangeEnd = date('Y-m-d 23:59:59');

$prevRangeEndTs = strtotime($rangeStart . ' -1 second');
$prevRangeStartTs = strtotime('-' . ($periodDays - 1) . ' days', $prevRangeEndTs);
$prevRangeStart = date('Y-m-d 00:00:00', $prevRangeStartTs);
$prevRangeEnd = date('Y-m-d 23:59:59', $prevRangeEndTs);

$conn = getDbConnection();
if (!$conn || $conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$hasApprovedAt = false;
$colResult = $conn->query("SHOW COLUMNS FROM scholarship LIKE 'approved_at'");
if ($colResult && $colResult->num_rows > 0) {
  $hasApprovedAt = true;
} else {
  // Backfill schema support for period-based approved listing reports.
  if ($conn->query("ALTER TABLE scholarship ADD COLUMN approved_at DATETIME NULL") === true) {
    $hasApprovedAt = true;
  }
}

$conn->query(
    "CREATE TABLE IF NOT EXISTS sms_dispatch_log (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(32) NOT NULL,
        message_preview VARCHAR(255) NOT NULL,
        message_type VARCHAR(50) NOT NULL DEFAULT 'general',
        trigger_source VARCHAR(50) NOT NULL DEFAULT 'manual',
        provider_http_code INT NOT NULL DEFAULT 0,
        provider_status VARCHAR(20) NOT NULL DEFAULT 'failed',
        provider_message VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sms_dispatch_log_created_at (created_at),
        INDEX idx_sms_dispatch_log_status (provider_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

function fetchSingleInt($conn, $sql, $types = '', $params = array()) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $value = 0;
    if ($result && ($row = $result->fetch_assoc())) {
        $first = array_values($row);
        $value = isset($first[0]) ? (int) $first[0] : 0;
    }
    $stmt->close();
    return $value;
}

  function calculateTrend($current, $previous) {
    $current = (int) $current;
    $previous = (int) $previous;
    $delta = $current - $previous;
    $trendClass = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
    $deltaPrefix = $delta > 0 ? '+' : '';

    if ($previous > 0) {
      $percent = ($delta / $previous) * 100;
    } elseif ($current > 0) {
      $percent = 100;
    } else {
      $percent = 0;
    }

    return array(
      'delta' => $delta,
      'delta_prefix' => $deltaPrefix,
      'trend_class' => $trendClass,
      'percent' => $percent
    );
  }

$activeStudents = fetchSingleInt($conn, "SELECT COUNT(*) FROM student WHERE status = 'active'");
$activeSignatories = fetchSingleInt($conn, "SELECT COUNT(*) FROM signatory WHERE status = 'active'");
$activeAdmins = fetchSingleInt($conn, "SELECT COUNT(*) FROM admin WHERE status = 'active'");
$activeTotal = $activeStudents + $activeSignatories + $activeAdmins;

$usersWithActivity = fetchSingleInt(
    $conn,
    "SELECT COUNT(DISTINCT studentID) FROM application WHERE appDate BETWEEN ? AND ?",
    'ss',
    array($rangeStart, $rangeEnd)
);

$verifiedListingsPeriod = 0;
$verifiedListingsPrevious = 0;
if ($hasApprovedAt) {
    $verifiedListingsPeriod = fetchSingleInt(
        $conn,
        "SELECT COUNT(*) FROM scholarship WHERE adminapproval = 'Approved' AND approved_at BETWEEN ? AND ?",
        'ss',
        array($rangeStart, $rangeEnd)
    );
    $verifiedListingsPrevious = fetchSingleInt(
        $conn,
        "SELECT COUNT(*) FROM scholarship WHERE adminapproval = 'Approved' AND approved_at BETWEEN ? AND ?",
        'ss',
        array($prevRangeStart, $prevRangeEnd)
    );
}

$smsDispatchedPeriod = fetchSingleInt(
    $conn,
    "SELECT COUNT(*) FROM sms_dispatch_log WHERE provider_status = 'sent' AND created_at BETWEEN ? AND ?",
    'ss',
    array($rangeStart, $rangeEnd)
);
$smsDispatchedPrevious = fetchSingleInt(
    $conn,
    "SELECT COUNT(*) FROM sms_dispatch_log WHERE provider_status = 'sent' AND created_at BETWEEN ? AND ?",
    'ss',
    array($prevRangeStart, $prevRangeEnd)
);

$smsSourceBreakdown = array();
$sourceSql = "
    SELECT trigger_source, COUNT(*) AS total
    FROM sms_dispatch_log
    WHERE provider_status = 'sent' AND created_at BETWEEN ? AND ?
    GROUP BY trigger_source
    ORDER BY total DESC
";
$sourceStmt = $conn->prepare($sourceSql);
if ($sourceStmt) {
    $sourceStmt->bind_param('ss', $rangeStart, $rangeEnd);
    $sourceStmt->execute();
    $sourceRes = $sourceStmt->get_result();
    if ($sourceRes) {
        while ($row = $sourceRes->fetch_assoc()) {
            $smsSourceBreakdown[] = $row;
        }
    }
    $sourceStmt->close();
}

$comparisonRows = array(
    array(
        'label' => 'Verified listings',
        'current' => $verifiedListingsPeriod,
        'previous' => $verifiedListingsPrevious
    ),
    array(
        'label' => 'SMS dispatched',
        'current' => $smsDispatchedPeriod,
        'previous' => $smsDispatchedPrevious
    )
);

    $listingTrend = calculateTrend($verifiedListingsPeriod, $verifiedListingsPrevious);
    $smsTrend = calculateTrend($smsDispatchedPeriod, $smsDispatchedPrevious);

    $sourceTotal = 0;
    foreach ($smsSourceBreakdown as $sourceRow) {
      $sourceTotal += (int) $sourceRow['total'];
    }

$conn->close();

$pageTitle = 'Admin Reports';
$assetPrefix = '../';
$roleStyles = array('css/admin.css');
$pageStyles = array('css/pages/admin-reports.css');
require __DIR__ . '/../includes/head-dashboard.php';
?>
<body class="app-shell">
  <div class="app-page">
    <?php require __DIR__ . '/../includes/nav-admin.php'; ?>

    <main class="reports-container">
      <section class="reports-hero">
        <h2>Platform Statistics Dashboard</h2>
        <p>Administrative statistics for activity, verified scholarships, and SMS delivery volume.</p>

        <div class="period-switcher">
          <a class="period-btn <?php echo $period === 'weekly' ? 'active' : ''; ?>" href="tempReports.php?period=weekly">Weekly</a>
          <a class="period-btn <?php echo $period === 'monthly' ? 'active' : ''; ?>" href="tempReports.php?period=monthly">Monthly</a>
        </div>

        <div class="report-actions">
          <?php if (file_exists(__DIR__ . '/../backend/adminDownloadReport.php')): ?>
            <a class="download-btn" href="../backend/adminDownloadReport.php?period=<?php echo urlencode($period); ?>">Download PDF Report</a>
            <p class="download-hint">Your browser download dialog lets you choose where to save the PDF.</p>
          <?php else: ?>
            <p class="download-hint">PDF export is not available yet in this deployment.</p>
          <?php endif; ?>
        </div>

        <div class="range-caption">
          Reporting Window: <strong><?php echo htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
          (<?php echo htmlspecialchars(date('M d, Y', strtotime($rangeStart)), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars(date('M d, Y', strtotime($rangeEnd)), ENT_QUOTES, 'UTF-8'); ?>)
        </div>
      </section>

      <section class="report-grid">
        <article class="report-card">
          <h3>Active User Accounts (Live)</h3>
          <div class="metric"><?php echo number_format($activeTotal); ?></div>
          <div class="subtext">Current active administrators, signatories, and students.</div>
          <ul class="mini-breakdown">
            <li>Admins: <?php echo number_format($activeAdmins); ?></li>
            <li>Signatories: <?php echo number_format($activeSignatories); ?></li>
            <li>Students: <?php echo number_format($activeStudents); ?></li>
          </ul>
        </article>

        <article class="report-card">
          <h3>Verified Listings (<?php echo htmlspecialchars($period === 'monthly' ? '30d' : '7d', ENT_QUOTES, 'UTF-8'); ?>)</h3>
          <div class="metric"><?php echo number_format($verifiedListingsPeriod); ?></div>
          <div class="subtext">Scholarships approved by admins during the selected period.</div>
          <div class="trend <?php echo $listingTrend['trend_class']; ?>">
            <?php echo $listingTrend['delta_prefix'] . number_format($listingTrend['delta']); ?>
            (<?php echo number_format($listingTrend['percent'], 1); ?>%) vs previous period
          </div>
        </article>

        <article class="report-card">
          <h3>SMS Notifications Dispatched (<?php echo htmlspecialchars($period === 'monthly' ? '30d' : '7d', ENT_QUOTES, 'UTF-8'); ?>)</h3>
          <div class="metric"><?php echo number_format($smsDispatchedPeriod); ?></div>
          <div class="subtext">Successfully sent SMS notifications logged by the platform.</div>
          <div class="trend <?php echo $smsTrend['trend_class']; ?>">
            <?php echo $smsTrend['delta_prefix'] . number_format($smsTrend['delta']); ?>
            (<?php echo number_format($smsTrend['percent'], 1); ?>%) vs previous period
          </div>
        </article>

        <article class="report-card">
          <h3>Users With Activity</h3>
          <div class="metric"><?php echo number_format($usersWithActivity); ?></div>
          <div class="subtext">Distinct students with application activity in the selected period.</div>
        </article>
      </section>

      <section class="report-panels">
        <article class="panel">
          <h3>Period Comparison</h3>
          <table class="report-table">
            <thead>
              <tr>
                <th>Metric</th>
                <th>Current Period</th>
                <th>Previous Period</th>
                <th>Change</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($comparisonRows as $row): ?>
                <?php
                $trend = calculateTrend($row['current'], $row['previous']);
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo number_format((int) $row['current']); ?></td>
                  <td><?php echo number_format((int) $row['previous']); ?></td>
                  <td>
                    <span class="delta <?php echo $trend['trend_class']; ?>">
                      <?php echo $trend['delta_prefix'] . number_format($trend['delta']); ?>
                      (<?php echo number_format($trend['percent'], 1); ?>%)
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </article>

        <article class="panel">
          <h3>SMS Source Breakdown</h3>
          <?php if (empty($smsSourceBreakdown)): ?>
            <p class="empty-note">No sent SMS logs found in this period.</p>
          <?php else: ?>
            <table class="report-table">
              <thead>
                <tr>
                  <th>Source</th>
                  <th>Sent Volume</th>
                  <th>Share</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($smsSourceBreakdown as $sourceRow): ?>
                  <?php
                  $sourceCount = (int) $sourceRow['total'];
                  $sourceShare = $sourceTotal > 0 ? ($sourceCount / $sourceTotal) * 100 : 0;
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string) $sourceRow['trigger_source'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format($sourceCount); ?></td>
                    <td>
                      <div class="share-wrap">
                        <span class="share-value"><?php echo number_format($sourceShare, 1); ?>%</span>
                        <div class="share-bar"><span style="width: <?php echo number_format($sourceShare, 1, '.', ''); ?>%"></span></div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </article>
      </section>
    </main>
  </div>

<?php require __DIR__ . '/../includes/scripts-dashboard.php'; ?>
