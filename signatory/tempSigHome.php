<?php
  session_start();
  require '../config.php';
    require '../backend/security.php';
    require_login(3);
  
  $_SESSION['selectedAppID'] = 0;
  $_SESSION['appList'] = NULL;

  $currentUserID = (int) ($_SESSION['currentUserID'] ?? 0);
  if ($currentUserID <= 0) {
      header('Location: ../index.php');
      exit;
  }

  $conn = getDbConnection();
  if (!$conn || $conn->connect_error) {
      die('Database connection failed.');
  }

  function h($value) {
      return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
  }

  function computeProfileCompletion(array $studentRow) {
      $fields = array(
          'firstName', 'lastName', 'gender', 'birthDate', 'birthPlace',
          'presStreetAddr', 'presProvCity', 'presRegion', 'contactNo', 'dept', 'college'
      );

      $filled = 0;
      foreach ($fields as $field) {
          $value = isset($studentRow[$field]) ? trim((string) $studentRow[$field]) : '';
          if ($value !== '' && $value !== '0000-00-00') {
              $filled++;
          }
      }

      return (int) round(($filled / count($fields)) * 100);
  }
  
  $getName = $conn->prepare("SELECT firstName, middleName, lastName FROM signatory WHERE sigID = ? LIMIT 1");
  if ($getName) {
      $getName->bind_param("i", $currentUserID);
      $getName->execute();
      $nameResult = $getName->get_result();
      if ($rows9 = $nameResult->fetch_row()) {
          $parts = array_filter(array_map('trim', $rows9));
          $_SESSION['currentUserName'] = implode(' ', $parts);
      }
      $getName->close();
  }

  // Format the name specifically for the Signatory display
  $displayUserName = (isset($_SESSION['currentUserName']) && $_SESSION['currentUserName'] != '') 
      ? strtoupper($_SESSION['currentUserName']) . " (SIGNATORY)" 
      : "AUTHORIZED SIGNATORY";

  $stats = array('students' => 0, 'profile_pct' => 0, 'active' => 0, 'approved' => 0);
  $applicants = array();
  $applications = array();
  $smsHistory = array();
    $smsNoticeType = trim((string) ($_GET['sms_status'] ?? ''));
    $smsNoticeText = trim((string) ($_GET['sms_message'] ?? ''));

  $statStmt = $conn->prepare(
      "SELECT
          COUNT(DISTINCT A.studentID) AS total_applicants,
          SUM(CASE WHEN LOWER(A.verifiedBySignatory) = 'pending' AND LOWER(A.appstatus) <> 'inactive' THEN 1 ELSE 0 END) AS pending_review,
          SUM(CASE WHEN LOWER(A.verifiedBySignatory) = 'approved' THEN 1 ELSE 0 END) AS approved_count
       FROM application A
       WHERE A.sigID = ?"
  );
  if ($statStmt) {
      $statStmt->bind_param('i', $currentUserID);
      $statStmt->execute();
      $statResult = $statStmt->get_result();
      if ($statRow = $statResult->fetch_assoc()) {
          $stats['students'] = (int) ($statRow['total_applicants'] ?? 0);
          $stats['active'] = (int) ($statRow['pending_review'] ?? 0);
          $stats['approved'] = (int) ($statRow['approved_count'] ?? 0);
      }
      $statStmt->close();
  }

  $applicantSql =
      "SELECT
          ST.studentID,
          ST.firstName,
          ST.middleName,
          ST.lastName,
          ST.presProvCity,
          ST.presRegion,
          ST.college,
          ST.dept,
          ST.gender,
          ST.birthDate,
          ST.birthPlace,
          ST.presStreetAddr,
          ST.contactNo,
            ST.phone,
          COUNT(A.applicationID) AS app_count
       FROM application A
       JOIN student ST ON ST.studentID = A.studentID
       WHERE A.sigID = ?
       GROUP BY ST.studentID, ST.firstName, ST.middleName, ST.lastName,
                ST.presProvCity, ST.presRegion, ST.college, ST.dept, ST.gender,
                ST.birthDate, ST.birthPlace, ST.presStreetAddr, ST.contactNo, ST.phone
       ORDER BY app_count DESC, ST.lastName ASC, ST.firstName ASC";

  $applicantStmt = $conn->prepare($applicantSql);
  $completionTotal = 0;
  if ($applicantStmt) {
      $applicantStmt->bind_param('i', $currentUserID);
      $applicantStmt->execute();
      $applicantResult = $applicantStmt->get_result();
      while ($row = $applicantResult->fetch_assoc()) {
          $profilePct = computeProfileCompletion($row);
          $completionTotal += $profilePct;

          if ($profilePct < 50) {
              $need = 'Critical';
          } elseif ($profilePct < 75) {
              $need = 'High';
          } else {
              $need = 'Medium';
          }

          $name = trim(implode(' ', array_filter(array($row['firstName'], $row['middleName'], $row['lastName']))));
          $location = trim((string) ($row['presProvCity'] ?: $row['presRegion'] ?: 'Not set'));
          $education = trim((string) ($row['college'] ?: $row['dept'] ?: 'Not set'));

          $applicants[] = array(
              'student_id' => (int) $row['studentID'],
              'name' => ($name !== '') ? $name : ('Student #' . (int) $row['studentID']),
              'location' => $location,
              'edu' => $education,
              'prog' => $profilePct,
              'apps' => (int) $row['app_count'],
              'need' => $need,
              'phone' => trim((string) ($row['phone'] ?? '')),
          );
      }
      $applicantStmt->close();
  }

  if (count($applicants) > 0) {
      $stats['profile_pct'] = (int) round($completionTotal / count($applicants));
  }

  $reviewSql =
      "SELECT
          A.applicationID,
          A.appDate,
          A.appstatus,
          A.verifiedBySignatory,
          ST.firstName,
          ST.middleName,
          ST.lastName,
          S.schname,
          S.appDeadline
       FROM application A
       JOIN student ST ON ST.studentID = A.studentID
       JOIN scholarship S ON S.scholarshipID = A.scholarshipID
       WHERE A.sigID = ?
       ORDER BY A.appDate DESC
       LIMIT 8";

  $reviewStmt = $conn->prepare($reviewSql);
  if ($reviewStmt) {
      $reviewStmt->bind_param('i', $currentUserID);
      $reviewStmt->execute();
      $reviewResult = $reviewStmt->get_result();
      while ($row = $reviewResult->fetch_assoc()) {
          $studentName = trim(implode(' ', array_filter(array($row['firstName'], $row['middleName'], $row['lastName']))));
          $status = trim((string) $row['verifiedBySignatory']);
          if ($status === '') {
              $status = trim((string) $row['appstatus']);
          }
          $status = ($status !== '') ? $status : 'Pending';

          $summary = 'Applied: ' . date('M d, Y', strtotime((string) $row['appDate']));
          if (!empty($row['appDeadline']) && $row['appDeadline'] !== '0000-00-00') {
              $summary .= ' | Deadline: ' . date('M d, Y', strtotime((string) $row['appDeadline']));
          }

          $applications[] = array(
              'id' => (int) $row['applicationID'],
              'student' => ($studentName !== '') ? $studentName : 'Student',
              'opp' => (string) $row['schname'],
              'summary' => $summary,
              'docs' => 'Submitted via application portal',
              'status' => $status,
          );

          $smsType = (strtolower($status) === 'approved') ? 'Status' : 'Reminder';
          $smsHistory[] = array(
              'to' => ($studentName !== '') ? $studentName : 'Student',
              'type' => $smsType,
              'msg' => 'Application #' . (int) $row['applicationID'] . ' is currently "' . $status . '".',
              'time' => date('M d, Y H:i', strtotime((string) $row['appDate'])),
              'status' => 'Generated',
          );
      }
      $reviewStmt->close();
  }

  if (count($smsHistory) > 6) {
      $smsHistory = array_slice($smsHistory, 0, 6);
  }

  $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signatory Dashboard | ScholarConnect</title>
    <!-- Linked directly to your new Signatory CSS file -->
    <link rel="stylesheet" href="../css/pages/signatory.css">
