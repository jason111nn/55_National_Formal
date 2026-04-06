==== 執行指南 ====
此系統針對「第 55 屆全國技能競賽 Back-end 模組 2 (書籍管理系統)」開發。

[ 執行環境 ]
1. 請確認已安裝 Apache 及 MySQL (例如使用 XAMPP)。
2. 本系統依賴 `.htaccess` 的 mod_rewrite 進行 URL 路由重寫，請確保您的 Apache 設定 (httpd.conf) 中已將 AllowOverride 設為 All。
3. 根目錄為 `00_module_d/`，請將其放入伺服器可訪問之目錄下。

[ 網址範例 ]
由於使用自動路由，以下網址皆能直接正確對接：
- 登入: /00_module_d/login
- 書籍清單: /00_module_d/books
- JSON API: /00_module_d/books.json
- 公開書本頁: /00_module_d/01/[ISBN]

[ 預設帳號 ]
超級管理員：
帳號：admin
密碼：1234

[ 圖片與資料 ]
所有書籍上傳之圖片將放置於 `/00_module_d/images/` 目錄。
資料庫部分請使用附加的 SQL 檔案匯入 (`55_national`)。
