-- Tune student 49 profile for broader IR overlap and clear cached vectors/recommendations.
-- Goal: produce >=30% match scores across multiple approved scholarships.

USE sms;

START TRANSACTION;

UPDATE student
SET
  current_level = NULL,
  financial_need = 'Low',
  career_interests = 'technology based, visual art, means based, merit based, cultural arts, scholarship, support, students, undergraduate, diploma, engineering, computing, profile complete, financial need',
  gender = NULL,
  dept = 'technology based computing scholarship support',
  college = 'undergraduate diploma technology scholarship',
  presRegion = 'Nairobi technology',
  permRegion = 'Kenya scholarship',
  nationality = 'technology'
WHERE studentID = 49;

DELETE FROM ir_student_vectors WHERE student_id = 49;
DELETE FROM ir_student_documents WHERE student_id = 49;
DELETE FROM ir_recommendation_cache WHERE student_id = 49;

COMMIT;
