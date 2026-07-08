<?php
session_start();
require '../config.php';
require '../backend/MatchingEngine.php'; // Inject the newly built algorithm

// Legacy Auth Check
$currentUserID = $_SESSION['currentUserID'] ?? null;
if ($currentUserID == NULL) {
   header("Location: ../index.php");
   exit();
}

$conn = getDbConnection();

// Fetch Student Profile Completion check
$studentQuery = "SELECT current_level, financial_need, career_interests FROM student WHERE studentID = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $currentUserID);
$stmt->execute();
$stuRes = $stmt->get_result()->fetch_assoc();
$stmt->close();

$studentName = $_SESSION['currentUserName'] ?? "Student";

// Calculate Profile Completion (Basic Example)
$fieldsFilled = 0;
if (!empty($stuRes['current_level'])) $fieldsFilled++;
if (!empty($stuRes['financial_need'])) $fieldsFilled++;
if (!empty($stuRes['career_interests'])) $fieldsFilled++;
$profileCompletion = round(($fieldsFilled / 3) * 100);

if ($profileCompletion < 100) {
    header("Location: tempUserProfile.php?force_edit=1&onboarding=1");
    exit();
}

// ==========================================
// THE MATCHING ENGINE IN ACTION
// ==========================================
$opportunities = MatchingEngine::getMatches($currentUserID);
$opportunities = array_values(array_filter($opportunities, function($opp) {
    return ((int) ($opp['match'] ?? 0)) >= 30;
}));

// Live Stats Calculation
$stats = [
    'total_matches' => 0,
    'active_apps' => 0,
    'draft_apps' => 0,
    'deadlines' => 0
];

foreach ($opportunities as $opp) {
    if ($opp['match'] >= 30) $stats['total_matches']++;
    if ($opp['urgent']) $stats['deadlines']++;
}

// Fetch Real Applications
$applications = [];
$appSql = "SELECT A.applicationID, S.schname as opp_title, SI.firstName as org, A.appstatus as status, A.appDate as date, A.verifiedBySignatory
           FROM application A
           JOIN scholarship S ON A.scholarshipID = S.scholarshipID
           LEFT JOIN signatory SI ON S.sigID = SI.sigID
           WHERE A.studentID = ?";
$appStmt = $conn->prepare($appSql);
$appStmt->bind_param("i", $currentUserID);
$appStmt->execute();
$appRes = $appStmt->get_result();
while ($row = $appRes->fetch_assoc()) {
    if (strtolower($row['status']) === 'active' || strtolower($row['status']) === 'processing') {
        $stats['active_apps']++;
    } elseif (strtolower($row['status']) === 'draft') {
        $stats['draft_apps']++;
    }
    $applications[] = $row;
}
$appStmt->close();
$conn->close();

