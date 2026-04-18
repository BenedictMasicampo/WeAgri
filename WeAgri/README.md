# WeAgri

WeAgri is a PHP single-page application for farmers that combines:

- real-time-style consultation updates through polling
- an AgroLLM-inspired AI assistant
- expert routing for more complex concerns
- role-based login for admins, farmers, and consultants
- notifications, knowledge highlights, and consultation tracking

## Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Local server: XAMPP

## Run in XAMPP

1. Copy the project folder into `htdocs`.
2. Start Apache and MySQL in XAMPP.
3. Import [database/schema.sql](/C:/Users/User/Downloads/WeAgri/database/schema.sql) into phpMyAdmin.
4. If your MySQL credentials are different from XAMPP defaults, update [config/app.php](/C:/Users/User/Downloads/WeAgri/config/app.php).
5. Open `http://localhost/WeAgri/`.

## Notes

- The app prefers MySQL automatically when the `weagri` database and tables exist.
- If MySQL is not ready yet, the app falls back to `storage/data.json` demo data so the UI still works.
- The AI layer uses a lightweight RAG-style retrieval flow over the agricultural knowledge base and can be replaced later with a real AgroLLM service.

## Demo Accounts

- Admin: `admin@weagri.local` / `admin123`
- Farmer: `farmer@weagri.local` / `farmer123`
- Consultant: `liza@weagri.local` / `consultant123`
- Additional consultants: `mateo@weagri.local`, `ana@weagri.local`, `paulo@weagri.local` / `consultant123`
