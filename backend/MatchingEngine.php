<?php
// backend/MatchingEngine.php

// Ensure the config file is loaded if it hasn't been already
if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/../config.php';
}
require_once __DIR__ . '/IRRecommendationEngine.php';

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
        // IR-only matching engine.
        if (!class_exists('IRRecommendationEngine')) {
            return [];
        }

        try {
            $irMatches = IRRecommendationEngine::getMatches((int) $studentId);
            if (is_array($irMatches)) {
                return $irMatches;
            }
        } catch (Throwable $e) {
            return [];
        }
        return [];
    }

    /**
     * Retrieve students whose profile matches a newly added scholarship
     */
    public static function getMatchedStudentsForScholarship($scholarshipId) {
        if (!class_exists('IRRecommendationEngine')) {
            return [];
        }

        try {
            $matched = IRRecommendationEngine::getTopStudentsForScholarship((int) $scholarshipId, 10);
            if (is_array($matched)) {
                return $matched;
            }
        } catch (Throwable $e) {
            return [];
        }

        return [];
    }
}
?>