</head>
<body>

    <?php
      $sigNavActive = 'home';
      $sigDisplayName = $displayUserName;
      require __DIR__ . '/../includes/nav-signatory.php';
    ?>

    <!-- HERO CAROUSEL -->
    <div class="hero-carousel">
        <div class="wave-circle c1"></div>
        <div class="wave-circle c2"></div>
        
        <button class="carousel-nav nav-left">❮</button>
        
        <div class="slide active">
            <img src="../sig-pics/rev.jpg" alt="Review applications" class="slide-image">
            <h2>Review applications faster using a focused workflow.</h2>
        </div>
        <div class="slide">
            <img src="../sig-pics/crea.jpg" alt="Create scholarship opportunities" class="slide-image">
            <h2>Empower students from underserved communities.</h2>
        </div>
        <div class="slide">
            <img src="../sig-pics/track.jpg" alt="Track scholarship outcomes" class="slide-image">
            <h2>Stay ahead of every deadline with real-time alerts.</h2>
        </div>
        
        <button class="carousel-nav nav-right">❯</button>
        
        <div class="carousel-dots">
            <div class="dot active"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <!-- STATS ROW -->
    <div class="stats-row">
        <div class="stat-box">
            <h4>Total Applicants</h4>
            <div class="val"><?php echo h($stats['students']); ?></div>
        </div>
        <div class="stat-box">
            <h4>Profile Completion Avg</h4>
            <div class="val"><?php echo h($stats['profile_pct']); ?>%</div>
            <div class="stat-progress">
                <div class="stat-progress-fill" style="width: <?php echo h($stats['profile_pct']); ?>%;"></div>
            </div>
        </div>
        <div class="stat-box">
            <h4>Pending Review</h4>
            <div class="val"><?php echo h($stats['active']); ?></div>
        </div>
        <div class="stat-box">
            <h4>Approved</h4>
            <div class="val" style="color: var(--accent-green);"><?php echo h($stats['approved']); ?></div>
        </div>
    </div>

    <!-- OVERVIEW SECTION -->
    <section class="container overview-section">
        <!-- Oversized Bar Chart Icon -->
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
        </svg>
        <h2>SIGNATORY DASHBOARD OVERVIEW</h2>
        <p>Publish scholarships, review applicants, and update decisions from one unified workspace.</p>
    </section>

    <!-- FULL BLEED BANNER -->
    <section class="banner-section">
        <div class="banner-overlay"></div>
        <div class="banner-content container">
            <h2>Built for Scholarship Providers</h2>
            <p style="margin-bottom: 2rem; font-size: 1.1rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                Create transparent opportunities, define clear eligibility criteria, and keep applicants informed throughout the lifecycle.
            </p>
            <a href="tempAddScholarship.php"><button class="btn-ghost">CREATE SCHOLARSHIP</button></a>
        </div>
    </section>

    <!-- HIGHLIGHTS SECTION -->
    <section class="container highlights-section">
        <div class="highlight-card">
            <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=600&q=80" alt="Bookshelf" class="highlight-img">
            <div class="highlight-content">
                <h3>Publish Opportunities Quickly</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Launch new scholarship calls with structured details, deadlines, and funding information instantly to your target demographic.</p>
            </div>
        </div>
        <div class="highlight-card">
            <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=600&q=80" alt="Documents" class="highlight-img">
            <div class="highlight-content">
                <h3>Review Applications Efficiently</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Sort and assess candidate submissions with clear visibility into their application status and supporting documents.</p>
            </div>
        </div>
    </section>

    <!-- AGENT WORKSPACE TABS -->
    <section class="container">
        <div class="workspace-section">
            <div class="tab-header">
                <button class="tab-btn active" data-target="tab-tracking">Applicant Tracking</button>
                <button class="tab-btn" data-target="tab-review">Application Review</button>
                <button class="tab-btn" data-target="tab-sms">SMS Notifications</button>
            </div>

            <!-- Tab 1: Applicant Tracking -->
            <div class="tab-content active" id="tab-tracking">
                <div class="alert-card">
                    <div>
                        <strong style="color: #b45309;">Applicants Needing Attention</strong>
                        <p style="font-size: 0.85rem; color: #d97706; margin-top: 4px;">
                            <?php echo h(count(array_filter($applicants, function ($a) { return $a['prog'] < 75; }))); ?> students need profile updates before deadlines.
                        </p>
                    </div>
                    <button class="btn-action" style="background: #f59e0b;">Send Bulk Reminder</button>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Location</th>
                            <th>Education Level</th>
                            <th>Profile</th>
                            <th>Apps</th>
                            <th>Financial Need</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applicants)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color: var(--text-muted);">No applicants found for your scholarships yet.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach($applicants as $s): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--sig-navy);"><?php echo h($s['name']); ?></td>
                            <td><?php echo h($s['location']); ?></td>
                            <td><?php echo h($s['edu']); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="stat-progress" style="width: 60px; margin: 0;"><div class="stat-progress-fill" style="width: <?php echo h($s['prog']); ?>%;"></div></div>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo h($s['prog']); ?>%</span>
                                </div>
                            </td>
                            <td><span class="badge badge-count"><?php echo h($s['apps']); ?> Active</span></td>
                            <td>
                                <?php if($s['need'] == 'Critical' || $s['need'] == 'High'): ?>
                                    <span class="badge badge-need"><?php echo h($s['need']); ?></span>
                                <?php else: ?>
                                    <span class="badge" style="background: #f1f5f9; color: var(--text-muted);"><?php echo h($s['need']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="tempSigApplication.php?student=<?php echo h($s['student_id']); ?>" class="btn-action" style="text-decoration:none; display:inline-block;">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Application Review -->
            <div class="tab-content" id="tab-review">
                <div class="app-grid">
                    <?php if (empty($applications)): ?>
                    <div class="app-card">
                        <h3>No applications yet</h3>
                        <p style="font-size: 0.9rem; color: var(--text-muted);">Incoming applications for your scholarships will appear here.</p>
                    </div>
                    <?php endif; ?>
                    <?php foreach($applications as $app): ?>
                    <div class="app-card">
                        <div>
                            <span class="badge" style="background: #e0e7ff; color: #4338ca; margin-bottom: 8px; display: inline-block;"><?php echo h($app['status']); ?></span>
                            <h3><?php echo h($app['student']); ?></h3>
                            <h4><?php echo h($app['opp']); ?></h4>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted);"><?php echo h($app['summary']); ?></p>
                        <div class="docs-list"><strong>Docs:</strong> <?php echo h($app['docs']); ?></div>
                        <div class="app-actions">
                            <?php if(strtolower($app['status']) == 'draft' || strtolower($app['status']) == 'pending'): ?>
                                <button class="btn-outline" type="button">Remind to Complete</button>
                            <?php else: ?>
                                <a href="sigAppView.php?appID=<?php echo h($app['id']); ?>" class="btn-endorse" style="text-decoration:none; text-align:center;">Approve</a>
                                <button class="btn-outline" type="button">Contact</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tab 3: SMS Notifications -->
            <div class="tab-content" id="tab-sms">
                <div class="sms-layout">
                    <?php if ($smsNoticeType !== '' && $smsNoticeText !== ''): ?>
                    <div style="grid-column: 1 / -1; margin-bottom: 0.5rem; padding: 0.75rem 1rem; border-radius: var(--radius-sm); background: <?php echo ($smsNoticeType === 'ok') ? '#ecfdf3' : '#fef2f2'; ?>; color: <?php echo ($smsNoticeType === 'ok') ? '#065f46' : '#991b1b'; ?>; border: 1px solid <?php echo ($smsNoticeType === 'ok') ? '#a7f3d0' : '#fecaca'; ?>; font-size: 0.9rem;">
                        <?php echo h($smsNoticeText); ?>
                    </div>
                    <?php endif; ?>
                    <!-- History Table -->
                    <div>
                        <h3 style="margin-bottom: 1rem; color: var(--sig-navy); font-size: 1.1rem;">Message History</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Recipient</th>
                                    <th>Type</th>
                                    <th>Message</th>
                                    <th>Delivered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($smsHistory)): ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; color: var(--text-muted);">No message activity available yet.</td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach($smsHistory as $sms): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?php echo h($sms['to']); ?></td>
                                    <td><span class="badge" style="background: #f1f5f9; color: var(--text-main);"><?php echo h($sms['type']); ?></span></td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted); max-width: 240px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo h($sms['msg']); ?></td>
                                    <td style="font-size: 0.8rem;"><?php echo h($sms['time']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Compose Form -->
                    <div style="background: var(--sig-bg); padding: 1.5rem; border-radius: var(--radius-md);">
                        <h3 style="margin-bottom: 1rem; color: var(--sig-navy); font-size: 1.1rem;">Compose SMS</h3>
                        <form id="smsComposeForm" method="post" action="../backend/sigSendSms.php">
                            <div class="form-group">
                                <label>Recipient Student</label>
                                <select class="form-control" id="smsRecipient" name="student_id" required>
                                    <option value="">Select Student...</option>
                                    <?php foreach ($applicants as $s): ?>
                                    <option value="<?php echo h($s['student_id']); ?>" <?php echo ($s['phone'] === '') ? 'disabled' : ''; ?>><?php echo h($s['name']); ?><?php echo ($s['phone'] === '') ? ' (No mobile set)' : ''; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Message Type</label>
                                <select class="form-control" id="smsType" name="message_type" required>
                                    <option value="Reminder">Deadline Reminder</option>
                                    <option value="Profile">Profile Completion</option>
                                    <option value="Status">Application Status</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Message Text</label>
                                <textarea class="form-control" id="smsMessage" name="message_text" rows="4" placeholder="Type your message here..." required></textarea>
                            </div>
                            <button type="submit" class="btn-submit">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="sig-footer">
        <p>© 2026 ScholarConnect · Signatory Portal · Powered by Africa's Talking SMS</p>
    </footer>

    <script src="../js/signatory-landing.js"></script>
</body>
</html>