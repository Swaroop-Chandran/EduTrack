<?php
// api/students.php
// GET, POST, PUT, DELETE operations for student records

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
            $query = "SELECT u.*, sp.roll_no, sp.cgpa, sp.department_id, sp.year, sp.semester, sp.dob, sp.address, sp.gender, sp.religion, sp.caste, sp.nationality, sp.blood_group, sp.aadhaar_number, sp.father_name, sp.father_phone, sp.father_email, sp.father_occupation, sp.mother_name, sp.mother_phone, sp.mother_email, sp.mother_occupation, sp.annual_income, sp.present_address, d.name as department_name, d.code as department_code 
                      FROM users u 
                      JOIN student_profiles sp ON u.id = sp.user_id 
                      LEFT JOIN departments d ON sp.department_id = d.id 
                      WHERE u.role = 'student'";
            $params = [];
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $query .= " AND u.id = :id";
                $params[':id'] = (int)$_GET['id'];
            }
            
            if (isset($_GET['dept_id']) && $_GET['dept_id'] !== '') {
                $query .= " AND sp.department_id = :dept_id";
                $params[':dept_id'] = (int)$_GET['dept_id'];
            }
            
            $query .= " ORDER BY u.name ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$student) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Student not found.']);
                    exit;
                }
                unset($student['password']);
                echo json_encode($student, JSON_PRETTY_PRINT);
            } else {
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($students as &$s) {
                    unset($s['password']);
                }
                unset($s);
                echo json_encode($students, JSON_PRETTY_PRINT);
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
            $required = ['name', 'email', 'password', 'roll_no', 'department_id', 'year', 'semester'];
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

            // Check duplicate roll number
            $chkRoll = $db->prepare("SELECT id FROM student_profiles WHERE roll_no = :roll_no");
            $chkRoll->execute([':roll_no' => $data['roll_no']]);
            if ($chkRoll->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Roll number already exists.']);
                exit;
            }

            $avatar = $data['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150';
            $phone = $data['phone'] ?? null;
            $status = $data['status'] ?? 'active';

            // Begin Transaction
            $db->beginTransaction();

            // 1. Insert into users
            $userStmt = $db->prepare("INSERT INTO users (name, email, password, role, avatar, phone, status) 
                                      VALUES (:name, :email, :password, 'student', :avatar, :phone, :status)");
            $userStmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => $data['password'],
                ':avatar' => $avatar,
                ':phone' => $phone,
                ':status' => $status
            ]);
            $newUserId = $db->lastInsertId();

            // 2. Insert into student_profiles
            $cgpa = isset($data['cgpa']) ? floatval($data['cgpa']) : 0.00;
            $dob = $data['dob'] ?? null;
            $address = $data['address'] ?? null;
            $gender = $data['gender'] ?? null;
            $religion = $data['religion'] ?? null;
            $caste = $data['caste'] ?? null;
            $nationality = $data['nationality'] ?? null;
            $blood_group = $data['blood_group'] ?? null;
            $aadhaar_number = $data['aadhaar_number'] ?? null;
            $father_name = $data['father_name'] ?? null;
            $father_phone = $data['father_phone'] ?? null;
            $father_email = $data['father_email'] ?? null;
            $father_occupation = $data['father_occupation'] ?? null;
            $mother_name = $data['mother_name'] ?? null;
            $mother_phone = $data['mother_phone'] ?? null;
            $mother_email = $data['mother_email'] ?? null;
            $mother_occupation = $data['mother_occupation'] ?? null;
            $annual_income = isset($data['annual_income']) && $data['annual_income'] !== '' ? floatval($data['annual_income']) : null;
            $present_address = $data['present_address'] ?? null;

            $profStmt = $db->prepare("INSERT INTO student_profiles (
                user_id, roll_no, department_id, year, semester, cgpa, dob, address, 
                gender, religion, caste, nationality, blood_group, aadhaar_number, 
                father_name, father_phone, father_email, father_occupation, 
                mother_name, mother_phone, mother_email, mother_occupation, 
                annual_income, present_address
            ) VALUES (
                :user_id, :roll_no, :department_id, :year, :semester, :cgpa, :dob, :address,
                :gender, :religion, :caste, :nationality, :blood_group, :aadhaar_number,
                :father_name, :father_phone, :father_email, :father_occupation,
                :mother_name, :mother_phone, :mother_email, :mother_occupation,
                :annual_income, :present_address
            )");
            
            $profStmt->execute([
                ':user_id' => $newUserId,
                ':roll_no' => $data['roll_no'],
                ':department_id' => (int)$data['department_id'],
                ':year' => (int)$data['year'],
                ':semester' => (int)$data['semester'],
                ':cgpa' => $cgpa,
                ':dob' => $dob,
                ':address' => $address,
                ':gender' => $gender,
                ':religion' => $religion,
                ':caste' => $caste,
                ':nationality' => $nationality,
                ':blood_group' => $blood_group,
                ':aadhaar_number' => $aadhaar_number,
                ':father_name' => $father_name,
                ':father_phone' => $father_phone,
                ':father_email' => $father_email,
                ':father_occupation' => $father_occupation,
                ':mother_name' => $mother_name,
                ':mother_phone' => $mother_phone,
                ':mother_email' => $mother_email,
                ':mother_occupation' => $mother_occupation,
                ':annual_income' => $annual_income,
                ':present_address' => $present_address
            ]);

            $db->commit();

            http_response_code(201);
            echo json_encode(['message' => 'Student created successfully.', 'id' => $newUserId], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            // Update
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing student user id parameter.']);
                exit;
            }
            $userId = (int)$_GET['id'];

            // Check student user exists
            $chk = $db->prepare("SELECT id FROM users WHERE id = :id AND role = 'student'");
            $chk->execute([':id' => $userId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Student not found.']);
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

            // Check duplicate roll number (if roll number is changing)
            if (isset($data['roll_no'])) {
                $chkRoll = $db->prepare("SELECT id FROM student_profiles WHERE roll_no = :roll_no AND user_id != :id");
                $chkRoll->execute([':roll_no' => $data['roll_no'], ':id' => $userId]);
                if ($chkRoll->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Roll number already in use by another student.']);
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

            // 2. Update student_profiles table dynamically
            $profileFields = [
                'roll_no', 'department_id', 'year', 'semester', 'cgpa', 'dob', 'address',
                'gender', 'religion', 'caste', 'nationality', 'blood_group', 'aadhaar_number',
                'father_name', 'father_phone', 'father_email', 'father_occupation',
                'mother_name', 'mother_phone', 'mother_email', 'mother_occupation',
                'annual_income', 'present_address'
            ];
            $profileUpdates = [];
            $profileParams = [':user_id' => $userId];
            foreach ($profileFields as $field) {
                if (isset($data[$field])) {
                    $profileUpdates[] = "$field = :$field";
                    if (in_array($field, ['department_id', 'year', 'semester'])) {
                        $profileParams[":$field"] = (int)$data[$field];
                    } elseif ($field === 'cgpa' || $field === 'annual_income') {
                        $profileParams[":$field"] = $data[$field] !== '' && $data[$field] !== null ? floatval($data[$field]) : null;
                    } else {
                        $profileParams[":$field"] = $data[$field];
                    }
                }
            }
            if (!empty($profileUpdates)) {
                $profileQuery = "UPDATE student_profiles SET " . implode(', ', $profileUpdates) . " WHERE user_id = :user_id";
                $stmt = $db->prepare($profileQuery);
                $stmt->execute($profileParams);
            }

            $db->commit();

            echo json_encode(['message' => 'Student updated successfully.'], JSON_PRETTY_PRINT);
            break;

        case 'DELETE':
            // Delete
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing student user id parameter.']);
                exit;
            }
            $userId = (int)$_GET['id'];

            // Check student exists
            $chk = $db->prepare("SELECT id FROM users WHERE id = :id AND role = 'student'");
            $chk->execute([':id' => $userId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Student not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);

            echo json_encode(['message' => 'Student deleted successfully.'], JSON_PRETTY_PRINT);
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
