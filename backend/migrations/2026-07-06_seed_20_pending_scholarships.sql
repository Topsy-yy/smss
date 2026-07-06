START TRANSACTION;

SET @seed_sig_id := (SELECT COALESCE(MIN(sigID), 1) FROM signatory);

INSERT INTO scholarship (
    sigID,
    schname,
    schlocation,
    schlocationfrom,
    degree,
    gender,
    target_financial_need,
    religion,
    sch,
    appDeadline,
    granteesNum,
    funding,
    description,
    eligibility,
    benefits,
    apply,
    links,
    contact,
    adminapproval,
    previous_adminapproval,
    schstatus
)
SELECT
    @seed_sig_id,
    seed.schname,
    seed.schlocation,
    seed.schlocationfrom,
    seed.degree,
    seed.gender,
    seed.target_financial_need,
    '',
    seed.sch,
    seed.appDeadline,
    seed.granteesNum,
    seed.funding,
    seed.description,
    seed.eligibility,
    seed.benefits,
    seed.apply_steps,
    seed.links,
    seed.contact,
    'Pending',
    'Pending',
    'active'
FROM (
    SELECT 'Equity Access Scholarship 2027' AS schname, 'Nairobi' AS schlocation, 'All regions' AS schlocationfrom, 'diploma' AS degree, 'male+female' AS gender, 'Low' AS target_financial_need, 'means_based' AS sch, '2027-02-15' AS appDeadline, 20 AS granteesNum, '$800' AS funding, 'Support for diploma students with financial need and strong commitment to studies.' AS description, 'IR matching score must be >30% and student profile must be complete.' AS eligibility, 'Tuition support and mentorship.' AS benefits, 'Submit profile and required supporting documents via ScholarConnect.' AS apply_steps, 'https://scholarconnect.local/scholarships/equity-access' AS links, 'scholarships@scholarconnect.local' AS contact
    UNION ALL SELECT 'STEM Growth Grant 2027', 'Nairobi', 'All regions', 'undergraduate', 'male+female', 'Low', 'technology_based', '2027-02-20', 20, '$1200', 'Funding for undergraduate learners building practical technology solutions.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition aid, project stipend, and mentor support.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/stem-growth', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Future Engineers Bursary', 'Mombasa', 'Coastal region', 'undergraduate', 'male+female', 'Low', 'technology_based', '2027-02-25', 20, '$1000', 'Scholarship for undergraduate students pursuing engineering and applied technology.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition offset and learning resources.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/future-engineers', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Creative Talent Scholarship', 'Kisumu', 'Lake region', 'diploma', 'male+female', 'Low', 'visual_art', '2027-03-01', 20, '$900', 'Support for creative diploma students in visual arts and design.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition support and studio materials grant.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/creative-talent', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Merit Progress Award I', 'Nakuru', 'Rift Valley', 'diploma', 'male+female', 'Low', 'merit_based', '2027-03-05', 20, '$700', 'Merit scholarship for students showing steady academic progress.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition support.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/merit-progress-1', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Merit Progress Award II', 'Nakuru', 'Rift Valley', 'undergraduate', 'male+female', 'Low', 'merit_based', '2027-03-08', 20, '$1100', 'Merit scholarship for undergraduate students with consistent performance.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition support and learning stipend.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/merit-progress-2', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Science Impact Scholarship', 'Eldoret', 'All regions', 'undergraduate', 'male+female', 'Low', 'science_maths_based', '2027-03-12', 20, '$1150', 'Scholarship for science and mathematics-focused learners.', 'IR matching score must be >30% and student profile must be complete.', 'Lab and tuition support.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/science-impact', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Sports Potential Fund', 'Nairobi', 'All regions', 'diploma', 'male+female', 'Low', 'sports_talent', '2027-03-15', 20, '$850', 'Assistance for student-athletes balancing academics and sports.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition aid and training grant.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/sports-potential', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Community Leader Grant', 'Machakos', 'Eastern region', 'diploma', 'male+female', 'Low', 'means_based', '2027-03-18', 20, '$750', 'Support for students demonstrating community leadership.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition aid and leadership training.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/community-leader', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Women In Tech Scholarship', 'Nairobi', 'All regions', 'undergraduate', 'female', 'Low', 'technology_based', '2027-03-22', 20, '$1300', 'Dedicated support for women pursuing technology disciplines.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition support and mentorship circles.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/women-in-tech', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Inclusive Access Bursary', 'Kakamega', 'Western region', 'diploma', 'male+female', 'Low', 'means_based', '2027-03-25', 20, '$780', 'Bursary focused on equitable access for financially constrained students.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition subsidy and basic supplies support.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/inclusive-access', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Green Innovation Scholarship', 'Nairobi', 'All regions', 'undergraduate', 'male+female', 'Low', 'science_maths_based', '2027-03-28', 20, '$1250', 'Scholarship for projects in sustainability and green innovation.', 'IR matching score must be >30% and student profile must be complete.', 'Project grant and tuition support.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/green-innovation', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Digital Creators Award', 'Mombasa', 'Coastal region', 'undergraduate', 'male+female', 'Low', 'visual_art', '2027-04-02', 20, '$980', 'Support for digital creators in design, media, and visual storytelling.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition aid and creator toolkit support.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/digital-creators', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Rural Scholars Support', 'Kisii', 'Nyanza region', 'diploma', 'male+female', 'Low', 'means_based', '2027-04-06', 20, '$760', 'Scholarship for students from underserved rural communities.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition support and transport allowance.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/rural-scholars', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Entrepreneurship Seed Scholarship', 'Nairobi', 'All regions', 'undergraduate', 'male+female', 'Low', 'technology_based', '2027-04-10', 20, '$1400', 'Scholarship for students building startup or social venture ideas.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition and startup incubation support.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/entrepreneurship-seed', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Academic Resilience Grant', 'Kericho', 'Rift Valley', 'diploma', 'male+female', 'Low', 'merit_based', '2027-04-14', 20, '$820', 'Grant for students who have shown persistence despite hardship.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition assistance and academic coaching.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/academic-resilience', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Health Sciences Opportunity Fund', 'Nairobi', 'All regions', 'undergraduate', 'male+female', 'Low', 'science_maths_based', '2027-04-18', 20, '$1350', 'Funding for students in health and biomedical tracks.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition support and lab placement support.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/health-sciences', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Applied Math Scholars Award', 'Eldoret', 'All regions', 'undergraduate', 'male+female', 'Low', 'science_maths_based', '2027-04-22', 20, '$1180', 'Award for learners focusing on applied mathematics and analytics.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition aid and analytics workshop access.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/applied-math', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Emerging Developers Bursary', 'Nairobi', 'All regions', 'diploma', 'male+female', 'Low', 'technology_based', '2027-04-26', 20, '$920', 'Bursary for aspiring software and web developers.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition support and coding bootcamp voucher.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/emerging-developers', 'scholarships@scholarconnect.local'
    UNION ALL SELECT 'Opportunity Bridge Scholarship', 'Thika', 'Central region', 'undergraduate', 'male+female', 'Low', 'means_based', '2027-04-30', 20, '$1050', 'Bridge scholarship to expand opportunity for high-potential students.', 'IR matching score must be >30% and student profile must be complete.', 'Tuition support and mentorship.', 'Submit profile and required supporting documents via ScholarConnect.', 'https://scholarconnect.local/scholarships/opportunity-bridge', 'scholarships@scholarconnect.local'
) AS seed
WHERE NOT EXISTS (
    SELECT 1
    FROM scholarship existing
    WHERE existing.schname = seed.schname
);

COMMIT;
