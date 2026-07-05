<?php
// backend/IRRecommendationEngine.php

if (!function_exists('getDbConnection')) {
    require_once __DIR__ . '/../config.php';
}

class IRRecommendationEngine {
    const MODEL_VERSION = 'ir_v1';
    const CACHE_TTL_HOURS = 24;

    private static $infraReady = false;

    private static $defaultStopWords = [
        'a','an','the','and','or','for','to','of','in','on','at','from','by','with','without',
        'is','are','was','were','be','been','being','this','that','these','those','as','it','its',
        'your','you','our','we','they','their','them','he','she','his','her','i','me','my','mine',
        'about','into','over','after','before','during','up','down','out','off','very','can','could',
        'should','would','will','just','than','then','also','not','no','yes','any','all'
    ];

    private static $defaultSynonyms = [
        'ai' => 'artificial intelligence',
        'ict' => 'information technology',
        'cs' => 'computer science',
        'software engineering' => 'computer science',
        'cyber security' => 'cybersecurity',
        'financial aid' => 'scholarship funding',
        'tech' => 'technology',
        'stem' => 'science technology engineering mathematics',
        'undergrad' => 'undergraduate',
        'postgrad' => 'postgraduate'
    ];

    private static $protectedPhrases = [
        'computer science',
        'artificial intelligence',
        'information technology',
        'data science',
        'cybersecurity',
        'scholarship funding',
        'financial need',
        'science technology engineering mathematics',
        'high school'
    ];

    public static function getMatches($studentId) {
        $conn = getDbConnection();
        if (!$conn) {
            return [];
        }

        self::ensureInfrastructure($conn);

        $student = self::getStudentProfile($conn, (int) $studentId);
        if (!$student) {
            $conn->close();
            return [];
        }

        $corpusHash = self::syncScholarshipIndex($conn);
        $profileHash = self::buildStudentProfileHash($student);

        $cached = self::readCachedRecommendations($conn, (int) $studentId, $profileHash, $corpusHash);
        if (!empty($cached)) {
            $conn->close();
            return $cached;
        }

        $vocabulary = self::loadVocabulary($conn);
        if (empty($vocabulary)) {
            $conn->close();
            return [];
        }

        $studentVector = self::getOrBuildStudentVector($conn, (int) $studentId, $student, $profileHash, $corpusHash, $vocabulary);
        $studentNorm = self::vectorNorm($studentVector);

        $candidateRows = self::loadCandidateScholarships($conn, (int) $studentId, $student);
        if (empty($candidateRows)) {
            self::saveRecommendationCache($conn, (int) $studentId, $profileHash, $corpusHash, []);
            $conn->close();
            return [];
        }

        $candidateIds = [];
        foreach ($candidateRows as $row) {
            $candidateIds[] = (int) $row['id'];
        }

        $dotMap = self::computeDotProducts($conn, (int) $studentId, $candidateIds);
        $normMap = self::getScholarshipNorms($conn, $candidateIds);
        $explanations = self::getTopContributingTermsForScholarships($conn, (int) $studentId, $candidateIds);

        $results = [];
        foreach ($candidateRows as $row) {
            $schId = (int) $row['id'];
            $dot = (float) ($dotMap[$schId] ?? 0.0);
            $schNorm = (float) ($normMap[$schId] ?? 0.0);
            $score = 0.0;

            if ($studentNorm > 0.0 && $schNorm > 0.0 && $dot > 0.0) {
                $score = $dot / ($studentNorm * $schNorm);
            }

            if ($score < 0.0) {
                $score = 0.0;
            } elseif ($score > 1.0) {
                $score = 1.0;
            }

            $matchPercent = (int) round($score * 100);
            $daysLeft = (int) ceil((strtotime($row['deadline']) - time()) / (60 * 60 * 24));
            $orgParts = array_filter([$row['firstName'], $row['middleName'], $row['lastName']]);
            $orgName = trim(implode(' ', $orgParts));
            if ($orgName === '') {
                $orgName = 'Partner Organization';
            }

            $reasonTerms = isset($explanations[$schId]) ? $explanations[$schId] : [];

            $results[] = [
                'id' => $schId,
                'title' => (string) $row['title'],
                'org' => $orgName,
                'category' => (string) $row['category'],
                'match' => $matchPercent,
                'verified' => true,
                'deadline' => (string) $row['deadline'],
                'amount' => (string) $row['amount'],
                'desc' => (string) $row['desc'],
                'urgent' => ($daysLeft <= 7),
                'applied' => ((int) $row['applied']) === 1,
                'reasons' => $reasonTerms
            ];
        }

        usort($results, function($a, $b) {
            if ($a['match'] === $b['match']) {
                return strtotime($a['deadline']) <=> strtotime($b['deadline']);
            }
            return $b['match'] <=> $a['match'];
        });

        self::saveRecommendationCache($conn, (int) $studentId, $profileHash, $corpusHash, $results);

        $conn->close();
        return $results;
    }

