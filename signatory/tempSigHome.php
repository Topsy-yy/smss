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

    $sigFlashMessage = trim((string) ($_SESSION['sig_flash_message'] ?? ''));
    unset($_SESSION['sig_flash_message']);

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
    $studentProfiles = array();
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
          $primaryPhone = trim((string) (($row['phone'] ?? '') !== '' ? ($row['phone'] ?? '') : ($row['contactNo'] ?? '')));

          $applicants[] = array(
              'student_id' => (int) $row['studentID'],
              'name' => ($name !== '') ? $name : ('Student #' . (int) $row['studentID']),
              'location' => $location,
              'edu' => $education,
              'prog' => $profilePct,
              'apps' => (int) $row['app_count'],
              'need' => $need,
              'phone' => $primaryPhone,
          );

          $studentProfiles[(int) $row['studentID']] = array(
              'name' => ($name !== '') ? $name : ('Student #' . (int) $row['studentID']),
              'location' => $location,
              'education' => $education,
              'gender' => trim((string) ($row['gender'] ?? '')),
              'birthDate' => trim((string) ($row['birthDate'] ?? '')),
              'birthPlace' => trim((string) ($row['birthPlace'] ?? '')),
              'address' => trim((string) ($row['presStreetAddr'] ?? '')),
              'region' => trim((string) ($row['presRegion'] ?? '')),
              'phone' => $primaryPhone,
              'profilePct' => $profilePct,
              'appCount' => (int) $row['app_count'],
              'recentApplications' => array(),
          );
      }
      $applicantStmt->close();
  }

  if (count($applicants) > 0) {
      $stats['profile_pct'] = (int) round($completionTotal / count($applicants));
  }

  $needsAttentionCount = count(array_filter($applicants, function ($a) {
      return ((int) ($a['prog'] ?? 0)) < 75;
  }));

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
                 AND LOWER(A.verifiedBySignatory) IN ('approved', 'rejected')
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

  $recentByStudentSql =
      "SELECT
          A.studentID,
          A.applicationID,
          A.appDate,
          A.appstatus,
          A.verifiedBySignatory,
          S.schname
       FROM application A
       JOIN scholarship S ON S.scholarshipID = A.scholarshipID
       WHERE A.sigID = ?
       ORDER BY A.studentID ASC, A.appDate DESC, A.applicationID DESC";

  $recentStmt = $conn->prepare($recentByStudentSql);
  if ($recentStmt) {
      $recentStmt->bind_param('i', $currentUserID);
      $recentStmt->execute();
      $recentRes = $recentStmt->get_result();
      while ($recentRes && ($row = $recentRes->fetch_assoc())) {
          $sid = (int) ($row['studentID'] ?? 0);
          if (!isset($studentProfiles[$sid])) {
              continue;
          }

          if (count($studentProfiles[$sid]['recentApplications']) >= 5) {
              continue;
          }

          $decision = trim((string) ($row['verifiedBySignatory'] ?? ''));
          if ($decision === '') {
              $decision = trim((string) ($row['appstatus'] ?? 'Pending'));
          }

          $studentProfiles[$sid]['recentApplications'][] = array(
              'applicationID' => (int) ($row['applicationID'] ?? 0),
              'scholarship' => trim((string) ($row['schname'] ?? 'Scholarship')),
              'submitted' => (!empty($row['appDate']) && $row['appDate'] !== '0000-00-00')
                  ? date('M d, Y', strtotime((string) $row['appDate']))
                  : 'Not available',
              'status' => ($decision !== '') ? $decision : 'Pending',
          );
      }
      $recentStmt->close();
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

        <style>
            .sig-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 2500;
                max-width: 420px;
                background: #0f172a;
                color: #f8fafc;
                border: 1px solid rgba(148, 163, 184, 0.3);
                border-left: 5px solid #16a34a;
                border-radius: 12px;
                box-shadow: 0 12px 30px rgba(2, 6, 23, 0.35);
                padding: 12px 14px;
                display: flex;
                align-items: flex-start;
                gap: 10px;
                opacity: 0;
                transform: translateY(-10px);
                pointer-events: none;
                transition: opacity 0.2s ease, transform 0.2s ease;
            }

            .sig-toast.is-visible {
                opacity: 1;
                transform: translateY(0);
                pointer-events: auto;
            }

            .sig-toast__badge {
                background: #16a34a;
                color: #ffffff;
                border-radius: 999px;
                font-size: 0.75rem;
                line-height: 1;
                padding: 4px 8px;
                margin-top: 1px;
                flex: 0 0 auto;
            }

            .sig-toast__text {
                margin: 0;
                font-size: 0.92rem;
                line-height: 1.45;
                flex: 1 1 auto;
            }

            .sig-toast__close {
                background: transparent;
                border: 0;
                color: #cbd5e1;
                font-size: 1rem;
                line-height: 1;
                cursor: pointer;
                padding: 2px;
                margin-left: 4px;
                flex: 0 0 auto;
            }
        </style>
