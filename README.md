<div align="center">

# 第 55 屆全國技能競賽 - 網頁設計實作專案

<a href="https://jason111nn.github.io/">
  <img src="https://img.shields.io/badge/VISIT%20MY%20BLOG-0d1117?style=for-the-badge&logo=vuedotjs&logoColor=white" />
</a>
<a href="https://jason111nn.github.io/55_National_Formal">
  <img src="https://img.shields.io/badge/VIEW%20LIVE%20DEMO-8A2BE2?style=for-the-badge&logo=github&logoColor=white" />
</a>


這是一份針對 **第 55 屆全國技能競賽（Web Design）** 的實作專案。<br>為確保環境絕對穩定，專案採用 **「雙軌並行 (Dual-Track)」** 開發模式：<br>提供保底的原生 (Vanilla) 實作，以及現代化框架 (Laravel/Vue) 的重構版本。

</div>

---

## 🛠 技術區 (Tech Stack)

<div align="left">
  <img src="https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white">
  <img src="https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white">
  <img src="https://img.shields.io/badge/vuejs-%2335495e.svg?style=for-the-badge&logo=vuedotjs&logoColor=%234FC08D">
  <img src="https://img.shields.io/badge/composer-%23cb8e5e.svg?style=for-the-badge&logo=composer&logoColor=white">
</div>

### 🧱 底層環境
* **Server**: `Apache` (XAMPP 環境)
* **Database**: `MySQL` / `MariaDB`
* **Bundler**: `Vite` (整合 `PWA` 離線存取功能)

---

## ⚡ 快速開始 (Quick Start)

### 1. 環境自動修復
本專案提供一鍵式環境還原腳本。請在根目錄執行：
> 📄 **`setup.bat`** (Windows)
> 
> *自動執行 `npm install` 與避開版本衝突的 `composer install --ignore-platform-reqs`。*

### 2. 資料庫配置 (Database Setup)
1. 啟動 XAMPP MySQL，建立名為 **`55_skills`** 的資料庫。
2. **Laravel 遷移與初始化**：
   ```bash
   cd 00_module_d_laravel
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   ```

3.  **測試資料匯入**：若需預設測資，請匯入根目錄下的 `55_nationa.sql`。

### 3\. 模組啟動

  * **Vue 互動端**：進入 `00_module_b_vue` 執行 `npm run dev`。
  * **Laravel 管理端**：執行 `php artisan serve` 存取 `/01/{isbn}`。

-----

## 📄 授權協議 (License)

本專案採 [CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/deed.zh_TW) 授權釋出。

<div align="center">

**© 2026 Jason111nn.**

</div>
