-- Set student profiles to hit requested >=30% match-count targets and
-- enforce Women In Tech scholarship as female-only.

USE sms;

START TRANSACTION;

UPDATE student
SET
  current_level = 'diploma',
  financial_need = 'Medium',
  career_interests = 'merit based, stem, scholarship, diploma, visual art, students, financial need, computer science, cultural arts, artificial intelligence',
  gender = 'male',
  dept = 'technology based computing scholarship support',
  college = 'Technology Scholarship Program',
  presRegion = 'Nairobi',
  permRegion = 'Mombasa',
  nationality = NULL
WHERE studentID = 49;

UPDATE student
SET
  current_level = 'undergraduate',
  financial_need = 'Low',
  career_interests = 'scholarship, technology based, cultural arts, support, cybersecurity, computer science, students, stem, engineering',
  gender = 'female',
  dept = 'Information Technology',
  college = 'Technology Scholarship Program',
  presRegion = 'Nairobi technology',
  permRegion = 'Mombasa',
  nationality = NULL
WHERE studentID = 50;

UPDATE student
SET
  current_level = NULL,
  financial_need = 'Medium',
  career_interests = 'visual art, cultural arts, ict, artificial intelligence, support, software engineering, diploma, means based, computing, digital media',
  gender = NULL,
  dept = 'Creative Arts',
  college = 'School of Computing',
  presRegion = 'Mombasa',
  permRegion = 'Kenya',
  nationality = ''
WHERE studentID = 51;

UPDATE student
SET
  current_level = NULL,
  financial_need = 'Medium',
  career_interests = 'undergraduate, data science, technology based, computing, cultural arts, means based, diploma, stem, software engineering, support',
  gender = 'female',
  dept = 'technology based computing scholarship support',
  college = '',
  presRegion = '',
  permRegion = 'Kenya',
  nationality = 'Kenyan'
WHERE studentID = 52;

UPDATE student
SET
  current_level = NULL,
  financial_need = 'High',
  career_interests = 'students, visual art, artificial intelligence, computer science, merit based, financial need, computing, software engineering, support',
  gender = 'female',
  dept = 'Information Technology',
  college = '',
  presRegion = 'Kenya',
  permRegion = '',
  nationality = 'Kenyan'
WHERE studentID = 53;

UPDATE scholarship
SET gender = 'female'
WHERE scholarshipID = 57 OR LOWER(schname) = 'women in tech scholarship';

DELETE FROM ir_student_vectors WHERE student_id IN (49, 50, 51, 52, 53);
DELETE FROM ir_student_documents WHERE student_id IN (49, 50, 51, 52, 53);
DELETE FROM ir_recommendation_cache WHERE student_id IN (49, 50, 51, 52, 53);

COMMIT;
