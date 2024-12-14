<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require '../src/vendor/autoload.php';
$app = new \Slim\App;
// Secret key for JWTs
$key = 'server_hack';
// Middleware to verify JWT
$jwtMiddleware = function ($request, $response, $next) use ($key) {
    $authHeader = $request->getHeader('Authorization');
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader[0], $matches)) {
        $jwt = $matches[1];
        if (verifyToken($jwt, $key)) {
            return $next($request, $response);
        }
    }
    return $response->withStatus(401)->write(json_encode(["status" => "fail", "data" => ["title" => "Unauthorized"]]));
};
// Function to create JWT
function createToken($data, $key, $issuer, $audience, $expiry = 60) {
    $iat = time();
    $payload = [
        'iss' => $issuer,
        'aud' => $audience,
        'iat' => $iat,
        'exp' => $iat + $expiry,
        'data' => $data
    ];
    return JWT::encode($payload, $key, 'HS256');
}
// Function to verify JWT
function verifyToken($token, $key) {
    try {
        return JWT::decode($token, new Key($key, 'HS256'));
    } catch (Exception $e) {
        return false;
    }
}
// Database connection
function getConnection() {
    $servername = "localhost";
    $dbusername = "root";
    $dbpassword = "";
    $dbname = "libraryliv";
    return new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
}
// User registration
$app->post('/user/register', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $uname = $data->username;
    $pass = $data->password;
    try {
        $conn = getConnection();
        $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':username' => $uname,
            ':password' => hash("SHA256", $pass)
        ]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});
// User authentication (returns JWT)
$app->post('/user/authenticate', function (Request $request, Response $response) use ($key) {
    $data = json_decode($request->getBody());
    $uname = $data->username;
    $pass = $data->password;
    try {
        $conn = getConnection();
        $sql = "SELECT * FROM users WHERE username = :username AND password = :password";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':username' => $uname,
            ':password' => hash("SHA256", $pass)
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $token = createToken(['username' => $uname, 'userid' => $user['userid']], $key, 'http://library.org', 'http://library.com');
            return $response->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(["status" => "success", "token" => $token]));
        } else {
            return $response->withStatus(401)
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(["status" => "fail", "data" => ["title" => "Invalid credentials"]]));
        }
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});

// Get user information (no ID in URL, empty JSON body)
$app->get('/user/read', function (Request $request, Response $response) use ($key) {
    $authHeader = $request->getHeader('Authorization');
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader[0], $matches)) {
        $jwt = $matches[1];
        $decoded = verifyToken($jwt, $key);
        
        if ($decoded) {
            $userid = $decoded->data->userid; // Get user ID from decoded token
            try {
                $conn = getConnection();
                $sql = "SELECT * FROM users WHERE userid = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':id' => $userid]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    return $response->withHeader('Content-Type', 'application/json')
                                    ->write(json_encode(["status" => "success", "data" => $user]));
                } else {
                    return $response->withStatus(404)
                                    ->withHeader('Content-Type', 'application/json')
                                    ->write(json_encode(["status" => "fail", "data" => ["title" => "User not found"]]));
                }
            } catch (PDOException $e) {
                return $response->withHeader('Content-Type', 'application/json')
                                ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
            }
        }
    }
    return $response->withStatus(401)->write(json_encode(["status" => "fail", "data" => ["title" => "Unauthorized"]]));
})->add($jwtMiddleware);

// Update user by ID (generates its own token for the update)
$app->put('/user/update', function (Request $request, Response $response) use ($key) {
    $authHeader = $request->getHeader('Authorization');
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader[0], $matches)) {
        $jwt = $matches[1];
        $decoded = verifyToken($jwt, $key);
        
        if ($decoded) {
            $userid = $decoded->data->userid; // Get user ID from decoded token
            // Get the data for updating
            $data = json_decode($request->getBody());
            $uname = $data->username;
            $pass = $data->password;
            try {
                $conn = getConnection();
                $sql = "UPDATE users SET username = :username, password = :password WHERE userid = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':username' => $uname,
                    ':password' => hash("SHA256", $pass),
                    ':id' => $userid // Use the ID from the decoded token
                ]);
                
                // Generate a new token after successful update
                $newToken = createToken(['username' => $uname, 'userid' => $userid], $key, 'http://library.org', 'http://library.com');
                return $response->withHeader('Content-Type', 'application/json')
                                ->write(json_encode(["status" => "success", "token" => $newToken]));
            } catch (PDOException $e) {
                return $response->withHeader('Content-Type', 'application/json')
                                ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
            }
        }
    }
    return $response->withStatus(401)->write(json_encode(["status" => "fail", "data" => ["title" => "Unauthorized"]]));
})->add($jwtMiddleware);
// Delete user by ID (generates its own token for the delete operation)
$app->delete('/user/delete', function (Request $request, Response $response) use ($key) {
    $authHeader = $request->getHeader('Authorization');
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader[0], $matches)) {
        $jwt = $matches[1];
        $decoded = verifyToken($jwt, $key);
        
        if ($decoded) {
            $userid = $decoded->data->userid; // Get user ID from decoded token
            try {
                $conn = getConnection();
                $sql = "DELETE FROM users WHERE userid = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':id' => $userid]);
                // Generate a new token after successful deletion
                $newToken = createToken(['username' => $decoded->data->username, 'userid' => $userid], $key, 'http://library.org', 'http://library.com');
                return $response->withHeader('Content-Type', 'application/json')
                                ->write(json_encode(["status" => "success", "token" => $newToken]));
            } catch (PDOException $e) {
                return $response->withHeader('Content-Type', 'application/json')
                                ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
            }
        }
    }
    return $response->withStatus(401)->write(json_encode(["status" => "fail", "data" => ["title" => "Unauthorized"]]));
})->add($jwtMiddleware);
// Create a Book_Author Relationship
$app->post('/book_authors/register', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $authorid = $data->authorid;
    try {
        $conn = getConnection();
        // Use backticks for table name with hyphen
        $sql = "INSERT INTO `book_authors` (bookid, authorid) VALUES (:bookid, :authorid)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':bookid' => $bookid,
            ':authorid' => $authorid,
        ]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});
