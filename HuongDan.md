# 📖 BookHeaven - Hướng Dẫn Cài Đặt và Chạy Dự Án

## 🚀 Cài Đặt và Khởi Động

### 1. **Cài đặt các thư viện phụ thuộc**

```bash
composer install
```

### 2. **Cấu hình môi trường**

```bash
# Tạo file .env từ template (nếu chưa có)
cp .env.example .env
```

### 3. **Cập nhật cấu hình database**

-   Mở file `.env`
-   Cập nhật thông tin database:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=your_database_name
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

### 4. **Tạo khóa bảo mật**

```bash
# Tạo APP_KEY
php artisan key:generate

# Tạo JWT_SECRET
php artisan jwt:secret

```

### 5. **Chạy migration và seeder**

```bash
# Chạy tất cả migration
php artisan migrate

# Chạy dữ liệu mẫu
php artisan db:seed
```

### 6. **Khởi động dự án**

```bash
php artisan serve
```

🎉 **Dự án sẽ chạy tại:** `http://localhost:8000`

---

## 📝 Ghi Chú

> **Role Management:** Tui đang để tất cả ở role admin. tui chưa chia role tại ko biết API nào cho role nào. đó để xong thống kê lại tui chia sau.

---
