-- One-time migration: add foreign keys to IR tables on existing databases.
-- Run this on the target DB after importing/updating IR tables.
-- If any orphan count is non-zero, resolve those rows before running the ALTER TABLE block.

USE sms;

-- ---------------------------------------------------------------------------
-- 1) Orphan pre-checks (all should be 0)
-- ---------------------------------------------------------------------------
SELECT COUNT(*) AS orphan_rec_cache_students
FROM ir_recommendation_cache c
LEFT JOIN student s ON s.studentID = c.student_id
WHERE s.studentID IS NULL;

SELECT COUNT(*) AS orphan_rec_cache_scholarships
FROM ir_recommendation_cache c
LEFT JOIN scholarship sc ON sc.scholarshipID = c.scholarship_id
WHERE sc.scholarshipID IS NULL;

SELECT COUNT(*) AS orphan_sch_norms
FROM ir_scholarship_norms n
LEFT JOIN scholarship sc ON sc.scholarshipID = n.scholarship_id
WHERE sc.scholarshipID IS NULL;

SELECT COUNT(*) AS orphan_sch_vectors_sch
FROM ir_scholarship_vectors v
LEFT JOIN scholarship sc ON sc.scholarshipID = v.scholarship_id
WHERE sc.scholarshipID IS NULL;

SELECT COUNT(*) AS orphan_sch_vectors_terms
FROM ir_scholarship_vectors v
LEFT JOIN ir_vocabulary t ON t.term_id = v.term_id
WHERE t.term_id IS NULL;

SELECT COUNT(*) AS orphan_student_docs
FROM ir_student_documents d
LEFT JOIN student s ON s.studentID = d.student_id
WHERE s.studentID IS NULL;

SELECT COUNT(*) AS orphan_student_vectors_students
FROM ir_student_vectors v
LEFT JOIN student s ON s.studentID = v.student_id
WHERE s.studentID IS NULL;

SELECT COUNT(*) AS orphan_student_vectors_terms
FROM ir_student_vectors v
LEFT JOIN ir_vocabulary t ON t.term_id = v.term_id
WHERE t.term_id IS NULL;

-- ---------------------------------------------------------------------------
-- 2) Add foreign keys
-- ---------------------------------------------------------------------------
ALTER TABLE ir_recommendation_cache
  ADD CONSTRAINT fk_ir_rec_cache_student
    FOREIGN KEY (student_id) REFERENCES student(studentID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_ir_rec_cache_scholarship
    FOREIGN KEY (scholarship_id) REFERENCES scholarship(scholarshipID)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ir_scholarship_norms
  ADD CONSTRAINT fk_ir_sch_norms_scholarship
    FOREIGN KEY (scholarship_id) REFERENCES scholarship(scholarshipID)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ir_scholarship_vectors
  ADD CONSTRAINT fk_ir_sch_vectors_scholarship
    FOREIGN KEY (scholarship_id) REFERENCES scholarship(scholarshipID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_ir_sch_vectors_term
    FOREIGN KEY (term_id) REFERENCES ir_vocabulary(term_id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ir_student_documents
  ADD CONSTRAINT fk_ir_student_docs_student
    FOREIGN KEY (student_id) REFERENCES student(studentID)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE ir_student_vectors
  ADD CONSTRAINT fk_ir_student_vectors_student
    FOREIGN KEY (student_id) REFERENCES student(studentID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_ir_student_vectors_term
    FOREIGN KEY (term_id) REFERENCES ir_vocabulary(term_id)
    ON DELETE CASCADE ON UPDATE CASCADE;
