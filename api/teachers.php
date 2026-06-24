<?php
// api/teachers.php
// GET, POST, PUT, DELETE operations for teacher records

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
            $query = "SELECT u.*, tp.employee_id, tp.designation, tp.qualification, tp.department_id, d.name as department_name, d.code as department_code 
                      FROM users u 
                      JOIN teacher_profiles tp ON u.id = tp.user_id 
                      LEFT JOIN departments d ON tp.department_id = d.id 
                      WHERE u.role = 'teacher'";
            $params = [];
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $query .= " AND u.id = :id";
                $params[':id'] = (int)$_GET['id'];
            }
            
            $query .= " ORDER BY u.name ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$teacher) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Teacher not found.']);
                    exit;
                }
                unset($teacher['password']);
                echo json_encode($teacher, JSON_PRETTY_PRINT);
            } else {
                $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($teachers as &$t) {
                    unset($t['password']);
                }
                unset($t);
                echo json_encode($teachers, JSON_PRETTY_PRINT);
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
            $required = ['name', 'email', 'password', 'employee_id', 'department_id', 'designation', 'qualification'];
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

            // Check duplicate email
            $chkEmail = $db->prepare("SELECT id FROM users WHERE email = :email");
            $chkEmail->execute([':email' => $data['email']]);
            if ($chkEmail->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Email address already exists.']);
                exit;
            }

            // Check duplicate employee_id
            $chkEmp = $db->prepare("SELECT id FROM teacher_profiles WHERE employee_id = :employee_id");
            $chkEmp->execute([':employee_id' => $data['employee_id']]);
            if ($chkEmp->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Employee ID already exists.']);
                exit;
            }

            $avatar = $data['avatar'] ?? 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150';
            $phone = $data['phone'] ?? null;
            $status = $data['status'] ?? 'active';

            // Begin Transaction
            $db->beginTransaction();

            // 1. Insert into users
            $userStmt = $db->prepare("INSERT INTO users (name, email, password, role, avatar, phone, status) 
                                      VALUES (:name, :email, :password, 'teacher', :avatar, :phone, :status)");
            $userStmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => $data['password'],
                ':avatar' => $avatar,
                ':phone' => $phone,
                ':status' => $status
            ]);
            $newUserId = $db->lastInsertId();

            // 2. Insert into teacher_profiles
            $profStmt = $db->prepare("INSERT INTO teacher_profiles (user_id, employee_id, department_id, designation, qualification) 
                                      VALUES (:user_id, :employee_id, :department_id, :designation, :qualification)");
            $profStmt->execute([
                ':user_id' => $newUserId,
                ':employee_id' => $data['employee_id'],
                ':department_id' => (int)$data['department_id'],
                ':designation' => $data['designation'],
                ':qualification' => $data['qualification']
            ]);

            $db->commit();

            http_response_code(201);
            echo json_encode(['message' => 'Teacher created successfully.', 'id' => $newUserId], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            // Update
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing teacher user id parameter.']);
                exit;
            }
            $userId = (int)$_GET['id'];

            // Check teacher user exists
            $chk = $db->prepare("SELECT id FROM users WHERE id = :id AND role = 'teacher'");
            $chk->execute([':id' => $userId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Teacher not found.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input.']);
                exit;
            }

            // Check duplicate email (if email is changing)
            if (isset($data['email'])) {
                $chkEmail = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $chkEmail->execute([':email' => $data['email'], ':id' => $userId]);
                if ($chkEmail->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Email address already in use by another user.']);
                    exit;
                }
            }

            // Check duplicate employee_id (if employee_id is changing)
            if (isset($data['employee_id'])) {
                $chkEmp = $db->prepare("SELECT id FROM teacher_profiles WHERE employee_id = :employee_id AND user_id != :id");
                $chkEmp->execute([':employee_id' => $data['employee_id'], ':id' => $userId]);
                if ($chkEmp->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Employee ID already in use by another teacher.']);
                    exit;
                }
            }

            $db->beginTransaction();

            // 1. Update users table dynamically
            $userFields = ['name', 'email', 'password', 'avatar', 'phone', 'status'];
            $userUpdates = [];
            $userParams = [':id' => $userId];
            foreach ($userFields as $field) {
                if (isset($data[$field])) {
                    $userUpdates[] = "$field = :$field";
                    $userParams[":$field"] = $data[$field];
                }
            }
            if (!empty($userUpdates)) {
                $userQuery = "UPDATE users SET " . implode(', ', $userUpdates) . " WHERE id = :id";
                $stmt = $db->prepare($userQuery);
                $stmt->execute($userParams);
            }

            // 2. Update teacher_profiles table dynamically
            $profileFields = ['employee_id', 'department_id', 'designation', 'qualification'];
            $profileUpdates = [];
            $profileParams = [':user_id' => $userId];
            foreach ($profileFields as $field) {
                if (isset($data[$field])) {
                    $profileUpdates[] = "$field = :$field";
                    if ($field === 'department_id') {
                        $profileParams[":$field"] = (int)$data[$field];
                    } else {
                        $profileParams[":$field"] = $data[$field];
                    }
                }
            }
            if (!empty($profileUpdates)) {
                $profileQuery = "UPDATE teacher_profiles SET " . implode(', ', $profileUpdates) . " WHERE user_id = :user_id";
                $stmt = $db->prepare($profileQuery);
                $stmt->execute($profileParams);
            }

            $db->commit();

            echo json_encode(['message' => 'Teacher updated successfully.'], JSON_PRETTY_PRINT);
            break;

        case 'DELETE':
            // Delete
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing teacher user id parameter.']);
                exit;
            }
            $userId = (int)$_GET['id'];

            // Check teacher exists
            $chk = $db->prepare("SELECT id FROM users WHERE id = :id AND role = 'teacher'");
            $chk->execute([':id' => $userId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Teacher not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);

            echo json_encode(['message' => 'Teacher deleted successfully.'], JSON_PRETTY_PRINT);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed.']);
            break;
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
}
