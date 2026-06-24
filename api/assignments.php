<?php
// api/assignments.php
// GET, POST, PUT, DELETE operations for assignment records

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
            $query = "SELECT a.*, c.code as course_code, c.title as course_title, u.name as teacher_name 
                      FROM assignments a 
                      JOIN courses c ON a.course_id = c.id 
                      JOIN users u ON a.teacher_id = u.id";
            $params = [];
            
            $conditions = [];
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $conditions[] = "a.id = :id";
                $params[':id'] = (int)$_GET['id'];
            }
            if (isset($_GET['course_id']) && $_GET['course_id'] !== '') {
                $conditions[] = "a.course_id = :course_id";
                $params[':course_id'] = (int)$_GET['course_id'];
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " ORDER BY a.due_date ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$assignment) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Assignment not found.']);
                    exit;
                }
                echo json_encode($assignment, JSON_PRETTY_PRINT);
            } else {
                $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($assignments, JSON_PRETTY_PRINT);
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
            $required = ['course_id', 'teacher_id', 'title', 'description', 'due_date', 'max_marks'];
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

            // Validate course
            $chkCourse = $db->prepare("SELECT id FROM courses WHERE id = :id");
            $chkCourse->execute([':id' => (int)$data['course_id']]);
            if (!$chkCourse->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID does not exist.']);
                exit;
            }

            // Validate teacher
            $chkTeacher = $db->prepare("SELECT id FROM users WHERE id = :id");
            $chkTeacher->execute([':id' => (int)$data['teacher_id']]);
            if (!$chkTeacher->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Teacher user ID does not exist.']);
                exit;
            }

            $status = $data['status'] ?? 'active';
            $validStatus = ['active', 'closed'];
            if (!in_array($status, $validStatus)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status. Must be active or closed.']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO assignments (course_id, teacher_id, title, description, due_date, max_marks, status) 
                                  VALUES (:course_id, :teacher_id, :title, :description, :due_date, :max_marks, :status)");
            $stmt->execute([
                ':course_id' => (int)$data['course_id'],
                ':teacher_id' => (int)$data['teacher_id'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':due_date' => $data['due_date'],
                ':max_marks' => (int)$data['max_marks'],
                ':status' => $status
            ]);

            $newId = $db->lastInsertId();
            http_response_code(201);
            echo json_encode(['message' => 'Assignment created successfully.', 'id' => $newId], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            // Update
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing assignment id parameter.']);
                exit;
            }
            $assignmentId = (int)$_GET['id'];

            // Check assignment exists
            $chk = $db->prepare("SELECT id FROM assignments WHERE id = :id");
            $chk->execute([':id' => $assignmentId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Assignment not found.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input.']);
                exit;
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

            // Validate teacher if provided
            if (isset($data['teacher_id'])) {
                $chkTeacher = $db->prepare("SELECT id FROM users WHERE id = :id");
                $chkTeacher->execute([':id' => (int)$data['teacher_id']]);
                if (!$chkTeacher->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Teacher user ID does not exist.']);
                    exit;
                }
            }

            // Validate status if provided
            if (isset($data['status'])) {
                $validStatus = ['active', 'closed'];
                if (!in_array($data['status'], $validStatus)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid status. Must be active or closed.']);
                    exit;
                }
            }

            // Build dynamic update
            $updates = [];
            $params = [':id' => $assignmentId];
            $allowedFields = ['course_id', 'teacher_id', 'title', 'description', 'due_date', 'max_marks', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    if (in_array($field, ['course_id', 'teacher_id', 'max_marks'])) {
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

            $query = "UPDATE assignments SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);

            echo json_encode(['message' => 'Assignment updated successfully.'], JSON_PRETTY_PRINT);
            break;

        case 'DELETE':
            // Delete
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing assignment id parameter.']);
                exit;
            }
            $assignmentId = (int)$_GET['id'];

            // Check assignment exists
            $chk = $db->prepare("SELECT id FROM assignments WHERE id = :id");
            $chk->execute([':id' => $assignmentId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Assignment not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");
            $stmt->execute([':id' => $assignmentId]);

            echo json_encode(['message' => 'Assignment deleted successfully.'], JSON_PRETTY_PRINT);
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
