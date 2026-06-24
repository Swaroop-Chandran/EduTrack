<?php
// api/attendance.php
// GET, POST, PUT, DELETE operations for attendance records

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
            $query = "SELECT a.*, us.name as student_name, c.code as course_code, c.title as course_title, ut.name as teacher_name 
                      FROM attendance a 
                      JOIN users us ON a.student_id = us.id 
                      JOIN courses c ON a.course_id = c.id 
                      LEFT JOIN users ut ON a.marked_by = ut.id";
            $params = [];
            
            $conditions = [];
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $conditions[] = "a.id = :id";
                $params[':id'] = (int)$_GET['id'];
            }
            if (isset($_GET['student_id']) && $_GET['student_id'] !== '') {
                $conditions[] = "a.student_id = :student_id";
                $params[':student_id'] = (int)$_GET['student_id'];
            }
            if (isset($_GET['course_id']) && $_GET['course_id'] !== '') {
                $conditions[] = "a.course_id = :course_id";
                $params[':course_id'] = (int)$_GET['course_id'];
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " ORDER BY a.date DESC, us.name ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$attendance) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Attendance record not found.']);
                    exit;
                }
                echo json_encode($attendance, JSON_PRETTY_PRINT);
            } else {
                $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($attendances, JSON_PRETTY_PRINT);
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
            $required = ['student_id', 'course_id', 'date', 'status', 'marked_by'];
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
            $validStatus = ['present', 'absent', 'late'];
            if (!in_array($data['status'], $validStatus)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status. Must be one of: ' . implode(', ', $validStatus)]);
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

            // Validate marked_by
            $chkMarker = $db->prepare("SELECT id FROM users WHERE id = :id");
            $chkMarker->execute([':id' => (int)$data['marked_by']]);
            if (!$chkMarker->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Marked_by user ID does not exist.']);
                exit;
            }

            // Check duplicate attendance (student_id, course_id, date)
            $chkDup = $db->prepare("SELECT id FROM attendance WHERE student_id = :student_id AND course_id = :course_id AND date = :date");
            $chkDup->execute([
                ':student_id' => (int)$data['student_id'],
                ':course_id' => (int)$data['course_id'],
                ':date' => $data['date']
            ]);
            if ($chkDup->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Attendance record already exists for this student, course, and date.']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO attendance (student_id, course_id, date, status, marked_by) 
                                  VALUES (:student_id, :course_id, :date, :status, :marked_by)");
            $stmt->execute([
                ':student_id' => (int)$data['student_id'],
                ':course_id' => (int)$data['course_id'],
                ':date' => $data['date'],
                ':status' => $data['status'],
                ':marked_by' => (int)$data['marked_by']
            ]);

            $newId = $db->lastInsertId();
            http_response_code(201);
            echo json_encode(['message' => 'Attendance record created successfully.', 'id' => $newId], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            // Update
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing attendance id parameter.']);
                exit;
            }
            $attendanceId = (int)$_GET['id'];

            // Check attendance exists
            $chk = $db->prepare("SELECT * FROM attendance WHERE id = :id");
            $chk->execute([':id' => $attendanceId]);
            $currentRecord = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$currentRecord) {
                http_response_code(404);
                echo json_encode(['error' => 'Attendance record not found.']);
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
                $validStatus = ['present', 'absent', 'late'];
                if (!in_array($data['status'], $validStatus)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid status. Must be one of: ' . implode(', ', $validStatus)]);
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

            // Validate marked_by if provided
            if (isset($data['marked_by'])) {
                $chkMarker = $db->prepare("SELECT id FROM users WHERE id = :id");
                $chkMarker->execute([':id' => (int)$data['marked_by']]);
                if (!$chkMarker->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Marked_by user ID does not exist.']);
                    exit;
                }
            }

            // Check duplicate attendance if fields are changing
            $studentId = isset($data['student_id']) ? (int)$data['student_id'] : (int)$currentRecord['student_id'];
            $courseId = isset($data['course_id']) ? (int)$data['course_id'] : (int)$currentRecord['course_id'];
            $date = isset($data['date']) ? $data['date'] : $currentRecord['date'];

            if (isset($data['student_id']) || isset($data['course_id']) || isset($data['date'])) {
                $chkDup = $db->prepare("SELECT id FROM attendance WHERE student_id = :student_id AND course_id = :course_id AND date = :date AND id != :id");
                $chkDup->execute([
                    ':student_id' => $studentId,
                    ':course_id' => $courseId,
                    ':date' => $date,
                    ':id' => $attendanceId
                ]);
                if ($chkDup->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Attendance record already exists for this student, course, and date.']);
                    exit;
                }
            }

            // Build dynamic update
            $updates = [];
            $params = [':id' => $attendanceId];
            $allowedFields = ['student_id', 'course_id', 'date', 'status', 'marked_by'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    if (in_array($field, ['student_id', 'course_id', 'marked_by'])) {
                        $params[":$field"] = (int)$data[$field];
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

            $query = "UPDATE attendance SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);

            echo json_encode(['message' => 'Attendance record updated successfully.'], JSON_PRETTY_PRINT);
            break;

        case 'DELETE':
            // Delete
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing attendance id parameter.']);
                exit;
            }
            $attendanceId = (int)$_GET['id'];

            // Check attendance exists
            $chk = $db->prepare("SELECT id FROM attendance WHERE id = :id");
            $chk->execute([':id' => $attendanceId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Attendance record not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM attendance WHERE id = :id");
            $stmt->execute([':id' => $attendanceId]);

            echo json_encode(['message' => 'Attendance record deleted successfully.'], JSON_PRETTY_PRINT);
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
