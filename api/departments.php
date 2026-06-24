<?php
// api/departments.php
// GET, POST, PUT, DELETE operations for department records

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
            $query = "SELECT d.*, u.name as head_name, u.email as head_email, u.phone as head_phone 
                      FROM departments d 
                      LEFT JOIN users u ON d.head_id = u.id";
            $params = [];
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $query .= " WHERE d.id = :id";
                $params[':id'] = (int)$_GET['id'];
            }
            
            $query .= " ORDER BY d.name ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $department = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$department) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Department not found.']);
                    exit;
                }
                echo json_encode($department, JSON_PRETTY_PRINT);
            } else {
                $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($departments, JSON_PRETTY_PRINT);
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
            $required = ['name', 'code'];
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
            $chkCode = $db->prepare("SELECT id FROM departments WHERE code = :code");
            $chkCode->execute([':code' => $data['code']]);
            if ($chkCode->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Department code already exists.']);
                exit;
            }

            $headId = isset($data['head_id']) && $data['head_id'] !== '' ? (int)$data['head_id'] : null;

            // Check head_id exists
            if ($headId !== null) {
                $chkUser = $db->prepare("SELECT id FROM users WHERE id = :id");
                $chkUser->execute([':id' => $headId]);
                if (!$chkUser->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Head of Department user ID does not exist.']);
                    exit;
                }
            }

            $stmt = $db->prepare("INSERT INTO departments (name, code, head_id) VALUES (:name, :code, :head_id)");
            $stmt->execute([
                ':name' => $data['name'],
                ':code' => $data['code'],
                ':head_id' => $headId
            ]);

            $newId = $db->lastInsertId();
            http_response_code(201);
            echo json_encode(['message' => 'Department created successfully.', 'id' => $newId], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            // Update
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing department id parameter.']);
                exit;
            }
            $deptId = (int)$_GET['id'];

            // Check department exists
            $chk = $db->prepare("SELECT id FROM departments WHERE id = :id");
            $chk->execute([':id' => $deptId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Department not found.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input.']);
                exit;
            }

            // Check duplicate code (if code is changing)
            if (isset($data['code'])) {
                $chkCode = $db->prepare("SELECT id FROM departments WHERE code = :code AND id != :id");
                $chkCode->execute([':code' => $data['code'], ':id' => $deptId]);
                if ($chkCode->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Department code already in use by another department.']);
                    exit;
                }
            }

            // Check head_id exists (if head_id is changing)
            if (isset($data['head_id']) && $data['head_id'] !== '' && $data['head_id'] !== null) {
                $headId = (int)$data['head_id'];
                $chkUser = $db->prepare("SELECT id FROM users WHERE id = :id");
                $chkUser->execute([':id' => $headId]);
                if (!$chkUser->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Head of Department user ID does not exist.']);
                    exit;
                }
            }

            // Build dynamic update
            $updates = [];
            $params = [':id' => $deptId];
            $allowedFields = ['name', 'code', 'head_id'];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[] = "$field = :$field";
                    if ($field === 'head_id') {
                        $params[":$field"] = isset($data[$field]) && $data[$field] !== '' ? (int)$data[$field] : null;
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

            $query = "UPDATE departments SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);

            echo json_encode(['message' => 'Department updated successfully.'], JSON_PRETTY_PRINT);
            break;

        case 'DELETE':
            // Delete
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing department id parameter.']);
                exit;
            }
            $deptId = (int)$_GET['id'];

            // Check department exists
            $chk = $db->prepare("SELECT id FROM departments WHERE id = :id");
            $chk->execute([':id' => $deptId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Department not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM departments WHERE id = :id");
            $stmt->execute([':id' => $deptId]);

            echo json_encode(['message' => 'Department deleted successfully.'], JSON_PRETTY_PRINT);
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
