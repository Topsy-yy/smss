<?php

function email_tpl_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function email_tpl_footer() {
    return '<p>Kind Regards <br/>SMS Team</p><p><em>Please do not reply to this email as it is an automated message.</em></p>';
}

function email_tpl_signup_verification($verificationCode) {
    $code = (string)$verificationCode;
    return array(
        'subject' => 'Signup | Verification',
        'body' => '                  Thanks for signing up!                  <h1>Your account has been created</h1>You can <strong>login</strong> with the following credentials after you have activated your account by pressing the url below.                  Use the following code to Login To Our WebSite:<br/>' . $code . '<br/><br/> Thank You For Using Our WebSite!' . email_tpl_footer()
    );
}

function email_tpl_password_reset($resetCode) {
    $code = (string)$resetCode;
    return array(
        'subject' => 'Password Reset Request',
        'body' => '            Hey There,            <h1>We have got a Password Reset Request for your Account</h1><br/>           Use the following code to Reset Password :<br/>' . $code . '<br/><br/>         Thank You For Using Our WebSite!           ' . email_tpl_footer()
    );
}

function email_tpl_application_blocked($studentName, $scholarship) {
    $safeName = email_tpl_escape($studentName);
    $safeScholarship = email_tpl_escape($scholarship);
    return array(
        'subject' => 'Application Blocked - ' . $scholarship,
        'body' => '<h3>Application Blocked</h3><p>Hello ' . $safeName . ',</p><p>Your application for <strong>' . $safeScholarship . '</strong> has been temporarily blocked by the signatory.</p>' . email_tpl_footer()
    );
}

function email_tpl_application_restored($studentName, $scholarship) {
    $safeName = email_tpl_escape($studentName);
    $safeScholarship = email_tpl_escape($scholarship);
    return array(
        'subject' => 'Application Restored - ' . $scholarship,
        'body' => '<h3>Application Restored</h3><p>Hello ' . $safeName . ',</p><p>Your application for <strong>' . $safeScholarship . '</strong> has been restored by the signatory.</p>' . email_tpl_footer()
    );
}

function email_tpl_application_approved($studentName, $scholarship) {
    $safeName = email_tpl_escape($studentName);
    $safeScholarship = email_tpl_escape($scholarship);
    return array(
        'subject' => 'Application Approved - ' . $scholarship,
        'body' => '<h3>Application Approved</h3><p>Hello ' . $safeName . ',</p><p>Your application for <strong>' . $safeScholarship . '</strong> has been approved by the signatory and is now in processing.</p>' . email_tpl_footer()
    );
}

function email_tpl_application_rejected($studentName, $scholarship) {
    $safeName = email_tpl_escape($studentName);
    $safeScholarship = email_tpl_escape($scholarship);
    return array(
        'subject' => 'Application Rejected - ' . $scholarship,
        'body' => '<h3>Application Rejected</h3><p>Hello ' . $safeName . ',</p><p>Your application for <strong>' . $safeScholarship . '</strong> has been rejected by the signatory.</p><p>You may review other opportunities and apply again where eligible.</p>' . email_tpl_footer()
    );
}

function email_tpl_scholarship_approved($scholarship) {
    $safeScholarship = email_tpl_escape($scholarship);
    return array(
        'subject' => 'Scholarship Approved - ' . $scholarship,
        'body' => '<h3>Scholarship Approved</h3><p>Your scholarship <strong>' . $safeScholarship . '</strong> has been approved by Admin and is now visible to students.</p><p>You can sign in to review applications.</p>' . email_tpl_footer()
    );
}

function email_tpl_scholarship_rejected($scholarship) {
    $safeScholarship = email_tpl_escape($scholarship);
    return array(
        'subject' => 'Scholarship Rejected - ' . $scholarship,
        'body' => '<h3>Scholarship Rejected</h3><p>Your scholarship <strong>' . $safeScholarship . '</strong> was rejected by Admin.</p><p>Please review and update the listing before re-submitting.</p>' . email_tpl_footer()
    );
}

function email_tpl_scholarship_blocked($scholarship) {
    $safeScholarship = email_tpl_escape($scholarship);
    return array(
        'subject' => 'Scholarship Blocked - ' . $scholarship,
        'body' => '<h3>Scholarship Blocked</h3><p>Your scholarship <strong>' . $safeScholarship . '</strong> has been <strong>blocked</strong> by Admin. Related applications were also suspended.</p>' . email_tpl_footer()
    );
}

function email_tpl_scholarship_restored($scholarship) {
    $safeScholarship = email_tpl_escape($scholarship);
    return array(
        'subject' => 'Scholarship Restored - ' . $scholarship,
        'body' => '<h3>Scholarship Restored</h3><p>Your scholarship <strong>' . $safeScholarship . '</strong> has been <strong>unblocked</strong> by Admin. Related applications were restored.</p>' . email_tpl_footer()
    );
}

function email_tpl_account_blocked_student($name) {
    $safeName = email_tpl_escape($name);
    return array(
        'subject' => 'Account Blocked - ScholarConnect',
        'body' => '<h3>Account Status Update</h3><p>Hello ' . $safeName . ',</p><p>Your account has been <strong>blocked</strong> by Admin. Any active applications tied to your account were also suspended.</p><p>Please contact support/admin for assistance.</p>' . email_tpl_footer()
    );
}

function email_tpl_account_blocked_signatory($name) {
    $safeName = email_tpl_escape($name);
    return array(
        'subject' => 'Account Blocked - ScholarConnect',
        'body' => '<h3>Account Status Update</h3><p>Hello ' . $safeName . ',</p><p>Your signatory account has been <strong>blocked</strong> by Admin. Your scholarships and related applications were also blocked.</p><p>Please contact support/admin for reinstatement details.</p>' . email_tpl_footer()
    );
}

function email_tpl_account_restored_student($name) {
    $safeName = email_tpl_escape($name);
    return array(
        'subject' => 'Account Restored - ScholarConnect',
        'body' => '<h3>Account Restored</h3><p>Hello ' . $safeName . ',</p><p>Your account has been <strong>unblocked</strong> by Admin. Your related applications were restored to their previous states.</p>' . email_tpl_footer()
    );
}

function email_tpl_account_restored_signatory($name) {
    $safeName = email_tpl_escape($name);
    return array(
        'subject' => 'Account Restored - ScholarConnect',
        'body' => '<h3>Account Restored</h3><p>Hello ' . $safeName . ',</p><p>Your signatory account has been <strong>unblocked</strong> by Admin. Related scholarships and applications were restored.</p>' . email_tpl_footer()
    );
}
