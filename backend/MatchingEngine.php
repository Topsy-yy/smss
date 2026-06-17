<?php
// backend/MatchingEngine.php

// Ensure the config file is loaded if it hasn't been already
if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/../config.php';
}

class MatchingEngine {

    /**
     * Maps the financial need string to a comparable integer.
     */
    private static function getFinancialNeedValue($need) {
        $need = strtolower(trim($need));
        switch ($need) {
            case 'critical': return 4;
            case 'high':     return 3;
            case 'medium':   return 2;
            case 'low':      return 1;
            default:         return 0;
        }
    }

    /**
     * Calculates the Financial Need Score (Max 60 points)
     */
    private static function calculateFinancialScore($studentNeedStr, $targetNeedStr) {
        $targetNeedStr = strtolower(trim($targetNeedStr));
        
        // If the scholarship doesn't have a strict financial requirement
        if ($targetNeedStr === 'any' || $targetNeedStr === '') {
            return 60; // Max points, it's open to everyone
        }

        $studentVal = self::getFinancialNeedValue($studentNeedStr);
        $targetVal = self::getFinancialNeedValue($targetNeedStr);

        if ($studentVal >= $targetVal) {
            return 60; // Meets or exceeds the required financial need urgency
        } elseif (($targetVal - $studentVal) === 1) {
            return 30; // Just one tier below the target (e.g., student is Medium, target is High)
        }

        return 0; // Mismatch
    }

    /**
     * Calculates the Career Interest Score (Max 40 points)
     */
    private static function calculateInterestScore($studentInterests, $scholarshipCategory) {
        $category = strtolower(trim($scholarshipCategory));
        $interests = strtolower(trim($studentInterests));

        // Generic categories that don't require specific extracurriculars/interests
        if ($category === 'merit_based' || $category === 'means_based') {
            return 20; // Baseline neutral points to prevent unfair penalization
        }

        // If the student hasn't defined interests, they get 0 for specific scholarships
        if (empty($interests)) {
            return 0;
        }

        // Create arrays for comparison
        $interestArray = array_map('trim', explode(',', $interests));
        
        // If the specific scholarship category keyword exists in the student's interests
        // (e.g., "visual_art" matches an interest containing "art")
        foreach ($interestArray as $interest) {
            if (strpos($category, $interest) !== false || strpos($interest, $category) !== false) {
                return 40; // Exact/Strong overlap
            }
        }

        return 0; // No overlap
    }

    /**
     * Main function to retrieve, score, and rank opportunities for a specific student
     */
    public static function getMatches($studentId) {
        $conn = getDbConnection();
        if (!$conn) {
            return [];
        }

        // 1. Retrieve the Student's Profile
        $studentQuery = "SELECT current_level, financial_need, career_interests, gender FROM student WHERE studentID = ?";
        $stmt = $conn->prepare($studentQuery);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        $student = $studentResult->fetch_assoc();
        $stmt->close();

        if (!$student) {
            $conn->close();
            return []; // Student not found
        }

        $studentLevel = strtolower(trim((string)$student['current_level']));
        $studentNeed = (string)$student['financial_need'];
        $studentInterests = (string)$student['career_interests'];
        $studentGender = strtolower(trim((string)$student['gender']));

        // 2. Retrieve Active Scholarships
        // We also fetch whether the student has already applied using a LEFT JOIN
        $oppSql = "SELECT S.scholarshipID as id, S.schname as title, S.sch as category, 
                          S.degree, S.target_financial_need, S.gender as target_gender,
                          S.appDeadline as deadline, S.funding as amount, S.description as `desc`,
                          SI.firstName, SI.middleName, SI.lastName,
                          CASE WHEN A.applicationID IS NULL THEN 0 ELSE 1 END AS applied
                   FROM scholarship S
                   LEFT JOIN signatory SI ON SI.sigID = S.sigID
                   LEFT JOIN application A ON A.scholarshipID = S.scholarshipID AND A.studentID = ?
                   WHERE S.adminapproval = 'Approved' 
                     AND S.schstatus = 'active'
                     AND S.appDeadline >= CURDATE()";
                     
        $oppStmt = $conn->prepare($oppSql);
        $oppStmt->bind_param("i", $studentId);
        $oppStmt->execute();
        $oppResult = $oppStmt->get_result();

        $matchedOpportunities = [];

        // 3. The Matching Loop
        while ($row = $oppResult->fetch_assoc()) {
            
            // --- HARD FILTERS ---
            // A. Educational Level Filter
            $reqDegree = strtolower(trim($row['degree']));
            if (!empty($reqDegree) && $reqDegree !== 'select' && $studentLevel !== $reqDegree && !empty($studentLevel)) {
                continue; // Fails Hard Filter: Skip this scholarship entirely
            }

            // B. Gender Filter (Optional but recommended to keep logic sound)
            $reqGender = strtolower(trim($row['target_gender']));
            if (!empty($reqGender) && $reqGender !== 'select' && $reqGender !== 'male+female' && $reqGender !== 'prefer') {
                if ($studentGender !== '' && $studentGender !== $reqGender) {
                    continue; // Fails Hard Filter: Gender mismatch
                }
            }

            // --- SOFT SCORING ---
            $score = 0;
            
            // Pillar 1: Financial Need (Max 60)
            $score += self::calculateFinancialScore($studentNeed, $row['target_financial_need']);
            
            // Pillar 2: Career Interests / Category (Max 40)
            $score += self::calculateInterestScore($studentInterests, $row['category']);

            // Compile the data for the frontend
            $orgParts = array_filter([$row['firstName'], $row['middleName'], $row['lastName']]);
            $orgName = trim(implode(' ', $orgParts)) ?: 'Partner Organization';
            
            $daysLeft = (int) ceil((strtotime($row['deadline']) - time()) / (60 * 60 * 24));

            $matchedOpportunities[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'org' => $orgName,
                'category' => $row['category'],
                'match' => $score, // The calculated percentage out of 100
                'verified' => true,
                'deadline' => $row['deadline'],
                'amount' => $row['amount'],
                'desc' => $row['desc'],
                'urgent' => ($daysLeft <= 7),
                'applied' => ((int)$row['applied']) === 1
            ];
        }

        $oppStmt->close();
        $conn->close();

        // 4. Ranking (Sort descending by match score, then ascending by nearest deadline)
        usort($matchedOpportunities, function($a, $b) {
            if ($a['match'] === $b['match']) {
                return strtotime($a['deadline']) <=> strtotime($b['deadline']);
            }
            return $b['match'] <=> $a['match']; // Higher score first
        });

        return $matchedOpportunities;
    }
}
?>