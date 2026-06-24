<?php
// api/results.php
// GET, POST, PUT, DELETE operations for academic result records

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $db = Database::connect();

    switch ($method) {
        case 'GET':
            // Read
            $query = "SELECT r.*, us.name as student_name, c.code as course_code, c.title as course_title, e.title as exam_title 
                      FROM results r 
                      JOIN users us ON r.student_id = us.id 
                      JOIN courses c ON r.course_id = c.id 
                      LEFT JOIN exams e ON r.exam_id = e.id";
            $params = [];
            
            $conditions = [];
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $conditions[] = "r.id = :id";
                $params[':id'] = (int)$_GET['id'];
            }
            if (isset($_GET['student_id']) && $_GET['student_id'] !== '') {
                $conditions[] = "r.student_id = :student_id";
                $params[':student_id'] = (int)$_GET['student_id'];
            }
            if (isset($_GET['course_id']) && $_GET['course_id'] !== '') {
                $conditions[] = "r.course_id = :course_id";
                $params[':course_id'] = (int)$_GET['course_id'];
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " ORDER BY r.published_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$result) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Result record not found.']);
                    exit;
                }
                echo json_encode($result, JSON_PRETTY_PRINT);
            } else {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($results, JSON_PRETTY_PRINT);
            }
            break;

        case 'POST':
            // Create
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input.']);
                exit;
            }

            // Validation
            $required = ['student_id', 'course_id', 'exam_id', 'internal_marks', 'external_marks', 'total_marks', 'grade', 'semester', 'status'];
            $missing = [];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $missing[] = $field;
                }
            }
            if (!empty($missing)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
                exit;
            }

            // Validate status
            $validStatus = ['pass', 'fail'];
            if (!in_array($data['status'], $validStatus)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status. Must be pass or fail.']);
                exit;
            }

            // Validate student
            $chkStudent = $db->prepare("SELECT id FROM users WHERE id = :id AND role = 'student'");
            $chkStudent->execute([':id' => (int)$data['student_id']]);
            if (!$chkStudent->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Student ID does not exist or user is not a student.']);
                exit;
            }

            // Validate course
            $chkCourse = $db->prepare("SELECT id FROM courses WHERE id = :id");
            $chkCourse->execute([':id' => (int)$data['course_id']]);
            if (!$chkCourse->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID does not exist.']);
                exit;
            }

            // Validate exam
            $chkExam = $db->prepare("SELECT id FROM exams WHERE id = :id");
            $chkExam->execute([':id' => (int)$data['exam_id']]);
            if (!$chkExam->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Exam ID does not exist.']);
                exit;
            }

            // Check duplicate unique_student_course_exam
            $chkDup = $db->prepare("SELECT id FROM results WHERE student_id = :student_id AND course_id = :course_id AND exam_id = :exam_id");
            $chkDup->execute([
                ':student_id' => (int)$data['student_id'],
                ':course_id' => (int)$data['course_id'],
                ':exam_id' => (int)$data['exam_id']
            ]);
            if ($chkDup->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Result record already exists for this student, course, and exam.']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO results (student_id, course_id, exam_id, internal_marks, external_marks, total_marks, grade, semester, status) 
                                  VALUES (:student_id, :course_id, :exam_id, :internal_marks, :external_marks, :total_marks, :grade, :semester, :status)");
            $stmt->execute([
                ':student_id' => (int)$data['student_id'],
                ':course_id' => (int)$data['course_id'],
                ':exam_id' => (int)$data['exam_id'],
                ':internal_marks' => floatval($data['internal_marks']),
                ':external_marks' => floatval($data['external_marks']),
                ':total_marks' => floatval($data['total_marks']),
                ':grade' => $data['grade'],
                ':semester' => (int)$data['semester'],
                ':status' => $data['status']
            ]);

            $newId = $db->lastInsertId();
            http_response_code(201);
            echo json_encode(['message' => 'Result record created successfully.', 'id' => $newId], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            // Update
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing result id parameter.']);
                exit;
            }
            $resultId = (int)$_GET['id'];

            // Check result exists
            $chk = $db->prepare("SELECT * FROM results WHERE id = :id");
            $chk->execute([':id' => $resultId]);
            $currentRecord = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$currentRecord) {
                http_response_code(404);
                echo json_encode(['error' => 'Result record not found.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input.']);
                exit;
            }

            // Validate status if provided
            if (isset($data['status'])) {
                $validStatus = ['pass', 'fail'];
                if (!in_array($data['status'], $validStatus)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid status. Must be pass or fail.']);
                    exit;
                }
            }

            // Validate student if provided
            if (isset($data['student_id'])) {
                $chkStudent = $db->prepare("SELECT id FROM users WHERE id = :id AND role = 'student'");
                $chkStudent->execute([':id' => (int)$data['student_id']]);
                if (!$chkStudent->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Student ID does not exist or user is not a student.']);
                    exit;
                }
            }

            // Validate course if provided
            if (isset($data['course_id'])) {
                $chkCourse = $db->prepare("SELECT id FROM courses WHERE id = :id");
                $chkCourse->execute([':id' => (int)$data['course_id']]);
                if (!$chkCourse->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Course ID does not exist.']);
                    exit;
                }
            }

            // Validate exam if provided
            if (isset($data['exam_id'])) {
                $chkExam = $db->prepare("SELECT id FROM exams WHERE id = :id");
                $chkExam->execute([':id' => (int)$data['exam_id']]);
                if (!$chkExam->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Exam ID does not exist.']);
                    exit;
                }
            }

            // Check duplicate unique_student_course_exam if changing
            $studentId = isset($data['student_id']) ? (int)$data['student_id'] : (int)$currentRecord['student_id'];
            $courseId = isset($data['course_id']) ? (int)$data['course_id'] : (int)$currentRecord['course_id'];
            $examId = isset($data['exam_id']) ? (int)$data['exam_id'] : (int)$currentRecord['exam_id'];

            if (isset($data['student_id']) || isset($data['course_id']) || isset($data['exam_id'])) {
                $chkDup = $db->prepare("SELECT id FROM results WHERE student_id = :student_id AND course_id = :course_id AND exam_id = :exam_id AND id != :id");
                $chkDup->execute([
                    ':student_id' => $studentId,
                    ':course_id' => $courseId,
                    ':exam_id' => $examId,
                    ':id' => $resultId
                ]);
                if ($chkDup->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Result record already exists for this student, course, and exam.']);
                    exit;
                }
            }

            // Build dynamic update
            $updates = [];
            $params = [':id' => $resultId];
            $allowedFields = ['student_id', 'course_id', 'exam_id', 'internal_marks', 'external_marks', 'total_marks', 'grade', 'semester', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    if (in_array($field, ['student_id', 'course_id', 'exam_id', 'semester'])) {
                        $params[":$field"] = (int)$data[$field];
                    } elseif (in_array($field, ['internal_marks', 'external_marks', 'total_marks'])) {
                        $params[":$field"] = floatval($data[$field]);
                    } else {
                        $params[":$field"] = $data[$field];
                    }
                }
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields provided for update.']);
                exit;
            }

            $query = "UPDATE results SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);

            echo json_encode(['message' => 'Result record updated successfully.'], JSON_PRETTY_PRINT);
            break;

        case 'DELETE':
            // Delete
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing result id parameter.']);
                exit;
            }
            $resultId = (int)$_GET['id'];

            // Check result exists
            $chk = $db->prepare("SELECT id FROM results WHERE id = :id");
            $chk->execute([':id' => $resultId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Result record not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM results WHERE id = :id");
            $stmt->execute([':id' => $resultId]);

            echo json_encode(['message' => 'Result record deleted successfully.'], JSON_PRETTY_PRINT);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed.']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
}