// Helper Function for Deadline formatting
function formatDeadlineMsg($dateStr) {
    $diff = (strtotime($dateStr) - time()) / (60 * 60 * 24);
    if ($diff < 0) return "Closed";
    if ($diff < 7) return "<span class='badge-urgent'>Closes in " . ceil($diff) . " days</span>";
    if ($diff < 14) return "<span style='color: var(--warning);'>Closes in " . ceil($diff) . " days</span>";
    return "Closes " . date("M d, Y", strtotime($dateStr));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | ScholarConnect</title>
    <link rel="stylesheet" href="../css/pages/student-dashboard.css">
</head>
<body class="app-shell">

<?php
    $studentNavCurrent = 'home';
    require '../includes/nav-student.php';
?>

<div class="dashboard-wrapper">
    
    <!-- HERO BANNER -->
    <section class="banner-section" style="border-radius: var(--radius-lg); margin-top: 1rem;">
        <div class="banner-overlay" style="border-radius: var(--radius-lg);"></div>
        <div class="banner-content">
            <h2>Welcome, <?php echo htmlspecialchars($studentName); ?></h2>
            <p>Discover opportunities tailored for you</p>
        </div>
    </section>

    <!-- PROFILE COMPLETION ALERT -->
    <?php if ($profileCompletion < 100): ?>
    <div class="alert-warning">
        <div class="alert-content">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <div>
                <h3>Complete Your Profile</h3>
                <p>Add your Financial Need and Career Interests to unlock your matching score.</p>
            </div>
        </div>
        <div class="progress-container">
            <div class="progress-label">
                <span>Profile Progress</span>
                <span><?php echo $profileCompletion; ?>%</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $profileCompletion; ?>%;"></div>
            </div>
        </div>
        <a href="tempUserProfile.php" class="btn-solid" style="background: #ea580c; flex: 0 0 auto;">Update Profile</a>
    </div>
    <?php endif; ?>

    <!-- STATS ROW -->
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Total Matches</h4>
            <div class="value"><?php echo $stats['total_matches']; ?></div>
            <p>Opportunities available for you</p>
        </div>
        <div class="stat-card">
            <h4>Active Applications</h4>
            <div class="value"><?php echo $stats['active_apps']; ?></div>
            <p>Currently under review</p>
        </div>
        <div class="stat-card">
            <h4>Drafts</h4>
            <div class="value"><?php echo $stats['draft_apps']; ?></div>
            <p>Incomplete applications</p>
        </div>
        <div class="stat-card">
            <h4>Deadlines</h4>
            <div class="value" style="color: var(--danger);"><?php echo $stats['deadlines']; ?></div>
            <p>Closing Soon</p>
        </div>
    </div>

    <!-- TABBED CONTENT AREA -->
    <div class="tab-header">
        <button class="tab-btn active" data-target="tab-opportunities">Opportunities</button>
        <button class="tab-btn" data-target="tab-applications">My Applications</button>
    </div>

    <!-- TAB A: OPPORTUNITIES -->
    <div id="tab-opportunities" class="tab-content active">
        
        <div class="filter-bar">
            <div class="search-input">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">  <line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="searchOpps" placeholder="Search title or organization...">
            </div>
            <div class="filter-toggles">
                <button class="filter-pill active" data-filter="all">All Matches</button>
                <button class="filter-pill" data-filter="merit_based">Merit Based</button>
                <button class="filter-pill" data-filter="means_based">Means Based</button>
                <button class="filter-pill" data-filter="technology_based">Tech/STEM</button>
            </div>
        </div>

        <div id="emptyStateOpps" class="empty-state" style="display: <?php echo empty($opportunities) ? 'block' : 'none'; ?>;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <h3>No matching opportunities found</h3>
            <p style="color: var(--text-muted);">Ensure your profile education level is set correctly to unlock scholarships.</p>
        </div>

        <!-- Grid of Cards -->
        <div class="opp-grid">
            <?php foreach ($opportunities as $opp): ?>
                <?php $highMatchClass = ($opp['match'] >= 70) ? 'card-high-match' : ''; ?>
                
                <div class="opp-card <?php echo $highMatchClass; ?>" 
                     data-title="<?php echo htmlspecialchars($opp['title']); ?>" 
                     data-org="<?php echo htmlspecialchars($opp['org']); ?>" 
                     data-category="<?php echo htmlspecialchars($opp['category']); ?>">
                    
                    <div class="card-header">
                        <div>
                            <div class="card-title"><?php echo htmlspecialchars($opp['title']); ?></div>
                            <div class="card-org">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <?php echo htmlspecialchars($opp['org']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-desc"><?php echo htmlspecialchars($opp['desc']); ?></div>

                    <ul class="req-list">
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Funding: <strong><?php echo htmlspecialchars($opp['amount']); ?></strong></span></li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> <span><?php echo formatDeadlineMsg($opp['deadline']); ?></span></li>
                    </ul>

                    <div class="card-actions">
                        <?php if ($opp['applied']): ?>
                            <button class="btn-disabled">✓ Applied</button>
                        <?php else: ?>
                            <a href="tempschdesc.php?sch=<?php echo (int)$opp['id']; ?>" class="btn-solid">Apply Now</a>
                        <?php endif; ?>
                        <a href="tempschdesc.php?sch=<?php echo (int)$opp['id']; ?>" class="btn-outline">Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB B: MY APPLICATIONS -->
    <div id="tab-applications" class="tab-content">
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                <h3>No Applications Yet</h3>
                <p style="color: var(--text-muted); margin-bottom: 1rem;">You haven't started any applications.</p>
                <button id="btnBrowseOpps" class="btn-solid">Browse Opportunities</button>
            </div>
        <?php else: ?>
            <div class="opp-grid">
                <?php foreach ($applications as $app): ?>
                    <div class="app-card">
                        <div class="card-header">
                            <div>
                                <div class="card-title"><?php echo htmlspecialchars($app['opp_title']); ?></div>
                                <div class="card-org"><?php echo htmlspecialchars($app['org']); ?></div>
                            </div>
                            <?php if (strtolower($app['status']) == 'active'): ?>
                                <span class="badge badge-status-submitted">Submitted</span>
                            <?php else: ?>
                                <span class="badge badge-status-draft"><?php echo htmlspecialchars($app['status']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="card-meta">Applied on <?php echo htmlspecialchars($app['date']); ?></div>

                        <div class="req-list">
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Signatory Decision: <strong style="color: <?php echo ($app['verifiedBySignatory'] == 'Approved') ? 'var(--success)' : 'var(--text-main)'; ?>"><?php echo htmlspecialchars($app['verifiedBySignatory']); ?></strong>
                            </li>
                        </div>

                        <div class="card-actions">
                            <a href="tempUserView.php" class="btn-outline">Track Application Status</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="../js/student-dashboard.js"></script>
</body>
</html>