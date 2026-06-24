<?php
// api/placements.php
// GET, POST, PUT, DELETE operations for placement records

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
            $query = "SELECT p.*, u.name as poster_name 
                      FROM placements p 
                      LEFT JOIN users u ON p.posted_by = u.id";
            $params = [];
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $query .= " WHERE p.id = :id";
                $params[':id'] = (int)$_GET['id'];
            }
            
            $query .= " ORDER BY p.deadline ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            if (isset($_GET['id']) && $_GET['id'] !== '') {
                $placement = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$placement) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Placement not found.']);
                    exit;
                }
                echo json_encode($placement, JSON_PRETTY_PRINT);
            } else {
                $placements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($placements, JSON_PRETTY_PRINT);
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
            $required = ['company', 'role', 'type', 'location', 'stipend', 'eligibility', 'description', 'deadline', 'posted_by'];
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

            // Validate type
            $validTypes = ['internship', 'full-time', 'part-time', 'contract'];
            if (!in_array($data['type'], $validTypes)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid placement type. Must be one of: ' . implode(', ', $validTypes)]);
                exit;
            }

            // Validate posted_by
            $chkUser = $db->prepare("SELECT id FROM users WHERE id = :id");
            $chkUser->execute([':id' => (int)$data['posted_by']]);
            if (!$chkUser->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Poster user ID does not exist.']);
                exit;
            }

            $status = $data['status'] ?? 'open';
            $validStatus = ['open', 'closed'];
            if (!in_array($status, $validStatus)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status. Must be open or closed.']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO placements (company, role, type, location, stipend, eligibility, description, deadline, posted_by, status) 
                                  VALUES (:company, :role, :type, :location, :stipend, :eligibility, :description, :deadline, :posted_by, :status)");
            $stmt->execute([
                ':company' => $data['company'],
                ':role' => $data['role'],
                ':type' => $data['type'],
                ':location' => $data['location'],
                ':stipend' => $data['stipend'],
                ':eligibility' => $data['eligibility'],
                ':description' => $data['description'],
                ':deadline' => $data['deadline'],
                ':posted_by' => (int)$data['posted_by'],
                ':status' => $status
            ]);

            $newId = $db->lastInsertId();
            http_response_code(201);
            echo json_encode(['message' => 'Placement created successfully.', 'id' => $newId], JSON_PRETTY_PRINT);
            break;

        case 'PUT':
            // Update
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing placement id parameter.']);
                exit;
            }
            $placementId = (int)$_GET['id'];

            // Check placement exists
            $chk = $db->prepare("SELECT id FROM placements WHERE id = :id");
            $chk->execute([':id' => $placementId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Placement not found.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input.']);
                exit;
            }

            // Validate type if provided
            if (isset($data['type'])) {
                $validTypes = ['internship', 'full-time', 'part-time', 'contract'];
                if (!in_array($data['type'], $validTypes)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid placement type. Must be one of: ' . implode(', ', $validTypes)]);
                    exit;
                }
            }

            // Validate status if provided
            if (isset($data['status'])) {
                $validStatus = ['open', 'closed'];
                if (!in_array($data['status'], $validStatus)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid status. Must be open or closed.']);
                    exit;
                }
            }

            // Validate posted_by if provided
            if (isset($data['posted_by']) && $data['posted_by'] !== '' && $data['posted_by'] !== null) {
                $chkUser = $db->prepare("SELECT id FROM users WHERE id = :id");
                $chkUser->execute([':id' => (int)$data['posted_by']]);
                if (!$chkUser->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Poster user ID does not exist.']);
                    exit;
                }
            }

            // Build dynamic update
            $updates = [];
            $params = [':id' => $placementId];
            $allowedFields = ['company', 'role', 'type', 'location', 'stipend', 'eligibility', 'description', 'deadline', 'posted_by', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    if ($field === 'posted_by') {
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

            $query = "UPDATE placements SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);

            echo json_encode(['message' => 'Placement updated successfully.'], JSON_PRETTY_PRINT);
            break;

        case 'DELETE':
            // Delete
            if (!isset($_GET['id']) || $_GET['id'] === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Missing placement id parameter.']);
                exit;
            }
            $placementId = (int)$_GET['id'];

            // Check placement exists
            $chk = $db->prepare("SELECT id FROM placements WHERE id = :id");
            $chk->execute([':id' => $placementId]);
            if (!$chk->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Placement not found.']);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM placements WHERE id = :id");
            $stmt->execute([':id' => $placementId]);

            echo json_encode(['message' => 'Placement deleted successfully.'], JSON_PRETTY_PRINT);
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
