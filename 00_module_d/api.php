<?php
// API routes
header('Content-Type: application/json; charset=utf-8');

$hostUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/00_module_d";

if (strpos($route, 'books.json') === 0) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if($page < 1) $page = 1;
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    
    $where = "b.is_hidden = 0 AND p.status = 'active'";
    $params = [];
    if ($query) {
        $where .= " AND (b.title LIKE ? OR b.description LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }
    
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM books b JOIN publishers p ON b.publisher_id = p.id WHERE $where");
    $stmtC->execute($params);
    $total = $stmtC->fetchColumn();
    $per_page = 3;
    $total_pages = ceil($total / $per_page);
    if ($total_pages == 0) $total_pages = 1;
    
    $offset = ($page - 1) * $per_page;
    $stmt = $pdo->prepare("SELECT b.*, p.name as publisher_name, 
        (SELECT image_url FROM book_images WHERE book_id = b.id AND is_cover=1 LIMIT 1) as cover1,
        (SELECT image_url FROM book_images WHERE book_id = b.id ORDER BY id ASC LIMIT 1) as cover2
        FROM books b JOIN publishers p ON b.publisher_id = p.id 
        WHERE $where ORDER BY b.id DESC LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
    $data = [];
    foreach($books as $b) {
        $cover = $b['cover1'] ?: $b['cover2'];
        $coverUrl = $cover ? "$hostUrl/images/$cover" : null;
        $data[] = [
            'book_name' => $b['title'],
            'book_isbn' => $b['isbn'],
            'book_author' => $b['author'],
            'publisher_name' => $b['publisher_name'],
            'cover_image' => $coverUrl
        ];
    }
    
    $qStr = $query ? "&query=".urlencode($query) : "";
    echo json_encode([
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'per_page' => $per_page,
            'next_page_url' => ($page < $total_pages) ? "$hostUrl/books.json?page=".($page+1).$qStr : null,
            'prev_page_url' => ($page > 1) ? "$hostUrl/books.json?page=".($page-1).$qStr : null
        ]
    ], JSON_UNESCAPED_UNICODE);

} else if (preg_match('/^books\/([^\/]+)\.json$/', $route, $matches)) {
    $isbnReq = str_replace('-', '', $matches[1]);
    $stmt = $pdo->prepare("SELECT b.*, p.name as pname, p.address as paddress, p.phone as pphone, p.isbn_code, b.id as bid, p.id as pid 
                           FROM books b JOIN publishers p ON b.publisher_id = p.id 
                           WHERE REPLACE(b.isbn, '-', '') = ? AND b.is_hidden = 0 AND p.status = 'active'");
    $stmt->execute([$isbnReq]);
    $book = $stmt->fetch();
    if (!$book) {
        http_response_code(404);
        echo json_encode(["error" => "Not Found"]);
        exit;
    }
    
    // Images
    $stmtI = $pdo->prepare("SELECT image_url FROM book_images WHERE book_id = ? ORDER BY is_cover DESC, id ASC");
    $stmtI->execute([$book['bid']]);
    $imgs = [];
    foreach($stmtI->fetchAll() as $img) {
        $imgs[] = "$hostUrl/images/".$img['image_url'];
    }
    
    // Contacts
    $stmtC = $pdo->prepare("SELECT name as contact_name, phone as contact_phone, email as contact_email FROM contacts WHERE publisher_id = ?");
    $stmtC->execute([$book['pid']]);
    $contacts = $stmtC->fetchAll();
    
    echo json_encode([
        'book_name' => $book['title'],
        'book_description' => $book['description'],
        'book_isbn' => $book['isbn'],
        'book_author' => $book['author'],
        'images' => $imgs,
        'publisher' => [
            'publisher_name' => $book['pname'],
            'publisher_address' => $book['paddress'],
            'publisher_phone' => $book['pphone'],
            'publisher_isbn' => $book['isbn_code'],
            'contacts' => $contacts
        ]
    ], JSON_UNESCAPED_UNICODE);
}
