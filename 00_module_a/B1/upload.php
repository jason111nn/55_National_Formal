<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['folder'])) {
    $zip = new ZipArchive();
    $zipFileName = 'archive_' . time() . '.zip';
    $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = $_FILES['folder'];
        $count = count($files['name']);
        
        for ($i = 0; $i < $count; $i++) {
            // 過濾掉空檔案與錯誤檔案，(在此機制下，瀏覽器通常不會將空資料夾提交，
            // 若有對應路徑則保留其路徑結構)。
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                // webkitdirectory 提交時，name 會包含相對路徑 (ex: folder_name/file.txt)
                // 但 PHP 預設處理如果遇到特殊設定可能要用 full_path
                $relativePath = isset($files['full_path'][$i]) ? $files['full_path'][$i] : $files['name'][$i];
                $zip->addFile($files['tmp_name'][$i], $relativePath);
            }
        }
        $zip->close();

        // 輸出 ZIP 檔案強制下載
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.basename($zipFileName));
        header('Content-Length: ' . filesize($zipFilePath));
        readfile($zipFilePath);
        
        // 刪除暫存檔
        unlink($zipFilePath);
        exit;
    } else {
        echo "壓縮失敗";
    }
}
?>