    public static function markStudentProfileChanged($conn, $studentId) {
        self::ensureInfrastructure($conn);

        $studentId = (int) $studentId;

        $stmt = $conn->prepare('DELETE FROM ir_student_vectors WHERE student_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM ir_student_documents WHERE student_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM ir_recommendation_cache WHERE student_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->close();
        }
    }

    public static function markScholarshipCorpusDirty($conn) {
        self::ensureInfrastructure($conn);

        $conn->query("DELETE FROM ir_engine_meta WHERE meta_key = 'scholarship_corpus_hash'");
        $conn->query('TRUNCATE TABLE ir_vocabulary');
        $conn->query('TRUNCATE TABLE ir_scholarship_vectors');
        $conn->query('TRUNCATE TABLE ir_scholarship_norms');
        $conn->query('TRUNCATE TABLE ir_student_vectors');
        $conn->query('TRUNCATE TABLE ir_student_documents');
        $conn->query('TRUNCATE TABLE ir_recommendation_cache');
    }

    public static function getTopStudentsForScholarship($scholarshipId, $limit = 5) {
        $conn = getDbConnection();
        if (!$conn) {
            return [];
        }

        self::ensureInfrastructure($conn);

        $scholarshipId = (int) $scholarshipId;
        $limit = max(1, (int) $limit);

        $corpusHash = self::syncScholarshipIndex($conn);
        $vocabulary = self::loadVocabulary($conn);
        if (empty($vocabulary)) {
            $conn->close();
            return [];
        }

        $normStmt = $conn->prepare('SELECT norm_value FROM ir_scholarship_norms WHERE scholarship_id = ? LIMIT 1');
        $schNorm = 0.0;
        if ($normStmt) {
            $normStmt->bind_param('i', $scholarshipId);
            $normStmt->execute();
            $res = $normStmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $schNorm = (float) ($row['norm_value'] ?? 0.0);
            }
            $normStmt->close();
        }

        if ($schNorm <= 0.0) {
            $conn->close();
            return [];
        }