// Get All Book-Authors
$app->get('/book_authors/show', function (Request $request, Response $response) {
    try {
        $conn = getConnection();
        $sql = "SELECT * FROM book_authors";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $bookAuthors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => $bookAuthors]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});
// Update a Book-Author Relationship
$app->put('/book_authors/update/{collectionid}', function (Request $request, Response $response, array $args) {
    $collectionid = $args['collectionid'];
    $data = json_decode($request->getBody());
    $bookid = $data->bookid;
    $authorid = $data->authorid;
    try {
        $conn = getConnection();
        $sql = "UPDATE book_authors SET bookid = :bookid, authorid = :authorid WHERE collectionid = :collectionid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':bookid' => $bookid,
            ':authorid' => $authorid,
            ':collectionid' => $collectionid
        ]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
})->add($jwtMiddleware);
// Delete a Book-Author Relationship
$app->delete('/book_authors/delete/{collectionid}', function (Request $request, Response $response, array $args) {
    $collectionid = $args['collectionid'];
    try {
        $conn = getConnection();
        $sql = "DELETE FROM book_authors WHERE collectionid = :collectionid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':collectionid' => $collectionid]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
})->add($jwtMiddleware);
// =================== Books ===================
// Get All Books
$app->get('/books/read', function (Request $request, Response $response) {
    try {
        $conn = getConnection();
        $sql = "SELECT * FROM books";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $key = 'server_hack';
        $iat = time();
        $payload = [
            'iss' => 'http://library.org',
            'aud' => 'http://library.com',
            'iat' => $iat,
            'exp' => $iat + 3600,
            'data' => array("booksCount" => count($books))
        ];
        $new_jwt = JWT::encode($payload, $key, 'HS256');


        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => $books, "token" => $new_jwt]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});
// Create a Book
$app->post('/books/register', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $title = $data->title;
    $authorid = $data->authorid;
    try {
        $conn = getConnection();
        $sql = "INSERT INTO books (title, authorid) VALUES (:title, :authorid)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':authorid' => $authorid,
        ]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});
// Update a Book
$app->put('/books/update/{bookid}', function (Request $request, Response $response, array $args) {
    $bookid = $args['bookid'];
    $data = json_decode($request->getBody());
    $title = $data->title;
    $authorid = $data->authorid;
    try {
        $conn = getConnection();
        $sql = "UPDATE books SET title = :title, authorid = :authorid WHERE bookid = :bookid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':authorid' => $authorid,
            ':bookid' => $bookid
        ]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
})->add($jwtMiddleware);
// Delete a Book
$app->delete('/books/delete/{booksid}', function (Request $request, Response $response, array $args) {
    $booksid = $args['booksid'];
    try {
        $conn = getConnection();
        $sql = "DELETE FROM books WHERE bookid = :booksid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':booksid' => $booksid]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
})->add($jwtMiddleware);
// =================== Authors ===================
// Get All Authors
$app->get('/authors/read', function (Request $request, Response $response) {
    try {
        $conn = getConnection();
        $sql = "SELECT * FROM authors";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $key = 'server_hack';
            $iat = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $iat,
                'exp' => $iat + 3600,
                'data' => array("authorCount" => count($authors))
            ];
            $new_jwt = JWT::encode($payload, $key, 'HS256');


        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => $authors, "token" => $new_jwt]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});
// Create an Author
$app->post('/authors/register', function (Request $request, Response $response) {
    $data = json_decode($request->getBody());
    $name = $data->name;
    try {
        $conn = getConnection();
        $sql = "INSERT INTO authors (name) VALUES (:name)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':name' => $name]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
});
// Update an Author
$app->put('/authors/update/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $data = json_decode($request->getBody());
    $name = $data->name;
    try {
        $conn = getConnection();
        $sql = "UPDATE authors SET name = :name WHERE authorid = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':id' => $id
        ]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
})->add($jwtMiddleware);
// Delete an Author
$app->delete('/authors/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    try {
        $conn = getConnection();
        $sql = "DELETE FROM authors WHERE authorid = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "success", "data" => null]));
    } catch (PDOException $e) {
        return $response->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(["status" => "fail", "data" => ["title" => $e->getMessage()]]));
    }
})->add($jwtMiddleware);
$app->run();
?>
