# ASM Web - Asset Management System

## 📌 Overview
This project is a simple Asset Management System with:

- Backend: PHP (REST API)
- Frontend: Node.js (Express)
- Database: MySQL
- Deployment: Docker Compose

---

## 🚀 Requirements

- Docker
- Docker Compose

---

## ⚙️ Setup & Run Docker

### 1. Clone project

```bash
git clone https://github.com/DuckZiiii300305/asm-web.git
cd asm-web
```
### 2. Cấu hình môi trường (ENV)

Project đã cung cấp sẵn file `.env.docker` cho từng service:

Backend: `./backend/.env.docker`

Frontend: `./frontend/.env.docker`

Bạn có thể chỉnh sửa nếu cần (ví dụ: DB host, port, credentials), nhưng mặc định đã chạy được với Docker Compose.

### 3. Build & start containers
```bash
docker compose up -d --build
```
Lệnh này sẽ:

- Build image cho backend (PHP + Apache)

- Build frontend (Node.js)

- Pull MySQL image

- Tự động khởi tạo database từ folder `backend/migrations`

4. Kiểm tra container
```bash
docker ps
```
Bạn sẽ thấy 3 service:

- `asm_mysql`

- `asm_backend`

- `asm_frontend`

### 5. Truy cập hệ thống
| Service | URL |
| :--- | :--- |
| Frontend | http://localhost:3000 |
| Backend | http://localhost:8000 |
| API test | http://localhost:8000/health |