        $students = [];
        $sql = "SELECT studentID, firstName, middleName, lastName, gender, nationality, dept, college,
                   current_level, financial_need, career_interests, presRegion, permRegion,
                   contactNo, phone
                FROM student
                WHERE status = 'active'";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $students[] = $row;
            }
            $res->free();
        }

        if (empty($students)) {
            $conn->close();
            return [];
        }

        foreach ($students as $student) {
            $studentId = (int) $student['studentID'];
            $profileHash = self::buildStudentProfileHash($student);
            self::getOrBuildStudentVector($conn, $studentId, $student, $profileHash, $corpusHash, $vocabulary);
        }

        $dotStmt = $conn->prepare(
            'SELECT st.student_id, SUM(st.tfidf_value * sv.tfidf_value) AS dot_val
             FROM ir_student_vectors st
             JOIN ir_scholarship_vectors sv ON sv.term_id = st.term_id
             WHERE sv.scholarship_id = ?
             GROUP BY st.student_id'
        );
        $dotMap = [];
        if ($dotStmt) {
            $dotStmt->bind_param('i', $scholarshipId);
            $dotStmt->execute();
            $dotRes = $dotStmt->get_result();
            while ($dotRes && $row = $dotRes->fetch_assoc()) {
                $dotMap[(int) $row['student_id']] = (float) $row['dot_val'];
            }
            $dotStmt->close();
        }

        if (empty($dotMap)) {
            $conn->close();
            return [];
        }

        $normRes = $conn->query(
            'SELECT student_id, SQRT(SUM(tfidf_value * tfidf_value)) AS student_norm
             FROM ir_student_vectors
             GROUP BY student_id'
        );
        $studentNormMap = [];
        if ($normRes) {
            while ($row = $normRes->fetch_assoc()) {
                $studentNormMap[(int) $row['student_id']] = (float) $row['student_norm'];
            }
            $normRes->free();
        }

        $contribStmt = $conn->prepare(
            'SELECT st.student_id, v.term, (st.tfidf_value * sv.tfidf_value) AS contribution
             FROM ir_student_vectors st
             JOIN ir_scholarship_vectors sv ON sv.term_id = st.term_id
             JOIN ir_vocabulary v ON v.term_id = st.term_id
             WHERE sv.scholarship_id = ?
             ORDER BY st.student_id ASC, contribution DESC'
        );
        $termReasons = [];
        if ($contribStmt) {
            $contribStmt->bind_param('i', $scholarshipId);
            $contribStmt->execute();
            $contribRes = $contribStmt->get_result();
            while ($contribRes && $row = $contribRes->fetch_assoc()) {
                $studentId = (int) $row['student_id'];
                if (!isset($termReasons[$studentId])) {
                    $termReasons[$studentId] = [];
                }
                if (count($termReasons[$studentId]) >= 3) {
                    continue;
                }
                $termReasons[$studentId][] = str_replace('_', ' ', (string) $row['term']);
            }
            $contribStmt->close();
        }

        $studentMap = [];
        foreach ($students as $student) {
            $sid = (int) $student['studentID'];
            $studentMap[$sid] = $student;
        }

        $matched = [];
        foreach ($dotMap as $studentId => $dot) {
            $studentNorm = (float) ($studentNormMap[$studentId] ?? 0.0);
            if ($studentNorm <= 0.0 || $dot <= 0.0) {
                continue;
            }

            $score = $dot / ($studentNorm * $schNorm);
            if ($score <= 0.0) {
                continue;
            }

            if (!isset($studentMap[$studentId])) {
                continue;
            }

            $st = $studentMap[$studentId];
            $nameParts = array_filter([
                (string) ($st['firstName'] ?? ''),
                (string) ($st['middleName'] ?? ''),
                (string) ($st['lastName'] ?? '')
            ]);
            $studentName = trim(implode(' ', $nameParts));
            if ($studentName === '') {
                $studentName = 'Student ' . $studentId;
            }

            $matched[] = [
                'studentID' => $studentId,
                'name' => $studentName,
                'score' => (int) round(max(0.0, min(1.0, $score)) * 100),
                'reasons' => $termReasons[$studentId] ?? [],
                'phone' => (string) (($st['phone'] ?? '') !== '' ? $st['phone'] : ($st['contactNo'] ?? ''))
            ];
        }

        usort($matched, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $conn->close();
        return array_slice($matched, 0, $limit);
    }

    private static function ensureInfrastructure($conn) {
        if (self::$infraReady) {
            return;
        }

        $conn->query("CREATE TABLE IF NOT EXISTS ir_engine_meta (
            meta_key VARCHAR(120) NOT NULL PRIMARY KEY,
            meta_value TEXT NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS ir_stopwords (
            word VARCHAR(64) NOT NULL PRIMARY KEY
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS ir_synonyms (
            variant_term VARCHAR(191) NOT NULL PRIMARY KEY,
            canonical_term VARCHAR(191) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS ir_vocabulary (
            term_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            term VARCHAR(191) NOT NULL UNIQUE,
            doc_freq INT NOT NULL DEFAULT 0,
            idf_value DOUBLE NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS ir_scholarship_vectors (
            scholarship_id INT NOT NULL,
            term_id INT NOT NULL,
            tf_value DOUBLE NOT NULL,
            tfidf_value DOUBLE NOT NULL,
            PRIMARY KEY (scholarship_id, term_id),
            INDEX idx_ir_sch_term (term_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS ir_scholarship_norms (
            scholarship_id INT NOT NULL PRIMARY KEY,
            norm_value DOUBLE NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS ir_student_documents (
            student_id INT NOT NULL PRIMARY KEY,
            normalized_text LONGTEXT NOT NULL,
            profile_hash CHAR(40) NOT NULL,
            corpus_hash CHAR(40) NOT NULL,
            model_version VARCHAR(40) NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ir_student_doc_hash (profile_hash, corpus_hash, model_version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS ir_student_vectors (
            student_id INT NOT NULL,
            term_id INT NOT NULL,
            tf_value DOUBLE NOT NULL,
            tfidf_value DOUBLE NOT NULL,
            PRIMARY KEY (student_id, term_id),
            INDEX idx_ir_student_term (term_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS ir_recommendation_cache (
            student_id INT NOT NULL,
            scholarship_id INT NOT NULL,
            score_percent INT NOT NULL,
            explanation_json TEXT NOT NULL,
            profile_hash CHAR(40) NOT NULL,
            corpus_hash CHAR(40) NOT NULL,
            model_version VARCHAR(40) NOT NULL,
            generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (student_id, scholarship_id),
            INDEX idx_ir_rec_cache_lookup (student_id, profile_hash, corpus_hash, model_version, generated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::seedDefaults($conn);
        self::$infraReady = true;
    }

    private static function seedDefaults($conn) {
        $exists = $conn->query('SELECT COUNT(*) AS c FROM ir_stopwords');
        $countStop = 0;
        if ($exists) {
            $row = $exists->fetch_assoc();
            $countStop = (int) ($row['c'] ?? 0);
            $exists->free();
        }

        if ($countStop === 0) {
            $stmt = $conn->prepare('INSERT IGNORE INTO ir_stopwords (word) VALUES (?)');
            if ($stmt) {
                foreach (self::$defaultStopWords as $word) {
                    $w = trim($word);
                    if ($w === '') {
                        continue;
                    }
                    $stmt->bind_param('s', $w);
                    $stmt->execute();
                }
                $stmt->close();
            }
        }

        $existsSyn = $conn->query('SELECT COUNT(*) AS c FROM ir_synonyms');
        $countSyn = 0;
        if ($existsSyn) {
            $row = $existsSyn->fetch_assoc();
            $countSyn = (int) ($row['c'] ?? 0);
            $existsSyn->free();
        }

        if ($countSyn === 0) {
            $stmt = $conn->prepare('INSERT IGNORE INTO ir_synonyms (variant_term, canonical_term) VALUES (?, ?)');
            if ($stmt) {
                foreach (self::$defaultSynonyms as $variant => $canonical) {
                    $v = trim((string) $variant);
                    $c = trim((string) $canonical);
                    if ($v === '' || $c === '') {
                        continue;
                    }
                    $stmt->bind_param('ss', $v, $c);
                    $stmt->execute();
                }
                $stmt->close();
            }
        }
    }

    private static function getStudentProfile($conn, $studentId) {
        $sql = "SELECT studentID, firstName, lastName, gender, nationality, dept, college,
                       current_level, financial_need, career_interests, presRegion, permRegion
                FROM student WHERE studentID = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    private static function buildStudentProfileHash($student) {
        $parts = [
            (string) ($student['current_level'] ?? ''),
            (string) ($student['financial_need'] ?? ''),
            (string) ($student['career_interests'] ?? ''),
            (string) ($student['gender'] ?? ''),
            (string) ($student['dept'] ?? ''),
            (string) ($student['college'] ?? ''),
            (string) ($student['presRegion'] ?? ''),
            (string) ($student['permRegion'] ?? ''),
            (string) ($student['nationality'] ?? '')
        ];

        return sha1(implode('|', $parts));
    }

    private static function syncScholarshipIndex($conn) {
        $activeRows = [];
        $sql = "SELECT scholarshipID, schname, schlocation, schlocationfrom, degree, gender,
                       target_financial_need, sch, funding, description, eligibility, benefits, apply,
                       links, contact
                FROM scholarship
                WHERE adminapproval = 'Approved' AND schstatus = 'active' AND appDeadline >= CURDATE()";

        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $activeRows[] = $row;
            }
            $res->free();
        }

        $hashSeed = [];
        foreach ($activeRows as $row) {
            $hashSeed[] = implode('|', [
                (string) $row['scholarshipID'],
                (string) $row['schname'],
                (string) $row['sch'],
                (string) $row['degree'],
                (string) $row['gender'],
                (string) $row['target_financial_need'],
                (string) $row['description'],
                (string) $row['eligibility'],
                (string) $row['benefits'],
                (string) $row['apply']
            ]);
        }

        $corpusHash = sha1(implode('||', $hashSeed));

        $meta = self::getMetaValue($conn, 'scholarship_corpus_hash');
        if ($meta === $corpusHash) {
            return $corpusHash;
        }

        $stopWords = self::loadStopWords($conn);
        $synonyms = self::loadSynonyms($conn);

        $docTermsByScholarship = [];
        $dfCounter = [];

        foreach ($activeRows as $row) {
            $scholarshipId = (int) $row['scholarshipID'];
            $text = self::buildScholarshipDocument($row);
            $tokens = self::normalizeAndTokenize($text, $stopWords, $synonyms);
            $docTermsByScholarship[$scholarshipId] = $tokens;

            $seen = [];
            foreach ($tokens as $token) {
                if ($token === '') {
                    continue;
                }
                if (isset($seen[$token])) {
                    continue;
                }
                $seen[$token] = true;
                if (!isset($dfCounter[$token])) {
                    $dfCounter[$token] = 0;
                }
                $dfCounter[$token]++;
            }
        }

        $totalDocs = max(1, count($docTermsByScholarship));

        $conn->query('TRUNCATE TABLE ir_vocabulary');
        $conn->query('TRUNCATE TABLE ir_scholarship_vectors');
        $conn->query('TRUNCATE TABLE ir_scholarship_norms');
        $conn->query('TRUNCATE TABLE ir_student_vectors');
        $conn->query('TRUNCATE TABLE ir_student_documents');
        $conn->query('TRUNCATE TABLE ir_recommendation_cache');

        if (!empty($dfCounter)) {
            $insertVocab = $conn->prepare('INSERT INTO ir_vocabulary (term, doc_freq, idf_value) VALUES (?, ?, ?)');
            if ($insertVocab) {
                foreach ($dfCounter as $term => $df) {
                    $idf = log(($totalDocs + 1.0) / ($df + 1.0)) + 1.0;
                    $insertVocab->bind_param('sid', $term, $df, $idf);
                    $insertVocab->execute();
                }
                $insertVocab->close();
            }
        }

        $vocabulary = self::loadVocabulary($conn);

        $insertSchVec = $conn->prepare('INSERT INTO ir_scholarship_vectors (scholarship_id, term_id, tf_value, tfidf_value) VALUES (?, ?, ?, ?)');
        $insertNorm = $conn->prepare('INSERT INTO ir_scholarship_norms (scholarship_id, norm_value) VALUES (?, ?)');

        foreach ($docTermsByScholarship as $scholarshipId => $tokens) {
            $tf = self::buildTf($tokens);
            $normSq = 0.0;

            foreach ($tf as $term => $tfValue) {
                if (!isset($vocabulary[$term])) {
                    continue;
                }

                $termId = (int) $vocabulary[$term]['term_id'];
                $idf = (float) $vocabulary[$term]['idf'];
                $tfidf = $tfValue * $idf;
                $normSq += ($tfidf * $tfidf);

                if ($insertSchVec) {
                    $insertSchVec->bind_param('iidd', $scholarshipId, $termId, $tfValue, $tfidf);
                    $insertSchVec->execute();
                }
            }

            $norm = sqrt(max(0.0, $normSq));
            if ($insertNorm) {
                $insertNorm->bind_param('id', $scholarshipId, $norm);
                $insertNorm->execute();
            }
        }

        if ($insertSchVec) {
            $insertSchVec->close();
        }
        if ($insertNorm) {
            $insertNorm->close();
        }

        self::setMetaValue($conn, 'scholarship_corpus_hash', $corpusHash);

        return $corpusHash;
    }

    private static function getOrBuildStudentVector($conn, $studentId, $student, $profileHash, $corpusHash, $vocabulary) {
        $sql = "SELECT normalized_text FROM ir_student_documents
                WHERE student_id = ? AND profile_hash = ? AND corpus_hash = ? AND model_version = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $hasFreshDoc = false;

        if ($stmt) {
            $modelVersion = self::MODEL_VERSION;
            $stmt->bind_param('isss', $studentId, $profileHash, $corpusHash, $modelVersion);
            $stmt->execute();
            $res = $stmt->get_result();
            $hasFreshDoc = ($res && $res->num_rows > 0);
            $stmt->close();
        }

        if (!$hasFreshDoc) {
            $conn->query('DELETE FROM ir_student_vectors WHERE student_id = ' . (int) $studentId);
            $conn->query('DELETE FROM ir_student_documents WHERE student_id = ' . (int) $studentId);

            $stopWords = self::loadStopWords($conn);
            $synonyms = self::loadSynonyms($conn);

            $text = self::buildStudentDocument($student);
            $tokens = self::normalizeAndTokenize($text, $stopWords, $synonyms);
            $tf = self::buildTf($tokens);

            $insDoc = $conn->prepare('INSERT INTO ir_student_documents (student_id, normalized_text, profile_hash, corpus_hash, model_version) VALUES (?, ?, ?, ?, ?)');
            if ($insDoc) {
                $normalizedText = implode(' ', $tokens);
                $modelVersion = self::MODEL_VERSION;
                $insDoc->bind_param('issss', $studentId, $normalizedText, $profileHash, $corpusHash, $modelVersion);
                $insDoc->execute();
                $insDoc->close();
            }

            $insVec = $conn->prepare('INSERT INTO ir_student_vectors (student_id, term_id, tf_value, tfidf_value) VALUES (?, ?, ?, ?)');
            if ($insVec) {
                foreach ($tf as $term => $tfValue) {
                    if (!isset($vocabulary[$term])) {
                        continue;
                    }
                    $termId = (int) $vocabulary[$term]['term_id'];
                    $idf = (float) $vocabulary[$term]['idf'];
                    $tfidf = $tfValue * $idf;
                    $insVec->bind_param('iidd', $studentId, $termId, $tfValue, $tfidf);
                    $insVec->execute();
                }
                $insVec->close();
            }
        }

        return self::loadStudentVectorByTermId($conn, $studentId);
    }

    private static function loadCandidateScholarships($conn, $studentId, $student) {
        $studentLevel = strtolower(trim((string) ($student['current_level'] ?? '')));
        $studentGender = strtolower(trim((string) ($student['gender'] ?? '')));

        $sql = "SELECT S.scholarshipID AS id, S.schname AS title, S.sch AS category,
                       S.degree, S.gender AS target_gender,
                       S.appDeadline AS deadline, S.funding AS amount, S.description AS `desc`,
                       SI.firstName, SI.middleName, SI.lastName,
                       CASE WHEN A.applicationID IS NULL THEN 0 ELSE 1 END AS applied
                FROM scholarship S
                LEFT JOIN signatory SI ON SI.sigID = S.sigID
                LEFT JOIN application A ON A.scholarshipID = S.scholarshipID AND A.studentID = ?
                WHERE S.adminapproval = 'Approved'
                  AND S.schstatus = 'active'
                  AND S.appDeadline >= CURDATE()";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $reqDegree = strtolower(trim((string) ($row['degree'] ?? '')));
            if (!empty($reqDegree) && $reqDegree !== 'select' && !empty($studentLevel) && $studentLevel !== $reqDegree) {
                continue;
            }

            $reqGender = strtolower(trim((string) ($row['target_gender'] ?? '')));
            if (!empty($reqGender) && $reqGender !== 'select' && $reqGender !== 'male+female' && $reqGender !== 'prefer') {
                if ($studentGender !== '' && $studentGender !== $reqGender) {
                    continue;
                }
            }

            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }

    private static function computeDotProducts($conn, $studentId, $scholarshipIds) {
        if (empty($scholarshipIds)) {
            return [];
        }

        list($inClause, $types, $params) = self::buildInClauseParams($scholarshipIds);
        $sql = "SELECT sv.scholarship_id AS sid, SUM(sv.tfidf_value * st.tfidf_value) AS dot_value
                FROM ir_student_vectors st
                JOIN ir_scholarship_vectors sv ON sv.term_id = st.term_id
                WHERE st.student_id = ? AND sv.scholarship_id IN ($inClause)
                GROUP BY sv.scholarship_id";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $bindTypes = 'i' . $types;
        $bindValues = array_merge([$studentId], $params);
        self::bindParamsByRef($stmt, $bindTypes, $bindValues);

        $stmt->execute();
        $res = $stmt->get_result();

        $map = [];
        while ($row = $res->fetch_assoc()) {
            $map[(int) $row['sid']] = (float) $row['dot_value'];
        }

        $stmt->close();
        return $map;
    }

    private static function getScholarshipNorms($conn, $scholarshipIds) {
        if (empty($scholarshipIds)) {
            return [];
        }

        list($inClause, $types, $params) = self::buildInClauseParams($scholarshipIds);
        $sql = "SELECT scholarship_id, norm_value FROM ir_scholarship_norms WHERE scholarship_id IN ($inClause)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        self::bindParamsByRef($stmt, $types, $params);
        $stmt->execute();
        $res = $stmt->get_result();

        $map = [];
        while ($row = $res->fetch_assoc()) {
            $map[(int) $row['scholarship_id']] = (float) $row['norm_value'];
        }

        $stmt->close();
        return $map;
    }

    private static function getTopContributingTermsForScholarships($conn, $studentId, $scholarshipIds) {
        if (empty($scholarshipIds)) {
            return [];
        }

        list($inClause, $types, $params) = self::buildInClauseParams($scholarshipIds);
        $sql = "SELECT sv.scholarship_id AS sid, v.term,
                       (sv.tfidf_value * st.tfidf_value) AS contribution
                FROM ir_student_vectors st
                JOIN ir_scholarship_vectors sv ON sv.term_id = st.term_id
                JOIN ir_vocabulary v ON v.term_id = st.term_id
                WHERE st.student_id = ? AND sv.scholarship_id IN ($inClause)
                ORDER BY sid ASC, contribution DESC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $bindTypes = 'i' . $types;
        $bindValues = array_merge([$studentId], $params);
        self::bindParamsByRef($stmt, $bindTypes, $bindValues);

        $stmt->execute();
        $res = $stmt->get_result();

        $grouped = [];
        while ($row = $res->fetch_assoc()) {
            $sid = (int) $row['sid'];
            if (!isset($grouped[$sid])) {
                $grouped[$sid] = [];
            }

            if (count($grouped[$sid]) >= 5) {
                continue;
            }

            $term = (string) $row['term'];
            $grouped[$sid][] = str_replace('_', ' ', $term);
        }

        $stmt->close();
        return $grouped;
    }

    private static function readCachedRecommendations($conn, $studentId, $profileHash, $corpusHash) {
        $sql = "SELECT scholarship_id, score_percent, explanation_json
                FROM ir_recommendation_cache
                WHERE student_id = ? AND profile_hash = ? AND corpus_hash = ? AND model_version = ?
                  AND generated_at >= DATE_SUB(NOW(), INTERVAL " . (int) self::CACHE_TTL_HOURS . " HOUR)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $modelVersion = self::MODEL_VERSION;
        $stmt->bind_param('isss', $studentId, $profileHash, $corpusHash, $modelVersion);
        $stmt->execute();
        $res = $stmt->get_result();

        $cachedByScholarship = [];
        while ($row = $res->fetch_assoc()) {
            $sid = (int) $row['scholarship_id'];
            $cachedByScholarship[$sid] = [
                'match' => (int) $row['score_percent'],
                'reasons' => self::safeJsonDecode((string) $row['explanation_json'])
            ];
        }

        $stmt->close();

        if (empty($cachedByScholarship)) {
            return [];
        }

        $scholarshipIds = array_keys($cachedByScholarship);
        list($inClause, $types, $params) = self::buildInClauseParams($scholarshipIds);

        $metaSql = "SELECT S.scholarshipID AS id, S.schname AS title, S.sch AS category,
                           S.degree, S.gender AS target_gender,
                           S.appDeadline AS deadline, S.funding AS amount, S.description AS `desc`,
                           SI.firstName, SI.middleName, SI.lastName,
                           CASE WHEN A.applicationID IS NULL THEN 0 ELSE 1 END AS applied
                    FROM scholarship S
                    LEFT JOIN signatory SI ON SI.sigID = S.sigID
                    LEFT JOIN application A ON A.scholarshipID = S.scholarshipID AND A.studentID = ?
                    WHERE S.adminapproval = 'Approved' AND S.schstatus = 'active' AND S.appDeadline >= CURDATE()
                      AND S.scholarshipID IN ($inClause)";

        $stmt = $conn->prepare($metaSql);
        if (!$stmt) {
            return [];
        }

        $bindTypes = 'i' . $types;
        $bindValues = array_merge([$studentId], $params);
        self::bindParamsByRef($stmt, $bindTypes, $bindValues);

        $stmt->execute();
        $res = $stmt->get_result();

        $results = [];
        while ($row = $res->fetch_assoc()) {
            $sid = (int) $row['id'];
            if (!isset($cachedByScholarship[$sid])) {
                continue;
            }

            $daysLeft = (int) ceil((strtotime($row['deadline']) - time()) / (60 * 60 * 24));
            $orgParts = array_filter([$row['firstName'], $row['middleName'], $row['lastName']]);
            $orgName = trim(implode(' ', $orgParts));
            if ($orgName === '') {
                $orgName = 'Partner Organization';
            }

            $results[] = [
                'id' => $sid,
                'title' => (string) $row['title'],
                'org' => $orgName,
                'category' => (string) $row['category'],
                'match' => (int) $cachedByScholarship[$sid]['match'],
                'verified' => true,
                'deadline' => (string) $row['deadline'],
                'amount' => (string) $row['amount'],
                'desc' => (string) $row['desc'],
                'urgent' => ($daysLeft <= 7),
                'applied' => ((int) $row['applied']) === 1,
                'reasons' => $cachedByScholarship[$sid]['reasons']
            ];
        }

        $stmt->close();

        usort($results, function($a, $b) {
            if ($a['match'] === $b['match']) {
                return strtotime($a['deadline']) <=> strtotime($b['deadline']);
            }
            return $b['match'] <=> $a['match'];
        });

        return $results;
    }

    private static function saveRecommendationCache($conn, $studentId, $profileHash, $corpusHash, $results) {
        $stmt = $conn->prepare('DELETE FROM ir_recommendation_cache WHERE student_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->close();
        }

        if (empty($results)) {
            return;
        }

        $ins = $conn->prepare('INSERT INTO ir_recommendation_cache (student_id, scholarship_id, score_percent, explanation_json, profile_hash, corpus_hash, model_version, generated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        if (!$ins) {
            return;
        }

        $modelVersion = self::MODEL_VERSION;
        foreach ($results as $row) {
            $sid = (int) $row['id'];
            $score = (int) $row['match'];
            $explainJson = json_encode(array_values((array) ($row['reasons'] ?? [])));
            if ($explainJson === false) {
                $explainJson = '[]';
            }

            $ins->bind_param('iiissss', $studentId, $sid, $score, $explainJson, $profileHash, $corpusHash, $modelVersion);
            $ins->execute();
        }

        $ins->close();
    }

    private static function buildScholarshipDocument($row) {
        $parts = [
            (string) ($row['schname'] ?? ''),
            (string) ($row['sch'] ?? ''),
            (string) ($row['description'] ?? ''),
            (string) ($row['eligibility'] ?? ''),
            (string) ($row['benefits'] ?? ''),
            (string) ($row['apply'] ?? ''),
            (string) ($row['funding'] ?? ''),
            (string) ($row['degree'] ?? ''),
            (string) ($row['target_financial_need'] ?? ''),
            (string) ($row['schlocation'] ?? ''),
            (string) ($row['schlocationfrom'] ?? ''),
            (string) ($row['contact'] ?? ''),
            (string) ($row['links'] ?? '')
        ];

        return implode(' ', array_filter($parts, function($item) {
            return trim((string) $item) !== '';
        }));
    }

    private static function buildStudentDocument($student) {
        $parts = [
            'study level ' . (string) ($student['current_level'] ?? ''),
            'financial need ' . (string) ($student['financial_need'] ?? ''),
            'career interests ' . (string) ($student['career_interests'] ?? ''),
            'department ' . (string) ($student['dept'] ?? ''),
            'college ' . (string) ($student['college'] ?? ''),
            'region ' . (string) ($student['presRegion'] ?? ''),
            'region ' . (string) ($student['permRegion'] ?? ''),
            'nationality ' . (string) ($student['nationality'] ?? ''),
            'gender ' . (string) ($student['gender'] ?? '')
        ];

        return implode(' ', array_filter($parts, function($item) {
            return trim((string) $item) !== '';
        }));
    }

    private static function normalizeAndTokenize($text, $stopWords, $synonyms) {
        $text = strtolower((string) $text);
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/[^a-z0-9\s]+/i', ' ', $text);
        if ($text === null) {
            $text = '';
        }

        $text = preg_replace('/\s+/', ' ', trim($text));
        if ($text === null) {
            $text = '';
        }

        foreach ($synonyms as $variant => $canonical) {
            $variantPattern = '/\b' . preg_quote($variant, '/') . '\b/i';
            $text = preg_replace($variantPattern, (string) $canonical, $text);
            if ($text === null) {
                $text = '';
            }
        }

        foreach (self::$protectedPhrases as $phrase) {
            $phrasePattern = '/\b' . preg_quote($phrase, '/') . '\b/i';
            $text = preg_replace($phrasePattern, str_replace(' ', '_', $phrase), $text);
            if ($text === null) {
                $text = '';
            }
        }

        $tokens = preg_split('/\s+/', trim($text));
        if (!is_array($tokens)) {
            return [];
        }

        $normalized = [];
        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            if (is_numeric($token)) {
                continue;
            }

            if (isset($stopWords[$token])) {
                continue;
            }

            if (strlen($token) < 2) {
                continue;
            }

            $token = self::lightStem($token);
            if ($token !== '') {
                $normalized[] = $token;
            }
        }

        return $normalized;
    }

    private static function lightStem($token) {
        $t = (string) $token;

        if (substr($t, -3) === 'ies' && strlen($t) > 4) {
            return substr($t, 0, -3) . 'y';
        }

        if (substr($t, -3) === 'ing' && strlen($t) > 5) {
            return substr($t, 0, -3);
        }

        if (substr($t, -2) === 'ed' && strlen($t) > 4) {
            return substr($t, 0, -2);
        }

        if (substr($t, -1) === 's' && strlen($t) > 3) {
            return substr($t, 0, -1);
        }

        return $t;
    }

    private static function buildTf($tokens) {
        $counts = [];
        $total = 0;

        foreach ($tokens as $token) {
            if (!isset($counts[$token])) {
                $counts[$token] = 0;
            }
            $counts[$token]++;
            $total++;
        }

        if ($total <= 0) {
            return [];
        }

        $tf = [];
        foreach ($counts as $term => $count) {
            $tf[$term] = $count / $total;
        }

        return $tf;
    }

    private static function vectorNorm($vector) {
        $sum = 0.0;
        foreach ($vector as $val) {
            $sum += ($val * $val);
        }
        return sqrt(max(0.0, $sum));
    }

    private static function loadVocabulary($conn) {
        $res = $conn->query('SELECT term_id, term, idf_value FROM ir_vocabulary');
        $map = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $map[(string) $row['term']] = [
                    'term_id' => (int) $row['term_id'],
                    'idf' => (float) $row['idf_value']
                ];
            }
            $res->free();
        }

        return $map;
    }

    private static function loadStudentVectorByTermId($conn, $studentId) {
        $stmt = $conn->prepare('SELECT term_id, tfidf_value FROM ir_student_vectors WHERE student_id = ?');
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $res = $stmt->get_result();

        $vector = [];
        while ($row = $res->fetch_assoc()) {
            $vector[(int) $row['term_id']] = (float) $row['tfidf_value'];
        }

        $stmt->close();
        return $vector;
    }

    private static function loadStopWords($conn) {
        $res = $conn->query('SELECT word FROM ir_stopwords');
        $words = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $w = trim((string) $row['word']);
                if ($w !== '') {
                    $words[$w] = true;
                }
            }
            $res->free();
        }

        return $words;
    }

    private static function loadSynonyms($conn) {
        $res = $conn->query('SELECT variant_term, canonical_term FROM ir_synonyms');
        $synonyms = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $v = strtolower(trim((string) $row['variant_term']));
                $c = strtolower(trim((string) $row['canonical_term']));
                if ($v !== '' && $c !== '') {
                    $synonyms[$v] = $c;
                }
            }
            $res->free();
        }

        return $synonyms;
    }

    private static function getMetaValue($conn, $key) {
        $stmt = $conn->prepare('SELECT meta_value FROM ir_engine_meta WHERE meta_key = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $key);
        $stmt->execute();
        $res = $stmt->get_result();
        $value = null;

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $value = (string) ($row['meta_value'] ?? '');
        }

        $stmt->close();
        return $value;
    }

    private static function setMetaValue($conn, $key, $value) {
        $stmt = $conn->prepare('INSERT INTO ir_engine_meta (meta_key, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)');
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
        $stmt->close();
    }

    private static function buildInClauseParams($ids) {
        $cleanIds = [];
        foreach ($ids as $id) {
            $cleanIds[] = (int) $id;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $types = str_repeat('i', count($cleanIds));

        return [$placeholders, $types, $cleanIds];
    }

    private static function bindParamsByRef($stmt, $types, $values) {
        $refs = [];
        $refs[] = &$types;
        foreach ($values as $index => $value) {
            $refs[] = &$values[$index];
        }

        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    private static function safeJsonDecode($json) {
        $decoded = json_decode((string) $json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = $item;
            }
        }

        return $out;
    }
}
?>