</head>
<body>
        <?php if ($sigFlashMessage !== '') { ?>
            <div id="sigToast" class="sig-toast" role="status" aria-live="polite" aria-atomic="true">
                <span class="sig-toast__badge">Success</span>
                <p class="sig-toast__text"><?php echo htmlspecialchars($sigFlashMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <button type="button" id="sigToastClose" class="sig-toast__close" aria-label="Close notification">x</button>
            </div>
            <script type="text/javascript">
                (function () {
                    var toast = document.getElementById('sigToast');
                    var closeBtn = document.getElementById('sigToastClose');
                    if (!toast) {
                        return;
                    }

                    var hideToast = function () {
                        toast.classList.remove('is-visible');
                        window.setTimeout(function () {
                            if (toast && toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 220);
                    };

                    window.setTimeout(function () {
                        toast.classList.add('is-visible');
                    }, 30);

                    if (closeBtn) {
                        closeBtn.addEventListener('click', hideToast);
                    }

                    window.setTimeout(hideToast, 4200);
                })();
            </script>
        <?php } ?>

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
                <?php if ($smsNoticeType !== '' && $smsNoticeText !== ''): ?>
                <div style="margin-bottom: 0.75rem; padding: 0.75rem 1rem; border-radius: var(--radius-sm); background: <?php echo ($smsNoticeType === 'ok') ? '#ecfdf3' : '#fef2f2'; ?>; color: <?php echo ($smsNoticeType === 'ok') ? '#065f46' : '#991b1b'; ?>; border: 1px solid <?php echo ($smsNoticeType === 'ok') ? '#a7f3d0' : '#fecaca'; ?>; font-size: 0.9rem;">
                    <?php echo h($smsNoticeText); ?>
                </div>
                <?php endif; ?>
                <div class="alert-card">
                    <div>
                        <strong style="color: #b45309;">Applicants Needing Attention</strong>
                        <p style="font-size: 0.85rem; color: #d97706; margin-top: 4px;">
                            <?php echo h($needsAttentionCount); ?> students need profile updates before deadlines.
                        </p>
                    </div>
                    <form method="post" action="../backend/sigBulkReminder.php" style="margin: 0;">
                        <button type="submit" class="btn-action" style="background: #f59e0b;">Send Bulk Reminder</button>
                    </form>
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
                                <button
                                    type="button"
                                    class="btn-action js-student-profile-popup"
                                    data-student-id="<?php echo h($s['student_id']); ?>"
                                >View Details</button>
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
                        <h3>No reviewed applications yet</h3>
                        <p style="font-size: 0.9rem; color: var(--text-muted);">Only approved and rejected scholarship applications appear in this section.</p>
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
                            <?php $statusLower = strtolower(trim((string) $app['status'])); ?>
                            <button
                                type="button"
                                class="btn-endorse js-review-popup"
                                data-app-id="<?php echo h($app['id']); ?>"
                                data-student="<?php echo h($app['student']); ?>"
                                data-opp="<?php echo h($app['opp']); ?>"
                                data-status="<?php echo h($app['status']); ?>"
                                data-summary="<?php echo h($app['summary']); ?>"
                                data-action="<?php echo ($statusLower === 'rejected') ? 'Accept' : 'Reject'; ?>"
                            >Review</button>
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
                                    <option value="all">All Students With Mobile Numbers</option>
                                    <?php foreach ($applicants as $s): ?>
                                    <option value="<?php echo h($s['student_id']); ?>" <?php echo ($s['phone'] === '') ? 'disabled' : ''; ?>><?php echo h($s['name']); ?><?php echo ($s['phone'] === '') ? ' (No mobile set)' : ''; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Message Template</label>
                                <select class="form-control" id="smsTemplate" name="message_template" required>
                                    <option value="deadline_reminder">Deadline Reminder</option>
                                    <option value="profile_completion">Profile Completion</option>
                                    <option value="status_update">Application Status Update</option>
                                    <option value="resend_matches">Re-send Matched Scholarships</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Template Preview</label>
                                <textarea class="form-control" id="smsMessagePreview" rows="4" readonly>Deadline Reminder template will be sent to the selected student.</textarea>
                            </div>
                            <button type="submit" class="btn-submit">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    <div id="reviewAppModal" class="review-modal" aria-hidden="true">
        <div class="review-modal-card" role="dialog" aria-modal="true" aria-labelledby="reviewModalTitle">
            <button type="button" class="review-modal-close" id="reviewModalClose" aria-label="Close">&times;</button>
            <h3 id="reviewModalTitle">Application Review Summary</h3>
            <p id="reviewModalDesc" style="color: var(--text-muted); font-size: 0.92rem;"></p>
            <div class="review-modal-meta">
                <p><strong>Student:</strong> <span id="reviewModalStudent"></span></p>
                <p><strong>Scholarship:</strong> <span id="reviewModalOpp"></span></p>
                <p><strong>Current Decision:</strong> <span id="reviewModalStatus"></span></p>
            </div>
            <form id="reviewModalActionForm" method="post" action="../backend/sigAcceptReject.php" class="review-modal-actions">
                <input type="hidden" name="appID" id="reviewModalAppId" value="">
                <button type="button" class="btn-outline" id="reviewModalCancel">Cancel</button>
                <button type="submit" class="btn-endorse" id="reviewModalSubmit" name="accrej" value="Reject">Reject</button>
            </form>
        </div>
    </div>

    <div id="studentProfileModal" class="review-modal" aria-hidden="true">
        <div class="review-modal-card student-profile-modal-card" role="dialog" aria-modal="true" aria-labelledby="studentProfileModalTitle">
            <button type="button" class="review-modal-close" id="studentProfileModalClose" aria-label="Close">&times;</button>
            <h3 id="studentProfileModalTitle">Applicant Profile Details</h3>
            <div class="student-profile-grid">
                <p><strong>Full Name:</strong> <span id="studentProfileName">-</span></p>
                <p><strong>Profile Completion:</strong> <span id="studentProfilePct">-</span></p>
                <p><strong>Location:</strong> <span id="studentProfileLocation">-</span></p>
                <p><strong>Education:</strong> <span id="studentProfileEducation">-</span></p>
                <p><strong>Gender:</strong> <span id="studentProfileGender">-</span></p>
                <p><strong>Birth Date:</strong> <span id="studentProfileBirthDate">-</span></p>
                <p><strong>Birth Place:</strong> <span id="studentProfileBirthPlace">-</span></p>
                <p><strong>Region:</strong> <span id="studentProfileRegion">-</span></p>
                <p><strong>Address:</strong> <span id="studentProfileAddress">-</span></p>
                <p><strong>Mobile:</strong> <span id="studentProfilePhone">-</span></p>
                <p><strong>Total Applications:</strong> <span id="studentProfileTotalApps">-</span></p>
            </div>

            <h4 class="student-recent-title">Recent Applications Submitted</h4>
            <div class="student-recent-wrap">
                <table class="table student-recent-table">
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Scholarship</th>
                            <th>Submitted</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="studentRecentBody"></tbody>
                </table>
            </div>

            <div class="review-modal-actions">
                <button type="button" class="btn-outline" id="studentProfileModalDone">Done</button>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="sig-footer">
        <p>© 2026 ScholarConnect · Signatory Portal · Powered by Africa's Talking SMS</p>
    </footer>

    <script src="../js/signatory-landing.js"></script>
        <script>
            var studentProfileData = <?php echo json_encode($studentProfiles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            (function () {
                var templateSelect = document.getElementById('smsTemplate');
                var preview = document.getElementById('smsMessagePreview');
                if (!templateSelect || !preview) {
                    return;
                }

                var previews = {
                    deadline_reminder: 'Deadline Reminder template will be sent to the selected student.',
                    profile_completion: 'Profile Completion template will be sent to the selected student.',
                    status_update: 'Application Status Update template will be sent to the selected student.',
                    resend_matches: 'Matched Scholarships template will send top scholarship positions (eligible above 30%) for the selected recipient(s), including why they matched.'
                };

                var syncPreview = function () {
                    var key = templateSelect.value || 'deadline_reminder';
                    preview.value = previews[key] || previews.deadline_reminder;
                };

                templateSelect.addEventListener('change', syncPreview);
                syncPreview();
            })();

            (function () {
                var modal = document.getElementById('reviewAppModal');
                var closeBtn = document.getElementById('reviewModalClose');
                var cancelBtn = document.getElementById('reviewModalCancel');
                var submitBtn = document.getElementById('reviewModalSubmit');
                var appIdInput = document.getElementById('reviewModalAppId');
                var desc = document.getElementById('reviewModalDesc');
                var student = document.getElementById('reviewModalStudent');
                var opp = document.getElementById('reviewModalOpp');
                var status = document.getElementById('reviewModalStatus');
                var triggers = document.querySelectorAll('.js-review-popup');

                if (!modal || !closeBtn || !cancelBtn || !submitBtn || !appIdInput || !desc || !student || !opp || !status || !triggers.length) {
                    return;
                }

                function openModal() {
                    modal.classList.add('active');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }

                function closeModal() {
                    modal.classList.remove('active');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }

                triggers.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var appId = btn.getAttribute('data-app-id') || '';
                        var appStudent = btn.getAttribute('data-student') || 'Student';
                        var appOpp = btn.getAttribute('data-opp') || 'Scholarship';
                        var appStatus = btn.getAttribute('data-status') || 'Pending';
                        var appSummary = btn.getAttribute('data-summary') || '';
                        var action = btn.getAttribute('data-action') || 'Reject';

                        appIdInput.value = appId;
                        student.textContent = appStudent;
                        opp.textContent = appOpp;
                        status.textContent = appStatus;
                        desc.textContent = appSummary;

                        submitBtn.value = action;
                        submitBtn.textContent = action;

                        openModal();
                    });
                });

                closeBtn.addEventListener('click', closeModal);
                cancelBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', function (evt) {
                    if (evt.target === modal) {
                        closeModal();
                    }
                });
                document.addEventListener('keydown', function (evt) {
                    if (evt.key === 'Escape' && modal.classList.contains('active')) {
                        closeModal();
                    }
                });
            })();

            (function () {
                var modal = document.getElementById('studentProfileModal');
                var closeBtn = document.getElementById('studentProfileModalClose');
                var doneBtn = document.getElementById('studentProfileModalDone');
                var triggers = document.querySelectorAll('.js-student-profile-popup');
                var nameEl = document.getElementById('studentProfileName');
                var pctEl = document.getElementById('studentProfilePct');
                var locationEl = document.getElementById('studentProfileLocation');
                var educationEl = document.getElementById('studentProfileEducation');
                var genderEl = document.getElementById('studentProfileGender');
                var birthDateEl = document.getElementById('studentProfileBirthDate');
                var birthPlaceEl = document.getElementById('studentProfileBirthPlace');
                var regionEl = document.getElementById('studentProfileRegion');
                var addressEl = document.getElementById('studentProfileAddress');
                var phoneEl = document.getElementById('studentProfilePhone');
                var totalAppsEl = document.getElementById('studentProfileTotalApps');
                var recentBody = document.getElementById('studentRecentBody');

                if (!modal || !closeBtn || !doneBtn || !triggers.length || !recentBody) {
                    return;
                }

                function valOrDash(value) {
                    return (value && String(value).trim() !== '') ? String(value) : '-';
                }

                function openModal() {
                    modal.classList.add('active');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }

                function closeModal() {
                    modal.classList.remove('active');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }

                function renderRecentApps(apps) {
                    recentBody.innerHTML = '';

                    if (!apps || !apps.length) {
                        var empty = document.createElement('tr');
                        empty.innerHTML = '<td colspan="4" style="text-align:center;color:#64748b;">No recent applications found for this student.</td>';
                        recentBody.appendChild(empty);
                        return;
                    }

                    apps.forEach(function (app) {
                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td>' + valOrDash(app.applicationID) + '</td>' +
                            '<td>' + valOrDash(app.scholarship) + '</td>' +
                            '<td>' + valOrDash(app.submitted) + '</td>' +
                            '<td>' + valOrDash(app.status) + '</td>';
                        recentBody.appendChild(tr);
                    });
                }

                function populate(profile) {
                    nameEl.textContent = valOrDash(profile.name);
                    pctEl.textContent = valOrDash(profile.profilePct) + '%';
                    locationEl.textContent = valOrDash(profile.location);
                    educationEl.textContent = valOrDash(profile.education);
                    genderEl.textContent = valOrDash(profile.gender);
                    birthDateEl.textContent = valOrDash(profile.birthDate);
                    birthPlaceEl.textContent = valOrDash(profile.birthPlace);
                    regionEl.textContent = valOrDash(profile.region);
                    addressEl.textContent = valOrDash(profile.address);
                    phoneEl.textContent = valOrDash(profile.phone);
                    totalAppsEl.textContent = valOrDash(profile.appCount);
                    renderRecentApps(profile.recentApplications || []);
                }

                triggers.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var studentId = btn.getAttribute('data-student-id');
                        var profile = (studentProfileData && studentProfileData[studentId]) ? studentProfileData[studentId] : null;
                        if (!profile) {
                            return;
                        }
                        populate(profile);
                        openModal();
                    });
                });

                closeBtn.addEventListener('click', closeModal);
                doneBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', function (evt) {
                    if (evt.target === modal) {
                        closeModal();
                    }
                });
                document.addEventListener('keydown', function (evt) {
                    if (evt.key === 'Escape' && modal.classList.contains('active')) {
                        closeModal();
                    }
                });
            })();
        </script>
</body>
</html>