<?php
// api/courses.php
// GET, POST, PUT, DELETE operations for course records

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
            $query = "SELECT c.*, d.name as department_name, d.code as department_code, u.name as teacher_name 
                      FROM courses c 
                      JOIN departments d ON c.department_id = d.id 
                      JOIN users u ON c.teacher_id = u.id";
            $params = [];
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $query .= " WHERE c.id = :id";
                $params[':id'] = (int)$_GET['id'];
            } elseif (isset($_GET['dept_id']) && $_GET['dept_id'] !== '') {
                $query .= " WHERE c.department_id = :dept_id";
                $params[':dept_id'] = (int)$_GET['dept_id'];
            }
            
            $query .= " ORDER BY c.code ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$course) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Course not found.']);
                    exit;
                }
                echo json_encode($course, JSON_PRETTY_PRINT);
            } else {
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($courses, JSON_PRETTY_PRINT);
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
            $required = ['title', 'code', 'department_id', 'teacher_id', 'credits', 'semester', 'description'];
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

            // Check duplicate code
            $chk = $db->prepare("SELECT id FROM courses WHERE code = :code");
            $chk->execute([':code' => $data['code']]);
            if ($chk->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Course code already exists.']);
                exit;
            }

            $status = $data['status'] ?? 'active';

            $stmt = $db->prepare("INSERT INTO courses (title, code, department_id, teacher_id, credits, semester, description, status) 
                                  VALUES (:title, :code, :department_id, :teacher_id, :credits, :semester, :description, :status)");
            $stmt->execute([
                ':title' => $data['title'],
                ':code' => $data['code'],
                ':department_id' => (int)$data['department_id'],
                ':teacher_id' => (int)$data['teacher_id'],
                ':credits' => (int)$data['credits'],
                ':semester' => (int)$data['semester'],
                ':description' => $data['description'],
                ':status' => $status
            ]);

            $newId = $db->lastInsertId();
            http_response_code(201);
            echo json_encode(['message' => 'Course created successfully.', 'id' => $newId], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            // Update
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing course id parameter.']);
                exit;
            }
            $courseId = (int)$_GET['id'];

            // Check course exists
            $chk = $db->prepare("SELECT id FROM courses WHERE id = :id");
            $chk->execute([':id' => $courseId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Course not found.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input.']);
                exit;
            }

            // Build dynamic update query
            $updates = [];
            $params = [':id' => $courseId];
            $allowedFields = ['title', 'code', 'department_id', 'teacher_id', 'credits', 'semester', 'description', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    if (in_array($field, ['department_id', 'teacher_id', 'credits', 'semester'])) {
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

            // If code is being updated, verify it doesn't collide
            if (isset($data['code'])) {
                $codeChk = $db->prepare("SELECT id FROM courses WHERE code = :code AND id != :id");
                $codeChk->execute([':code' => $data['code'], ':id' => $courseId]);
                if ($codeChk->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Course code already in use by another course.']);
                    exit;
                }
            }

            $query = "UPDATE courses SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);

            echo json_encode(['message' => 'Course updated successfully.'], JSON_PRETTY_PRINT);
            break;

        case 'DELETE':
            // Delete
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing course id parameter.']);
                exit;
            }
            $courseId = (int)$_GET['id'];

            // Check course exists
            $chk = $db->prepare("SELECT id FROM courses WHERE id = :id");
            $chk->execute([':id' => $courseId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Course not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM courses WHERE id = :id");
            $stmt->execute([':id' => $courseId]);

            echo json_encode(['message' => 'Course deleted successfully.'], JSON_PRETTY_PRINT);
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
