<div align="center">

# 第 55 屆全國技能競賽 - 網頁設計實作專案

**Developer:** [jason111nn](https://github.com/Jason111nn) <br>
**Status:** 競賽解題紀錄與技術展示 (Module A-E)

<br>

這是一份針對 **第 55 屆全國技能競賽（Web Design）** 的實作專案。為確保比賽環境的絕對穩定性，專案採用 **「雙軌並行 (Dual-Track)」** 開發模式：提供保底的原生 (Vanilla) 實作，以及現代化框架 (Laravel/Vue) 的重構版本。

</div>

---

## 🛠 技術區 (Tech Stack)

### 🧱 環境
* **PHP**: `^8.0.x` (精準鎖定考場 XAMPP 預設版本)
* **Database**: `MySQL` / `MariaDB`
* **Server**: `Apache` (XAMPP 環境)

### 🚀 框架
* **Backend**: `Laravel 9.x` (MVC 架構、Eloquent ORM、ISBN 權重運算邏輯)
* **Frontend**: `Vue 3` (SFC 元件化、GSAP 動畫、狀態管理)
* **Bundler**: `Vite` (整合 `vite-plugin-pwa` 提供 **離線存取功能**)

---

## ⚡ 快速開始 (Quick Start)

### 1. 環境自動修復
本專案提供一鍵式環境還原腳本。請在根目錄執行：
> 📄 **`setup.bat`** (Windows)
> 
> *此腳本將自動執行 `npm install` 與避開版本衝突的 `composer install --ignore-platform-reqs`。*

### 2. 資料庫配置 (Database Setup)
1. 啟動 XAMPP MySQL。
2. 建立名為 **`55_skills`** 的資料庫。
3. **Laravel 遷移**：
   ```bash
   cd 00_module_d_laravel
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   ```
4. **測試資料匯入**：若需預設測資（Admin 帳號、ISBN 範例），請匯入根目錄下的 `55_nationa.sql`。

### 3. 模組啟動
* **Vue 互動端**：進入 `00_module_b_vue` 執行 `npm run dev`。
* **Laravel 管理端**：執行 `php artisan serve` 存取 `http://127.0.0.1:8000/01/{isbn}`。

---

## 📁 目錄結構與導覽

本專案根目錄附帶一個全局 **`index.html`**，作為全模組的快速跳轉入口：

* **`00_module_b/`**：健康飲食端 - 原生 JS 保底版
* **`00_module_b_vue/`**：健康飲食端 - Vue 3 SFC 進階版
* **`00_module_d/`**：圖書管理端 - 原生 PHP 保底版
* **`00_module_d_laravel/`**：圖書管理端 - Laravel 9 ORM 企業版
* **`00_module_e/`**：演算法實戰 - STDIN/STDOUT PHP 實作

---

## 🌐 GitHub 自動化部署 (CI/CD)

本專案已完全整合 GitHub Pages 與 GitHub Actions 自動化流程。

### 🛡️ `.gitignore` 保護機制
系統配置了嚴密的過濾機制，確保以下檔案不會進入遠端倉庫：
* **巨大依賴**：排除 `node_modules` 與 `vendor`，保持專案核心輕量。
* **安全性**：過濾 `.env` 敏感資料夾與私鑰。
* **編譯檔案**：排除本地 `dist/`，交由雲端虛擬機統一編譯。

### ⚙️ CI/CD 工作流 (GitHub Action)
專案內含 `.github/workflows/deploy.yml`。當您執行 `git push` 後，伺服器將自動：
1. 開啟 **Ubuntu (Node 20)** 虛擬機。
2. 自動於雲端執行 `npm run build`。
3. 將靜態入口與編譯結果部署至 `jason111nn.github.io`。

---

## 📄 授權協議 (License)

本專案採 [CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/deed.zh_TW) 授權釋出。

**© 2026 Jason111nn.**